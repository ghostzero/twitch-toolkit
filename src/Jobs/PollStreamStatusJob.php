<?php

namespace GhostZero\LPTHOOT\Jobs;

use GhostZero\LPTHOOT\Events\StreamDown;
use GhostZero\LPTHOOT\Events\StreamUp;
use GhostZero\LPTHOOT\Models\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use romanzipp\Twitch\Twitch;

class PollStreamStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Collection
     */
    public $channels;

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
     */
    public function handle(Twitch $twitch)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        Redis::throttle('lpthoot:throttle')->allow(500)->every(60)->then(function () use ($twitch) {
            $this->handleNow($twitch);
        }, function () {
            // Could not obtain lock...
            $this->release(60);
        });
    }

    private function handleNow(Twitch $twitch)
    {
        $channelStates = $this->getChannelStates($this->channels, $twitch);

        foreach ($this->channels as $channel) {
            $state = $channelStates->where('user_id', '==', $channel->id)->first();

            if ($this->hasStateChanged($channel, $state)) {
                $this->announceState($channel, $state);
            }
        }
    }

    function getChannelStates(Collection $channels, Twitch $twitch)
    {
        $userIds = $channels->pluck('id')->toArray();

        $result = $twitch->getStreamsByUserIds($userIds);

        return new Collection($result->data);
    }

    private function hasStateChanged(Channel $channel, $state)
    {
        return $channel->is_online !== ($state !== null);
    }

    private function announceState(Channel $channel, $state)
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
