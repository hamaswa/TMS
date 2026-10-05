<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontMenuItem extends Model
{
    use HasFactory;

    public const TYPES = ['collection', 'catalog', 'tailoring', 'contact', 'external'];
    public const LOCATIONS = ['header', 'footer', 'both'];

    protected $fillable = ['storefront_id', 'parent_id', 'storefront_collection_id', 'label_ur', 'label_en', 'item_type', 'url', 'location', 'open_in_new_tab', 'is_visible', 'sort_order'];
    protected $casts = ['open_in_new_tab' => 'boolean', 'is_visible' => 'boolean', 'sort_order' => 'integer'];

    public function storefront() { return $this->belongsTo(Storefront::class); }
    public function collection() { return $this->belongsTo(StorefrontCollection::class, 'storefront_collection_id'); }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id'); }

    public function localizedLabel(?string $locale = null): string
    {
        $locale = ($locale ?: app()->getLocale()) === 'en' ? 'en' : 'ur';
        return $this->getAttribute('label_'.$locale) ?: $this->getAttribute('label_'.($locale === 'ur' ? 'en' : 'ur')) ?: '';
    }

    public function publicUrl(): string
    {
        return match ($this->item_type) {
            'collection' => $this->collection ? route('storefront.collections.show', [$this->storefront, $this->collection]) : '#',
            'catalog' => route('storefront.clothing.index', $this->storefront),
            'tailoring' => route('storefront.tailoring.index', $this->storefront),
            'contact' => route('storefront.show', $this->storefront).'#shop-contact',
            default => $this->url ?: '#',
        };
    }

    public function isPubliclyAvailable(): bool
    {
        return match ($this->item_type) {
            'collection' => (bool) $this->storefront->show_clothing && (bool) $this->collection?->is_published,
            'catalog' => (bool) $this->storefront->show_clothing,
            'tailoring' => (bool) $this->storefront->show_tailoring,
            'external' => filled($this->url),
            default => true,
        };
    }
}
