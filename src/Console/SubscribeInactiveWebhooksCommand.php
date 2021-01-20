<?php

namespace GhostZero\TwitchToolkit\Console;

use Closure;
use GhostZero\TwitchToolkit\Models\WebSub;
use GhostZero\TwitchToolkit\WebSub\Subscriber;
use Illuminate\Console\Command;

class SubscribeInactiveWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitch-toolkit:subscribe-inactive-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to all twitch webhooks.';

    private int $count = 0;

    /**
     * Execute the console command.
     * @param Subscriber $subscriber
     */
    public function handle(Subscriber $subscriber): void
    {
        // renew all active webhooks that will expire soon
        WebSub::query()
            ->where(['active' => false])
            ->inRandomOrder()
            ->limit(floor(config('twitch-toolkit.web-sub.limiter.allow') * 0.8))
            ->get()->each($this->resubscribe($subscriber));

        $this->info(sprintf('Re-queued %s channels to subscribe to twitch webhooks.', $this->count));
    }

    private function resubscribe(Subscriber $subscriber): Closure
    {
        return function (WebSub $webSub) use ($subscriber) {
            $subscriber->subscribe($webSub->callback_url, $webSub->feed_url, $webSub->channel_id);
            $this->count++;
        };
    }
}
