<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Storefront extends Model
{
    use HasFactory;

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereHas('business', fn (Builder $business) => $business->where('status', Business::STATUS_ACTIVE));
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
