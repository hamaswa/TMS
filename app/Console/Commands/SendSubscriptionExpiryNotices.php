<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Services\SubscriptionExpiryNotifier;
use Illuminate\Console\Command;

class SendSubscriptionExpiryNotices extends Command
{
    protected $signature = 'subscriptions:send-expiry-notices';

    protected $description = 'Send deduplicated database notifications for subscriptions near expiry';

    public function handle(SubscriptionExpiryNotifier $notifier): int
    {
        $warningDate = now()->addDays(BusinessSubscription::EXPIRY_WARNING_DAYS)->toDateString();
        $sent = 0;

        Business::query()
            ->with(['owner', 'latestSubscription'])
            ->whereHas('latestSubscription', fn ($query) => $query->whereDate('ends_on', '<=', $warningDate))
            ->orderBy('id')
            ->chunkById(100, function ($businesses) use (&$sent, $notifier) {
                foreach ($businesses as $business) {
                    $subscription = $business->latestSubscription;
                    $owner = $business->owner;
                    if (! $subscription || ! $owner) {
                        continue;
                    }

                    $threshold = $this->thresholdFor($subscription->daysRemaining());
                    $delivered = $notifier->deliver($subscription, $owner, $threshold);
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
