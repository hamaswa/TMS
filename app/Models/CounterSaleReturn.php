<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterSaleReturn extends Model
{
    protected $guarded = [];
    protected $casts = ['return_total' => 'decimal:2', 'balance_reduction' => 'decimal:2', 'refund_amount' => 'decimal:2', 'credit_amount' => 'decimal:2'];
    public function items() { return $this->hasMany(CounterSaleReturnItem::class); }
    public function receipt() { return $this->belongsTo(CounterSaleReceipt::class, 'counter_sale_receipt_id'); }
}
