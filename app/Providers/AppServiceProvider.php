<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
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
        // Fix: switch cache and session to file driver in production so that no
        // database transactions are needed for sessions or rate-limiting.
        // This runs in register() — before any store is resolved — so it always wins.
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

        // Belt-and-suspenders: also force autocommit=1 at the PDO level on every
        // new connection so that explicit DB::transaction() calls still work correctly.
        DB::afterConnecting(function (Connection $connection) {
            if ($connection->getDriverName() === 'mysql') {
                try {
                    $connection->getPdo()->exec('SET autocommit = 1');
                } catch (\Throwable $e) {
                    // best-effort — do not block the request if this fails
                }
            }
        });

        // Extra safety: rebind RateLimiter to always use the file cache store
        // so that throttle middleware never touches DatabaseStore::increment().
        $this->app->singleton(RateLimiter::class, function ($app) {
            return new RateLimiter($app->make('cache')->store('file'));
        });
    }
}
