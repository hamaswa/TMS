<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Storefront;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
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

        $storefront->fill([
            ...collect($validated)->except(['logo', 'cover'])->all(),
            'business_id' => $business->id,
            'slug' => Str::lower($validated['slug']),
            'show_clothing' => $showClothing,
            'show_tailoring' => $showTailoring,
            'inquiries_enabled' => $request->boolean('inquiries_enabled'),
            'pickup_enabled' => $request->boolean('pickup_enabled'),
            'delivery_enabled' => $request->boolean('delivery_enabled'),
        ]);

        if ($request->hasFile('logo')) {
            $storefront->logo_path = $this->storeImage($request->file('logo'));
        }
        if ($request->hasFile('cover')) {
            $storefront->cover_path = $this->storeImage($request->file('cover'));
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
