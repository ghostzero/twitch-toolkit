<?php

namespace GhostZero\LPTHOOT;

use Illuminate\Support\Facades\Route;

class Webhooks
{
    /**
     * Add routes to automatically handle webhooks flow.
     *
     * @param array $options
     */
    public static function routes($options = [])
    {
        Route::middleware($options['middleware'] ?? 'api')
            ->namespace('\GhostZero\LPTHOOT\Http\Controllers')
            ->group(function () {
                Route::get('/lpthoot/webhooks/twitch', 'WebhookController@challenge');
                Route::post('/lpthoot/webhooks/twitch', 'WebhookController@store')
                    ->name('lpthoot.webhooks.twitch.callback');
            });
    }
}