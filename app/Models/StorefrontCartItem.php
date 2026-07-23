<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_cart_id',
        'clothing_listing_id',
        'cloth_color_id',
        'quantity',
        'unit_price_snapshot',
        'reserved_until',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price_snapshot' => 'decimal:2',
        'reserved_until' => 'datetime',
    ];

    public function cart()
    {
        return $this->belongsTo(StorefrontCart::class, 'storefront_cart_id');
    }

    public function listing()
    {
        return $this->belongsTo(StorefrontClothingListing::class, 'clothing_listing_id');
    }

    public function color()
    {
        return $this->belongsTo(ClothColor::class, 'cloth_color_id');
    }

    public function getLineTotalAttribute(): float
    {
        return round((float) $this->quantity * (float) $this->unit_price_snapshot, 2);
    }
}
