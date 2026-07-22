<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $fillable = ['user_id', 'supplier_id', 'purchase_id', 'payment_date', 'amount', 'reference', 'note'];
    protected $casts = ['payment_date' => 'date', 'amount' => 'decimal:2'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
}
