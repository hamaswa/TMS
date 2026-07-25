<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontPaymentReconciliationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_id',
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
        'expected_count' => 'integer',
        'expected_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(StorefrontPaymentReconciliation::class, 'reconciliation_id');
    }

    public function reconciler()
    {
        return $this->belongsTo(User::class, 'reconciled_by_user_id');
    }
}
