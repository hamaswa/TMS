<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleSession extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_NEEDS_ATTENTION = 'needs_attention';

    public const STATUS_CLAIMED = 'claimed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid',
        'user_id',
        'agent_user_id',
        'status',
        'revision',
        'customer_mode',
        'customer_id',
        'customer_data',
        'items',
        'payment_data',
        'note',
        'claimed_by_user_id',
        'claimed_at',
        'completed_by_user_id',
        'counter_sale_receipt_id',
        'completed_at',
        'attention_requested_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'customer_data' => 'array',
            'items' => 'array',
            'payment_data' => 'array',
            'claimed_at' => 'datetime',
            'completed_at' => 'datetime',
            'attention_requested_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function claimedBy()
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function receipt()
    {
        return $this->belongsTo(CounterSaleReceipt::class, 'counter_sale_receipt_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }
}
