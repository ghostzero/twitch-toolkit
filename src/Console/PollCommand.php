<?php

namespace GhostZero\TwitchToolkit\Console;

use GhostZero\TwitchToolkit\Jobs\PollStreamStatusJob;
use GhostZero\TwitchToolkit\Models\Channel;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

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
     * @return void
     */
    public function handle(): void
    {
        Channel::query()->chunk(100, function (Collection $channels) {
            $this->info("Dispatch PollStreamStatusJob for {$channels->count()} channels...");
            PollStreamStatusJob::dispatch($channels);
        });
    }
}
