<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCancellationSettlement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'order_total' => 'decimal:2',
        'paid_on_order' => 'decimal:2',
        'cancelled_unpaid_balance' => 'decimal:2',
        'other_balance_before' => 'decimal:2',
        'cancellation_charge' => 'decimal:2',
        'compensation' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'applied_to_other_balance' => 'decimal:2',
        'cash_refund' => 'decimal:2',
        'retained_credit' => 'decimal:2',
        'customer_balance_after' => 'decimal:2',
    ];
}
