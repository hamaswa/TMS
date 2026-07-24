<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontOrderRefund extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';

    public const METHOD_EASYPAISA = 'easypaisa';

    public const METHOD_BANK = 'bank_transfer';

    public const METHOD_RAAST = 'raast';

    protected $fillable = [
        'storefront_order_id',
        'reference',
        'amount',
        'method',
        'external_reference',
        'notes',
        'processed_by_user_id',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public static function methods(): array
    {
        return [
            self::METHOD_CASH => 'نقد رقم',
            self::METHOD_EASYPAISA => 'ایزی پیسہ',
            self::METHOD_BANK => 'بینک ٹرانسفر',
            self::METHOD_RAAST => 'راست',
        ];
    }

    public static function publicMethods(): array
    {
        return [
            self::METHOD_CASH => __('storefront.payment.refund_methods.cash'),
            self::METHOD_EASYPAISA => __('storefront.payment.refund_methods.easypaisa'),
            self::METHOD_BANK => __('storefront.payment.refund_methods.bank_transfer'),
            self::METHOD_RAAST => __('storefront.payment.refund_methods.raast'),
        ];
    }

    public function order()
    {
        return $this->belongsTo(StorefrontOrder::class, 'storefront_order_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
