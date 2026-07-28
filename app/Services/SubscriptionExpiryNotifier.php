<?php

namespace App\Services;

use App\Models\BusinessSubscription;
use App\Models\SubscriptionNotificationDelivery;
use App\Models\User;
use App\Notifications\SubscriptionExpiryNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class SubscriptionExpiryNotifier
{
    public function deliver(BusinessSubscription $subscription, User $owner, int $threshold): bool
    {
        try {
            return DB::transaction(function () use ($subscription, $owner, $threshold) {
                $delivery = SubscriptionNotificationDelivery::firstOrCreate([
                    'business_subscription_id' => $subscription->id,
                    'user_id' => $owner->id,
                    'threshold_days' => $threshold,
                ], [
                    'delivered_at' => now(),
                ]);

                if (! $delivery->wasRecentlyCreated) {
                    return false;
                }

                $owner->notify(new SubscriptionExpiryNotification($subscription, $threshold));

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
