<?php

namespace App\Services;

use App\Models\ClothColor;
use App\Models\Storefront;
use App\Models\StorefrontCart;
use App\Models\StorefrontClothingListing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontCartService
{
    public const RESERVATION_MINUTES = 30;

    public function find(Storefront $storefront, ?string $plainToken): ?StorefrontCart
    {
        if (! $plainToken) {
            return null;
        }
        $cart = $storefront->carts()
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('expires_at', '>', now())
            ->whereNull('checked_out_at')
            ->first();
        if ($cart) {
            $cart->items()->where('reserved_until', '<=', now())->delete();
        }

        return $cart;
    }

    public function getOrCreate(Storefront $storefront, ?string $plainToken): array
    {
        $cart = $this->find($storefront, $plainToken);
        if ($cart) {
            return [$cart, $plainToken];
        }

        $plainToken = Str::random(64);
        $cart = $storefront->carts()->create([
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
            'last_activity_at' => now(),
        ]);

        return [$cart, $plainToken];
    }

    public function reserve(
        StorefrontCart $cart,
        StorefrontClothingListing $listing,
        ClothColor $color,
        float $quantity
    ): void {
        DB::transaction(function () use ($cart, $listing, $color, $quantity) {
            $lockedColor = ClothColor::query()->lockForUpdate()->findOrFail($color->id);
            $existing = $cart->items()
                ->where('clothing_listing_id', $listing->id)
                ->where('cloth_color_id', $lockedColor->id)
                ->lockForUpdate()
                ->first();
            $reservedByOthers = (float) $lockedColor->storefrontCartItems()
                ->where('reserved_until', '>', now())
                ->whereHas('cart', fn ($query) => $query->where('expires_at', '>', now()))
                ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                ->sum('quantity');
            $available = max(0, (float) $lockedColor->length - $reservedByOthers);
            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => __('storefront.messages.available_quantity', [
                        'amount' => number_format($available, 2),
                    ]),
                ]);
            }

            $cart->items()->updateOrCreate(
                [
                    'clothing_listing_id' => $listing->id,
                    'cloth_color_id' => $lockedColor->id,
                ],
                [
                    'quantity' => $quantity,
                    'unit_price_snapshot' => $listing->cloth->sale_price ?: $listing->cloth->price,
                    'reserved_until' => now()->addMinutes(self::RESERVATION_MINUTES),
                ]
            );
            $cart->update([
                'expires_at' => now()->addDay(),
                'last_activity_at' => now(),
            ]);
        });
    }

    public function remove(StorefrontCart $cart, int $itemId): void
    {
        $cart->items()->whereKey($itemId)->delete();
        $cart->update(['last_activity_at' => now()]);
    }
}
