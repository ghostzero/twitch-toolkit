<?php

namespace GhostZero\TwitchToolkit\Providers;

use GhostZero\TwitchToolkit\Console;
use GhostZero\TwitchToolkit\TwitchToolkit;
use Illuminate\Support\ServiceProvider;

class TwitchToolkitServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/twitch-toolkit.php', 'twitch-toolkit'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!TwitchToolkit::$skipMigrations) {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        }

        $this->publishes([
            __DIR__ . '/../../config/twitch-toolkit.php' => config_path('twitch-toolkit.php')
        ], 'config');

        $this->publishes([
            __DIR__ . '/../../migrations/' => database_path('migrations')
        ], 'migrations');

        $this->registerCommands();
    }

    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ClearWebhooksCommand::class,
                Console\PollCommand::class,
                Console\StatusCommand::class,
                Console\SubscribeCommand::class,
                Console\SubscribeInactiveWebhooksCommand::class,
                Console\SubscribeWebhooksCommand::class,
                Console\UnsubscribeCommand::class,
            ]);
        }
    }
}
