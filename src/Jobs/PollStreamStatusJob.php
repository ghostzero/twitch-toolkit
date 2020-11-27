<?php

namespace GhostZero\TwitchToolkit\Jobs;

use GhostZero\TwitchToolkit\Events\StreamDown;
use GhostZero\TwitchToolkit\Events\StreamUp;
use GhostZero\TwitchToolkit\Models\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use romanzipp\Twitch\Twitch;

class PollStreamStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Collection $channels;

    /**
     * Create a new job instance.
     *
     * @param Collection $channels
     */
    public function __construct(Collection $channels)
    {
        $this->channels = $channels;
    }

    /**
     * Execute the job.
     *
     * @param Twitch $twitch
     * @return void
     * @throws LimiterTimeoutException
     */
    public function handle(Twitch $twitch): void
    {
        Redis::throttle('twitch-toolkit:throttle')->allow(500)->every(60)->then(function () use ($twitch) {
            $this->handleNow($twitch);
        }, function () {
            // Could not obtain lock...
            $this->release(60);
        });
    }

    private function handleNow(Twitch $twitch): void
    {
        $channelStates = $this->getChannelStates($this->channels, $twitch);

        foreach ($this->channels as $channel) {
            $state = $channelStates->where('user_id', '==', $channel->id)->first();

            if ($this->hasStateChanged($channel, $state)) {
                $this->announceState($channel, $state);
            }
        }
    }

    private function getChannelStates(Collection $channels, Twitch $twitch): Collection
    {
        $userIds = $channels->pluck('id')->toArray();

        $result = $twitch->getStreams(['user_id' => $userIds]);

        return new Collection($result->data());
    }

    private function hasStateChanged(Channel $channel, $state): bool
    {
        return $channel->is_online !== ($state !== null);
    }

    private function announceState(Channel $channel, $state): void
    {
        // persist new state
        $channel->update([
            'is_online' => $state !== null,
            'changed_at' => now(),
        ]);

        // broadcast new state
        if ($channel->is_online) {
            broadcast(new StreamUp($channel, (array)$state));
        } else {
            broadcast(new StreamDown($channel));
        }
    }
}
