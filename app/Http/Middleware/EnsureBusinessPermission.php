<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $allowed = collect(explode('|', $permission))
            ->contains(fn (string $candidate) => $request->user()?->hasBusinessPermission($candidate));
        abort_unless($allowed, 403, 'آپ کو یہ کام کرنے کی اجازت نہیں ہے۔');

        return $next($request);
    }
}
