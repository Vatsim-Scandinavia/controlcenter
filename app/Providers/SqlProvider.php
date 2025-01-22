<?php

namespace App\Providers;

use App\Contracts\SqlContract;
use App\Services\Sql\PostgresSql;
use App\Services\Sql\StandardSql;
use Illuminate\Support\ServiceProvider;

class SqlProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(SqlContract::class, function ($app) {
            if (config('database.default') == 'pgsql') {
                return new PostgresSql();
            } else {
                return new StandardSql();
            }
        });
    }
}
