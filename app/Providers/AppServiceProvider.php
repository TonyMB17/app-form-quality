<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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

        // Safety net: If the cache store is set to 'database', switch it to
        // 'file' at runtime. This avoids DatabaseStore::increment() starting a DB
        // transaction for the rate-limiter, which triggers the autocommit=0 conflict.
        if (config('cache.default') === 'database') {
            config(['cache.default' => 'file']);
        }
    }
}
