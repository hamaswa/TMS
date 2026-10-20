<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClothCategory extends Model
{
    public const NAME_SEPARATOR = ' › ';

    // Meter stock is cut from a roll; suit stock is a packed women's suit sold whole.
    public const UNIT_METER = 'meter';
    public const UNIT_SUIT = 'suit';
    public const UNITS = [self::UNIT_METER, self::UNIT_SUIT];
    public const UNIT_LABELS = [
        self::UNIT_METER => 'میٹر',
        self::UNIT_SUIT => 'سوٹ',
    ];

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'name_ur',
        'stock_unit',
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

    public static function unitLabelFor(?string $unit): string
    {
        return self::UNIT_LABELS[$unit] ?? self::UNIT_LABELS[self::UNIT_METER];
    }

    public function unitLabel(): string
    {
        return self::unitLabelFor($this->stock_unit);
    }

    public function countsSuits(): bool
    {
        return $this->stock_unit === self::UNIT_SUIT;
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

    /**
     * Categories a cloth can be put in, in display order: sub categories, plus main categories
     * that have none. Each one has its parent loaded for fullName().
     */
    public static function selectableFor(int $ownerId)
    {
        self::ensureDefaultFor($ownerId);

        return self::where('user_id', $ownerId)->topLevel()->active()->ordered()
            ->with(['children' => fn ($query) => $query->where('is_active', true)])
            ->get()
            ->flatMap(fn (self $parent) => $parent->children->isEmpty()
                ? [$parent]
                : $parent->children->each(fn (self $child) => $child->setRelation('parent', $parent))->all())
            ->values();
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
