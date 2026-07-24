<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Models\Storefront;
use App\Models\StorefrontCart;
use App\Models\StorefrontClothingListing;
use App\Services\StorefrontCartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PublicStorefrontCartController extends Controller
{
    public function show(Request $request, Storefront $storefront, StorefrontCartService $cartService)
    {
        $this->ensureVisible($storefront);
        $cart = $cartService->find($storefront, $request->session()->get($this->sessionKey($storefront)));
        $cart?->load(['customer', 'items.listing.cloth.brand', 'items.listing.cloth.type', 'items.color']);

        return view('storefront.public.cart', compact('storefront', 'cart'));
    }

    public function store(
        Request $request,
        Storefront $storefront,
        StorefrontClothingListing $listing,
        StorefrontCartService $cartService
    ) {
        $this->ensureVisible($storefront);
        abort_unless(
            $listing->storefront_id === $storefront->id
            && $listing->is_published
            && (int) $listing->cloth?->user_id === (int) $storefront->business->owner_user_id,
            404
        );
        $validated = $request->validate([
            'cloth_color_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0.25', 'max:1000'],
        ]);
        $color = $listing->cloth->colors()->findOrFail($validated['cloth_color_id']);
        [$cart, $plainToken] = $cartService->getOrCreate(
            $storefront,
            $request->session()->get($this->sessionKey($storefront))
        );
        $cartService->reserve($cart, $listing, $color, round((float) $validated['quantity'], 2));
        $request->session()->put($this->sessionKey($storefront), $plainToken);

        return redirect()->route('storefront.cart.show', $storefront)
            ->with('success', __('storefront.messages.cart_reserved'));
    }

    public function update(
        Request $request,
        Storefront $storefront,
        int $item,
        StorefrontCartService $cartService
    ) {
        $this->ensureVisible($storefront);
        $cart = $this->cartOrFail($request, $storefront, $cartService);
        $cartItem = $cart->items()->with(['listing.cloth', 'color'])->findOrFail($item);
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.25', 'max:1000'],
        ]);
        $cartService->reserve(
            $cart,
            $cartItem->listing,
            $cartItem->color,
            round((float) $validated['quantity'], 2)
        );

        return redirect()->route('storefront.cart.show', $storefront)
            ->with('success', __('storefront.messages.cart_updated'));
    }

    public function destroy(
        Request $request,
        Storefront $storefront,
        int $item,
        StorefrontCartService $cartService
    ) {
        $this->ensureVisible($storefront);
        $cart = $this->cartOrFail($request, $storefront, $cartService);
        abort_unless($cart->items()->whereKey($item)->exists(), 404);
        $cartService->remove($cart, $item);

        return redirect()->route('storefront.cart.show', $storefront)
            ->with('success', __('storefront.messages.cart_removed'));
    }

    public function linkCustomer(
        Request $request,
        Storefront $storefront,
        StorefrontCartService $cartService
    ) {
        $this->ensureVisible($storefront);
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'digits:6'],
        ]);
        $cart = $this->cartOrFail($request, $storefront, $cartService);
        $result = DB::transaction(function () use ($validated, $storefront, $cart) {
            $customer = Customers::query()
                ->where('user_id', $storefront->business->owner_user_id)
                ->where('phone_number1', $validated['phone'])
                ->lockForUpdate()
                ->first();
            if ($customer?->pin_locked_until?->isFuture()) {
                return 'locked';
            }
            if (! $customer || ! $customer->mobile_pin || ! Hash::check($validated['pin'], $customer->mobile_pin)) {
                if ($customer) {
                    $attempts = min(255, (int) $customer->pin_failed_attempts + 1);
                    $customer->forceFill([
                        'pin_failed_attempts' => $attempts,
                        'pin_locked_until' => $attempts >= 5 ? now()->addMinutes(15) : null,
                    ])->save();
                }

                return 'invalid';
            }
            $customer->forceFill(['pin_failed_attempts' => 0, 'pin_locked_until' => null])->save();
            $cart->update(['customer_id' => $customer->id, 'last_activity_at' => now()]);

            return 'linked';
        });
        if ($result !== 'linked') {
            throw ValidationException::withMessages([
                'phone' => $result === 'locked'
                    ? __('storefront.messages.identity_locked')
                    : __('storefront.messages.identity_invalid'),
            ]);
        }

        return redirect()->route('storefront.cart.show', $storefront)
            ->with('success', __('storefront.messages.identity_linked'));
    }

    public function unlinkCustomer(
        Request $request,
        Storefront $storefront,
        StorefrontCartService $cartService
    ) {
        $this->ensureVisible($storefront);
        $cart = $this->cartOrFail($request, $storefront, $cartService);
        $cart->update(['customer_id' => null, 'last_activity_at' => now()]);

        return redirect()->route('storefront.cart.show', $storefront);
    }

    private function cartOrFail(Request $request, Storefront $storefront, StorefrontCartService $cartService): StorefrontCart
    {
        $cart = $cartService->find($storefront, $request->session()->get($this->sessionKey($storefront)));
        abort_unless($cart, 404);

        return $cart;
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

    private function sessionKey(Storefront $storefront): string
    {
        return 'storefront_cart_token_'.$storefront->id;
    }
}
