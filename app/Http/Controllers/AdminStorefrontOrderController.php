<?php

namespace App\Http\Controllers;

use App\Models\StorefrontOrder;
use App\Services\StorefrontCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminStorefrontOrderController extends Controller
{
    public function index(Request $request)
    {
        $storefront = Auth::user()->business?->storefront;
        abort_unless($storefront, 404);
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'complete', 'cancelled'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $orders = $storefront->orders()
            ->with(['customer:id,name,phone_number1', 'items:id,storefront_order_id,item_name,color,quantity,line_total'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(function ($nested) use ($search) {
                $nested->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number1', 'like', "%{$search}%"));
            }))
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        return view('storefront.admin.orders', compact('storefront', 'orders'));
    }

    public function update(
        Request $request,
        StorefrontOrder $order,
        StorefrontCheckoutService $checkout
    ) {
        $storefront = Auth::user()->business?->storefront;
        abort_unless($storefront && $order->storefront_id === $storefront->id, 404);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['complete', 'cancelled'])],
        ]);
        $checkout->updateStatus($order, $validated['status']);

        return redirect()->route('admin.storefront.orders.index')
            ->with('success', $validated['status'] === 'complete'
                ? 'آن لائن آرڈر مکمل کر دیا گیا ہے۔'
                : 'آن لائن آرڈر منسوخ کر کے اسٹاک اور گاہک کا بقایا درست کر دیا گیا ہے۔');
    }
}
