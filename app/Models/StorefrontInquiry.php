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
    public const PAYMENT_JAZZCASH = 'jazzcash';
    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    public const PAYMENT_RAAST = 'raast';

    public const VERIFICATION_NOT_REQUIRED = 'not_required';
    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_REJECTED = 'rejected';

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
        'payment_evidence_path',
        'payment_evidence_original_name',
        'payment_evidence_mime_type',
        'payment_evidence_size',
        'payment_evidence_submitted_at',
        'payment_verification_status',
        'payment_verification_notes',
        'payment_verified_by_user_id',
        'payment_verified_at',
        'payment_rejected_at',
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
            self::PAYMENT_JAZZCASH => 'جاز کیش',
            self::PAYMENT_BANK_TRANSFER => 'بینک ٹرانسفر',
            self::PAYMENT_RAAST => 'راست',
        ];
    }

    public static function publicPaymentMethods(): array
    {
        return [
            self::PAYMENT_UNPAID => __('storefront.payment.methods.unpaid'),
            self::PAYMENT_COD => __('storefront.payment.methods.cod'),
            self::PAYMENT_EASYPAISA => __('storefront.payment.methods.easypaisa'),
            self::PAYMENT_JAZZCASH => __('storefront.payment.methods.jazzcash'),
            self::PAYMENT_BANK_TRANSFER => __('storefront.payment.methods.bank_transfer'),
            self::PAYMENT_RAAST => __('storefront.payment.methods.raast'),
        ];
    }

    public static function manualPaymentMethods(): array
    {
        return StorefrontOrder::manualPaymentMethods();
    }

    public static function requiresManualVerification(?string $method): bool
    {
        return in_array($method, self::manualPaymentMethods(), true);
    }

    protected $casts = [
        'preferred_date' => 'date',
        'contacted_at' => 'datetime',
        'closed_at' => 'datetime',
        'payment_verified_at' => 'datetime',
        'payment_rejected_at' => 'datetime',
        'payment_evidence_size' => 'integer',
        'payment_evidence_submitted_at' => 'datetime',
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

    public function paymentVerifier()
    {
        return $this->belongsTo(User::class, 'payment_verified_by_user_id');
    }

    public static function verificationStatuses(): array
    {
        return [
            self::VERIFICATION_NOT_REQUIRED => 'تصدیق درکار نہیں',
            self::VERIFICATION_PENDING => 'تصدیق زیرِ انتظار',
            self::VERIFICATION_VERIFIED => 'ادائیگی تصدیق شدہ',
            self::VERIFICATION_REJECTED => 'ادائیگی مسترد',
        ];
    }

    public function getReferenceAttribute(): string
    {
        return 'TMSI-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
