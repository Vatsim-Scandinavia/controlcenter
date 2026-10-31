<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiToken
{
    /**
     * Handle an incoming request.
     *
     * Accepts any number of middleware arguments. The literal `edit` requires a
     * key with write access (as before); any other argument is treated as a
     * required API scope the key must hold. A route with no scope argument is
     * served to any valid key, so existing (unscoped) keys keep working.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, string ...$args)
    {
        $requiresEdit = in_array('edit', $args, true);
        $requiredScopes = array_values(array_diff($args, ['edit']));

        // Authenticate by searching for the key, then enforce edit rights and
        // any required module scopes against the key's grants.
        $key = ApiKey::find($request->bearerToken());

        $authorized = $key !== null
            && ! ($requiresEdit && $key->read_only == true)
            && $this->keyHasScopes($key, $requiredScopes);

        if (! $authorized) {

            // Exception for open routes. Compare the path only (getPathInfo), so a query
            // string such as `/api/v1/bookings?date=today` still resolves as a public route.
            // Scoped routes are never public.
            $openRoutes = ['/api/bookings', '/api/positions', '/api/v1/bookings', '/api/v1/positions'];
            if ($requiredScopes === [] && in_array($request->getPathInfo(), $openRoutes, true)) {
                $request->attributes->set('unauthenticated', true);

                return $next($request);
            } else {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }
        }

        // Update last used
        $key->update(['last_used_at' => now()]);

        return $next($request);
    }

    /**
     * Whether the key holds every required scope. No required scopes means the
     * route is unscoped and any valid key is accepted.
     *
     * @param  array<int, string>  $scopes
     */
    private function keyHasScopes(ApiKey $key, array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (! $key->hasScope($scope)) {
                return false;
            }
        }

        return true;
    }
}
