<?php

namespace App\Http\Middleware;

use App\Modules\ModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards module routes. Routes are registered for every compatible installed
 * module so the route cache stays stable, but requests are rejected with a 404
 * while the module is disabled (or globally suppressed by safe mode). This is
 * the runtime enabled-check behind the `/modules/<slug>` prefix.
 */
class EnsureModuleEnabled
{
    public function __construct(private ModuleRegistry $registry) {}

    public function handle(Request $request, Closure $next, string $slug): Response
    {
        if (! $this->registry->isEnabled($slug)) {
            abort(404);
        }

        return $next($request);
    }
}
