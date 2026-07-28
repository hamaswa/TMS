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
        'tailoring_inquiries_enabled',
        'tailoring_unpaid_enabled',
        'tailoring_cod_enabled',
        'tailoring_easypaisa_enabled',
        'tailoring_jazzcash_enabled',
        'tailoring_bank_transfer_enabled',
        'tailoring_raast_enabled',
        'tailoring_pickup_enabled',
        'tailoring_delivery_enabled',
        'online_ordering_enabled',
        'clothing_online_ordering_enabled',
        'clothing_unpaid_enabled',
        'clothing_cod_enabled',
        'clothing_easypaisa_enabled',
        'clothing_jazzcash_enabled',
        'clothing_bank_transfer_enabled',
        'clothing_raast_enabled',
        'clothing_pickup_enabled',
        'clothing_delivery_enabled',
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
            'tailoring_inquiries_enabled' => 'boolean',
            'tailoring_unpaid_enabled' => 'boolean',
            'tailoring_cod_enabled' => 'boolean',
            'tailoring_easypaisa_enabled' => 'boolean',
            'tailoring_jazzcash_enabled' => 'boolean',
            'tailoring_bank_transfer_enabled' => 'boolean',
            'tailoring_raast_enabled' => 'boolean',
            'tailoring_pickup_enabled' => 'boolean',
            'tailoring_delivery_enabled' => 'boolean',
            'online_ordering_enabled' => 'boolean',
            'clothing_online_ordering_enabled' => 'boolean',
            'clothing_unpaid_enabled' => 'boolean',
            'clothing_cod_enabled' => 'boolean',
            'clothing_easypaisa_enabled' => 'boolean',
            'clothing_jazzcash_enabled' => 'boolean',
            'clothing_bank_transfer_enabled' => 'boolean',
            'clothing_raast_enabled' => 'boolean',
            'clothing_pickup_enabled' => 'boolean',
            'clothing_delivery_enabled' => 'boolean',
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
            ->whereHas('business', fn (Builder $business) => $business
                ->where('status', Business::STATUS_ACTIVE)
                ->where(function (Builder $subscriptionAccess) {
                    $subscriptionAccess
                        ->whereDoesntHave('subscriptions')
                        ->orWhereHas('subscriptions', fn (Builder $subscription) => $subscription
                            ->whereNull('cancelled_at')
                            ->whereDate('starts_on', '<=', now()->toDateString())
                            ->whereDate('ends_on', '>=', now()->toDateString())
                            ->where(fn (Builder $feature) => $feature
                                ->whereNull('allow_storefront')
                                ->orWhere('allow_storefront', true)));
                }));
    }

    public function isModerationActive(): bool
    {
        return $this->moderation_status === self::MODERATION_ACTIVE;
    }

    public function acceptedPaymentMethods(): array
    {
        if (! $this->clothingOrderingEnabled()) {
            return [];
        }

        return array_intersect_key(StorefrontOrder::publicPaymentMethods(), array_filter([
            StorefrontOrder::PAYMENT_UNPAID => $this->moduleSetting('clothing_unpaid_enabled', 'unpaid_orders_enabled'),
            StorefrontOrder::PAYMENT_COD => $this->moduleSetting('clothing_cod_enabled', 'cod_enabled')
                && $this->clothingDeliveryEnabled(),
            StorefrontOrder::PAYMENT_EASYPAISA => $this->moduleSetting('clothing_easypaisa_enabled', 'easypaisa_enabled'),
            StorefrontOrder::PAYMENT_JAZZCASH => $this->moduleSetting('clothing_jazzcash_enabled', 'jazzcash_enabled'),
            StorefrontOrder::PAYMENT_BANK_TRANSFER => $this->moduleSetting('clothing_bank_transfer_enabled', 'bank_transfer_enabled'),
            StorefrontOrder::PAYMENT_RAAST => $this->moduleSetting('clothing_raast_enabled', 'raast_enabled'),
        ]));
    }

    public function acceptedInquiryPaymentMethods(): array
    {
        return array_intersect_key(StorefrontInquiry::publicPaymentMethods(), array_filter([
            StorefrontInquiry::PAYMENT_UNPAID => $this->moduleSetting('tailoring_unpaid_enabled', 'unpaid_orders_enabled'),
            StorefrontInquiry::PAYMENT_COD => $this->moduleSetting('tailoring_cod_enabled', 'cod_enabled')
                && $this->tailoringDeliveryEnabled(),
            StorefrontInquiry::PAYMENT_EASYPAISA => $this->moduleSetting('tailoring_easypaisa_enabled', 'easypaisa_enabled'),
            StorefrontInquiry::PAYMENT_JAZZCASH => $this->moduleSetting('tailoring_jazzcash_enabled', 'jazzcash_enabled'),
            StorefrontInquiry::PAYMENT_BANK_TRANSFER => $this->moduleSetting('tailoring_bank_transfer_enabled', 'bank_transfer_enabled'),
            StorefrontInquiry::PAYMENT_RAAST => $this->moduleSetting('tailoring_raast_enabled', 'raast_enabled'),
        ]));
    }

    public function tailoringInquiriesEnabled(): bool
    {
        return $this->moduleSetting('tailoring_inquiries_enabled', 'inquiries_enabled');
    }

    public function clothingOrderingEnabled(): bool
    {
        return $this->moduleSetting('clothing_online_ordering_enabled', 'online_ordering_enabled');
    }

    public function tailoringPickupEnabled(): bool
    {
        return $this->moduleSetting('tailoring_pickup_enabled', 'pickup_enabled');
    }

    public function tailoringDeliveryEnabled(): bool
    {
        return $this->moduleSetting('tailoring_delivery_enabled', 'delivery_enabled');
    }

    public function clothingPickupEnabled(): bool
    {
        return $this->moduleSetting('clothing_pickup_enabled', 'pickup_enabled');
    }

    public function clothingDeliveryEnabled(): bool
    {
        return $this->moduleSetting('clothing_delivery_enabled', 'delivery_enabled');
    }

    public function offersPickup(): bool
    {
        return ($this->show_tailoring && $this->tailoringPickupEnabled())
            || ($this->show_clothing && $this->clothingPickupEnabled());
    }

    public function offersDelivery(): bool
    {
        return ($this->show_tailoring && $this->tailoringDeliveryEnabled())
            || ($this->show_clothing && $this->clothingDeliveryEnabled());
    }

    public function acceptsPaymentMethod(string $method): bool
    {
        return array_key_exists($method, $this->acceptedPaymentMethods());
    }

    private function moduleSetting(string $specific, string $legacy): bool
    {
        return $this->getAttribute($specific) === null
            ? (bool) $this->getAttribute($legacy)
            : (bool) $this->getAttribute($specific);
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
