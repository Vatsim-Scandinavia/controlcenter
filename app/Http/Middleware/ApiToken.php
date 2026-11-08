<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\Booking;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @param  mixed  $editRights
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, $args = '')
    {
        // Authenticate by searching for the key, check if middleware requires edit rights and compare to key access
        $key = ApiKey::find($request->bearerToken());

        if ($key == null || ($args == 'edit' && $key->read_only == true)) {

            // Exception for open routes. Compare the path only (getPathInfo), so a query
            // string such as `/api/v1/bookings?date=today` still resolves as a public route.
            $openRoutes = ['/api/bookings', '/api/positions', '/api/v1/bookings', '/api/v1/positions'];
            if (in_array($request->getPathInfo(), $openRoutes, true)) {
                $request->attributes->set('unauthenticated', true);

                return $next($request);
            } else {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }
        }

        $request->attributes->set('api_key', $key);

        // Integration credentials can only write their own externally managed bookings.
        if ($args === 'edit') {
            $booking = $request->route('booking');
            if ($booking instanceof Booking) {
                if (($key->integration_id !== null || $booking->source === 'EXTERNAL')
                    && ($booking->source !== 'EXTERNAL' || $key->integration_id === null || $booking->integration_id !== $key->integration_id)) {
                    return response()->json(['message' => 'This booking belongs to another booking system.'], 403);
                }
            } elseif ($key->integration_id !== null && (! is_string($request->input('source')) || strtoupper($request->input('source')) !== 'EXTERNAL')) {
                return response()->json(['message' => 'Integration credentials must use the EXTERNAL booking source.'], 403);
            }
        }

        // Update last used
        $key->update(['last_used_at' => now()]);

        return $next($request);
    }
}
