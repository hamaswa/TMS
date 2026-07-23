<?php

namespace App\Http\Controllers;

use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use Illuminate\Http\Request;

class PublicStorefrontController extends Controller
{
    public function index()
    {
        $storefronts = Storefront::query()
            ->publiclyVisible()
            ->with('business:id,status,tailoring_enabled,clothing_enabled')
            ->latest('published_at')
            ->paginate(12);

        return view('storefront.public.index', compact('storefronts'));
    }

    public function show(Storefront $storefront)
    {
        abort_unless(
            $storefront->is_published && $storefront->business?->isActive(),
            404
        );

        return view('storefront.public.show', [
            'storefront' => $storefront->load('business'),
            'preview' => false,
        ]);
    }

    public function clothing(Request $request, Storefront $storefront)
    {
        $this->ensureClothingVisible($storefront);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:100'],
        ]);
        $listings = $storefront->clothingListings()
            ->where('is_published', true)
            ->whereHas('cloth', function ($query) use ($storefront) {
                $query->where('user_id', $storefront->business->owner_user_id);
            })
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('public_name', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%')
                        ->orWhereHas('cloth.brand', fn ($brand) => $brand->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('cloth.type', fn ($type) => $type->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->when($filters['color'] ?? null, fn ($query, $color) => $query->whereHas(
                'cloth.colors',
                fn ($colors) => $colors->where('color', $color)
            ))
            ->with(['cloth.brand', 'cloth.type', 'cloth.colors', 'cloth.images'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
        $colors = $storefront->clothingListings()
            ->where('is_published', true)
            ->join('cloths', 'cloths.id', '=', 'storefront_clothing_listings.cloth_id')
            ->join('cloth_colors', 'cloth_colors.cloth_id', '=', 'cloths.id')
            ->where('cloths.user_id', $storefront->business->owner_user_id)
            ->whereNull('cloths.deleted_at')
            ->orderBy('cloth_colors.color')
            ->distinct()
            ->pluck('cloth_colors.color');

        return view('storefront.public.clothing.index', compact('storefront', 'listings', 'colors', 'filters'));
    }

    public function clothingShow(Storefront $storefront, StorefrontClothingListing $listing)
    {
        $this->ensureClothingVisible($storefront);
        abort_unless(
            $listing->storefront_id === $storefront->id
            && $listing->is_published
            && (int) $listing->cloth?->user_id === (int) $storefront->business->owner_user_id,
            404
        );

        return view('storefront.public.clothing.show', [
            'storefront' => $storefront,
            'listing' => $listing->load(['cloth.brand', 'cloth.type', 'cloth.colors', 'cloth.images']),
        ]);
    }

    private function ensureClothingVisible(Storefront $storefront): void
    {
        abort_unless(
            $storefront->is_published
            && $storefront->show_clothing
            && $storefront->business?->isActive()
            && $storefront->business->clothing_enabled,
            404
        );
    }
}
