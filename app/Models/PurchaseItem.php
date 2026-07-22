<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'cloth_id', 'cloth_color_id', 'color', 'quantity', 'unit_cost', 'line_total', 'received_quantity', 'returned_quantity'];
    protected $casts = ['quantity' => 'decimal:2', 'unit_cost' => 'decimal:2', 'line_total' => 'decimal:2', 'received_quantity' => 'decimal:2', 'returned_quantity' => 'decimal:2'];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function cloth() { return $this->belongsTo(Cloth::class); }
    public function clothColor() { return $this->belongsTo(ClothColor::class); }
}
