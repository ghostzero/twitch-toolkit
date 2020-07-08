<?php

namespace GhostZero\TwitchToolkit;

use Illuminate\Support\Facades\Route;

class TwitchToolkit
{
    public static bool $skipMigrations = false;

    /**
     * Add routes to automatically handle webhooks flow.
     *
     * @param array $options
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