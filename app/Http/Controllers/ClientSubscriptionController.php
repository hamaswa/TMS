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
}
