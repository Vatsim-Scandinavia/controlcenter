<?php

namespace App\Providers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Contracts\DivisionApiContract;
use App\Modules\ModuleRegistry;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * Resolves the active division API driver from a registry rather than a closed
 * switch. Drivers come from two sources that share one registry:
 *  - **built-in** core drivers declared in `config('vatsim.division_api_drivers')`
 *    (the core registration path), such as VATEUD;
 *  - **module** drivers registered through the module system's
 *    `ControlCenter\ModuleApi\Registrar::driver()`.
 *
 * The admin toggle `divisionApiEnabled` is the master switch and
 * `vatsim.division_api_driver` names the active driver, preserving the original
 * behaviour: enabled with a recognised driver resolves it; enabled with an
 * unrecognised driver warns and falls back to no-op; disabled is silent.
 */
class DivisionApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DivisionApiContract::class, function (Application $app) {
            $enabled = Setting::get('divisionApiEnabled', false);

            // Disabled is a deliberate choice, not a misconfiguration.
            if (! $enabled) {
                return new NoOpAdapter;
            }

            $configured = config('vatsim.division_api_driver');
            $drivers = $this->drivers($app);

            if (is_string($configured) && $configured !== '' && isset($drivers[$configured])) {
                return $drivers[$configured]($app);
            }

            // Enabled, but no registered driver matches the configured name, so
            // every call would silently do nothing. That pairing is always a
            // misconfiguration worth surfacing.
            Log::warning('Division API is enabled but the configured driver is not recognised. Falling back to no-op, so no calls will reach the division.', [
                'driver' => $configured,
            ]);

            return new NoOpAdapter;
        });
    }

    /**
     * The division API driver registry: built-in core drivers merged with any
     * drivers registered by enabled modules, keyed by name.
     *
     * @return array<string, Closure(Application): object>
     */
    private function drivers(Application $app): array
    {
        $drivers = [];

        foreach ((array) config('vatsim.division_api_drivers', []) as $name => $class) {
            $drivers[$name] = fn (Application $app): object => $app->make($class);
        }

        /** @var ModuleRegistry $registry */
        $registry = $app->make(ModuleRegistry::class);

        foreach ($registry->drivers() as $driver) {
            if ($driver['contract'] === DivisionApiContract::class) {
                $drivers[$driver['name']] = $driver['factory'];
            }
        }

        return $drivers;
    }
}
