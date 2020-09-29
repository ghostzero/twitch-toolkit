<?php

namespace GhostZero\TwitchToolkit\Console;

use GhostZero\TwitchToolkit\Jobs\PollStreamStatusJob;
use GhostZero\TwitchToolkit\Models\Channel;
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
    protected $signature = 'twitch-toolkit:poll';

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
    public function handle(Twitch $twitch): void
    {
        Channel::query()->chunk(100, function (Collection $channels) use ($twitch) {
            $this->info("Dispatch PollStreamStatusJob for {$channels->count()} channels...");
            PollStreamStatusJob::dispatch($channels);
        });
    }
}
