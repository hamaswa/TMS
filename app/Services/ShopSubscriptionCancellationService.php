<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionPlanRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A shop owner stops their subscription. The paid plan keeps working until its end date
 * (no refund), nothing after it starts, and the owner can undo before the end date.
 */
class ShopSubscriptionCancellationService
{
    public function cancelAtPeriodEnd(Business $business, User $owner, string $reason): BusinessSubscription
    {
        return DB::transaction(function () use ($business, $owner, $reason) {
            Business::query()->lockForUpdate()->findOrFail($business->id);
            $current = $business->fresh()->currentSubscription();
            if (! $current) {
                throw ValidationException::withMessages(['cancel_reason' => 'آپ کا کوئی فعال پلان نہیں ہے جسے منسوخ کیا جا سکے۔']);
            }

            $current->forceFill([
                'cancel_requested_at' => now(),
                'cancel_requested_by_user_id' => $owner->id,
                'cancel_request_reason' => $reason,
            ])->save();

            // Plans queued to start after this one are cancelled, and open plan requests are closed.
            $business->subscriptions()
                ->whereNull('cancelled_at')
                ->whereDate('starts_on', '>', now()->toDateString())
                ->get()
                ->each(fn (BusinessSubscription $queued) => $queued->forceFill([
                    'cancelled_at' => now(),
                    'cancelled_by_user_id' => $owner->id,
                    'cancellation_reason' => 'Cancelled by the shop: '.$reason,
                ])->save());
            $business->planRequests()->pending()->get()
                ->each(fn (SubscriptionPlanRequest $request) => $request->forceFill([
                    'status' => SubscriptionPlanRequest::STATUS_CLOSED,
                    'review_note' => 'Closed because the shop cancelled its subscription.',
                    'reviewed_at' => now(),
                ])->save());

            return $current;
        });
    }

    public function undo(Business $business): BusinessSubscription
    {
        $current = $business->currentSubscription();
        if (! $current || ! $current->isEndingByShopRequest()) {
            throw ValidationException::withMessages(['cancel_reason' => 'منسوخی کی کوئی درخواست موجود نہیں۔']);
        }

        $current->forceFill([
            'cancel_requested_at' => null,
            'cancel_requested_by_user_id' => null,
            'cancel_request_reason' => null,
        ])->save();

        return $current;
    }
}
