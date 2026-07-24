<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontOrderReturn extends Model
{
    use HasFactory;

    public const TYPE_REFUND = 'refund';

    public const TYPE_EXCHANGE = 'exchange';

    protected $fillable = [
        'storefront_order_id',
        'reference',
        'type',
        'refund_amount',
        'refund_method',
        'external_reference',
        'notes',
        'processed_by_user_id',
        'processed_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_REFUND => 'واپسی / رقم یا بقایا ایڈجسٹمنٹ',
            self::TYPE_EXCHANGE => 'رنگ تبدیل / متبادل کپڑا',
        ];
    }

    public function order()
    {
        return $this->belongsTo(StorefrontOrder::class, 'storefront_order_id');
    }

    public function items()
    {
        return $this->hasMany(StorefrontOrderReturnItem::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
