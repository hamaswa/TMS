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
        abort_unless($storefront->online_ordering_enabled, 404);
        $methods = array_values(array_filter([
            $storefront->pickup_enabled ? 'pickup' : null,
            $storefront->delivery_enabled ? 'delivery' : null,
        ]));
        $request->mergeIfMissing(['payment_method' => StorefrontOrder::PAYMENT_UNPAID]);
        $validated = $request->validate([
            'fulfillment_method' => ['required', Rule::in($methods)],
            'delivery_address' => [
                Rule::requiredIf($request->input('fulfillment_method') === 'delivery'),
                'nullable',
                'string',
                'max:1000',
            ],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(array_keys($storefront->acceptedPaymentMethods()))],
            'payment_sender_phone' => [
                Rule::requiredIf($request->input('payment_method') === StorefrontOrder::PAYMENT_EASYPAISA),
                'nullable',
                'string',
                'min:7',
                'max:50',
            ],
            'payment_reference' => [
                Rule::requiredIf($request->input('payment_method') === StorefrontOrder::PAYMENT_EASYPAISA),
                'nullable',
                'string',
                'max:100',
            ],
        ]);
        if ($validated['payment_method'] === StorefrontOrder::PAYMENT_COD
            && $validated['fulfillment_method'] !== 'delivery') {
            throw ValidationException::withMessages([
                'payment_method' => __('storefront.messages.cod_requires_delivery'),
            ]);
        }
        $cart = $cartService->find($storefront, $request->session()->get($this->cartSessionKey($storefront)));
        if (! $cart) {
            throw ValidationException::withMessages(['checkout' => __('storefront.messages.cart_unavailable')]);
        }

        [$order] = $checkout->checkout(
            $cart,
            $validated['fulfillment_method'],
            $validated['delivery_address'] ?? null,
            $validated['customer_note'] ?? null,
            $validated['payment_method'],
            $validated['payment_sender_phone'] ?? null,
            $validated['payment_reference'] ?? null,
        );
        $request->session()->forget($this->cartSessionKey($storefront));
        $request->session()->put($this->orderSessionKey($order), true);
        try {
            Notification::send($storefront->business->owner, new NewStorefrontOrderNotification($order));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('storefront.orders.show', [$storefront, $order->reference])
            ->with('success', __('storefront.messages.order_saved'));
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
        $customer = Customers::findByPhoneForOwner(
            $storefront->business->owner_user_id,
            $validated['phone']
        );
        if (! $customer || ! $customer->mobile_pin || ! Hash::check($validated['pin'], $customer->mobile_pin)
            || (int) $customer->id !== (int) $order->customer_id) {
            throw ValidationException::withMessages(['phone' => __('storefront.messages.identity_invalid')]);
        }
        $request->session()->put($this->orderSessionKey($order), true);

        return redirect()->route('storefront.orders.show', [$storefront, $order->reference]);
    }

    private function ensureVisible(Storefront $storefront): void
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

    private function cartSessionKey(Storefront $storefront): string
    {
        return 'storefront_cart_token_'.$storefront->id;
    }

    private function orderSessionKey(StorefrontOrder $order): string
    {
        return 'storefront_order_access_'.$order->id;
    }
}
