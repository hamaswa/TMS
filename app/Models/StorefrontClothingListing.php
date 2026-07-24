<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontClothingListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_id',
        'cloth_id',
        'public_name',
        'description',
        'is_featured',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function cloth()
    {
        return $this->belongsTo(Cloth::class);
    }

    public function scopeWithReservableStock(Builder $query): Builder
    {
        $now = now();

        return $query->whereHas('cloth.colors', function (Builder $colors) use ($now) {
            $colors->whereRaw(
                'CAST(cloth_colors.length AS DECIMAL(12,2)) > COALESCE((
                    SELECT SUM(storefront_cart_items.quantity)
                    FROM storefront_cart_items
                    INNER JOIN storefront_carts
                        ON storefront_carts.id = storefront_cart_items.storefront_cart_id
                    WHERE storefront_cart_items.cloth_color_id = cloth_colors.id
                        AND storefront_cart_items.reserved_until > ?
                        AND storefront_carts.expires_at > ?
                ), 0)',
                [$now, $now]
            );
        });
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->public_name
            ?: collect([$this->cloth?->brand?->name, $this->cloth?->type?->name])->filter()->implode(' — ')
            ?: 'کپڑا';
    }
}
