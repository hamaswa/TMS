<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasFactory;

    public const METHODS = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'easypaisa' => 'EasyPaisa',
        'jazzcash' => 'JazzCash',
        'raast' => 'Raast',
        'other' => 'Other',
    ];

    protected $fillable = [
        'business_id',
        'business_subscription_id',
        'paid_on',
        'amount',
        'payment_method',
        'reference',
        'notes',
        'recorded_by_user_id',
        'reversed_at',
        'reversed_by_user_id',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'paid_on' => 'date',
            'amount' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function subscription()
    {
        return $this->belongsTo(BusinessSubscription::class, 'business_subscription_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }
}
