<?php

namespace GhostZero\LPTHOOT\Console;

use GhostZero\LPTHOOT\Jobs\PollStreamStatusJob;
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
            PollStreamStatusJob::dispatch($channels);
        });
    }
}
