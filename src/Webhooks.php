<?php

namespace GhostZero\TwitchToolkit;

use Illuminate\Support\Facades\Route;

class Webhooks
{
    /**
     * Add routes to automatically handle webhooks flow.
     *
     * @param array $options
     * @deprecated Please use TwitchToolkit::routes(...) instead. This method will be removed in version 4.
     */
    public static function routes($options = [])
    {
        Route::middleware($options['middleware'] ?? 'api')
            ->namespace('\GhostZero\TwitchToolkit\Http\Controllers')
            ->group(function () {
                Route::get('/twitch-toolkit/webhooks/twitch', 'WebhookController@challenge');
                Route::post('/twitch-toolkit/webhooks/twitch', 'WebhookController@store')
                    ->name('twitch-toolkit.webhooks.twitch.callback');
            });
    }
}