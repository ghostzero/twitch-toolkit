<?php

namespace GhostZero\TwitchToolkit\Console;

use GhostZero\TwitchToolkit\Jobs\SubscribeTwitchWebhooks;
use GhostZero\TwitchToolkit\Models\Channel;
use Illuminate\Console\Command;

class SubscribeWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch-toolkit:subscribe-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to all twitch webhooks.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $count = 0;
        Channel::query()
            ->whereNotNull('oauth_access_token')
            ->whereDate('oauth_expires_at', '>=', now())
            ->each(function (Channel $channel) use (&$count) {
                dispatch(new SubscribeTwitchWebhooks($channel));
                $count++;
            });

        $this->info(sprintf('Queued %s channels to subscribe to twitch webhooks.', $count));
    }
}
