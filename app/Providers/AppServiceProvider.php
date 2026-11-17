<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Railway MySQL runs with autocommit=0 for non-root users, which causes
        // PDOException "There is already an active transaction" whenever Laravel
        // tries to call beginTransaction() (e.g. DatabaseStore::increment() for throttle).
        //
        // Fix: switch cache and session to file driver at runtime in production
        // so no DB transactions are needed for sessions or rate-limiting.
        // Runs in register() — before any store is resolved — so it always wins
        // over whatever CACHE_STORE / SESSION_DRIVER env variables are set.
        if ($this->app->environment('production')) {
            config(['cache.default' => 'file']);
            config(['session.driver' => 'file']);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Railway terminates HTTPS at its reverse proxy.
        if ($this->app->environment('production')) {
            \Illuminate\Http\Middleware\TrustProxies::at('*');
        }

        // Extra safety: rebind RateLimiter to always use the file cache store
        // so throttle middleware never calls DatabaseStore::increment().
        $this->app->singleton(RateLimiter::class, function ($app) {
            return new RateLimiter($app->make('cache')->store('file'));
        });
    }
}
