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
    private const INTEGRATION_READABLE_ROUTES = ['api.booking.index', 'api.booking.show', 'api.positions.index'];

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

        $denial = $this->scopeDenial($request, $key, $args === 'edit');
        if ($denial !== null) {
            return response()->json(['message' => $denial], 403);
        }

        // Update last used
        $key->update(['last_used_at' => now()]);

        return $next($request);
    }

    private function scopeDenial(Request $request, ApiKey $key, bool $writes): ?string
    {
        $booking = $request->route('booking');
        $booking = $booking instanceof Booking ? $booking : null;
        $isExternal = $booking?->source === 'EXTERNAL';

        if ($key->integration_id === null) {
            return $writes && $isExternal ? 'This booking belongs to another booking system.' : null;
        }

        $ownsBooking = $isExternal && $booking->integration_id === $key->integration_id;

        if (! $writes) {
            $routeName = str_replace('api.v1.', 'api.', (string) $request->route()->getName());
            $readable = in_array($routeName, self::INTEGRATION_READABLE_ROUTES, true) && ($booking === null || $ownsBooking);

            return $readable ? null : 'Integration credentials can only read their own bookings and public positions.';
        }

        if ($booking !== null) {
            return $ownsBooking ? null : 'This booking belongs to another booking system.';
        }

        $source = $request->input('source');

        return is_string($source) && strtoupper($source) === 'EXTERNAL' ? null : 'Integration credentials must use the EXTERNAL booking source.';
    }
}
