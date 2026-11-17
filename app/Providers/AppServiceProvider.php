<?php

namespace App\Providers;

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

        // Fix 1: Force autocommit=1 on each new MySQL connection via raw PDO exec.
        // Railway's MySQL sets autocommit=0 via server init_connect for non-root users,
        // which causes PDOException "There is already an active transaction" when any
        // Laravel code (e.g. Cache rate-limiter) tries to start a DB transaction.
        DB::afterConnecting(function (Connection $connection) {
            if ($connection->getDriverName() === 'mysql') {
                try {
                    $connection->getPdo()->exec('SET autocommit = 1');
                } catch (\Throwable $e) {
                    // best-effort
                }
            }
        });

        // Fix 2 (safety net): If the cache store is set to 'database', switch it to
        // 'file' at runtime. This avoids DatabaseStore::increment() starting a DB
        // transaction for the rate-limiter, which triggers the autocommit=0 conflict.
        if (config('cache.default') === 'database') {
            config(['cache.default' => 'file']);
        }
    }
}
