<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontOrderReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_order_return_id',
        'storefront_order_item_id',
        'quantity',
        'line_total',
        'restocked',
        'replacement_cloth_color_id',
        'replacement_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'line_total' => 'decimal:2',
        'restocked' => 'boolean',
        'replacement_quantity' => 'decimal:2',
    ];

    public function returnRecord()
    {
        return $this->belongsTo(StorefrontOrderReturn::class, 'storefront_order_return_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(StorefrontOrderItem::class, 'storefront_order_item_id');
    }

    public function replacementColor()
    {
        return $this->belongsTo(ClothColor::class, 'replacement_cloth_color_id');
    }
}
