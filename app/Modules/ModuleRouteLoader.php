<?php

namespace App\Modules;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Registers the web and API routes contributed by **enabled** modules, applying
 * the standard middleware stack, the `/modules/<slug>` (and `/api/modules/<slug>`)
 * prefix, a module-namespaced route-name prefix and the enabled-check
 * middleware.
 *
 * Only {@see ModuleRegistry::booted()} modules are activated here, so a disabled
 * module's `register()` hook is never run on a normal boot and its route
 * callbacks never execute — which also means safe mode (where `booted()` is
 * empty) registers no module routes at all. Each module's route registration is
 * isolated so a throwing route callback cannot break application boot.
 *
 * Note: because routes are registered only for enabled modules, an instance
 * that uses `route:cache` must re-run it after enabling or disabling a module.
 */
class ModuleRouteLoader
{
    public function __construct(private ModuleRegistry $registry) {}

    public function load(): void
    {
        $registered = false;

        foreach ($this->registry->booted() as $slug => $registrar) {
            try {
                foreach ($registrar->webRoutes as $routes) {
                    Route::middleware(['web', 'auth', 'activity', 'suspended', "module:{$slug}"])
                        ->prefix("modules/{$slug}")
                        ->name("modules.{$slug}.")
                        ->group($routes);
                    $registered = true;
                }

                foreach ($registrar->apiRoutes as $routes) {
                    Route::middleware(['api', "module:{$slug}"])
                        ->prefix("api/modules/{$slug}")
                        ->name("api.modules.{$slug}.")
                        ->group($routes);
                    $registered = true;
                }
            } catch (Throwable $e) {
                // A module's route definitions must never break boot.
                ModuleRegistry::captureToSentry($e, $slug);
                Log::error("Module [{$slug}] failed while registering routes: {$e->getMessage()}", ['exception' => $e, 'module' => $slug]);
            }
        }

        // When routes are added after the router has already built its name
        // lookup (a module toggled at runtime, or the test harness), the lookup
        // used by route() and Route::has() goes stale; rebuild it.
        $routes = Route::getRoutes();

        if ($registered && method_exists($routes, 'refreshNameLookups')) {
            $routes->refreshNameLookups();
        }
    }
}
