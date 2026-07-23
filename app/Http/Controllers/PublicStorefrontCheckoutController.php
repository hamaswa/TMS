<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Models\Storefront;
use App\Models\StorefrontOrder;
use App\Notifications\NewStorefrontOrderNotification;
use App\Services\StorefrontCartService;
use App\Services\StorefrontCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicStorefrontCheckoutController extends Controller
{
    public function store(
        Request $request,
        Storefront $storefront,
        StorefrontCartService $cartService,
        StorefrontCheckoutService $checkout
    ) {
        $this->ensureVisible($storefront);
        $methods = array_values(array_filter([
            $storefront->pickup_enabled ? 'pickup' : null,
            $storefront->delivery_enabled ? 'delivery' : null,
        ]));
        $validated = $request->validate([
            'fulfillment_method' => ['required', Rule::in($methods)],
            'delivery_address' => [
                Rule::requiredIf($request->input('fulfillment_method') === 'delivery'),
                'nullable',
                'string',
                'max:1000',
            ],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $cart = $cartService->find($storefront, $request->session()->get($this->cartSessionKey($storefront)));
        if (! $cart) {
            throw ValidationException::withMessages(['checkout' => 'ٹوکری دستیاب نہیں یا اس کا وقت ختم ہو گیا ہے۔']);
        }

        [$order] = $checkout->checkout(
            $cart,
            $validated['fulfillment_method'],
            $validated['delivery_address'] ?? null,
            $validated['customer_note'] ?? null
        );
        $request->session()->forget($this->cartSessionKey($storefront));
        $request->session()->put($this->orderSessionKey($order), true);
        try {
            Notification::send($storefront->business->owner, new NewStorefrontOrderNotification($order));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('storefront.orders.show', [$storefront, $order->reference])
            ->with('success', 'آپ کا آرڈر محفوظ ہو گیا ہے۔ حوالہ نمبر سنبھال کر رکھیں۔');
    }

    public function show(Request $request, Storefront $storefront, string $reference)
    {
        $this->ensureVisible($storefront);
        $order = $storefront->orders()
            ->where('reference', $reference)
            ->with(['customer', 'items'])
            ->firstOrFail();
        $authorized = $request->session()->get($this->orderSessionKey($order), false);

        return view('storefront.public.order', compact('storefront', 'order', 'authorized'));
    }

    public function authenticate(Request $request, Storefront $storefront, string $reference)
    {
        $this->ensureVisible($storefront);
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'digits:6'],
        ]);
        $order = $storefront->orders()->where('reference', $reference)->firstOrFail();
        $customer = Customers::query()
            ->whereKey($order->customer_id)
            ->where('user_id', $storefront->business->owner_user_id)
            ->first();
        if (! $customer || ! $customer->mobile_pin || ! Hash::check($validated['pin'], $customer->mobile_pin)
            || $customer->phone_number1 !== $validated['phone']) {
            throw ValidationException::withMessages(['phone' => 'فون نمبر یا پن درست نہیں ہے۔']);
        }
        $request->session()->put($this->orderSessionKey($order), true);

        return redirect()->route('storefront.orders.show', [$storefront, $order->reference]);
    }

    private function ensureVisible(Storefront $storefront): void
    {
        abort_unless(
            $storefront->is_published
            && $storefront->show_clothing
            && $storefront->business?->isActive()
            && $storefront->business->clothing_enabled,
            404
        );
    }

    private function cartSessionKey(Storefront $storefront): string
    {
        return 'storefront_cart_token_'.$storefront->id;
    }

    private function orderSessionKey(StorefrontOrder $order): string
    {
        return 'storefront_order_access_'.$order->id;
    }
}
