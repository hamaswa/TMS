<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->business_id && ! $user->isBusinessOwner() && ($user->must_change_password || $user->employeePasswordExpired())) {
            return redirect()->route('employee.password.edit');
        }

        return $next($request);
    }
}
