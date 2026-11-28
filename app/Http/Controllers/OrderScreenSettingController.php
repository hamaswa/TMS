<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Lets the shop choose how the order page offers stock: a dropdown row, or a box per item with a cart. */
class OrderScreenSettingController extends Controller
{
    /** The layout choice now lives on the settings page; old links land on its section there. */
    public function edit(): RedirectResponse
    {
        return redirect()->to(route('admin.setting.index').'#order-screen');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'counter_order_layout' => ['required', Rule::in(Business::ORDER_LAYOUTS)],
        ]);

        $this->business()->update($validated);

        return redirect()->to(route('admin.setting.index').'#order-screen')
            ->with('success', 'آرڈر اسکرین کی ترتیب محفوظ کر دی گئی ہے۔');
    }

    /** Order number style for new orders: ORD-000108 (standard) or plain 108 with suits 108-1 (simple). */
    public function updateSerialStyle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_serial_style' => ['required', Rule::in(Business::SERIAL_STYLES)],
        ]);

        $this->business()->update($validated);

        return redirect()->to(route('admin.setting.index').'#order-serial')
            ->with('success', 'آرڈر نمبر کی ترتیب محفوظ کر دی گئی ہے۔');
    }

    private function business(): Business
    {
        $user = Auth::user()->loadMissing(['business', 'ownedBusiness']);

        return $user->business ?? $user->ownedBusiness ?? abort(404);
    }
}
