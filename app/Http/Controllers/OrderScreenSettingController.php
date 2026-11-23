<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Lets the shop choose how the order page offers stock: a dropdown row, or a box per item with a cart. */
class OrderScreenSettingController extends Controller
{
    public function edit(): View
    {
        return view('order-screen.edit', ['business' => $this->business()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'counter_order_layout' => ['required', Rule::in(Business::ORDER_LAYOUTS)],
        ]);

        $this->business()->update($validated);

        return back()->with('success', 'آرڈر اسکرین کی ترتیب محفوظ کر دی گئی ہے۔');
    }

    private function business(): Business
    {
        $user = Auth::user()->loadMissing(['business', 'ownedBusiness']);

        return $user->business ?? $user->ownedBusiness ?? abort(404);
    }
}
