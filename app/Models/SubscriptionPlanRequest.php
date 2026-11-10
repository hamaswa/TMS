<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /** Closed without a decision, e.g. the shop picked another plan or the admin assigned one directly. */
    public const STATUS_CLOSED = 'closed';

    public const METHOD_EASYPAISA = 'easypaisa';

    /** Ways a shop can pay online for a plan: [key => [Urdu label, icon]]. */
    public const ONLINE_METHODS = [
        'easypaisa' => ['ایزی پیسہ', 'fa-mobile-alt'],
        'jazzcash' => ['جاز کیش', 'fa-wallet'],
        'sadapay' => ['سادہ پے', 'fa-credit-card'],
        'raast' => ['راست', 'fa-bolt'],
        'bank_transfer' => ['بینک ٹرانسفر', 'fa-university'],
    ];

    protected $fillable = [
        'business_id',
        'subscription_plan_id',
        'is_upgrade',
        'upgrade_reason',
        'requested_by_user_id',
        'amount',
        'payment_method',
        'payment_reference',
        'proof_path',
        'proof_original_name',
        'proof_mime_type',
        'status',
        'review_note',
        'reviewed_by_user_id',
        'reviewed_at',
        'business_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_upgrade' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function subscription()
    {
        return $this->belongsTo(BusinessSubscription::class, 'business_subscription_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Pending requests the super admin has not been alerted about yet, and marks them as announced.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function takeUnannouncedFor(User $admin): \Illuminate\Support\Collection
    {
        $requests = self::query()->pending()
            ->when($admin->plan_requests_seen_id, fn ($query, $seenId) => $query->where('id', '>', $seenId))
            ->with(['business', 'plan'])
            ->orderBy('id')
            ->get();

        if ($requests->isNotEmpty()) {
            $admin->forceFill(['plan_requests_seen_id' => $requests->max('id')])->saveQuietly();
        }

        return $requests;
    }

    /** Plain data for the super admin popup. */
    public function alertData(): array
    {
        return [
            'shop' => $this->business?->name ?? 'Shop',
            'plan' => $this->plan?->name ?? 'Plan',
            'upgrade' => (bool) $this->is_upgrade,
            'amount' => number_format((float) $this->amount),
            'reference' => $this->payment_reference,
            'method' => $this->payment_method ? (SubscriptionPayment::METHODS[$this->payment_method] ?? $this->payment_method) : null,
            'reason' => $this->upgrade_reason,
            'url' => $this->business ? route('administrator.clients.show', $this->business->owner_user_id).'#plan-request' : null,
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
