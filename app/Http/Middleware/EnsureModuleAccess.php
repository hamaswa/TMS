<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        abort_unless($user && $user->hasModule($module), 403, 'یہ سہولت آپ کے اکاؤنٹ کے لیے فعال نہیں ہے۔');

        return $next($request);
    }
}
