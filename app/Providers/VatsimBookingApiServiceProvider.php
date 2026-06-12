<?php

namespace App\Providers;

use App\Contracts\VatsimBookingApiContract;
use App\Services\VatsimBooking\Api;
use App\Services\VatsimBooking\NoOpApi;
use Illuminate\Support\ServiceProvider;

class VatsimBookingApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(VatsimBookingApiContract::class, function ($app) {
            if ($app->environment('production')) {
                return new Api();
            }

            return new NoOpApi();
        });
    }
}
