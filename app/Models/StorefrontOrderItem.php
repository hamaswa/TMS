<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_order_id',
        'clothing_listing_id',
        'cloth_id',
        'cloth_color_id',
        'item_name',
        'color',
        'quantity',
        'unit_price',
        'line_total',
        'cost_per_meter',
        'cost_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'cost_per_meter' => 'decimal:4',
        'cost_total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(StorefrontOrder::class, 'storefront_order_id');
    }

    public function listing()
    {
        return $this->belongsTo(StorefrontClothingListing::class, 'clothing_listing_id');
    }

    public function cloth()
    {
        return $this->belongsTo(Cloth::class);
    }

    public function colorRecord()
    {
        return $this->belongsTo(ClothColor::class, 'cloth_color_id');
    }
}
