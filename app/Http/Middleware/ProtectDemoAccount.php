<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProtectDemoAccount
{
    private const SENSITIVE_ROUTE_PATTERNS = [
        'admin.setting.*',
        'admin.*-setting',
        'admin.team.*',
        'admin.user.*',
        'admin.storefront.update',
        'admin.storefront.publish',
        'admin.storefront.module-settings.update',
        'admin.storefront.clothing.update',
        'admin.storefront.tailoring.store',
        'admin.storefront.tailoring.update',
        'password.update',
        'employee.password.*',
    ];

    public function handle(Request $request, Closure $next)
    {
        $isDemo = $request->user()?->business?->isDemo() ?? false;
        $protected = $request->isMethod('DELETE')
            || collect(self::SENSITIVE_ROUTE_PATTERNS)->contains(fn ($pattern) => $request->routeIs($pattern));

        if (! $isDemo || ! $protected || $request->isMethod('GET')) {
            return $next($request);
        }

        $message = 'یہ عوامی ڈیمو اکاؤنٹ ہے؛ اکاؤنٹ، ٹیم، ویب اسٹور اور حذف کرنے والی تبدیلیاں محفوظ ہیں۔';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return back()->with('warning', $message);
    }
}
