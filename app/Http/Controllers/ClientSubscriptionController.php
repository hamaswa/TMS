<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class ClientSubscriptionController extends Controller
{
    public function index()
    {
        $business = Auth::user()->ownedBusiness()->firstOrFail();
        $subscriptions = $business->subscriptions()
            ->with(['payments.recordedBy', 'payments.reversedBy'])
            ->withSum('activePayments', 'amount')
            ->latest('ends_on')
            ->get();
        $currentSubscription = $subscriptions->first(fn ($subscription) => ! $subscription->cancelled_at);

        return view('subscriptions.client', compact('business', 'subscriptions', 'currentSubscription'));
    }

    public function required()
    {
        $user = Auth::user();
        $business = $user->business()->with('owner')->firstOrFail();
        $latestSubscription = $business->latestSubscription()->first();
        $nextSubscription = $business->subscriptions()
            ->whereNull('cancelled_at')
            ->whereDate('starts_on', '>', now()->toDateString())
            ->oldest('starts_on')
            ->first();

        return view('subscriptions.required', compact(
            'user',
            'business',
            'latestSubscription',
            'nextSubscription'
        ));
    }
}
