<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontPaymentReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_id',
        'settlement_date',
        'payment_method',
        'expected_count',
        'expected_amount',
        'actual_amount',
        'variance_amount',
        'external_reference',
        'notes',
        'reconciled_by_user_id',
        'reconciled_at',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'expected_count' => 'integer',
        'expected_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function reconciler()
    {
        return $this->belongsTo(User::class, 'reconciled_by_user_id');
    }

    public function events()
    {
        return $this->hasMany(StorefrontPaymentReconciliationEvent::class, 'reconciliation_id');
    }
}
