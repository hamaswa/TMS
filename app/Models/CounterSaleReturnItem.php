<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterSaleReturnItem extends Model
{
    protected $guarded = [];
    protected $casts = ['quantity' => 'decimal:2', 'inventory_quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2', 'restocked' => 'boolean'];
    public function saleStock() { return $this->belongsTo(SaleStock::class); }
    public function saleReturn() { return $this->belongsTo(CounterSaleReturn::class, 'counter_sale_return_id'); }
}
