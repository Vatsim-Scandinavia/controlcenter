<?php

namespace App\Http\Middleware;

use App\Modules\ModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards module routes. Module routes are registered at runtime for enabled
 * modules only (never cached), and this is the belt-and-braces enabled-check
 * behind the `/modules/<slug>` prefix: a request is rejected with a 404 if the
 * module is disabled or globally suppressed by safe mode, even if a route was
 * registered earlier in the same request.
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
