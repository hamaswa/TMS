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
            'public_name_ur' => ['nullable', 'string', 'max:180'],
            'public_name_en' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'description_ur' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_available' => ['nullable', 'boolean'],
            'online_order_enabled' => ['nullable', 'boolean'],
            'minimum_order_quantity' => ['nullable', 'numeric', 'min:0.01', 'max:1000'],
            'maximum_order_quantity' => ['nullable', 'numeric', 'min:0.01', 'max:1000', 'gte:minimum_order_quantity'],
            'order_increment' => ['nullable', 'numeric', 'min:0.01', 'max:1000'],
            'preorder_enabled' => ['nullable', 'boolean'],
            'preorder_lead_days' => ['nullable', 'required_if:preorder_enabled,1', 'integer', 'min:1', 'max:365'],
        ]);
        $contentLocale = $storefront->default_locale === 'en' ? 'en' : 'ur';
        foreach (['public_name', 'description'] as $field) {
            if (filled($validated[$field.'_'.$contentLocale] ?? null)) {
                $validated[$field] = $validated[$field.'_'.$contentLocale];
            }
        }

        if ($request->boolean('is_published') && ! $storefront->show_clothing) {
            throw ValidationException::withMessages([
                'is_published' => 'پہلے آن لائن دکان کی ترتیب میں کپڑے کا شعبہ فعال کریں۔',
            ]);
        }

        $listing = StorefrontClothingListing::firstOrNew(
            ['storefront_id' => $storefront->id, 'cloth_id' => $cloth->id],
        );
        $listing->fill([
            'public_name' => $validated['public_name'] ?? null,
            'public_name_ur' => $validated['public_name_ur'] ?? null,
            'public_name_en' => $validated['public_name_en'] ?? null,
            'description' => $validated['description'] ?? null,
            'description_ur' => $validated['description_ur'] ?? null,
            'description_en' => $validated['description_en'] ?? null,
            'tags' => collect(preg_split('/[,،\n]+/u', $validated['tags'] ?? ''))->map(fn ($tag) => trim($tag))->filter()->unique()->values()->all(),
            'is_featured' => $request->boolean('is_featured'),
            'is_published' => $request->boolean('is_published'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);
        if ($request->boolean('product_controls_present')) {
            $listing->fill([
                'is_available' => $request->boolean('is_available'),
                'online_order_enabled' => $request->boolean('online_order_enabled'),
                'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 0.25,
                'maximum_order_quantity' => $validated['maximum_order_quantity'] ?? null,
                'order_increment' => $validated['order_increment'] ?? 0.25,
                'preorder_enabled' => $request->boolean('preorder_enabled'),
                'preorder_lead_days' => $request->boolean('preorder_enabled')
                    ? ($validated['preorder_lead_days'] ?? null)
                    : null,
            ]);
        }
        $listing->save();

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
