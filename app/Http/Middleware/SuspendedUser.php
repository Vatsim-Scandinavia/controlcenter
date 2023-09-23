<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuspendedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (\Auth::check()) {
            $user = \Auth::user();
            if ($user->rating == 0) {
                \Auth::logout();

                return redirect('/')->with('error', 'Your account has been suspended.');
            }
        }

        return $next($request);
    }
}
