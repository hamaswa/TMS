<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontTailoringService extends Model
{
    use HasFactory;

    public const DEPOSIT_NONE = 'none';
    public const DEPOSIT_PERCENTAGE = 'percentage';
    public const DEPOSIT_FIXED = 'fixed';

    public const MEASUREMENT_SHOP_VISIT = 'shop_visit';
    public const MEASUREMENT_EXISTING_PROFILE = 'existing_profile';
    public const MEASUREMENT_HOME_VISIT = 'home_visit';

    protected $fillable = [
        'storefront_id',
        'name',
        'description',
        'price_from',
        'price_unit',
        'estimated_days',
        'deposit_type',
        'deposit_value',
        'measurement_methods',
        'weekly_booking_limit',
        'is_featured',
        'is_published',
        'is_available',
        'accepts_inquiries',
        'sort_order',
    ];

    protected $casts = [
        'price_from' => 'decimal:2',
        'estimated_days' => 'integer',
        'deposit_value' => 'decimal:2',
        'measurement_methods' => 'array',
        'weekly_booking_limit' => 'integer',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'is_available' => 'boolean',
        'accepts_inquiries' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function inquiries()
    {
        return $this->hasMany(StorefrontInquiry::class, 'tailoring_service_id');
    }

    public static function depositTypes(): array
    {
        return [
            self::DEPOSIT_NONE => 'پیشگی رقم ضروری نہیں',
            self::DEPOSIT_PERCENTAGE => 'قیمت کا فیصد',
            self::DEPOSIT_FIXED => 'مقررہ رقم',
        ];
    }

    public static function measurementMethodLabels(): array
    {
        return [
            self::MEASUREMENT_SHOP_VISIT => 'دکان پر نئی پیمائش',
            self::MEASUREMENT_EXISTING_PROFILE => 'محفوظ شدہ پیمائش',
            self::MEASUREMENT_HOME_VISIT => 'گھر پر پیمائش',
        ];
    }

    public function availableMeasurementMethods(): array
    {
        return $this->measurement_methods ?: [
            self::MEASUREMENT_SHOP_VISIT,
            self::MEASUREMENT_EXISTING_PROFILE,
        ];
    }

    public function depositAmount(): ?float
    {
        if ($this->deposit_type === self::DEPOSIT_FIXED) {
            return round((float) $this->deposit_value, 2);
        }
        if ($this->deposit_type === self::DEPOSIT_PERCENTAGE
            && $this->price_from !== null && $this->deposit_value !== null) {
            return round((float) $this->price_from * (float) $this->deposit_value / 100, 2);
        }

        return null;
    }
}
