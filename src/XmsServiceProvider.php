<?php

namespace OursBlanc\Xms;

use Illuminate\Support\ServiceProvider;

class XmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/xms.php', 'xms');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'xms');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/xms.php' => config_path('xms.php'),
            ], 'xms-config');
        }
    }
}
