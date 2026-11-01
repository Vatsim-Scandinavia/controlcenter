<?php

namespace App\Modules;

use App\Http\Middleware\RegisterModuleRoutes;
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
 * This runs at request time from {@see RegisterModuleRoutes}
 * (after the core routes have loaded, cached or not), so module routes are
 * never written into the route cache: the live enabled state is the single
 * source of truth on every request. Enabling/disabling a module takes effect
 * immediately without re-running `route:cache`, and the cache can be built by
 * any means (`route:cache`, `optimize`, a programmatic call) without capturing
 * module routes, because the cache build never handles an HTTP request.
 *
 * Each module's registration is staged into its own collection and merged into
 * the live routes only if the whole module registered cleanly. A throwing route
 * callback is logged, reported to Sentry, and leaves nothing behind: the staged
 * (partial) routes are discarded and the router's group stack is restored to its
 * pre-module depth ({@see Router::group()} does not pop its own attributes when
 * the callback throws), so a broken module cannot leave a half route live nor
 * bleed its prefix/middleware/name onto routes registered afterwards.
 */
class ModuleRouteLoader
{
    public function __construct(private ModuleRegistry $registry) {}

    public function load(): void
    {
        $booted = $this->registry->booted();

        if ($booted === []) {
            return;
        }

        /** @var Router $router */
        $router = Route::getFacadeRoot();
        $live = $router->getRoutes();
        $registered = false;

        foreach ($booted as $slug => $registrar) {
            if ($registrar->webRoutes === [] && $registrar->apiRoutes === []) {
                continue;
            }

            // Register this module into a throwaway collection so a failure
            // part-way through can be discarded wholesale; only a clean module
            // is merged into the live routes.
            $staging = new RouteCollection;
            $depth = count($router->getGroupStack());
            $this->useRoutes($router, $staging);

            try {
                foreach ($registrar->webRoutes as $routes) {
                    Route::middleware(['web', 'auth', 'activity', 'suspended', "module:{$slug}"])
                        ->prefix("modules/{$slug}")
                        ->name("modules.{$slug}.")
                        ->group($routes);
                }

                foreach ($registrar->apiRoutes as $routes) {
                    Route::middleware(['api', "module:{$slug}"])
                        ->prefix("api/modules/{$slug}")
                        ->name("api.modules.{$slug}.")
                        ->group($routes);
                }

                foreach ($staging->getRoutes() as $route) {
                    $live->add($route);
                    $registered = true;
                }
            } catch (Throwable $e) {
                // Discard the staged (partial) routes entirely.
                ModuleRegistry::captureToSentry($e, $slug);
                Log::error("Module [{$slug}] failed while registering routes: {$e->getMessage()}", ['exception' => $e, 'module' => $slug]);
            } finally {
                $this->restoreGroupStack($router, $depth);
                $this->useRoutes($router, $live);
            }
        }

        // The merged routes carry their full (group-prefixed) names, but a cached
        // instance holds a compiled collection whose name lookup needs rebuilding
        // for route() and Route::has() to find them.
        if ($registered) {
            $this->refreshNameLookups($live);
        }
    }

    /**
     * Point the router (and the container's `routes` binding) at the given
     * collection. Used to stage a module's routes in isolation and then restore
     * the live collection — which may be a {@see CompiledRouteCollection} and so
     * cannot go through Router::setRoutes() (typed to RouteCollection).
     */
    private function useRoutes(Router $router, RouteCollectionInterface $routes): void
    {
        (new ReflectionProperty(Router::class, 'routes'))->setValue($router, $routes);
        app()->instance('routes', $routes);
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
