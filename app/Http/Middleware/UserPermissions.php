<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use View;

class UserPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (\Auth::check()) {

            // Load in permission values
            $user = \Auth::user();
            $isMentorOrAbove = $user->isMentorOrAbove();
            $isModeratorOrAbove = $user->isModeratorOrAbove();
            $isAdmin = $user->isAdmin();    
            
            // Pass permission values to all views
            View::share('userIsMentorOrAbove', $isMentorOrAbove);
            View::share('userIsModeratorOrAbove', $isModeratorOrAbove);
            View::share('userIsAdmin', $isAdmin);
        }

        return $next($request);
    }
}
