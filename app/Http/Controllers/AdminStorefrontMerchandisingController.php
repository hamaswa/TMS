<?php

namespace App\Http\Controllers;

use App\Models\ClothBrand;
use App\Models\ClothType;
use App\Models\StorefrontCollection;
use App\Models\StorefrontHeroSlide;
use App\Models\StorefrontMenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminStorefrontMerchandisingController extends Controller
{
    public function index()
    {
        [$storefront, $ownerId] = $this->storefrontAndOwner();
        $collections = $storefront->collections()->withCount('menuItems')->get();
        $heroSlides = $storefront->heroSlides()->with(['primaryCollection', 'secondaryCollection'])->get();
        $menuItems = $storefront->menuItems()->with(['collection', 'children.collection'])->whereNull('parent_id')->get();
        $listings = $storefront->clothingListings()->where('is_published', true)
            ->whereHas('cloth', fn ($q) => $q->where('user_id', $ownerId))
            ->with(['cloth.brand', 'cloth.type'])->orderBy('sort_order')->get();
        $types = ClothType::where('user_id', $ownerId)->orderBy('name')->get();
        $brands = ClothBrand::where('user_id', $ownerId)->orderBy('name')->get();
        $tags = $storefront->clothingListings()->get()->flatMap(fn ($listing) => $listing->tags ?: [])->filter()->unique()->sort()->values();

        return view('storefront.admin.merchandising', compact('storefront', 'heroSlides', 'collections', 'menuItems', 'listings', 'types', 'brands', 'tags'));
    }

    public function storeHeroSlide(Request $request)
    {
        [$storefront] = $this->storefrontAndOwner();
        $data = $this->validateHeroSlide($request, $storefront->id);
        $storefront->heroSlides()->create($this->heroSlidePayload($request, $data));

        return back()->with('success', 'نئی ہیرو سلائیڈ محفوظ ہو گئی ہے۔');
    }

    public function updateHeroSlide(Request $request, StorefrontHeroSlide $heroSlide)
    {
        [$storefront] = $this->storefrontAndOwner();
        abort_unless($heroSlide->storefront_id === $storefront->id, 404);
        $data = $this->validateHeroSlide($request, $storefront->id);
        $heroSlide->update($this->heroSlidePayload($request, $data, $heroSlide));

        return back()->with('success', 'ہیرو سلائیڈ اپ ڈیٹ ہو گئی ہے۔');
    }

    public function destroyHeroSlide(StorefrontHeroSlide $heroSlide)
    {
        [$storefront] = $this->storefrontAndOwner();
        abort_unless($heroSlide->storefront_id === $storefront->id, 404);
        $heroSlide->delete();

        return back()->with('success', 'ہیرو سلائیڈ حذف ہو گئی ہے۔');
    }

    public function storeCollection(Request $request)
    {
        [$storefront] = $this->storefrontAndOwner();
        $data = $this->validateCollection($request, $storefront->id);
        $storefront->collections()->create($this->collectionPayload($request, $data));

        return back()->with('success', 'نئی کلیکشن محفوظ ہو گئی ہے۔');
    }

    public function updateCollection(Request $request, StorefrontCollection $collection)
    {
        [$storefront] = $this->storefrontAndOwner();
        abort_unless($collection->storefront_id === $storefront->id, 404);
        $data = $this->validateCollection($request, $storefront->id, $collection->id);
        $collection->update($this->collectionPayload($request, $data));

        return back()->with('success', 'کلیکشن اپ ڈیٹ ہو گئی ہے۔');
    }

    public function destroyCollection(StorefrontCollection $collection)
    {
        [$storefront] = $this->storefrontAndOwner();
        abort_unless($collection->storefront_id === $storefront->id, 404);
        $collection->delete();

        return back()->with('success', 'کلیکشن حذف ہو گئی ہے۔');
    }

    public function storeMenuItem(Request $request)
    {
        [$storefront] = $this->storefrontAndOwner();
        $data = $this->validateMenuItem($request, $storefront->id);
        $storefront->menuItems()->create($this->menuPayload($request, $data));

        return back()->with('success', 'مینو آئٹم شامل ہو گیا ہے۔');
    }

    public function updateMenuItem(Request $request, StorefrontMenuItem $menuItem)
    {
        [$storefront] = $this->storefrontAndOwner();
        abort_unless($menuItem->storefront_id === $storefront->id, 404);
        $data = $this->validateMenuItem($request, $storefront->id, $menuItem->id);
        $menuItem->update($this->menuPayload($request, $data));

        return back()->with('success', 'مینو آئٹم اپ ڈیٹ ہو گیا ہے۔');
    }

    public function destroyMenuItem(StorefrontMenuItem $menuItem)
    {
        [$storefront] = $this->storefrontAndOwner();
        abort_unless($menuItem->storefront_id === $storefront->id, 404);
        $menuItem->delete();

        return back()->with('success', 'مینو آئٹم حذف ہو گیا ہے۔');
    }

    private function validateCollection(Request $request, int $storefrontId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name_ur' => ['nullable', 'string', 'max:180', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:180', 'required_without:name_ur'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('storefront_collections')->where('storefront_id', $storefrontId)->ignore($ignoreId)],
            'description_ur' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'source_type' => ['required', Rule::in(StorefrontCollection::SOURCE_TYPES)],
            'category_ids' => ['nullable', 'array', 'max:50'],
            'category_ids.*' => ['integer'],
            'brand_ids' => ['nullable', 'array', 'max:50'],
            'brand_ids.*' => ['integer'],
            'collection_tags' => ['nullable', 'string', 'max:500'],
            'listing_ids' => ['nullable', 'array', 'max:100'],
            'listing_ids.*' => ['integer'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:10000000', 'gte:min_price'],
            'sort_mode' => ['required', Rule::in(StorefrontCollection::SORT_MODES)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $requiredRule = match ($data['source_type']) {
            'manual' => ! empty($data['listing_ids']),
            'category' => ! empty($data['category_ids']),
            'brand' => ! empty($data['brand_ids']),
            'tag' => filled($data['collection_tags'] ?? null),
            default => true,
        };
        if (! $requiredRule) {
            throw ValidationException::withMessages(['source_type' => 'منتخب کلیکشن قسم کے لیے کم از کم ایک متعلقہ آئٹم منتخب کریں۔']);
        }

        return $data;
    }

    private function validateHeroSlide(Request $request, int $storefrontId): array
    {
        $data = $request->validate([
            'title_ur' => ['nullable', 'string', 'max:180', 'required_without:title_en'],
            'title_en' => ['nullable', 'string', 'max:180', 'required_without:title_ur'],
            'text_ur' => ['nullable', 'string', 'max:700'],
            'text_en' => ['nullable', 'string', 'max:700'],
            'media_type' => ['required', Rule::in(StorefrontHeroSlide::MEDIA_TYPES)],
            'image' => ['nullable', 'image', 'max:8192'],
            'mobile_image' => ['nullable', 'image', 'max:6144'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:51200'],
            'video_poster' => ['nullable', 'image', 'max:6144'],
            'alignment' => ['required', Rule::in(['start', 'center'])],
            'overlay_strength' => ['required', 'integer', 'min:20', 'max:90'],
            'primary_label_ur' => ['nullable', 'string', 'max:80'],
            'primary_label_en' => ['nullable', 'string', 'max:80'],
            'primary_link_type' => ['required', Rule::in(StorefrontHeroSlide::LINK_TYPES)],
            'primary_collection_id' => ['nullable', Rule::exists('storefront_collections', 'id')->where('storefront_id', $storefrontId)],
            'primary_url' => ['nullable', 'string', 'max:1000', 'regex:/^(https?:\/\/|\/|#)/i'],
            'secondary_label_ur' => ['nullable', 'string', 'max:80'],
            'secondary_label_en' => ['nullable', 'string', 'max:80'],
            'secondary_link_type' => ['required', Rule::in(StorefrontHeroSlide::LINK_TYPES)],
            'secondary_collection_id' => ['nullable', Rule::exists('storefront_collections', 'id')->where('storefront_id', $storefrontId)],
            'secondary_url' => ['nullable', 'string', 'max:1000', 'regex:/^(https?:\/\/|\/|#)/i'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        foreach (['primary', 'secondary'] as $button) {
            $type = $data[$button.'_link_type'];
            if ($type !== 'none' && blank($data[$button.'_label_ur'] ?? null) && blank($data[$button.'_label_en'] ?? null)) {
                throw ValidationException::withMessages([$button.'_label_ur' => 'فعال بٹن کے لیے اردو یا English لیبل درج کریں۔']);
            }
            if ($type === 'collection' && empty($data[$button.'_collection_id'])) {
                throw ValidationException::withMessages([$button.'_collection_id' => 'کلیکشن بٹن کے لیے کلیکشن منتخب کریں۔']);
            }
            if ($type === 'custom' && empty($data[$button.'_url'])) {
                throw ValidationException::withMessages([$button.'_url' => 'اپنے لنک والے بٹن کے لیے محفوظ URL درج کریں۔']);
            }
        }

        return $data;
    }

    private function heroSlidePayload(Request $request, array $data, ?StorefrontHeroSlide $slide = null): array
    {
        $payload = collect($data)->except(['image', 'mobile_image', 'video', 'video_poster'])->all();
        $payload['is_active'] = $request->boolean('is_active');
        foreach (['primary', 'secondary'] as $button) {
            if ($payload[$button.'_link_type'] !== 'collection') {
                $payload[$button.'_collection_id'] = null;
            }
            if ($payload[$button.'_link_type'] !== 'custom') {
                $payload[$button.'_url'] = null;
            }
        }
        foreach (['image' => 'image_path', 'mobile_image' => 'mobile_image_path', 'video' => 'video_path', 'video_poster' => 'video_poster_path'] as $upload => $column) {
            if ($request->hasFile($upload)) {
                $payload[$column] = $this->storeMedia($request->file($upload));
            } elseif ($request->boolean('remove_'.$upload)) {
                $payload[$column] = null;
            } elseif ($slide) {
                $payload[$column] = $slide->{$column};
            }
        }

        return $payload;
    }

    private function collectionPayload(Request $request, array $data): array
    {
        $tags = collect(preg_split('/[,،\n]+/u', $data['collection_tags'] ?? ''))->map(fn ($tag) => trim($tag))->filter()->unique()->values()->all();

        return [
            'slug' => $data['slug'] ?: Str::slug($data['name_en'] ?: Str::ascii($data['name_ur'])),
            'name_ur' => $data['name_ur'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'description_ur' => $data['description_ur'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'source_type' => $data['source_type'],
            'rules' => array_filter([
                'category_ids' => $data['category_ids'] ?? [],
                'brand_ids' => $data['brand_ids'] ?? [],
                'tags' => $tags,
                'listing_ids' => $data['listing_ids'] ?? [],
                'min_price' => $data['min_price'] ?? null,
                'max_price' => $data['max_price'] ?? null,
                'online_order_enabled' => $request->boolean('online_order_enabled'),
            ], fn ($value) => $value !== null && $value !== [] && $value !== ''),
            'sort_mode' => $data['sort_mode'],
            'is_published' => $request->boolean('is_published'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function validateMenuItem(Request $request, int $storefrontId, ?int $menuItemId = null): array
    {
        $data = $request->validate([
            'label_ur' => ['nullable', 'string', 'max:120', 'required_without:label_en'],
            'label_en' => ['nullable', 'string', 'max:120', 'required_without:label_ur'],
            'item_type' => ['required', Rule::in(StorefrontMenuItem::TYPES)],
            'storefront_collection_id' => ['nullable', Rule::exists('storefront_collections', 'id')->where('storefront_id', $storefrontId)],
            'parent_id' => ['nullable', Rule::exists('storefront_menu_items', 'id')->where(fn ($q) => $q->where('storefront_id', $storefrontId)->whereNull('parent_id'))],
            'url' => ['nullable', 'url:http,https', 'max:1000'],
            'location' => ['required', Rule::in(StorefrontMenuItem::LOCATIONS)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        if ($data['item_type'] === 'collection' && empty($data['storefront_collection_id'])) {
            throw ValidationException::withMessages(['storefront_collection_id' => 'کلیکشن مینو کے لیے کلیکشن منتخب کریں۔']);
        }
        if ($data['item_type'] === 'external' && empty($data['url'])) {
            throw ValidationException::withMessages(['url' => 'بیرونی لنک درج کریں۔']);
        }
        if ($menuItemId && (int) ($data['parent_id'] ?? 0) === $menuItemId) {
            throw ValidationException::withMessages(['parent_id' => 'مینو آئٹم خود اپنا والد نہیں بن سکتا۔']);
        }

        return $data;
    }

    private function menuPayload(Request $request, array $data): array
    {
        return [
            'label_ur' => $data['label_ur'] ?? null,
            'label_en' => $data['label_en'] ?? null,
            'item_type' => $data['item_type'],
            'storefront_collection_id' => $data['item_type'] === 'collection' ? $data['storefront_collection_id'] : null,
            'parent_id' => $data['parent_id'] ?? null,
            'url' => $data['item_type'] === 'external' ? $data['url'] : null,
            'location' => $data['location'],
            'open_in_new_tab' => $data['item_type'] === 'external' && $request->boolean('open_in_new_tab'),
            'is_visible' => $request->boolean('is_visible'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function storefrontAndOwner(): array
    {
        $user = Auth::user();
        abort_unless($user->business && $user->business->storefront, 404, 'پہلے آن لائن دکان کی بنیادی معلومات محفوظ کریں۔');

        return [$user->business->storefront, $user->businessOwnerId()];
    }

    private function storeMedia($file): string
    {
        $directory = public_path('images/storefronts');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $name = $file->hashName();
        $file->move($directory, $name);

        return 'images/storefronts/'.$name;
    }
}
