<?php

namespace App\Providers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Contracts\DivisionApiContract;
use App\Helpers\Vatsim;
use App\Services\DivisionApi\Adapters\EUD;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use Illuminate\Support\ServiceProvider;

class DivisionApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(DivisionApiContract::class, function ($app) {
            $apiType = Vatsim::getDivision();
            $enabled = Setting::get('division_api_enabled', true); // Setting from admin panel

            if (! $enabled) {
                return new NoOpAdapter();
            }

            switch ($apiType) {
                case 'EUD':
                    return new EUD();
                default:
                    return new NoOpAdapter();
            }
        });
    }
}