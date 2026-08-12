<?php

namespace App\Models;

use App\Models\Cloth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClothColor extends Model
{
    use HasFactory;
    protected $fillable = ['cloth_id', 'color','user_id','length', 'average_unit_cost'];

    protected $casts = ['length' => 'decimal:2', 'average_unit_cost' => 'decimal:4'];

    public function cloth()
    {
        return $this->belongsTo(Cloth::class);
    }

    public function storefrontCartItems()
    {
        return $this->hasMany(StorefrontCartItem::class, 'cloth_color_id');
    }

    public function latestPurchaseReceipt()
    {
        return $this->hasOne(InventoryMovement::class, 'cloth_color_id')->where('movement_type', 'purchase_receipt')->latestOfMany();
    }

    public function latestCostedStockAddition()
    {
        return $this->hasOne(InventoryMovement::class, 'cloth_color_id')
        ->whereIn('movement_type', ['purchase_receipt', 'manual_adjustment_in'])
        ->latestOfMany();
    }

    public function reservableLength(): float
    {
        $reserved = $this->storefrontCartItems()
            ->where('reserved_until', '>', now())
            ->whereHas('cart', fn ($query) => $query->where('expires_at', '>', now()))
            ->sum('quantity');

        return max(0, round((float) $this->length - (float) $reserved, 2));
    }
}
