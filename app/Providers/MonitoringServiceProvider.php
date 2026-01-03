<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Conditionally register Laravel Telescope if it's installed and enabled
        if (class_exists(\Laravel\Telescope\Telescope::class) && env('MONITORING_TELESCOPE', false)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        }

        // Conditionally register Debugbar (barryvdh/laravel-debugbar)
        if (class_exists(\Barryvdh\Debugbar\ServiceProvider::class) && env('MONITORING_DEBUGBAR', false)) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        // Conditionally register Sentry if present and DSN is configured
        if (env('SENTRY_DSN') && class_exists(\Sentry\Laravel\ServiceProvider::class)) {
            $this->app->register(\Sentry\Laravel\ServiceProvider::class);
        }
    }
}
