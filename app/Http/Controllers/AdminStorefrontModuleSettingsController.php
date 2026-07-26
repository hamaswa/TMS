<?php

namespace App\Http\Controllers;

use App\Models\Storefront;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminStorefrontModuleSettingsController extends Controller
{
    private const MODULES = ['tailoring', 'clothing'];

    public function edit(string $module)
    {
        [$storefront, $module] = $this->storefront($module);

        return view('storefront.admin.module-settings', compact('storefront', 'module'));
    }

    public function update(Request $request, string $module)
    {
        [$storefront, $module] = $this->storefront($module);
        $validated = $request->validate([
            'accepting_enabled' => ['nullable', 'boolean'],
            'payment_collection_mode' => ['required', Rule::in(['none', 'methods'])],
            'cod_enabled' => ['nullable', 'boolean'],
            'easypaisa_enabled' => ['nullable', 'boolean'],
            'jazzcash_enabled' => ['nullable', 'boolean'],
            'bank_transfer_enabled' => ['nullable', 'boolean'],
            'raast_enabled' => ['nullable', 'boolean'],
            'pickup_enabled' => ['nullable', 'boolean'],
            'delivery_enabled' => ['nullable', 'boolean'],
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
        ]);

        $accepting = $request->boolean('accepting_enabled');
        $noPayment = $validated['payment_collection_mode'] === 'none';
        $methods = [
            'unpaid' => $noPayment,
            'cod' => ! $noPayment && $request->boolean('cod_enabled'),
            'easypaisa' => ! $noPayment && $request->boolean('easypaisa_enabled'),
            'jazzcash' => ! $noPayment && $request->boolean('jazzcash_enabled'),
            'bank_transfer' => ! $noPayment && $request->boolean('bank_transfer_enabled'),
            'raast' => ! $noPayment && $request->boolean('raast_enabled'),
        ];
        $pickup = $request->boolean('pickup_enabled');
        $delivery = $request->boolean('delivery_enabled');

        if ($accepting && ! $pickup && ! $delivery) {
            throw ValidationException::withMessages([
                'accepting_enabled' => 'نئی درخواستیں یا آرڈر قبول کرنے کے لیے دکان سے وصولی یا گھر تک فراہمی منتخب کریں۔',
            ]);
        }
        if ($accepting && ! in_array(true, $methods, true)) {
            throw ValidationException::withMessages([
                'payment_collection_mode' => 'کم از کم ایک ادائیگی کا طریقہ منتخب کریں۔',
            ]);
        }
        if ($methods['cod'] && ! $delivery) {
            throw ValidationException::withMessages([
                'cod_enabled' => 'کیش آن ڈیلیوری کے لیے گھر تک فراہمی فعال کریں۔',
            ]);
        }

        $this->validateReceivingDetails($request, $storefront, $methods, $validated);

        $prefix = $module === 'tailoring' ? 'tailoring' : 'clothing';
        $storefront->fill([
            $module === 'tailoring' ? 'tailoring_inquiries_enabled' : 'clothing_online_ordering_enabled' => $accepting,
            "{$prefix}_unpaid_enabled" => $methods['unpaid'],
            "{$prefix}_cod_enabled" => $methods['cod'],
            "{$prefix}_easypaisa_enabled" => $methods['easypaisa'],
            "{$prefix}_jazzcash_enabled" => $methods['jazzcash'],
            "{$prefix}_bank_transfer_enabled" => $methods['bank_transfer'],
            "{$prefix}_raast_enabled" => $methods['raast'],
            "{$prefix}_pickup_enabled" => $pickup,
            "{$prefix}_delivery_enabled" => $delivery,
            ...$this->selectedReceivingDetails($methods, $validated),
        ]);
        if ($methods['raast'] && $request->hasFile('raast_qr')) {
            $storefront->raast_qr_path = $this->storeImage($request->file('raast_qr'));
        }
        $storefront->save();

        return redirect()->route('admin.storefront.module-settings.edit', $module)
            ->with('success', $module === 'tailoring'
                ? 'ٹیلرنگ کی ترتیب محفوظ ہو گئی۔'
                : 'کپڑوں کی دکان کی ترتیب محفوظ ہو گئی۔');
    }

    private function storefront(string $module): array
    {
        abort_unless(in_array($module, self::MODULES, true), 404);
        $business = Auth::user()->business;
        abort_unless($business && $business->hasModule($module), 404);
        $storefront = $business->storefront;
        abort_unless($storefront, 404);

        return [$storefront, $module];
    }

    private function validateReceivingDetails(
        Request $request,
        Storefront $storefront,
        array $methods,
        array $validated
    ): void
    {
        if ($methods['easypaisa'] && (blank($validated['easypaisa_account_title'] ?? null)
            || blank($validated['easypaisa_account_number'] ?? null))) {
            throw ValidationException::withMessages([
                'easypaisa_account_number' => 'ایزی پیسہ کے لیے اکاؤنٹ کا عنوان اور نمبر درج کریں۔',
            ]);
        }
        if ($methods['jazzcash'] && (blank($validated['jazzcash_account_title'] ?? null)
            || blank($validated['jazzcash_account_number'] ?? null))) {
            throw ValidationException::withMessages([
                'jazzcash_account_number' => 'جاز کیش کے لیے اکاؤنٹ کا عنوان اور نمبر درج کریں۔',
            ]);
        }
        if ($methods['bank_transfer'] && (blank($validated['bank_name'] ?? null)
            || blank($validated['bank_account_title'] ?? null)
            || (blank($validated['bank_account_number'] ?? null) && blank($validated['bank_iban'] ?? null)))) {
            throw ValidationException::withMessages([
                'bank_account_number' => 'بینک، اکاؤنٹ عنوان، اور اکاؤنٹ نمبر یا IBAN درج کریں۔',
            ]);
        }
        if ($methods['raast'] && blank($validated['raast_id'] ?? null)
            && ! $request->hasFile('raast_qr') && blank($storefront->raast_qr_path)) {
            throw ValidationException::withMessages([
                'raast_id' => 'راست فعال کرنے کے لیے راست ID یا اپنے بینک/والٹ کا جاری کردہ QR اپ لوڈ کریں۔',
            ]);
        }
    }

    private function selectedReceivingDetails(array $methods, array $validated): array
    {
        return collect()
            ->when($methods['easypaisa'], fn ($details) => $details->merge(
                collect($validated)->only(['easypaisa_account_title', 'easypaisa_account_number'])
            ))
            ->when($methods['jazzcash'], fn ($details) => $details->merge(
                collect($validated)->only(['jazzcash_account_title', 'jazzcash_account_number'])
            ))
            ->when($methods['bank_transfer'], fn ($details) => $details->merge(
                collect($validated)->only(['bank_name', 'bank_account_title', 'bank_account_number', 'bank_iban'])
            ))
            ->when($methods['raast'], fn ($details) => $details->merge(
                collect($validated)->only(['raast_account_title', 'raast_id'])
            ))
            ->all();
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
