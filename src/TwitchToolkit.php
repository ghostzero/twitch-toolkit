<?php


namespace GhostZero\TwitchToolkit;

use Illuminate\Support\Facades\Route;

class TwitchToolkit
{
    public static $skipMigrations = false;

    /**
     * Add routes to automatically handle webhooks flow.
     *
     * @param array $options
     */
    public static function routes($options = [])
    {
        Webhooks::routes($options);
    }
}