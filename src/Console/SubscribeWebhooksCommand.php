<?php

namespace GhostZero\TwitchToolkit\Console;

use Carbon\Carbon;
use Closure;
use GhostZero\TwitchToolkit\Models\WebSub;
use GhostZero\TwitchToolkit\WebSub\Subscriber;
use Illuminate\Console\Command;
use romanzipp\Twitch\Twitch;

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

    private int $count = 0;

    /**
     * Execute the console command.
     * @param Subscriber $subscriber
     */
    public function handle(Subscriber $subscriber): void
    {
        WebSub::query()
            ->whereRaw('active = 1 and expires_at <= ADDDATE(now(), INTERVAL 10 MINUTE)')
            ->orWhereRaw('active = 0 and denied <> 1')
            ->orWhereRaw('active = 1 and accepted = 0')
            ->oldest('leased_at')->limit(300)->get()
            ->each($this->resubscribe($subscriber));

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
