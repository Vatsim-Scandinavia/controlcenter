<?php

namespace App\Http\Middleware;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleRouteLoader;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registers enabled modules' routes at the start of every HTTP request, before
 * the router dispatches.
 *
 * Doing this per request — rather than during boot — is deliberate: module
 * routes are never written into the route cache, because the cache is built
 * without ever handling an HTTP request (`route:cache`, `optimize`, or a
 * programmatic call all serialise a fresh application's routes, and this
 * middleware never runs there). The live enabled state is therefore the single
 * source of truth on every request, so enabling or disabling a module takes
 * effect immediately and correctly across replicas, cached or not.
 */
class RegisterModuleRoutes
{
    public function __construct(private ModuleRegistry $registry) {}

    public function handle(Request $request, Closure $next): Response
    {
        (new ModuleRouteLoader($this->registry))->load();

        return $next($request);
    }
}
