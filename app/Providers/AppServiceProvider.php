<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensure local fallback for Spatie permission trait is loaded if the package
        // is not installed or autoloaded. This prevents a fatal error in the
        // User model where the trait is referenced.
        $fallback = app_path('Support/SpatiePermissionFallback.php');
        if (file_exists($fallback)) {
            require_once $fallback;
        }

        // Register our optional monitoring provider which will conditionally
        // register Telescope/Debugbar/Sentry if the packages and configuration
        // are present. This keeps monitoring optional and non-fatal.
        $this->app->register(\App\Providers\MonitoringServiceProvider::class);

        // If the spatie/laravel-permission package is installed but migrations have
        // not been run yet, the package can attempt to query `permissions` and
        // blow up. Bind a lightweight no-op PermissionRegistrar when the
        // permissions table is missing to avoid runtime DB errors during early
        // development and CI runs.
        try {
            if (class_exists(\Spatie\Permission\PermissionRegistrar::class) && !\Illuminate\Support\Facades\Schema::hasTable('permissions')) {
                $this->app->bind(\Spatie\Permission\PermissionRegistrar::class, function () {
                    return new class {
                        public function registerPermissions() {}
                        public function loadPermissions() {}
                        public function getPermissionsWithRoles() { return collect([]); }
                        public function getSerializedPermissionsForCache() { return []; }
                        public function getPermissions(array $options = [], $refresh = false) { return collect([]); }
                    };
                });
                logger()->warning('Spatie permission tables missing; bound noop PermissionRegistrar.');
            }
        } catch (\Throwable $e) {
            logger()->warning('Could not determine spatie permissions availability: ' . $e->getMessage());
        }

        // Ensure role middleware alias exists as early as possible in the booting
        // lifecycle, so routes that reference 'role' do not cause a BindingResolutionException
        // if Spatie is not installed.
        $this->app->booting(function () {
            try {
                if (! class_exists(\Spatie\Permission\Middlewares\RoleMiddleware::class)) {
                    $this->app->make(\Illuminate\Routing\Router::class)->aliasMiddleware('role', \App\Http\Middleware\EnsureHasRole::class);
                }
                if (! class_exists(\Spatie\Permission\Middlewares\PermissionMiddleware::class)) {
                    $this->app->make(\Illuminate\Routing\Router::class)->aliasMiddleware('permission', \App\Http\Middleware\EnsureHasRole::class);
                }
                if (! class_exists(\Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class)) {
                    $this->app->make(\Illuminate\Routing\Router::class)->aliasMiddleware('role_or_permission', \App\Http\Middleware\EnsureHasRole::class);
                }
            } catch (\Throwable $e) {
                logger()->warning('Failed to alias role middleware during booting: ' . $e->getMessage());
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // If the app uses database sessions but the sessions table is missing,
        // fall back to the safer "file" session driver at runtime to avoid
        // SQL exceptions thrown by the DatabaseSessionHandler during requests.
        try {
            if (config('session.driver') === 'database') {
                if (!\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                    config(['session.driver' => 'file']);
                    logger()->warning('Session table missing; falling back to file session driver.');
                }
            }
        } catch (\Throwable $e) {
            config(['session.driver' => 'file']);
            logger()->warning('Could not verify session table; falling back to file session driver.');
        }

        // If Redis is configured for session or cache storage but cannot be
        // connected to at runtime, fall back to file/session and array cache to
        // avoid hard failures. This keeps the app usable while the infra is
        // repaired.
        try {
            // Sessions
            if (config('session.driver') === 'redis') {
                try {
                    // The Redis facade may not be available; guard it.
                    \Illuminate\Support\Facades\Redis::connection()->ping();
                } catch (\Throwable $e) {
                    config(['session.driver' => 'file']);
                    logger()->warning('Could not connect to Redis for sessions; falling back to file session driver.');
                }
            }

            // Cache
            if (config('cache.default') === 'redis') {
                try {
                    \Illuminate\Support\Facades\Redis::connection()->ping();
                } catch (\Throwable $e) {
                    // Use array cache to avoid external dependency for cache lookups
                    config(['cache.default' => 'array']);
                    logger()->warning('Could not connect to Redis for cache; falling back to array cache driver.');
                }
            }
        } catch (\Throwable $e) {
            // If anything unexpected happens, keep going with conservative defaults
            logger()->warning('Error while verifying Redis connectivity; using conservative session/cache defaults.');
            config(['session.driver' => config('session.driver') ?: 'file']);
            config(['cache.default' => config('cache.default') ?: 'array']);
        }

        // In debug mode, clear compiled Blade views to ensure updated templates
        // are used immediately — this helps when fixing view exceptions like
        // Vite manifest failures during development.
        if (config('app.debug')) {
            try {
                $views = glob(storage_path('framework/views') . '/*.php');
                foreach ($views as $v) {
                    @unlink($v);
                }
            } catch (\Throwable $e) {
                // not critical — ignore
            }
        }

        // Ensure the router has a safe 'role' middleware alias if Spatie middleware
        // is not installed (avoids BindingResolutionException: Target class [role] does not exist).
        try {
            $router = $this->app->make(\Illuminate\Routing\Router::class);

            if (! class_exists(\Spatie\Permission\Middlewares\RoleMiddleware::class)) {
                $router->aliasMiddleware('role', \App\Http\Middleware\EnsureHasRole::class);
            }
            if (! class_exists(\Spatie\Permission\Middlewares\PermissionMiddleware::class)) {
                $router->aliasMiddleware('permission', \App\Http\Middleware\EnsureHasRole::class);
            }
            if (! class_exists(\Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class)) {
                $router->aliasMiddleware('role_or_permission', \App\Http\Middleware\EnsureHasRole::class);
            }

            // For the public tablet kiosk, scanning tokens is a stateless POST that may
            // not have a session cookie. Exclude the kiosk scan endpoint from CSRF
            // verification so kiosks can post tokens without a session.
            try {
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::except(['/kiosk/scan', 'kiosk/scan']);
            } catch (\Throwable $e) {
                logger()->warning('Could not add /kiosk/scan to CSRF exceptions: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            // If aliasing fails for any reason, log and continue — we don't want
            // the entire app to crash while attempting to help routing.
            logger()->warning('Failed to ensure role middleware aliasing: ' . $e->getMessage());
        }
    }
}
