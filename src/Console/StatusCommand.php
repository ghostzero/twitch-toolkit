<?php

namespace GhostZero\LPTHOOT\Console;

use GhostZero\LPTHOOT\Models\Channel;
use Illuminate\Console\Command;
use romanzipp\Twitch\Twitch;

class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lpthoot:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the current lpthoot system status.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $count = Channel::query()->count();
        $online = Channel::query()->where(['is_online' => true])->count();
        $offline = Channel::query()->where(['is_online' => false])->count();

        $this->info("Subscriptions: {$count}");
        $this->info("Online: {$online}");
        $this->info("Offline: {$offline}");
    }
}
