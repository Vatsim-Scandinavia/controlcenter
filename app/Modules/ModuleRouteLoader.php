<?php

namespace App\Modules;

use Illuminate\Support\Facades\Route;

/**
 * Registers the web and API routes contributed by modules, applying the
 * standard middleware stack, the `/modules/<slug>` (and `/api/modules/<slug>`)
 * prefix, a module-namespaced route-name prefix and the enabled-check
 * middleware. Shared by the service provider and the test harness.
 */
class ModuleRouteLoader
{
    public function __construct(private ModuleRegistry $registry) {}

    public function load(): void
    {
        $registered = false;

        foreach ($this->registry->discovered() as $slug => $module) {
            if (! $module->compatible) {
                continue;
            }

            $registrar = $this->registry->registrarFor($slug);

            if ($registrar === null) {
                continue;
            }

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
