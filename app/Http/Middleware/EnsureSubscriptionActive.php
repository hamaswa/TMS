<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionExpiryNotifier;
use Closure;
use Illuminate\Http\Request;

class EnsureSubscriptionActive
{
    public function __construct(private readonly SubscriptionExpiryNotifier $notifier)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $business = $request->user()?->business;
        if (! $business || $business->hasActiveSubscriptionAccess()) {
            return $next($request);
        }

        $expired = $business->subscriptions()
            ->whereNull('cancelled_at')
            ->whereDate('ends_on', '<', now()->toDateString())
            ->latest('ends_on')
            ->first();
        if ($expired && $business->owner) {
            $this->notifier->deliver($expired, $business->owner, -1);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your business subscription is not active.',
                'renewal_url' => route('admin.subscription.required'),
            ], 402);
        }

        return redirect()->route('admin.subscription.required');
    }
}
