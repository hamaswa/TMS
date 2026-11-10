<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shops pick a plan and report their Easypaisa payment; the super admin then
 * approves (which creates the subscription and records the payment) or rejects it.
 */
class SubscriptionPlanRequestService
{
    public const DISK = 'local';

    public function submit(
        Business $business,
        User $owner,
        SubscriptionPlan $plan,
        ?string $reference,
        ?UploadedFile $proof,
        ?string $upgradeReason = null,
        ?string $method = null,
    ): SubscriptionPlanRequest {
        $reference = $reference !== null ? strtoupper(trim($reference)) : null;
        if ($reference && $this->referenceAlreadyUsed($reference)) {
            throw ValidationException::withMessages([
                'payment_reference' => 'یہ ٹرانزیکشن آئی ڈی پہلے ہی استعمال ہو چکی ہے۔',
            ]);
        }

        $stored = $proof ? $this->storeProof($proof, $business) : [];

        try {
            return DB::transaction(function () use ($business, $owner, $plan, $reference, $stored, $upgradeReason, $method) {
                Business::query()->lockForUpdate()->findOrFail($business->id);
                // One open request per shop: a new choice replaces the previous one.
                $business->planRequests()->pending()->get()
                    ->each(fn (SubscriptionPlanRequest $old) => $this->close($old, 'Replaced by a newer request from the shop.'));

                return $business->planRequests()->create([
                    'subscription_plan_id' => $plan->id,
                    'is_upgrade' => $upgradeReason !== null,
                    'upgrade_reason' => $upgradeReason,
                    'requested_by_user_id' => $owner->id,
                    'amount' => $plan->price,
                    'payment_method' => $reference ? ($method ?? SubscriptionPlanRequest::METHOD_EASYPAISA) : null,
                    'payment_reference' => $reference,
                    ...$stored,
                    'status' => SubscriptionPlanRequest::STATUS_PENDING,
                ]);
            });
        } catch (\Throwable $exception) {
            $this->deleteProof($stored['proof_path'] ?? null);

            throw $exception;
        }
    }

    /**
     * Running plans stop when an upgrade starts: one used for some days ends yesterday,
     * one that only started today is cancelled. Queued future plans are left alone.
     */
    private function endRunningPlansForUpgrade(Business $business, SubscriptionPlan $plan, User $admin): void
    {
        $today = now()->startOfDay();
        $business->subscriptions()
            ->whereNull('cancelled_at')
            ->whereDate('starts_on', '<=', $today->toDateString())
            ->whereDate('ends_on', '>=', $today->toDateString())
            ->get()
            ->each(function (BusinessSubscription $running) use ($today, $plan, $admin) {
                $note = BusinessSubscription::UPGRADE_REPLACED_NOTE.' '.$plan->name.' on '.$today->format('d M Y').'.';
                if ($running->starts_on->lt($today)) {
                    $running->forceFill([
                        'ends_on' => $today->copy()->subDay()->toDateString(),
                        'notes' => trim(($running->notes ? $running->notes."\n" : '').$note),
                    ])->save();
                } else {
                    $running->forceFill([
                        'cancelled_at' => now(),
                        'cancelled_by_user_id' => $admin->id,
                        'cancellation_reason' => $note,
                    ])->save();
                }
            });
    }

    public function approve(SubscriptionPlanRequest $request, User $admin): BusinessSubscription
    {
        return DB::transaction(function () use ($request, $admin) {
            $request = SubscriptionPlanRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($request->isPending(), 422, 'This request has already been handled.');
            $business = Business::query()->lockForUpdate()->findOrFail($request->business_id);
            $plan = $request->plan;

            if ($request->is_upgrade) {
                // An upgrade starts today and replaces the plan running now.
                $startsOn = now()->startOfDay();
                $this->endRunningPlansForUpgrade($business, $plan, $admin);
            } else {
                // Start after any running or future period so renewals don't overlap.
                $lastEnd = $business->subscriptions()->whereNull('cancelled_at')->max('ends_on');
                $startsOn = $lastEnd && Carbon::parse($lastEnd)->gte(now()->startOfDay())
                    ? Carbon::parse($lastEnd)->addDay()
                    : now()->startOfDay();
            }

            $subscription = $business->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $startsOn->copy()->addDays($plan->billing_period_days - 1)->toDateString(),
                'fee' => $request->amount,
                'notes' => 'Activated from the shop\'s plan request #'.$request->id.'.',
                'created_by_user_id' => $admin->id,
                ...$plan->entitlementSnapshot(),
            ]);

            if ($request->payment_reference && (float) $request->amount > 0) {
                $subscription->payments()->create([
                    'business_id' => $business->id,
                    'paid_on' => $request->created_at->toDateString(),
                    'amount' => $request->amount,
                    'payment_method' => $request->payment_method ?? SubscriptionPlanRequest::METHOD_EASYPAISA,
                    'reference' => $request->payment_reference,
                    'notes' => 'Verified from the shop\'s plan request #'.$request->id.'.',
                    'recorded_by_user_id' => $admin->id,
                ]);
            }

            $request->forceFill([
                'status' => SubscriptionPlanRequest::STATUS_APPROVED,
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
                'business_subscription_id' => $subscription->id,
            ])->save();

            return $subscription;
        });
    }

    public function reject(SubscriptionPlanRequest $request, User $admin, string $reason): void
    {
        DB::transaction(function () use ($request, $admin, $reason) {
            $request = SubscriptionPlanRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($request->isPending(), 422, 'This request has already been handled.');
            $request->forceFill([
                'status' => SubscriptionPlanRequest::STATUS_REJECTED,
                'review_note' => $reason,
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
            ])->save();
        });
    }

    /** Called when the super admin assigns a subscription by hand, so the shop stops seeing "under review". */
    public function closePendingFor(Business $business, User $admin): void
    {
        $business->planRequests()->pending()->get()->each(function (SubscriptionPlanRequest $request) use ($admin) {
            $this->close($request, 'Closed because the super admin assigned a subscription directly.', $admin);
        });
    }

    public function proofResponse(SubscriptionPlanRequest $request): StreamedResponse
    {
        abort_unless($request->proof_path && Storage::disk(self::DISK)->exists($request->proof_path), 404);

        $mimeType = $request->proof_mime_type ?: 'application/octet-stream';
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ];
        $name = $request->proof_original_name ?: 'payment-proof';

        return str_starts_with($mimeType, 'image/')
            ? Storage::disk(self::DISK)->response($request->proof_path, $name, $headers)
            : Storage::disk(self::DISK)->download($request->proof_path, $name, $headers);
    }

    private function close(SubscriptionPlanRequest $request, string $note, ?User $admin = null): void
    {
        $request->forceFill([
            'status' => SubscriptionPlanRequest::STATUS_CLOSED,
            'review_note' => $note,
            'reviewed_by_user_id' => $admin?->id,
            'reviewed_at' => now(),
        ])->save();
    }

    /** A transaction ID can only back one payment: reject reuse across open, approved and recorded payments. */
    private function referenceAlreadyUsed(string $reference): bool
    {
        return SubscriptionPlanRequest::query()
            ->whereIn('status', [SubscriptionPlanRequest::STATUS_PENDING, SubscriptionPlanRequest::STATUS_APPROVED])
            ->where('payment_reference', $reference)
            ->exists()
            || DB::table('subscription_payments')
                ->whereNull('reversed_at')
                ->whereIn('payment_method', array_keys(SubscriptionPlanRequest::ONLINE_METHODS))
                ->where('reference', $reference)
                ->exists();
    }

    private function storeProof(UploadedFile $file, Business $business): array
    {
        $path = $file->store('subscription-payment-proofs/'.$business->id, self::DISK);
        if (! $path) {
            throw ValidationException::withMessages(['payment_proof' => 'اسکرین شاٹ محفوظ نہیں ہو سکا۔ دوبارہ کوشش کریں۔']);
        }

        $name = preg_replace('/[\x00-\x1F\x7F"\\\\]+/u', '-', basename($file->getClientOriginalName())) ?: 'payment-proof';

        return [
            'proof_path' => $path,
            'proof_original_name' => Str::limit($name, 240, ''),
            'proof_mime_type' => $file->getMimeType() ?: 'application/octet-stream',
        ];
    }

    private function deleteProof(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
