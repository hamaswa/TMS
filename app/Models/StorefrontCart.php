<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_id',
        'customer_id',
        'token_hash',
        'expires_at',
        'last_activity_at',
        'checked_out_at',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(StorefrontCartItem::class);
    }

    public function order()
    {
        return $this->hasOne(StorefrontOrder::class);
    }
}
