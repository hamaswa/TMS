<?php

namespace App\Http\Controllers;

use App\Models\Cloth;
use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminStorefrontClothingController extends Controller
{
    public function index()
    {
        [$storefront, $ownerId] = $this->storefrontAndOwner();
        $cloths = Cloth::query()
            ->where('user_id', $ownerId)
            ->with(['brand', 'type', 'colors', 'images'])
            ->withSum('colors as available_length', 'length')
            ->latest()
            ->paginate(15);
        $listings = $storefront->clothingListings()
            ->whereIn('cloth_id', $cloths->pluck('id'))
            ->get()
            ->keyBy('cloth_id');

        return view('storefront.admin.clothing', compact('storefront', 'cloths', 'listings'));
    }

    public function update(Request $request, Cloth $cloth)
    {
        [$storefront, $ownerId] = $this->storefrontAndOwner();
        abort_unless((int) $cloth->user_id === $ownerId, 404);

        $validated = $request->validate([
            'public_name' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        if ($request->boolean('is_published') && ! $storefront->show_clothing) {
            throw ValidationException::withMessages([
                'is_published' => 'پہلے آن لائن دکان کی ترتیب میں کپڑے کا شعبہ فعال کریں۔',
            ]);
        }

        StorefrontClothingListing::updateOrCreate(
            ['storefront_id' => $storefront->id, 'cloth_id' => $cloth->id],
            [
                'public_name' => $validated['public_name'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_featured' => $request->boolean('is_featured'),
                'is_published' => $request->boolean('is_published'),
                'sort_order' => $validated['sort_order'] ?? 0,
            ]
        );

        return redirect()->route('admin.storefront.clothing.index')
            ->with('success', 'کپڑے کی عوامی فہرست محفوظ ہو گئی ہے۔');
    }

    private function storefrontAndOwner(): array
    {
        $user = Auth::user();
        $business = $user->business;
        abort_unless($business && $business->clothing_enabled, 404);
        $storefront = $business->storefront()->first();
        abort_unless($storefront, 404, 'پہلے آن لائن دکان کی بنیادی معلومات محفوظ کریں۔');

        return [$storefront, $user->businessOwnerId()];
    }
}
