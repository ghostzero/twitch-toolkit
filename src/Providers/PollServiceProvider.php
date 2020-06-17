<?php

namespace GhostZero\LPTHOOT\Providers;

use GhostZero\LPTHOOT\Console;
use GhostZero\LPTHOOT\LPTHOOT;
use Illuminate\Support\ServiceProvider;

class PollServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/lpthoot.php', 'lpthoot'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!LPTHOOT::$skipMigrations) {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        }

        $this->publishes([
            __DIR__ . '/../../config/lpthoot.php' => config_path('lpthoot.php')
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
                Console\PollCommand::class,
                Console\SubscribeCommand::class,
                Console\UnsubscribeCommand::class,
                Console\StatusCommand::class,
                Console\SubscribeWebhooksCommand::class,
                Console\ClearWebhooksCommand::class,
            ]);
        }
    }
}
