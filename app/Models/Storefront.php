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

    public function getLogoUrlAttribute(): ?string
    {
        return $this->publicAssetUrl($this->logo_path);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->publicAssetUrl($this->cover_path);
    }

    private function publicAssetUrl(?string $path): ?string
    {
        if (! $path || ! is_file(public_path($path))) {
            return null;
        }

        return asset(str_replace('\\', '/', $path));
    }
}
