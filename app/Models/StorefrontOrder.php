<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'storefront_id',
        'storefront_cart_id',
        'customer_id',
        'transaction_id',
        'reference',
        'tracking_token_hash',
        'status',
        'fulfillment_method',
        'delivery_address',
        'customer_note',
        'subtotal',
        'paid_amount',
        'balance_amount',
        'placed_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $hidden = ['tracking_token_hash'];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'placed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function cart()
    {
        return $this->belongsTo(StorefrontCart::class, 'storefront_cart_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function items()
    {
        return $this->hasMany(StorefrontOrderItem::class);
    }
}
