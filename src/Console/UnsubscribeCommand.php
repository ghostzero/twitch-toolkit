<?php

namespace GhostZero\TwitchToolkit\Console;

use GhostZero\TwitchToolkit\Models\Channel;
use Illuminate\Console\Command;
use romanzipp\Twitch\Twitch;

class UnsubscribeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch-toolkit:unsubscribe {login}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unsubscribe a given channel from the poll hell.';

    /**
     * Execute the console command.
     *
     * @param Twitch $twitch
     * @return void
     */
    public function handle(Twitch $twitch)
    {
        $response = $twitch->getUsers(['login' => $this->argument('login')]);

        if ($response->success()) {
            $user = $response->shift();
            Channel::unsubscribe($user->id);
            $this->info("Unsubscribed from {$user->id} ($user->display_name)!");
        }
    }
}
