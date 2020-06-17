<?php

namespace GhostZero\TwitchToolkit\Console;

use Illuminate\Console\Command;
use romanzipp\Twitch\Twitch;

class ClearWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch-toolkit:clear-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unsubscribe all twitch webhooks.';

    /**
     * Execute the console command.
     *
     * @param Twitch $twitch
     * @return void
     */
    public function handle(Twitch $twitch)
    {
        $count = 0;
        $success = 0;

        $result = $twitch->getWebhookSubscriptions(['first' => 100]);

        foreach ($result->data as $item) {
            $this->info(sprintf("Unsubscribe: \n%s\n%s\n", $item->callback, $item->topic));
            $response = $twitch->unsubscribeWebhook(urlencode($item->callback), $item->topic);

            if ($response->success()) {
                $success++;
            } else {
                $this->error('Error: ' . $response->error() . '(' . $response->status . ')');
            }

            $count++;
        }

        $this->info(sprintf(
            'Cleaned %s of %s with an total of %s channels.',
            $success,
            $count,
            $result->total
        ));
    }
}
