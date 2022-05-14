<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;

class ApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  mixed   $editRights
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     * 
     */
    public function handle(Request $request, Closure $next, $args = "")
    {
        // Authenticate by searching for the key, check if middleware requires edit rights and compare to key access
        $key = ApiKey::find($request->bearerToken());

        if ($key == null || ($args == "edit" && $key->read_only == true)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        // Update last used
        $key->update(['last_used_at' => now()]);

        return $next($request);
    }
}
