<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = ['user_id', 'supplier_id', 'purchase_number', 'purchase_date', 'status', 'total_amount', 'paid_amount', 'balance_amount', 'reference', 'note', 'received_at', 'cancelled_at'];
    protected $casts = ['purchase_date' => 'date', 'received_at' => 'datetime', 'cancelled_at' => 'datetime', 'total_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'balance_amount' => 'decimal:2'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function items() { return $this->hasMany(PurchaseItem::class); }
    public function payments() { return $this->hasMany(SupplierPayment::class); }
    public function returns() { return $this->hasMany(PurchaseReturn::class); }
}
