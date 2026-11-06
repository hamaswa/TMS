<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Super admin: the Easypaisa account shops pay their subscription into. */
class PlatformPaymentSettingsController extends Controller
{
    private const DISK = 'local';

    public function edit()
    {
        return view('Administrator.payment-settings', [
            'accountName' => PlatformSetting::get(PlatformSetting::EASYPAISA_ACCOUNT_NAME),
            'number' => PlatformSetting::get(PlatformSetting::EASYPAISA_NUMBER),
            'hasQr' => filled(PlatformSetting::get(PlatformSetting::EASYPAISA_QR_PATH)),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'account_name' => ['required', 'string', 'max:100'],
            'number' => ['required', 'string', 'regex:/^03\d{2}-?\d{7}$/'],
            'qr' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_qr' => ['nullable', 'boolean'],
        ], [
            'number.regex' => 'Enter a Pakistani mobile number like 0300-1234567.',
        ]);

        PlatformSetting::put(PlatformSetting::EASYPAISA_ACCOUNT_NAME, trim($validated['account_name']));
        PlatformSetting::put(PlatformSetting::EASYPAISA_NUMBER, $this->formatNumber($validated['number']));

        $oldQr = PlatformSetting::get(PlatformSetting::EASYPAISA_QR_PATH);
        if ($request->hasFile('qr')) {
            PlatformSetting::put(PlatformSetting::EASYPAISA_QR_PATH, $request->file('qr')->store('platform/easypaisa', self::DISK));
            $this->deleteFile($oldQr);
        } elseif ($request->boolean('remove_qr')) {
            PlatformSetting::put(PlatformSetting::EASYPAISA_QR_PATH, null);
            $this->deleteFile($oldQr);
        }

        return back()->with('success', 'Easypaisa details saved. Shops now see them when they choose a plan.');
    }

    /** Served to signed-in users only (shop owners paying, and the super admin). */
    public function qr()
    {
        $path = PlatformSetting::get(PlatformSetting::EASYPAISA_QR_PATH);
        abort_unless($path && Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->response($path, 'easypaisa-qr', [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function formatNumber(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number);

        return substr($digits, 0, 4).'-'.substr($digits, 4);
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
