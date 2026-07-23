<?php

namespace App\Http\Controllers;

use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use App\Models\StorefrontInquiry;
use App\Models\StorefrontTailoringService;
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
            $storefront->is_published
            && $storefront->isModerationActive()
            && $storefront->business?->isActive(),
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
            && $storefront->isModerationActive()
            && $storefront->show_clothing
            && $storefront->business?->isActive()
            && $storefront->business->clothing_enabled,
            404
        );
    }

    public function tailoring(Storefront $storefront)
    {
        $this->ensureTailoringVisible($storefront);
        $services = $storefront->tailoringServices()
            ->where('is_published', true)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return view('storefront.public.tailoring.index', compact('storefront', 'services'));
    }

    public function tailoringShow(Storefront $storefront, StorefrontTailoringService $service)
    {
        $this->ensureTailoringVisible($storefront);
        abort_unless($service->storefront_id === $storefront->id && $service->is_published, 404);

        return view('storefront.public.tailoring.show', compact('storefront', 'service'));
    }

    public function submitInquiry(Request $request, Storefront $storefront)
    {
        $this->ensureTailoringVisible($storefront);
        abort_unless($storefront->inquiries_enabled, 404);
        $validated = $request->validate([
            'tailoring_service_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'min:7', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:3000'],
            'website' => ['prohibited'],
        ], [
            'preferred_date.after_or_equal' => 'پسندیدہ تاریخ آج یا اس کے بعد کی منتخب کریں۔',
        ]);
        $service = null;
        if (! empty($validated['tailoring_service_id'])) {
            $service = $storefront->tailoringServices()
                ->where('is_published', true)
                ->findOrFail($validated['tailoring_service_id']);
        }
        $inquiry = $storefront->inquiries()->create([
            ...collect($validated)->except(['website', 'tailoring_service_id'])->all(),
            'tailoring_service_id' => $service?->id,
            'status' => StorefrontInquiry::STATUS_NEW,
        ]);

        return redirect()->route('storefront.tailoring.index', $storefront)
            ->with('inquiry_success', 'آپ کی درخواست موصول ہو گئی ہے۔ حوالہ نمبر: '.$inquiry->reference);
    }

    private function ensureTailoringVisible(Storefront $storefront): void
    {
        abort_unless(
            $storefront->is_published
            && $storefront->isModerationActive()
            && $storefront->show_tailoring
            && $storefront->business?->isActive()
            && $storefront->business->tailoring_enabled,
            404
        );
    }
}
