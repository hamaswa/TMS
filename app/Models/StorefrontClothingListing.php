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
        'is_available',
        'online_order_enabled',
        'minimum_order_quantity',
        'maximum_order_quantity',
        'order_increment',
        'preorder_enabled',
        'preorder_lead_days',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'is_available' => 'boolean',
        'online_order_enabled' => 'boolean',
        'minimum_order_quantity' => 'decimal:2',
        'maximum_order_quantity' => 'decimal:2',
        'order_increment' => 'decimal:2',
        'preorder_enabled' => 'boolean',
        'preorder_lead_days' => 'integer',
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

    public function minimumOrderQuantity(): float
    {
        return max(0.01, (float) ($this->minimum_order_quantity ?? 0.25));
    }

    public function maximumOrderQuantity(): float
    {
        return min(1000, max($this->minimumOrderQuantity(), (float) ($this->maximum_order_quantity ?? 1000)));
    }

    public function orderIncrement(): float
    {
        return max(0.01, (float) ($this->order_increment ?? 0.25));
    }

    public function acceptsOnlineOrders(): bool
    {
        return (bool) $this->is_available && (bool) $this->online_order_enabled;
    }

    public function acceptsQuantity(float $quantity): bool
    {
        $quantityUnits = (int) round($quantity * 100);
        $minimumUnits = (int) round($this->minimumOrderQuantity() * 100);
        $incrementUnits = max(1, (int) round($this->orderIncrement() * 100));

        return $quantity >= $this->minimumOrderQuantity()
            && $quantity <= $this->maximumOrderQuantity()
            && ($quantityUnits - $minimumUnits) % $incrementUnits === 0;
    }
}
