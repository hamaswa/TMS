<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterOrderComplaint extends Model
{
    protected $fillable = ['counter_order_id', 'counter_order_item_id', 'order_id', 'user_id',
        'created_by_user_id', 'reason', 'requested_tailoring_refund',
        'requested_cloth_compensation', 'status', 'approved_tailoring_refund',
        'approved_cloth_compensation', 'actual_cash_payout', 'payment_method',
        'payment_reference', 'settled_by_user_id', 'settled_at'];

    public function item() { return $this->belongsTo(CounterOrderItem::class, 'counter_order_item_id'); }
}
