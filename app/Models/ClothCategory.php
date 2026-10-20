<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClothCategory extends Model
{
    public const NAME_SEPARATOR = ' › ';

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'name_ur',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function cloths()
    {
        return $this->hasMany(Cloth::class, 'cloth_category_id');
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return $locale === 'ur' && $this->name_ur ? $this->name_ur : $this->name;
    }

    /** "Women › Abayas" for a child, just "Women" for a parent. */
    public function fullName(?string $locale = null): string
    {
        $name = $this->localizedName($locale);

        return $this->parent ? $this->parent->localizedName($locale).self::NAME_SEPARATOR.$name : $name;
    }

    /** A child's slug is prefixed with its parent's, so "Abayas" under Women becomes "women-abayas". */
    public static function makeSlug(string $name, ?self $parent = null): string
    {
        $slug = Str::slug($name) ?: Str::lower(Str::random(8));

        return $parent ? $parent->slug.'-'.$slug : $slug;
    }

    /** Like makeSlug(), but adds -2, -3… when another category of the shop already uses the slug. */
    public static function makeUniqueSlug(int $ownerId, string $name, ?self $parent = null, ?int $ignoreId = null): string
    {
        $base = self::makeSlug($name, $parent);
        $slug = $base;

        for ($suffix = 2; self::where('user_id', $ownerId)->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))->exists(); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    /** Every shop starts with "Men", since all stock before categories existed was men's cloth. */
    public static function ensureDefaultFor(int $ownerId): void
    {
        if (self::where('user_id', $ownerId)->exists()) {
            return;
        }

        self::create([
            'user_id' => $ownerId,
            'name' => 'Men',
            'name_ur' => 'مردانہ',
            'slug' => 'men',
        ]);
    }
}
