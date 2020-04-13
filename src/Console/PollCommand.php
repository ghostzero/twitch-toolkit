<?php

namespace GhostZero\LPTHOOT\Console;

use GhostZero\LPTHOOT\Events\StreamDown;
use GhostZero\LPTHOOT\Events\StreamUp;
use GhostZero\LPTHOOT\Models\Channel;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use romanzipp\Twitch\Twitch;

class PollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lpthoot:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Let\'s Poll The Hell Out Of Twitch';

    /**
     * Execute the console command.
     *
     * @param Twitch $twitch
     * @return void
     */
    public function handle(Twitch $twitch)
    {
        Channel::query()->chunk(100, function (Collection $channels) use ($twitch) {

            $channelStates = $this->getChannelStates($channels, $twitch);

            foreach ($channels as $channel) {
                $isOnline = $channelStates->where('id', '==', $channel->id)->count() > 0;

                if ($this->hasStateChanged($channel, $isOnline)) {
                    $this->announceState($channel, $isOnline);
                }
            }
        });
    }

    function getChannelStates(Collection $channels, Twitch $twitch)
    {
        $userIds = $channels->pluck('id')->toArray();

        $result = $twitch->getStreamsByUserIds($userIds);

        return new Collection($result->data);
    }

    private function hasStateChanged(Channel $channel, $newState)
    {
        return $channel->is_online !== $newState;
    }

    private function announceState(Channel $channel, $isOnline)
    {
        if ($isOnline) {
            broadcast(new StreamUp($channel));
        } else {
            broadcast(new StreamDown($channel));
        }

        $channel->update([
            'is_online' => $isOnline,
            'changed_at' => now(),
        ]);
    }
}
