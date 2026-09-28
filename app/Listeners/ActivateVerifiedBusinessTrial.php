<?php

namespace App\Listeners;

use App\Models\Business;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;

class ActivateVerifiedBusinessTrial
{
    public function handle(Verified $event): void
    {
        $user = $event->user;
        if (! $user->isBusinessOwner()) {
            return;
        }

        $business = $user->ownedBusiness()->first();
        if (! $business || $business->status !== Business::STATUS_PENDING
            || ! $business->subscriptions()->where('is_trial', true)->exists()) {
            return;
        }

        DB::transaction(function () use ($business) {
            $locked = Business::lockForUpdate()->findOrFail($business->id);
            if ($locked->status !== Business::STATUS_PENDING) {
                return;
            }
            $locked->forceFill([
                'status' => Business::STATUS_ACTIVE,
                'approved_at' => now(),
                'status_changed_at' => now(),
                'status_reason' => 'Email verified; self-service trial activated.',
            ])->save();
            $locked->statusHistory()->create([
                'from_status' => Business::STATUS_PENDING,
                'to_status' => Business::STATUS_ACTIVE,
                'reason' => 'Email verified; self-service trial activated.',
                'created_at' => now(),
            ]);
        });
    }
}
