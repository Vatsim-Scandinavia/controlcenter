<?php

namespace App\Providers;

use App\Contracts\DivisionApiContract;
use App\Modules\ModuleRegistry;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use ControlCenter\ModuleApi\Registrar;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * Resolves the active division API driver from a registry rather than a closed
 * switch. Core ships only the {@see NoOpAdapter} default; concrete drivers (such
 * as the VATEUD module) register themselves through the module system's
 * {@see Registrar::driver()}, and this provider selects
 * among them using core config.
 *
 * Selection, resolved lazily on first use:
 *  - `vatsim.division_api_driver` names the active driver when set; a configured
 *    name with no matching enabled provider warns and falls back to no-op.
 *  - with no name set, a single enabled provider is used unambiguously; more
 *    than one is ambiguous and warns; none means the division API is simply off.
 */
class DivisionApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DivisionApiContract::class, function (Application $app) {
            /** @var ModuleRegistry $registry */
            $registry = $app->make(ModuleRegistry::class);

            $drivers = array_values(array_filter(
                $registry->drivers(),
                fn (array $driver): bool => $driver['contract'] === DivisionApiContract::class,
            ));

            $configured = config('vatsim.division_api_driver');

            // An explicit selection by name wins when set.
            if (is_string($configured) && $configured !== '') {
                foreach ($drivers as $driver) {
                    if (strcasecmp($driver['name'], $configured) === 0) {
                        return ($driver['factory'])($app);
                    }
                }

                // The admin enabled a driver by name, but no enabled module
                // provides it, so every call would silently do nothing. That
                // pairing is always a misconfiguration worth surfacing.
                Log::warning('A division API driver is configured but no enabled module provides it. Falling back to no-op, so no calls will reach the division.', [
                    'driver' => $configured,
                ]);

                return new NoOpAdapter;
            }

            // No explicit selection: a single enabled provider is unambiguous.
            if (count($drivers) === 1) {
                return ($drivers[0]['factory'])($app);
            }

            if (count($drivers) > 1) {
                Log::warning('Multiple enabled modules provide a division API driver but none is selected via vatsim.division_api_driver. Falling back to no-op.', [
                    'drivers' => array_column($drivers, 'name'),
                ]);
            }

            return new NoOpAdapter;
        });
    }
}
