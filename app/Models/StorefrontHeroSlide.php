<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontHeroSlide extends Model
{
    use HasFactory;

    public const MEDIA_TYPES = ['image', 'video', 'color'];
    public const LINK_TYPES = ['none', 'catalog', 'tailoring', 'collection', 'contact', 'custom'];

    protected $fillable = [
        'storefront_id', 'title_ur', 'title_en', 'text_ur', 'text_en', 'media_type',
        'image_path', 'mobile_image_path', 'video_path', 'video_poster_path',
        'alignment', 'overlay_strength', 'primary_label_ur', 'primary_label_en',
        'primary_link_type', 'primary_collection_id', 'primary_url',
        'secondary_label_ur', 'secondary_label_en', 'secondary_link_type',
        'secondary_collection_id', 'secondary_url', 'is_active', 'starts_at',
        'ends_at', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'overlay_strength' => 'integer',
        'sort_order' => 'integer',
    ];

    public function storefront() { return $this->belongsTo(Storefront::class); }
    public function primaryCollection() { return $this->belongsTo(StorefrontCollection::class, 'primary_collection_id'); }
    public function secondaryCollection() { return $this->belongsTo(StorefrontCollection::class, 'secondary_collection_id'); }

    public function scopeCurrentlyVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function localized(string $field, ?string $locale = null): ?string
    {
        $locale = ($locale ?: app()->getLocale()) === 'en' ? 'en' : 'ur';
        return $this->getAttribute($field.'_'.$locale)
            ?: $this->getAttribute($field.'_'.($locale === 'ur' ? 'en' : 'ur'));
    }

    public function getImageUrlAttribute(): ?string { return $this->publicAssetUrl($this->image_path); }
    public function getMobileImageUrlAttribute(): ?string { return $this->publicAssetUrl($this->mobile_image_path); }
    public function getVideoUrlAttribute(): ?string { return $this->publicAssetUrl($this->video_path); }
    public function getVideoPosterUrlAttribute(): ?string { return $this->publicAssetUrl($this->video_poster_path); }

    public function buttonUrl(string $button): ?string
    {
        $type = $this->getAttribute($button.'_link_type');
        return match ($type) {
            'catalog' => $this->storefront->show_clothing ? route('storefront.clothing.index', $this->storefront) : null,
            'tailoring' => $this->storefront->show_tailoring ? route('storefront.tailoring.index', $this->storefront) : null,
            'collection' => ($collection = $this->getRelationValue($button.'Collection')) && $collection->is_published
                ? route('storefront.collections.show', [$this->storefront, $collection]) : null,
            'contact' => route('storefront.show', $this->storefront).'#shop-contact',
            'custom' => $this->getAttribute($button.'_url'),
            default => null,
        };
    }

    private function publicAssetUrl(?string $path): ?string
    {
        return $path && is_file(public_path($path)) ? asset(str_replace('\\', '/', $path)) : null;
    }
}
