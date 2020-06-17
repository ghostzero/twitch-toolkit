<?php

namespace GhostZero\TwitchToolkit\Console;

use GhostZero\TwitchToolkit\Models\Channel;
use Illuminate\Console\Command;
use romanzipp\Twitch\Twitch;

class SubscribeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch-toolkit:subscribe {login} {--capability=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe a given channel into the loop hell or webhooks.';

    /**
     * Execute the console command.
     *
     * @param Twitch $twitch
     * @return void
     */
    public function handle(Twitch $twitch)
    {
        $response = $twitch->getUserByName($this->argument('login'));

        if ($response->success()) {
            $user = $response->shift();
            Channel::subscribe($user->id, $this->getCapabilities());
            $this->info("Subscribed to {$user->id} ($user->display_name)!");
        }
    }

    private function getCapabilities(): array
    {
        if (!$option = $this->option('capability')) {
            return [Channel::TYPE_POLLING];
        }

        if (!is_array($option)) {
            return [$option];
        }

        return $option;
    }
}
