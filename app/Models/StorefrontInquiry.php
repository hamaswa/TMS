<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontInquiry extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_CLOSED = 'closed';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_COD = 'cod';
    public const PAYMENT_EASYPAISA = 'easypaisa';

    protected $fillable = [
        'storefront_id',
        'tailoring_service_id',
        'customer_name',
        'phone',
        'email',
        'city',
        'preferred_date',
        'message',
        'payment_method',
        'payment_sender_phone',
        'payment_reference',
        'status',
        'admin_notes',
        'contacted_at',
        'closed_at',
    ];

    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_UNPAID => 'ابھی ادائیگی نہیں',
            self::PAYMENT_COD => 'کیش آن ڈیلیوری',
            self::PAYMENT_EASYPAISA => 'ایزی پیسہ',
        ];
    }

    protected $casts = [
        'preferred_date' => 'date',
        'contacted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => 'نئی درخواست',
            self::STATUS_CONTACTED => 'رابطہ ہو گیا',
            self::STATUS_CLOSED => 'بند',
        ];
    }

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function service()
    {
        return $this->belongsTo(StorefrontTailoringService::class, 'tailoring_service_id');
    }

    public function getReferenceAttribute(): string
    {
        return 'TMSI-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
