<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Storefront extends Model
{
    use HasFactory;

    public const MODERATION_ACTIVE = 'active';

    public const MODERATION_PAUSED = 'paused';

    public const MODERATION_STATUSES = [self::MODERATION_ACTIVE, self::MODERATION_PAUSED];

    protected $fillable = [
        'business_id',
        'slug',
        'display_name',
        'tagline',
        'description',
        'public_phone',
        'public_email',
        'address',
        'city',
        'default_locale',
        'logo_path',
        'cover_path',
        'show_clothing',
        'show_tailoring',
        'inquiries_enabled',
        'online_ordering_enabled',
        'unpaid_orders_enabled',
        'cod_enabled',
        'easypaisa_enabled',
        'jazzcash_enabled',
        'bank_transfer_enabled',
        'raast_enabled',
        'easypaisa_account_title',
        'easypaisa_account_number',
        'jazzcash_account_title',
        'jazzcash_account_number',
        'bank_name',
        'bank_account_title',
        'bank_account_number',
        'bank_iban',
        'raast_account_title',
        'raast_id',
        'raast_qr_path',
        'pickup_enabled',
        'delivery_enabled',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'show_clothing' => 'boolean',
            'show_tailoring' => 'boolean',
            'inquiries_enabled' => 'boolean',
            'online_ordering_enabled' => 'boolean',
            'unpaid_orders_enabled' => 'boolean',
            'cod_enabled' => 'boolean',
            'easypaisa_enabled' => 'boolean',
            'jazzcash_enabled' => 'boolean',
            'bank_transfer_enabled' => 'boolean',
            'raast_enabled' => 'boolean',
            'pickup_enabled' => 'boolean',
            'delivery_enabled' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function clothingListings()
    {
        return $this->hasMany(StorefrontClothingListing::class);
    }

    public function tailoringServices()
    {
        return $this->hasMany(StorefrontTailoringService::class);
    }

    public function inquiries()
    {
        return $this->hasMany(StorefrontInquiry::class);
    }

    public function carts()
    {
        return $this->hasMany(StorefrontCart::class);
    }

    public function orders()
    {
        return $this->hasMany(StorefrontOrder::class);
    }

    public function moderatedBy()
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }

    public function moderationHistory()
    {
        return $this->hasMany(StorefrontModerationHistory::class)->latest('created_at')->latest('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('moderation_status', self::MODERATION_ACTIVE)
            ->whereHas('business', fn (Builder $business) => $business->where('status', Business::STATUS_ACTIVE));
    }

    public function isModerationActive(): bool
    {
        return $this->moderation_status === self::MODERATION_ACTIVE;
    }

    public function acceptedPaymentMethods(): array
    {
        if (! $this->online_ordering_enabled) {
            return [];
        }

        return array_intersect_key(StorefrontOrder::publicPaymentMethods(), array_filter([
            StorefrontOrder::PAYMENT_UNPAID => $this->unpaid_orders_enabled,
            StorefrontOrder::PAYMENT_COD => $this->cod_enabled && $this->delivery_enabled,
            StorefrontOrder::PAYMENT_EASYPAISA => $this->easypaisa_enabled,
            StorefrontOrder::PAYMENT_JAZZCASH => $this->jazzcash_enabled,
            StorefrontOrder::PAYMENT_BANK_TRANSFER => $this->bank_transfer_enabled,
            StorefrontOrder::PAYMENT_RAAST => $this->raast_enabled,
        ]));
    }

    public function acceptedInquiryPaymentMethods(): array
    {
        return array_intersect_key(StorefrontInquiry::publicPaymentMethods(), array_filter([
            StorefrontInquiry::PAYMENT_UNPAID => $this->unpaid_orders_enabled,
            StorefrontInquiry::PAYMENT_COD => $this->cod_enabled && $this->delivery_enabled,
            StorefrontInquiry::PAYMENT_EASYPAISA => $this->easypaisa_enabled,
            StorefrontInquiry::PAYMENT_JAZZCASH => $this->jazzcash_enabled,
            StorefrontInquiry::PAYMENT_BANK_TRANSFER => $this->bank_transfer_enabled,
            StorefrontInquiry::PAYMENT_RAAST => $this->raast_enabled,
        ]));
    }

    public function acceptsPaymentMethod(string $method): bool
    {
        return array_key_exists($method, $this->acceptedPaymentMethods());
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->publicAssetUrl($this->logo_path);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->publicAssetUrl($this->cover_path);
    }

    public function getRaastQrUrlAttribute(): ?string
    {
        return $this->publicAssetUrl($this->raast_qr_path);
    }

    private function publicAssetUrl(?string $path): ?string
    {
        if (! $path || ! is_file(public_path($path))) {
            return null;
        }

        return asset(str_replace('\\', '/', $path));
    }
}
