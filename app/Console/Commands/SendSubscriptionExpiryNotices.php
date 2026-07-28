<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionNotificationDelivery;
use App\Notifications\SubscriptionExpiryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendSubscriptionExpiryNotices extends Command
{
    protected $signature = 'subscriptions:send-expiry-notices';

    protected $description = 'Send deduplicated database notifications for subscriptions near expiry';

    public function handle(): int
    {
        $warningDate = now()->addDays(BusinessSubscription::EXPIRY_WARNING_DAYS)->toDateString();
        $sent = 0;

        Business::query()
            ->with(['owner', 'latestSubscription'])
            ->whereHas('latestSubscription', fn ($query) => $query->whereDate('ends_on', '<=', $warningDate))
            ->orderBy('id')
            ->chunkById(100, function ($businesses) use (&$sent) {
                foreach ($businesses as $business) {
                    $subscription = $business->latestSubscription;
                    $owner = $business->owner;
                    if (! $subscription || ! $owner) {
                        continue;
                    }

                    $threshold = $this->thresholdFor($subscription->daysRemaining());
                    $delivered = DB::transaction(function () use ($subscription, $owner, $threshold) {
                        $exists = SubscriptionNotificationDelivery::query()
                            ->where('business_subscription_id', $subscription->id)
                            ->where('user_id', $owner->id)
                            ->where('threshold_days', $threshold)
                            ->exists();
                        if ($exists) {
                            return false;
                        }

                        $owner->notify(new SubscriptionExpiryNotification($subscription, $threshold));
                        SubscriptionNotificationDelivery::create([
                            'business_subscription_id' => $subscription->id,
                            'user_id' => $owner->id,
                            'threshold_days' => $threshold,
                            'delivered_at' => now(),
                        ]);

                        return true;
                    });
                    if ($delivered) {
                        $sent++;
                    }
                }
            });

        $this->info("Subscription expiry notifications sent: {$sent}");

        return self::SUCCESS;
    }

    private function thresholdFor(int $daysRemaining): int
    {
        if ($daysRemaining < 0) {
            return -1;
        }
        foreach ([0, 1, 3, 7, 14] as $threshold) {
            if ($daysRemaining <= $threshold) {
                return $threshold;
            }
        }

        return BusinessSubscription::EXPIRY_WARNING_DAYS;
    }
}
