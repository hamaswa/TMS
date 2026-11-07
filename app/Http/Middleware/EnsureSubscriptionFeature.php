<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Blocks a page when the shop's plan does not include the feature, e.g. allow_storefront. */
class EnsureSubscriptionFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $business = $request->user()?->business;
        abort_unless($business && $business->subscriptionAllowsFeature($feature), 403, 'یہ سہولت آپ کے موجودہ پلان میں شامل نہیں ہے۔');

        return $next($request);
    }
}
