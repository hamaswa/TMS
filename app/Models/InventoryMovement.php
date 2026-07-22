<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = ['user_id', 'cloth_id', 'cloth_color_id', 'movement_type', 'quantity', 'balance_after', 'unit_cost', 'reference_type', 'reference_id', 'note', 'occurred_at'];
    protected $casts = ['quantity' => 'decimal:2', 'balance_after' => 'decimal:2', 'unit_cost' => 'decimal:4', 'occurred_at' => 'datetime'];

    public function cloth() { return $this->belongsTo(Cloth::class); }
    public function clothColor() { return $this->belongsTo(ClothColor::class); }
}
