<?php

namespace App\Http\Middleware;

use App\Helpers\VatsimRating;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SuspendedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (\Auth::check()) {
            $user = \Auth::user();
            if ($user->rating == VatsimRating::SUS) {
                \Auth::logout();

                return redirect('/')->with('error', 'Your account has been suspended.');
            }
        }

        return $next($request);
    }
}
