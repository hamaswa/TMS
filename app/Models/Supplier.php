<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'contact_person', 'phone', 'email', 'address', 'opening_balance', 'active'];
    protected $casts = ['active' => 'boolean', 'opening_balance' => 'decimal:2'];

    public function purchases() { return $this->hasMany(Purchase::class); }
    public function payments() { return $this->hasMany(SupplierPayment::class); }
}
