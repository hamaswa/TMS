<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontCollection extends Model
{
    use HasFactory;

    public const SOURCE_TYPES = ['manual', 'category', 'brand', 'tag', 'sale', 'featured', 'new_arrivals', 'in_stock', 'all'];
    public const SORT_MODES = ['manual', 'newest', 'price_asc', 'price_desc', 'featured'];

    protected $fillable = ['storefront_id', 'slug', 'name_ur', 'name_en', 'description_ur', 'description_en', 'source_type', 'rules', 'sort_mode', 'is_published', 'sort_order'];

    protected $casts = ['rules' => 'array', 'is_published' => 'boolean', 'sort_order' => 'integer'];

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function menuItems()
    {
        return $this->hasMany(StorefrontMenuItem::class);
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = ($locale ?: app()->getLocale()) === 'en' ? 'en' : 'ur';

        return $this->getAttribute('name_'.$locale) ?: $this->getAttribute('name_'.($locale === 'ur' ? 'en' : 'ur')) ?: $this->slug;
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        $locale = ($locale ?: app()->getLocale()) === 'en' ? 'en' : 'ur';

        return $this->getAttribute('description_'.$locale) ?: $this->getAttribute('description_'.($locale === 'ur' ? 'en' : 'ur'));
    }

    public function resolvedListings(): Builder
    {
        $rules = $this->rules ?: [];
        $query = StorefrontClothingListing::query()
            ->where('storefront_id', $this->storefront_id)
            ->where('is_published', true)
            ->where('is_available', true)
            ->whereHas('cloth', fn (Builder $cloth) => $cloth->where('user_id', $this->storefront->business->owner_user_id));

        match ($this->source_type) {
            'manual' => $query->whereIn('id', array_values(array_filter(array_map('intval', $rules['listing_ids'] ?? [])))),
            'category' => $query->whereHas('cloth', fn (Builder $cloth) => $cloth->whereIn('cloth_type_id', array_map('intval', $rules['category_ids'] ?? []))),
            'brand' => $query->whereHas('cloth', fn (Builder $cloth) => $cloth->whereIn('cloth_brand_id', array_map('intval', $rules['brand_ids'] ?? []))),
            'tag' => $query->where(function (Builder $tagQuery) use ($rules) {
                foreach ($rules['tags'] ?? [] as $tag) {
                    $tagQuery->orWhereJsonContains('tags', $tag);
                }
            }),
            'sale' => $query->whereHas('cloth', fn (Builder $cloth) => $cloth->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price')),
            'featured' => $query->where('is_featured', true),
            'new_arrivals' => $query,
            'in_stock' => $query->withReservableStock(),
            default => $query,
        };

        $query
            ->when(! empty($rules['online_order_enabled']), fn (Builder $q) => $q->where('online_order_enabled', true))
            ->when(isset($rules['min_price']) && $rules['min_price'] !== '', fn (Builder $q) => $q->whereHas('cloth', fn (Builder $cloth) => $cloth->whereRaw("CAST(COALESCE(NULLIF(sale_price, ''), price) AS DECIMAL(12,2)) >= ?", [(float) $rules['min_price']])))
            ->when(isset($rules['max_price']) && $rules['max_price'] !== '', fn (Builder $q) => $q->whereHas('cloth', fn (Builder $cloth) => $cloth->whereRaw("CAST(COALESCE(NULLIF(sale_price, ''), price) AS DECIMAL(12,2)) <= ?", [(float) $rules['max_price']])));

        return match ($this->sort_mode) {
            'newest' => $query->latest('storefront_clothing_listings.id'),
            'price_asc' => $query->join('cloths as collection_cloths', 'collection_cloths.id', '=', 'storefront_clothing_listings.cloth_id')->orderByRaw("CAST(COALESCE(NULLIF(collection_cloths.sale_price, ''), collection_cloths.price) AS DECIMAL(12,2)) ASC")->select('storefront_clothing_listings.*'),
            'price_desc' => $query->join('cloths as collection_cloths', 'collection_cloths.id', '=', 'storefront_clothing_listings.cloth_id')->orderByRaw("CAST(COALESCE(NULLIF(collection_cloths.sale_price, ''), collection_cloths.price) AS DECIMAL(12,2)) DESC")->select('storefront_clothing_listings.*'),
            'featured' => $query->orderByDesc('is_featured')->orderBy('sort_order'),
            default => $query->orderBy('sort_order')->latest('storefront_clothing_listings.id'),
        };
    }
}
