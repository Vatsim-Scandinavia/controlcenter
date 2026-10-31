<?php

namespace App\Providers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Contracts\DivisionApiContract;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use App\Services\DivisionApi\Adapters\VATEUD;
use Illuminate\Support\Facades\Log;
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
                    // The admin panel says the API is on, but no driver matches, so
                    // every call silently does nothing. That pairing is always a
                    // misconfiguration, and without this it is invisible until
                    // someone notices the roster drifting.
                    Log::warning('Division API is enabled but the configured driver is not recognised. Falling back to no-op, so no calls will reach the division.', [
                        'driver' => $apiType,
                    ]);

                    return new NoOpAdapter();
            }
        });
    }
}
