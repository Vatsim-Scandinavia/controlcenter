<?php

namespace App\Modules;

use App\Http\Middleware\ApiToken;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\RegisterModuleRoutes;
use App\Http\Middleware\SuspendedUser;
use App\Http\Middleware\UserActive;
use App\Http\Middleware\VerifyCsrfToken;
use App\Providers\ModuleServiceProvider;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/**
 * Registers the web and API routes contributed by **enabled** modules.
 *
 * Only {@see ModuleRegistry::booted()} modules are activated, so a disabled
 * module (and safe mode, where booted() is empty) registers nothing. Runs at
 * request time (HTTP middleware) and at command start (console), never baked
 * into the route cache — see {@see RegisterModuleRoutes}
 * and {@see ModuleServiceProvider}.
 *
 * Each module registers into a staging collection seeded once with the existing
 * routes (so `route('login')` resolves inside a callback). Only the module's own
 * new routes are merged into the live collection, and only after each one passes
 * validation; a module that throws or fails validation is discarded whole (its
 * staged routes removed and the group stack restored), leaving the live routes
 * untouched.
 *
 * Route guarantees are *enforced*, not conventional, by resolving each new
 * route's effective middleware with {@see Router::gatherRouteMiddleware()} and
 * requiring the mandatory classes to survive, plus requiring the route to stay
 * under the module's URI and name prefix and not to collide with an existing
 * route. This defeats stripping middleware by alias, class or parent class,
 * `setAction`, `setUri`, raw collection adds and prefix/name escapes. It does
 * not sandbox a module's own code (see the developer guide): a webhook that
 * needs CSRF exemption cannot be served through web() and must use api().
 */
class ModuleRouteLoader
{
    /**
     * Middleware classes a web module route must keep. Documented in
     * docs/development/modules.md.
     *
     * @var array<int, class-string>
     */
    private const WEB_MANDATORY = [
        Authenticate::class,
        SuspendedUser::class,
        UserActive::class,
        VerifyCsrfToken::class,
        EnsureModuleEnabled::class,
    ];

    /**
     * Middleware classes an API module route must keep.
     *
     * @var array<int, class-string>
     */
    private const API_MANDATORY = [
        ApiToken::class,
        ThrottleRequests::class,
        EnsureModuleEnabled::class,
    ];

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

        // Seed staging once: it carries the existing routes for lookups, while
        // only the modules' own additions are tracked for merging.
        $staging = new RouteCollection;

        foreach ($live->getRoutes() as $existing) {
            $staging->add($existing);
        }

        $staging->refreshNameLookups();
        $staging->refreshActionLookups();

        $originalIds = $this->routeIds($staging);
        $this->useRoutes($router, $staging);
        $registered = false;

        try {
            foreach ($booted as $slug => $registrar) {
                if ($registrar->webRoutes === [] && $registrar->apiRoutes === []) {
                    continue;
                }

                $before = $this->routeIds($staging);
                $depth = count($router->getGroupStack());

                try {
                    $this->registerGroup($router, $staging, 'web', $slug, $registrar->webRoutes, $before);
                    $this->registerGroup($router, $staging, 'api', $slug, $registrar->apiRoutes, $before);
                    $registered = count($this->routeIds($staging)) > count($before) || $registered;
                } catch (Throwable $e) {
                    // Discard everything this module staged, restore the group
                    // stack, and keep going with the other modules.
                    $staging = $this->rebuildTo($router, $staging, $before);
                    $this->restoreGroupStack($router, $depth);
                    ModuleRegistry::captureToSentry($e, $slug);
                    Log::error("Module [{$slug}] failed while registering routes: {$e->getMessage()}", ['exception' => $e, 'module' => $slug]);
                }
            }
        } finally {
            foreach ($staging->getRoutes() as $route) {
                if (! in_array(spl_object_id($route), $originalIds, true)) {
                    $live->add($route);
                }
            }

            $this->useRoutes($router, $live);
        }

        if ($registered) {
            $this->refreshNameLookups($live);
        }
    }

    /**
     * Register one group's route closures, then validate every route the group
     * produced. Throws (rejecting the whole module) on the first violation.
     *
     * @param  array<int, \Closure>  $closures
     * @param  array<int, int>  $existingIds  Routes present before this module.
     */
    private function registerGroup(Router $router, RouteCollection $staging, string $kind, string $slug, array $closures, array $existingIds): void
    {
        if ($closures === []) {
            return;
        }

        $uriPrefix = $kind === 'api' ? "api/modules/{$slug}" : "modules/{$slug}";
        $namePrefix = $kind === 'api' ? "api.modules.{$slug}." : "modules.{$slug}.";
        $middleware = $kind === 'api'
            ? ['api', 'api-token', "module:{$slug}"]
            : ['web', 'auth', 'activity', 'suspended', "module:{$slug}"];

        $before = $this->routeIds($staging);

        foreach ($closures as $closure) {
            Route::middleware($middleware)->prefix($uriPrefix)->name($namePrefix)->group($closure);
        }

        $existing = $this->methodUriSet($staging, $existingIds);

        foreach ($staging->getRoutes() as $route) {
            if (in_array(spl_object_id($route), $before, true)) {
                continue; // not new in this group
            }

            $this->validate($router, $route, $kind, $slug, $uriPrefix, $namePrefix, $existing);
        }
    }

    /**
     * Enforce the guarantees on a single new module route.
     *
     * @param  array<string, true>  $existing  method+URI keys of pre-existing routes.
     */
    private function validate(Router $router, RouteObject $route, string $kind, string $slug, string $uriPrefix, string $namePrefix, array $existing): void
    {
        $uri = ltrim($route->uri(), '/');

        if (! str_starts_with($uri, $uriPrefix)) {
            throw new RuntimeException("route [{$uri}] escaped the module URI prefix [{$uriPrefix}]");
        }

        $name = $route->getName();

        if (! is_string($name) || ! str_starts_with($name, $namePrefix)) {
            throw new RuntimeException("route [{$uri}] has name [" . (string) $name . "] outside the module name prefix [{$namePrefix}]");
        }

        foreach ($route->methods() as $method) {
            if (isset($existing["{$method} {$uri}"])) {
                throw new RuntimeException("route [{$method} {$uri}] collides with an existing route");
            }
        }

        $resolved = [];

        foreach ($router->gatherRouteMiddleware($route) as $middleware) {
            if (is_string($middleware)) {
                $resolved[strtok($middleware, ':')] = true;
            }
        }

        $mandatory = $kind === 'api' ? self::API_MANDATORY : self::WEB_MANDATORY;

        foreach ($mandatory as $class) {
            if (! isset($resolved[$class])) {
                throw new RuntimeException("route [{$uri}] is missing mandatory middleware [" . class_basename($class) . ']');
            }
        }
    }

    /**
     * method+URI keys for the routes whose ids are in $ids.
     *
     * @param  array<int, int>  $ids
     * @return array<string, true>
     */
    private function methodUriSet(RouteCollection $staging, array $ids): array
    {
        $set = [];

        foreach ($staging->getRoutes() as $route) {
            if (! in_array(spl_object_id($route), $ids, true)) {
                continue;
            }

            foreach ($route->methods() as $method) {
                $set["{$method} " . ltrim($route->uri(), '/')] = true;
            }
        }

        return $set;
    }

    /**
     * Rebuild staging down to the routes whose ids are in $keepIds (dropping a
     * failed module's partial additions) and point the router at the result.
     *
     * @param  array<int, int>  $keepIds
     */
    private function rebuildTo(Router $router, RouteCollection $staging, array $keepIds): RouteCollection
    {
        $rebuilt = new RouteCollection;

        foreach ($staging->getRoutes() as $route) {
            if (in_array(spl_object_id($route), $keepIds, true)) {
                $rebuilt->add($route);
            }
        }

        $rebuilt->refreshNameLookups();
        $rebuilt->refreshActionLookups();
        $this->useRoutes($router, $rebuilt);

        return $rebuilt;
    }

    /**
     * Object ids of every route currently in the collection.
     *
     * @return array<int, int>
     */
    private function routeIds(RouteCollectionInterface $routes): array
    {
        return array_map('spl_object_id', array_values($routes->getRoutes()));
    }

    /**
     * Point the router (and the container's `routes` binding) at the given
     * collection, bypassing Router::setRoutes() because the live collection may
     * be a {@see CompiledRouteCollection} (typed incompatibly).
     */
    private function useRoutes(Router $router, RouteCollectionInterface $routes): void
    {
        (new ReflectionProperty(Router::class, 'routes'))->setValue($router, $routes);
        app()->instance('routes', $routes);
    }

    /**
     * Rebuild the name/action lookups for the collection the router uses. A
     * cached instance holds a {@see CompiledRouteCollection} whose own
     * refreshNameLookups() is a no-op; its inner dynamic collection is what must
     * be refreshed for route() and Route::has() to find the merged routes.
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
     * leaves its attributes on the stack when the route callback throws.
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
