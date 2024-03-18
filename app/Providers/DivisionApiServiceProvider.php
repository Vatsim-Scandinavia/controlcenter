<?php

namespace App\Providers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Contracts\DivisionApiContract;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use App\Services\DivisionApi\Adapters\VATEUD;
use Illuminate\Support\ServiceProvider;

class DivisionApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(DivisionApiContract::class, function ($app) {
            $apiType = config('vatsim.division_api_driver'); // Setting from environment
            $enabled = Setting::get('divisionApiEnabled', false); // Setting from admin panel

            if (! $enabled) {
                return new NoOpAdapter();
            }

            switch ($apiType) {
                case 'VATEUD':
                    return new VATEUD();
                default:
                    return new NoOpAdapter();
            }
        });
    }
}
