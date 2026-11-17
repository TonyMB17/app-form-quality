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

        // Railway's MySQL server sets autocommit=0 via init_connect for non-root users,
        // causing PDOException "There is already an active transaction" when Laravel
        // tries to begin a transaction. This hook runs after EVERY new connection,
        // after the server's init_connect, ensuring autocommit is always ON.
        DB::afterConnecting(function (Connection $connection) {
            if ($connection->getDriverName() === 'mysql') {
                $connection->unprepared('SET autocommit = 1');
            }
        });
    }
}
