<?php

namespace GhostZero\TwitchToolkit\Console;

use Closure;
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
     * @var int
     */
    private $count = 0;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // renew all unconfirmed webhooks
        WebhookSubscription::query()
            ->whereNull('confirmed_at')
            ->inRandomOrder()->limit(50)->get()
            ->each($this->resubscribe());

        // renew all expired webhooks
        WebhookSubscription::query()
            ->whereRaw('DATE_SUB(DATE_ADD(leased_at, INTERVAL lease SECOND), INTERVAL 90 SECOND) <= NOW()')
            ->inRandomOrder()->limit(100)->get()
            ->each($this->resubscribe());

        $this->info(sprintf('Re-queued %s channels to subscribe to twitch webhooks.', $this->count));
    }

    private function resubscribe(): Closure
    {
        return function (WebhookSubscription $subscription) {
            dispatch(new SubscribeTwitchWebhooks($subscription->channel, [
                SubscribeTwitchWebhooks::OPTIONS_ONLY => [
                    $subscription->activity,
                ]
            ]));
            $this->count++;
        };
    }
}
