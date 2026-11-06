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

    protected $fillable = [
        'business_id',
        'subscription_plan_id',
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

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
