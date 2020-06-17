<?php

namespace GhostZero\TwitchToolkit\Console;

use GhostZero\TwitchToolkit\Jobs\SubscribeTwitchWebhooks;
use GhostZero\TwitchToolkit\Models\WebhookSubscription;
use Illuminate\Console\Command;

class SubscribeUnconfirmedWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'twitch-toolkit:subscribe-unconfirmed-webhooks';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Re-subscribe to all twitch webhooks.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $count = 0;
        WebhookSubscription::query()
            ->whereNull('confirmed_at')
            ->limit(100)->get()
            ->each(function (WebhookSubscription $subscription) use (&$count) {
                dispatch(new SubscribeTwitchWebhooks($subscription->channel));
                $count++;
            });

        $this->info(sprintf('Re-queued %s channels to subscribe to twitch webhooks.', $count));
    }
}
