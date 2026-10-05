<?php

namespace App\Http\Controllers;

use App\Models\ClothBrand;
use App\Models\ClothType;
use App\Models\Setting;
use App\Models\Storefront;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminStorefrontController extends Controller
{
    public function edit()
    {
        [$business, $storefront] = $this->storefrontForCurrentBusiness();
        if ($storefront->exists) {
            $storefront->loadCount([
                'clothingListings as published_clothing_listings_count' => fn ($query) => $query->where('is_published', true),
                'tailoringServices as published_tailoring_services_count' => fn ($query) => $query->where('is_published', true),
            ]);
        }

        return view('storefront.admin.edit', compact('business', 'storefront'));
    }

    public function update(Request $request)
    {
        [$business, $storefront] = $this->storefrontForCurrentBusiness();
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:150'],
            'display_name_ur' => ['nullable', 'string', 'max:150'],
            'display_name_en' => ['nullable', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('storefronts', 'slug')->ignore($storefront->id),
            ],
            'tagline' => ['nullable', 'string', 'max:180'],
            'tagline_ur' => ['nullable', 'string', 'max:180'],
            'tagline_en' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'description_ur' => ['nullable', 'string', 'max:3000'],
            'description_en' => ['nullable', 'string', 'max:3000'],
            'public_phone' => ['nullable', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s-]{6,28}$/'],
            'public_email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'address_ur' => ['nullable', 'string', 'max:1000'],
            'address_en' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'city_ur' => ['nullable', 'string', 'max:100'],
            'city_en' => ['nullable', 'string', 'max:100'],
            'default_locale' => ['required', Rule::in(['ur', 'en'])],
            'hero_title_ur' => ['nullable', 'string', 'max:180'],
            'hero_title_en' => ['nullable', 'string', 'max:180'],
            'hero_text_ur' => ['nullable', 'string', 'max:500'],
            'hero_text_en' => ['nullable', 'string', 'max:500'],
            'announcement_ur' => ['nullable', 'string', 'max:180'],
            'announcement_en' => ['nullable', 'string', 'max:180'],
            'design_settings_present' => ['nullable', 'boolean'],
            'theme_primary_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_accent_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_background_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_surface_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_text_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_style' => ['sometimes', 'required', Rule::in(['modern', 'classic', 'minimal'])],
            'hero_layout' => ['sometimes', 'required', Rule::in(['overlay', 'split', 'minimal'])],
            'hero_alignment' => ['sometimes', 'required', Rule::in(['start', 'center'])],
            'hero_overlay_strength' => ['sometimes', 'required', 'integer', 'min:20', 'max:90'],
            'hero_height' => ['sometimes', 'required', Rule::in(['compact', 'standard', 'tall'])],
            'hero_media_type' => ['sometimes', 'required', Rule::in(['image', 'video', 'color'])],
            'hero_media_fit' => ['sometimes', 'required', Rule::in(['cover', 'contain'])],
            'hero_media_position' => ['sometimes', 'required', Rule::in(['center', 'top', 'bottom'])],
            'hero_text_animation' => ['sometimes', 'required', Rule::in(['none', 'fade', 'fade_up', 'slide'])],
            'corner_style' => ['sometimes', 'required', Rule::in(['square', 'soft', 'rounded'])],
            'product_columns' => ['sometimes', 'required', 'integer', Rule::in([2, 3, 4])],
            'product_columns_tablet' => ['sometimes', 'required', 'integer', Rule::in([1, 2, 3])],
            'product_columns_mobile' => ['sometimes', 'required', 'integer', Rule::in([1, 2])],
            'product_image_ratio' => ['sometimes', 'required', Rule::in(['square', 'portrait', 'landscape', 'natural'])],
            'show_product_brand' => ['nullable', 'boolean'],
            'show_product_category' => ['nullable', 'boolean'],
            'show_product_stock' => ['nullable', 'boolean'],
            'header_layout' => ['sometimes', 'required', Rule::in(['menu_first', 'search_first'])],
            'sticky_header' => ['nullable', 'boolean'],
            'show_nav_categories' => ['nullable', 'boolean'],
            'show_nav_brands' => ['nullable', 'boolean'],
            'show_featured_products' => ['nullable', 'boolean'],
            'show_benefits' => ['nullable', 'boolean'],
            'show_services' => ['nullable', 'boolean'],
            'show_about' => ['nullable', 'boolean'],
            'navigation_links' => ['nullable', 'array', 'max:6'],
            'navigation_links.*.label_ur' => ['nullable', 'string', 'max:50'],
            'navigation_links.*.label_en' => ['nullable', 'string', 'max:50'],
            'navigation_links.*.url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/|#)/i'],
            'navigation_links.*.location' => ['nullable', Rule::in(['header', 'footer', 'both'])],
            'navigation_links.*.new_tab' => ['nullable', 'boolean'],
            'footer_style' => ['sometimes', 'required', Rule::in(['simple', 'columns', 'brand'])],
            'footer_text_ur' => ['nullable', 'string', 'max:500'],
            'footer_text_en' => ['nullable', 'string', 'max:500'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:500'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:500'],
            'tiktok_url' => ['nullable', 'url:http,https', 'max:500'],
            'youtube_url' => ['nullable', 'url:http,https', 'max:500'],
            'show_clothing' => ['nullable', 'boolean'],
            'show_tailoring' => ['nullable', 'boolean'],
            'inquiries_enabled' => ['nullable', 'boolean'],
            'commerce_settings_present' => ['nullable', 'boolean'],
            'payment_collection_mode' => ['nullable', Rule::in(['none', 'methods'])],
            'online_ordering_enabled' => ['nullable', 'boolean'],
            'unpaid_orders_enabled' => ['nullable', 'boolean'],
            'cod_enabled' => ['nullable', 'boolean'],
            'easypaisa_enabled' => ['nullable', 'boolean'],
            'jazzcash_enabled' => ['nullable', 'boolean'],
            'bank_transfer_enabled' => ['nullable', 'boolean'],
            'raast_enabled' => ['nullable', 'boolean'],
            'easypaisa_account_title' => ['nullable', 'string', 'max:150'],
            'easypaisa_account_number' => ['nullable', 'string', 'max:50'],
            'jazzcash_account_title' => ['nullable', 'string', 'max:150'],
            'jazzcash_account_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_account_title' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_iban' => ['nullable', 'string', 'max:34', 'regex:/^PK[0-9A-Z]{22}$/i'],
            'raast_account_title' => ['nullable', 'string', 'max:150'],
            'raast_id' => ['nullable', 'string', 'max:100'],
            'raast_qr' => ['nullable', 'image', 'max:2048'],
            'pickup_enabled' => ['nullable', 'boolean'],
            'delivery_enabled' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'hero_video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:51200'],
            'hero_video_poster' => ['nullable', 'image', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_cover' => ['nullable', 'boolean'],
            'remove_hero_video' => ['nullable', 'boolean'],
        ], [
            'slug.regex' => 'دکان کے لنک میں صرف انگریزی حروف، اعداد اور ڈیش استعمال کریں۔',
            'slug.unique' => 'یہ دکان لنک پہلے سے استعمال ہو رہا ہے۔',
        ]);

        $showClothing = $request->boolean('show_clothing');
        $showTailoring = $request->boolean('show_tailoring');
        if ($showClothing && ! $business->clothing_enabled) {
            throw ValidationException::withMessages(['show_clothing' => 'اس کاروبار کے لیے کپڑے کی دکان فعال نہیں ہے۔']);
        }
        if ($showTailoring && ! $business->tailoring_enabled) {
            throw ValidationException::withMessages(['show_tailoring' => 'اس کاروبار کے لیے ٹیلرنگ فعال نہیں ہے۔']);
        }
        if (! $showClothing && ! $showTailoring) {
            throw ValidationException::withMessages(['show_clothing' => 'عوامی دکان کے لیے کم از کم ایک شعبہ منتخب کریں۔']);
        }
        $commerceSettingsPresent = $request->boolean('commerce_settings_present');
        $onlineOrderingEnabled = $commerceSettingsPresent
            ? $request->boolean('online_ordering_enabled')
            : (bool) $storefront->online_ordering_enabled;
        $paymentCollectionMode = $commerceSettingsPresent
            ? ($validated['payment_collection_mode'] ?? null)
            : null;
        $noPaymentNow = $paymentCollectionMode === 'none';
        $unpaidOrdersEnabled = $commerceSettingsPresent
            ? ($paymentCollectionMode
                ? $noPaymentNow
                : $request->boolean('unpaid_orders_enabled'))
            : (bool) $storefront->unpaid_orders_enabled;
        $codEnabled = $commerceSettingsPresent
            ? (! $noPaymentNow && $request->boolean('cod_enabled'))
            : (bool) $storefront->cod_enabled;
        $easypaisaEnabled = $commerceSettingsPresent
            ? (! $noPaymentNow && $request->boolean('easypaisa_enabled'))
            : (bool) $storefront->easypaisa_enabled;
        $jazzcashEnabled = $commerceSettingsPresent
            ? (! $noPaymentNow && $request->boolean('jazzcash_enabled'))
            : (bool) $storefront->jazzcash_enabled;
        $bankTransferEnabled = $commerceSettingsPresent
            ? (! $noPaymentNow && $request->boolean('bank_transfer_enabled'))
            : (bool) $storefront->bank_transfer_enabled;
        $raastEnabled = $commerceSettingsPresent
            ? (! $noPaymentNow && $request->boolean('raast_enabled'))
            : (bool) $storefront->raast_enabled;
        if ($commerceSettingsPresent && $onlineOrderingEnabled && ! $showClothing) {
            throw ValidationException::withMessages([
                'online_ordering_enabled' => 'آن لائن آرڈر کے لیے کپڑے کی عوامی دکان فعال کریں۔',
            ]);
        }
        if ($commerceSettingsPresent && $onlineOrderingEnabled
            && ! $unpaidOrdersEnabled && ! $codEnabled && ! $easypaisaEnabled
            && ! $jazzcashEnabled && ! $bankTransferEnabled && ! $raastEnabled) {
            throw ValidationException::withMessages([
                'online_ordering_enabled' => 'آن لائن آرڈر کے لیے کم از کم ایک ادائیگی کا طریقہ منتخب کریں۔',
            ]);
        }
        if ($commerceSettingsPresent && $request->boolean('inquiries_enabled') && $showTailoring
            && ! $unpaidOrdersEnabled
            && ! ($codEnabled && $request->boolean('delivery_enabled'))
            && ! $easypaisaEnabled && ! $jazzcashEnabled
            && ! $bankTransferEnabled && ! $raastEnabled) {
            throw ValidationException::withMessages([
                'inquiries_enabled' => 'ٹیلرنگ درخواستوں کے لیے کم از کم ایک ادائیگی کا طریقہ منتخب کریں۔',
            ]);
        }
        if ($commerceSettingsPresent && $onlineOrderingEnabled
            && ! $request->boolean('pickup_enabled') && ! $request->boolean('delivery_enabled')) {
            throw ValidationException::withMessages([
                'online_ordering_enabled' => 'آن لائن آرڈر کے لیے دکان سے وصولی یا گھر تک فراہمی منتخب کریں۔',
            ]);
        }
        if ($commerceSettingsPresent && $onlineOrderingEnabled && $codEnabled
            && ! $request->boolean('delivery_enabled')) {
            throw ValidationException::withMessages([
                'cod_enabled' => 'کیش آن ڈیلیوری کے لیے گھر تک فراہمی فعال کریں۔',
            ]);
        }
        if ($commerceSettingsPresent && $jazzcashEnabled
            && (blank($validated['jazzcash_account_title'] ?? null)
                || blank($validated['jazzcash_account_number'] ?? null))) {
            throw ValidationException::withMessages([
                'jazzcash_account_number' => 'جاز کیش فعال کرنے کے لیے اکاؤنٹ کا عنوان اور نمبر درج کریں۔',
            ]);
        }
        if ($commerceSettingsPresent && $easypaisaEnabled
            && (blank($validated['easypaisa_account_title'] ?? null)
                || blank($validated['easypaisa_account_number'] ?? null))) {
            throw ValidationException::withMessages([
                'easypaisa_account_number' => 'ایزی پیسہ فعال کرنے کے لیے اکاؤنٹ کا عنوان اور نمبر درج کریں۔',
            ]);
        }
        if ($commerceSettingsPresent && $bankTransferEnabled
            && (blank($validated['bank_name'] ?? null)
                || blank($validated['bank_account_title'] ?? null)
                || (blank($validated['bank_account_number'] ?? null) && blank($validated['bank_iban'] ?? null)))) {
            throw ValidationException::withMessages([
                'bank_account_number' => 'بینک ٹرانسفر کے لیے بینک، اکاؤنٹ عنوان، اور اکاؤنٹ نمبر یا IBAN درج کریں۔',
            ]);
        }
        if ($commerceSettingsPresent && $raastEnabled
            && blank($validated['raast_id'] ?? null)
            && ! $request->hasFile('raast_qr')
            && blank($storefront->raast_qr_path)) {
            throw ValidationException::withMessages([
                'raast_id' => 'راست فعال کرنے کے لیے راست ID یا اپنے بینک/والٹ کا جاری کردہ Raast QR اپ لوڈ کریں۔',
            ]);
        }

        $paymentDetailFields = [
            'easypaisa_account_title',
            'easypaisa_account_number',
            'jazzcash_account_title',
            'jazzcash_account_number',
            'bank_name',
            'bank_account_title',
            'bank_account_number',
            'bank_iban',
            'raast_account_title',
            'raast_id',
        ];
        $selectedPaymentDetails = collect()
            ->when($easypaisaEnabled, fn ($details) => $details->merge(
                collect($validated)->only(['easypaisa_account_title', 'easypaisa_account_number'])
            ))
            ->when($jazzcashEnabled, fn ($details) => $details->merge(
                collect($validated)->only(['jazzcash_account_title', 'jazzcash_account_number'])
            ))
            ->when($bankTransferEnabled, fn ($details) => $details->merge(
                collect($validated)->only(['bank_name', 'bank_account_title', 'bank_account_number', 'bank_iban'])
            ))
            ->when($raastEnabled, fn ($details) => $details->merge(
                collect($validated)->only(['raast_account_title', 'raast_id'])
            ))
            ->all();

        $contentLocale = $validated['default_locale'];
        foreach (['display_name', 'tagline', 'description', 'address', 'city'] as $field) {
            $localized = $validated[$field.'_'.$contentLocale] ?? null;
            if (filled($localized)) {
                $validated[$field] = $localized;
            }
        }
        $designSettingsPresent = $request->boolean('design_settings_present');
        $navigationLinks = collect($validated['navigation_links'] ?? [])
            ->map(fn (array $link) => [
                'label_ur' => trim((string) ($link['label_ur'] ?? '')),
                'label_en' => trim((string) ($link['label_en'] ?? '')),
                'url' => trim((string) ($link['url'] ?? '')),
                'location' => $link['location'] ?? 'both',
                'new_tab' => filter_var($link['new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->filter(fn (array $link) => $link['url'] !== ''
                && ($link['label_ur'] !== '' || $link['label_en'] !== ''))
            ->values()->all();

        $storefront->fill([
            ...collect($validated)->except([
                'logo',
                'cover',
                'commerce_settings_present',
                'payment_collection_mode',
                'online_ordering_enabled',
                'unpaid_orders_enabled',
                'cod_enabled',
                'easypaisa_enabled',
                'jazzcash_enabled',
                'bank_transfer_enabled',
                'raast_enabled',
                'raast_qr',
                'navigation_links',
                'design_settings_present',
                'hero_video',
                'hero_video_poster',
                'remove_logo',
                'remove_cover',
                'remove_hero_video',
                'show_product_brand',
                'show_product_category',
                'show_product_stock',
                ...$paymentDetailFields,
            ])->all(),
            ...$selectedPaymentDetails,
            'business_id' => $business->id,
            'slug' => Str::lower($validated['slug']),
            'show_clothing' => $showClothing,
            'show_tailoring' => $showTailoring,
            'inquiries_enabled' => $request->boolean('inquiries_enabled'),
            'online_ordering_enabled' => $onlineOrderingEnabled,
            'unpaid_orders_enabled' => $unpaidOrdersEnabled,
            'cod_enabled' => $codEnabled,
            'easypaisa_enabled' => $easypaisaEnabled,
            'jazzcash_enabled' => $jazzcashEnabled,
            'bank_transfer_enabled' => $bankTransferEnabled,
            'raast_enabled' => $raastEnabled,
            'pickup_enabled' => $request->boolean('pickup_enabled'),
            'delivery_enabled' => $request->boolean('delivery_enabled'),
            'show_nav_categories' => $designSettingsPresent
                ? $request->boolean('show_nav_categories') : ($storefront->show_nav_categories ?? true),
            'show_nav_brands' => $designSettingsPresent
                ? $request->boolean('show_nav_brands') : ($storefront->show_nav_brands ?? true),
            'show_featured_products' => $designSettingsPresent
                ? $request->boolean('show_featured_products') : ($storefront->show_featured_products ?? true),
            'show_benefits' => $designSettingsPresent
                ? $request->boolean('show_benefits') : ($storefront->show_benefits ?? true),
            'show_services' => $designSettingsPresent
                ? $request->boolean('show_services') : ($storefront->show_services ?? true),
            'show_about' => $designSettingsPresent
                ? $request->boolean('show_about') : ($storefront->show_about ?? true),
            'sticky_header' => $designSettingsPresent
                ? $request->boolean('sticky_header') : ($storefront->sticky_header ?? true),
            'show_product_brand' => $designSettingsPresent
                ? $request->boolean('show_product_brand') : ($storefront->show_product_brand ?? true),
            'show_product_category' => $designSettingsPresent
                ? $request->boolean('show_product_category') : ($storefront->show_product_category ?? true),
            'show_product_stock' => $designSettingsPresent
                ? $request->boolean('show_product_stock') : ($storefront->show_product_stock ?? true),
            'navigation_links' => $designSettingsPresent
                ? $navigationLinks : ($storefront->navigation_links ?? []),
        ]);

        if ($request->hasFile('logo')) {
            $storefront->logo_path = $this->storeImage($request->file('logo'));
        } elseif ($request->boolean('remove_logo')) {
            $storefront->logo_path = null;
        }
        if ($request->hasFile('cover')) {
            $storefront->cover_path = $this->storeImage($request->file('cover'));
        } elseif ($request->boolean('remove_cover')) {
            $storefront->cover_path = null;
        }
        if ($request->hasFile('hero_video')) {
            $storefront->hero_video_path = $this->storeImage($request->file('hero_video'));
        } elseif ($request->boolean('remove_hero_video')) {
            $storefront->hero_video_path = null;
        }
        if ($request->hasFile('hero_video_poster')) {
            $storefront->hero_video_poster_path = $this->storeImage($request->file('hero_video_poster'));
        }
        if ($raastEnabled && $request->hasFile('raast_qr')) {
            $storefront->raast_qr_path = $this->storeImage($request->file('raast_qr'));
        }
        $storefront->save();

        return redirect()->route('admin.storefront.edit')->with('success', 'آن لائن دکان کی معلومات محفوظ ہو گئی ہیں۔');
    }

    public function publish(Request $request)
    {
        [$business, $storefront] = $this->storefrontForCurrentBusiness();
        abort_unless($storefront->exists, 422, 'پہلے آن لائن دکان کی معلومات محفوظ کریں۔');

        $published = $request->boolean('published');
        if ($published) {
            if (! $business->isActive()) {
                throw ValidationException::withMessages(['published' => 'صرف فعال کاروبار اپنی آن لائن دکان شائع کر سکتا ہے۔']);
            }
            if (! $storefront->show_clothing && ! $storefront->show_tailoring) {
                throw ValidationException::withMessages(['published' => 'شائع کرنے سے پہلے کم از کم ایک شعبہ منتخب کریں۔']);
            }
        }

        $storefront->update([
            'is_published' => $published,
            'published_at' => $published ? ($storefront->published_at ?? now()) : null,
        ]);

        return redirect()->route('admin.storefront.edit')->with(
            'success',
            $published ? 'آن لائن دکان عوام کے لیے شائع ہو گئی ہے۔' : 'آن لائن دکان عارضی طور پر چھپا دی گئی ہے۔'
        );
    }

    public function preview()
    {
        [, $storefront] = $this->storefrontForCurrentBusiness();
        abort_unless($storefront->exists, 404);
        App::setLocale($storefront->default_locale ?: 'ur');

        $storefront->load([
            'business',
            'heroSlides' => fn ($query) => $query->where('is_active', true)->with(['primaryCollection', 'secondaryCollection']),
            'clothingListings' => fn ($query) => $query
                ->where('is_published', true)
                ->whereHas('cloth', fn ($cloth) => $cloth->where('user_id', $storefront->business->owner_user_id))
                ->with(['cloth.brand', 'cloth.type', 'cloth.images', 'cloth.colors'])
                ->orderByDesc('is_featured')->orderBy('sort_order')->latest('id')->limit(8),
            'tailoringServices' => fn ($query) => $query
                ->where('is_published', true)->where('is_available', true)
                ->orderByDesc('is_featured')->orderBy('sort_order')->latest('id')->limit(4),
        ]);
        $categoryRows = $storefront->clothingListings()
            ->where('is_published', true)
            ->join('cloths', 'cloths.id', '=', 'storefront_clothing_listings.cloth_id')
            ->where('cloths.user_id', $storefront->business->owner_user_id)
            ->whereNull('cloths.deleted_at')
            ->get(['cloths.cloth_type_id', 'cloths.cloth_brand_id']);
        $clothTypes = ClothType::query()->where('user_id', $storefront->business->owner_user_id)
            ->whereIn('id', $categoryRows->pluck('cloth_type_id')->filter()->unique())->orderBy('name')->get();
        $clothBrands = ClothBrand::query()->where('user_id', $storefront->business->owner_user_id)
            ->whereIn('id', $categoryRows->pluck('cloth_brand_id')->filter()->unique())->orderBy('name')->get();

        return view('storefront.public.show', [
            'storefront' => $storefront,
            'preview' => true,
            'clothTypes' => $clothTypes,
            'clothBrands' => $clothBrands,
        ]);
    }

    private function storefrontForCurrentBusiness(): array
    {
        $user = Auth::user();
        $business = $user->business;
        abort_unless($business, 404);
        $setting = Setting::where('user_id', $user->businessOwnerId())->latest('id')->first();
        $storefront = $business->storefront()->first() ?: new Storefront([
            'business_id' => $business->id,
            'slug' => 'shop-'.str_pad((string) $business->id, 6, '0', STR_PAD_LEFT),
            'display_name' => $setting?->name ?: $business->name,
            'tagline' => $setting?->note,
            'public_phone' => $setting?->contact_no,
            'address' => $setting?->address,
            'show_clothing' => $business->clothing_enabled,
            'show_tailoring' => $business->tailoring_enabled,
            'inquiries_enabled' => true,
            'online_ordering_enabled' => true,
            'unpaid_orders_enabled' => true,
            'cod_enabled' => false,
            'easypaisa_enabled' => false,
            'jazzcash_enabled' => false,
            'bank_transfer_enabled' => false,
            'raast_enabled' => false,
            'pickup_enabled' => true,
            'delivery_enabled' => false,
            'default_locale' => 'ur',
            'theme_primary_color' => '#126b4f',
            'theme_accent_color' => '#d98a12',
            'theme_background_color' => '#f5f7f6',
            'theme_surface_color' => '#ffffff',
            'theme_text_color' => '#17372e',
            'font_style' => 'modern',
            'hero_layout' => 'overlay',
            'hero_alignment' => 'start',
            'hero_overlay_strength' => 70,
            'hero_height' => 'standard',
            'hero_media_type' => 'image',
            'hero_media_fit' => 'cover',
            'hero_media_position' => 'center',
            'hero_text_animation' => 'fade_up',
            'corner_style' => 'soft',
            'product_columns' => 3,
            'product_columns_tablet' => 2,
            'product_columns_mobile' => 1,
            'product_image_ratio' => 'portrait',
            'show_product_brand' => true,
            'show_product_category' => true,
            'show_product_stock' => true,
            'header_layout' => 'menu_first',
            'sticky_header' => true,
            'show_nav_categories' => true,
            'show_nav_brands' => true,
            'show_featured_products' => true,
            'show_benefits' => true,
            'show_services' => true,
            'show_about' => true,
            'footer_style' => 'columns',
        ]);

        return [$business, $storefront];
    }

    private function storeImage($image): string
    {
        $directory = public_path('images/storefronts');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $name = $image->hashName();
        $image->move($directory, $name);

        return 'images/storefronts/'.$name;
    }
}
