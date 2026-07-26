<?php

namespace App\Http\Controllers;

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

        return view('storefront.admin.edit', compact('business', 'storefront'));
    }

    public function update(Request $request)
    {
        [$business, $storefront] = $this->storefrontForCurrentBusiness();
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('storefronts', 'slug')->ignore($storefront->id),
            ],
            'tagline' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'public_phone' => ['nullable', 'string', 'max:50'],
            'public_email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'default_locale' => ['required', Rule::in(['ur', 'en'])],
            'show_clothing' => ['nullable', 'boolean'],
            'show_tailoring' => ['nullable', 'boolean'],
            'inquiries_enabled' => ['nullable', 'boolean'],
            'commerce_settings_present' => ['nullable', 'boolean'],
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
        $unpaidOrdersEnabled = $commerceSettingsPresent
            ? $request->boolean('unpaid_orders_enabled')
            : (bool) $storefront->unpaid_orders_enabled;
        $codEnabled = $commerceSettingsPresent
            ? $request->boolean('cod_enabled')
            : (bool) $storefront->cod_enabled;
        $easypaisaEnabled = $commerceSettingsPresent
            ? $request->boolean('easypaisa_enabled')
            : (bool) $storefront->easypaisa_enabled;
        $jazzcashEnabled = $commerceSettingsPresent
            ? $request->boolean('jazzcash_enabled')
            : (bool) $storefront->jazzcash_enabled;
        $bankTransferEnabled = $commerceSettingsPresent
            ? $request->boolean('bank_transfer_enabled')
            : (bool) $storefront->bank_transfer_enabled;
        $raastEnabled = $commerceSettingsPresent
            ? $request->boolean('raast_enabled')
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

        $storefront->fill([
            ...collect($validated)->except([
                'logo',
                'cover',
                'commerce_settings_present',
                'online_ordering_enabled',
                'unpaid_orders_enabled',
                'cod_enabled',
                'easypaisa_enabled',
                'jazzcash_enabled',
                'bank_transfer_enabled',
                'raast_enabled',
                'raast_qr',
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
        ]);

        if ($request->hasFile('logo')) {
            $storefront->logo_path = $this->storeImage($request->file('logo'));
        }
        if ($request->hasFile('cover')) {
            $storefront->cover_path = $this->storeImage($request->file('cover'));
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

        return view('storefront.public.show', [
            'storefront' => $storefront->load('business'),
            'preview' => true,
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
