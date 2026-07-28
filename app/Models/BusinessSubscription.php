<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSubscription extends Model
{
    use HasFactory;

    public const EXPIRY_WARNING_DAYS = 14;

    protected $fillable = [
        'business_id',
        'plan_name',
        'starts_on',
        'ends_on',
        'fee',
        'notes',
        'created_by_user_id',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'fee' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function activePayments()
    {
        return $this->hasMany(SubscriptionPayment::class)->whereNull('reversed_at');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function amountPaid(): float
    {
        if (array_key_exists('active_payments_sum_amount', $this->attributes)) {
            return (float) ($this->active_payments_sum_amount ?? 0);
        }

        return (float) $this->activePayments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return max(0, (float) $this->fee - $this->amountPaid());
    }

    public function daysRemaining(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->ends_on->copy()->startOfDay(), false);
    }

    public function state(): string
    {
        if ($this->cancelled_at) {
            return 'cancelled';
        }
        if ($this->ends_on->isBefore(now()->startOfDay())) {
            return 'expired';
        }
        if ($this->daysRemaining() <= self::EXPIRY_WARNING_DAYS) {
            return 'expiring';
        }

        return 'active';
    }
}
