<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = ['user_id', 'supplier_id', 'purchase_id', 'return_number', 'return_date', 'total_amount', 'note'];
    protected $casts = ['return_date' => 'date', 'total_amount' => 'decimal:2'];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function items() { return $this->hasMany(PurchaseReturnItem::class); }
}
