<?php

namespace GhostZero\TwitchToolkit\Jobs;

use GhostZero\TwitchToolkit\Models\WebSub;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use romanzipp\Twitch\Twitch;

class WebSubSubscriber implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const TWITCH_WEBHOOK_MAX_LOCKS = 600;
    public const TWITCH_WEBHOOK_DECAY = 60;

    public WebSub $webSub;

    public function __construct(WebSub $webSub)
    {
        $this->webSub = $webSub;
    }

    /**
     * @param Twitch $twitch
     * @throws LimiterTimeoutException
     */
    public function handle(Twitch $twitch): void
    {
        Redis::throttle('throttle:api.twitch.tv/webhooks')
            ->allow(self::TWITCH_WEBHOOK_MAX_LOCKS)
            ->every(self::TWITCH_WEBHOOK_DECAY)
            ->then(function () use ($twitch) {
                $response = $twitch->subscribeWebhook([], [
                    'hub.callback' => $this->webSub->callback_url,
                    'hub.mode' => 'subscribe',
                    'hub.topic' => $this->webSub->feed_url,
                    'hub.lease_seconds' => $this->webSub->lease_seconds,
                    'hub.secret' => $this->webSub->secret,
                ]);

                $this->webSub->update(['accepted' => $response->success()]);

                if (!$response->success()) {
                    Log::critical('Cannot request subscription', [
                        'client_id' => $twitch->getClientId(),
                        'client_secret' => $twitch->getClientSecret(),
                        'token' => $twitch->getToken(),
                        'error' => $response->error(),
                    ]);
                }

                Log::info('Subscribed to a twitch webhook.', [
                    'subscription_id' => $this->webSub->getKey(),
                    'accepted' => $response->success(),
                ]);
            }, function () {
                Log::warning("Reached webhook throttle. Release job for feed {$this->webSub->feed_url}.");
                $this->release(self::TWITCH_WEBHOOK_DECAY);
            });
    }
}
