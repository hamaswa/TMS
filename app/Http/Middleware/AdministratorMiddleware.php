<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Illuminate\Support\Facades\Auth;

class AdministratorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next,$role)
    {
        if (Auth::check()) {
            // Check if the user has the required role
            if (Auth::user()->hasRole($role)) {
                return $next($request);
            }

            if (in_array('shop_owner', explode('|', $role), true) && Auth::user()->isBusinessMember()) {
                return $next($request);
            }
        }

        // If the user does not have the required role, throw an UnauthorizedException
        throw UnauthorizedException::forRoles([$role]);
        // $user = auth()->user();
        // if ($user && $user->role == 'administrative') {
        //     return $next($request);
        // } else {
        //     abort(403, 'Unauthorized access.');
        // }
    }
}
