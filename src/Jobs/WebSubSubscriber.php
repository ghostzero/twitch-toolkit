<?php

namespace GhostZero\TwitchToolkit\Jobs;

use GhostZero\TwitchToolkit\Models\WebSub;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use romanzipp\Twitch\Twitch;

class WebSubSubscriber implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            ->allow(config('twitch-toolkit.web-sub.limiter.allow', 800))
            ->every(config('twitch-toolkit.web-sub.limiter.every', 60))
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
                        'error' => $response->getErrorMessage(),
                    ]);
                }

                if (!$response->success() && Str::contains($response->getErrorMessage(), ['token', 'Token'])) {
                    Log::critical('Invalidate token in cache: ' . $response->getErrorMessage());
                    $flushed = Cache::store(config('twitch-api.oauth_client_credentials.cache_store'))
                        ->forget($key = config('twitch-api.oauth_client_credentials.cache_key'));

                    if (!$flushed) {
                        Log::critical("Twitch Access Token key {$key} cannot be flushed.");
                    }
                }
            }, function () {
                Log::warning("Reached webhook throttle. Release job for feed {$this->webSub->feed_url}.");
                $this->release(config('twitch-toolkit.web-sub.limiter.every', 60));
            });
    }
}
