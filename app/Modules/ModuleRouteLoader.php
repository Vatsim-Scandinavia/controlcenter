<?php

namespace App\Modules;

use App\Providers\ModuleServiceProvider;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;
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
 * empty) registers no module routes at all.
 *
 * This runs at runtime after the core routes have loaded (see
 * {@see ModuleServiceProvider}), and module routes are never
 * written into the route cache. That keeps enabling/disabling a module
 * effective immediately without re-running `route:cache`, and makes the current
 * enabled state the single source of truth across replicas.
 *
 * Each module's registration is isolated: a throwing route callback is logged,
 * reported to Sentry, and — crucially — the router's group stack is restored to
 * its pre-module depth, because {@see Router::group()} does not pop its own
 * attributes when the callback throws. Without that restore, the module's
 * prefix, middleware and name-prefix would bleed onto every route registered
 * afterwards (core routes and later modules alike).
 */
class ModuleRouteLoader
{
    public function __construct(private ModuleRegistry $registry) {}

    public function load(): void
    {
        $registered = false;

        /** @var Router $router */
        $router = Route::getFacadeRoot();

        foreach ($this->registry->booted() as $slug => $registrar) {
            $depth = count($router->getGroupStack());

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
                // A module's route definitions must never break boot, nor leak
                // their group attributes onto routes registered afterwards.
                $this->restoreGroupStack($router, $depth);
                ModuleRegistry::captureToSentry($e, $slug);
                Log::error("Module [{$slug}] failed while registering routes: {$e->getMessage()}", ['exception' => $e, 'module' => $slug]);
            }
        }

        // Module routes are added after the router has already built its name
        // lookup (core routes loaded first), so the lookup used by route() and
        // Route::has() is stale; rebuild it.
        if ($registered) {
            $this->refreshNameLookups(Route::getRoutes());
        }
    }

    /**
     * Rebuild the name/action lookups for the collection the router is using.
     *
     * On a cached instance the router holds a {@see CompiledRouteCollection}
     * whose own refreshNameLookups() is a no-op; the routes we add at runtime
     * live in its inner dynamic collection, which is what must be refreshed for
     * route() and Route::has() to find them (group name-prefixes are applied
     * fluently, after the route is added, so they are not indexed at add time).
     */
    private function refreshNameLookups(RouteCollectionInterface $routes): void
    {
        if ($routes instanceof CompiledRouteCollection) {
            $property = new ReflectionProperty(CompiledRouteCollection::class, 'routes');
            $routes = $property->getValue($routes);
        }

        if ($routes instanceof RouteCollection) {
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
        }
    }

    /**
     * Trim the router's group stack back to the given depth. Router::group()
     * leaves its attributes on the stack when the route callback throws; there
     * is no public API to pop them, so restore the stack directly.
     */
    private function restoreGroupStack(Router $router, int $depth): void
    {
        if (count($router->getGroupStack()) <= $depth) {
            return;
        }

        $property = new ReflectionProperty(Router::class, 'groupStack');

        /** @var array<int, array<string, mixed>> $stack */
        $stack = $property->getValue($router);
        $property->setValue($router, array_slice($stack, 0, $depth));
    }
}
