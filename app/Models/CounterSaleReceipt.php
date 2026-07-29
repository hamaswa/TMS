<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterSaleReceipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'user_id',
        'customer_id',
        'first_sale_stock_id',
        'status',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(SaleStock::class, 'counter_sale_receipt_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }
}
