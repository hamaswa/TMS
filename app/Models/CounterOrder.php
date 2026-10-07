<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterOrder extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_FORWARDED = 'forwarded';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'reference', 'user_id', 'customer_id', 'sale_session_id', 'created_by_user_id',
        'claimed_by_user_id', 'status', 'subtotal', 'paid_amount', 'balance_amount',
        'note', 'confirmed_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'paid_amount' => 'decimal:2', 'balance_amount' => 'decimal:2',
            'confirmed_at' => 'datetime', 'closed_at' => 'datetime',
        ];
    }

    public function customer() { return $this->belongsTo(Customers::class); }
    public function saleSession() { return $this->belongsTo(SaleSession::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function claimedBy() { return $this->belongsTo(User::class, 'claimed_by_user_id'); }
    public function items() { return $this->hasMany(CounterOrderItem::class); }

    public function recalculate(): void
    {
        $subtotal = round((float) $this->items()->where('status', '!=', CounterOrderItem::STATUS_CANCELLED)->sum('line_total'), 2);
        $this->forceFill([
            'subtotal' => $subtotal,
            'balance_amount' => max(0, round($subtotal - (float) $this->paid_amount, 2)),
        ])->save();
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
