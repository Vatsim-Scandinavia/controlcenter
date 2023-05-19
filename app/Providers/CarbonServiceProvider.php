<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class CarbonServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Carbon::macro('toEuropeanDate', function ($includeDayName = null) {
            if ($includeDayName) {
                return $this->format('D. d/m/Y');
            }

            return $this->format('d/m/Y');
        });

        Carbon::macro('toEuropeanDateTime', function () {
            return $this->format('d/m/Y H:i\z');
        });

        Carbon::macro('toEuropeanTime', function () {
            return $this->format('H:i\z');
        });
    }
}
