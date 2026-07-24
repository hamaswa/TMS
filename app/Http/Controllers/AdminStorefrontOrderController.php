<?php

namespace App\Http\Controllers;

use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderRefund;
use App\Models\StorefrontOrderReturn;
use App\Services\StorefrontCheckoutService;
use App\Services\StorefrontReturnService;
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
            ->with([
                'customer:id,name,phone_number1',
                'items:id,storefront_order_id,cloth_id,cloth_color_id,item_name,color,quantity,unit_price,line_total',
                'items.cloth:id',
                'items.cloth.colors:id,cloth_id,color,length',
                'items.returnItems:id,storefront_order_item_id,quantity',
                'paymentVerifier:id,name,username',
                'refunds:id,storefront_order_id,reference,amount,method,external_reference,refunded_at',
                'returns:id,storefront_order_id,reference,type,refund_amount,refund_method,external_reference,processed_at',
                'returns.items:id,storefront_order_return_id,storefront_order_item_id,quantity,line_total,restocked,replacement_cloth_color_id,replacement_quantity',
                'returns.items.orderItem:id,item_name,color',
                'returns.items.replacementColor:id,color',
            ])
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
            'refund_method' => [
                Rule::requiredIf(
                    $request->input('status') === StorefrontOrder::STATUS_CANCELLED
                    && (float) $order->paid_amount > 0
                ),
                'nullable',
                Rule::in(array_keys(StorefrontOrderRefund::methods())),
            ],
            'refund_reference' => [
                Rule::requiredIf(
                    $request->input('status') === StorefrontOrder::STATUS_CANCELLED
                    && (float) $order->paid_amount > 0
                    && $request->input('refund_method') !== StorefrontOrderRefund::METHOD_CASH
                ),
                'nullable',
                'string',
                'max:100',
            ],
            'refund_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $isPaidCancellation = $validated['status'] === StorefrontOrder::STATUS_CANCELLED
            && (float) $order->paid_amount > 0;
        if ($isPaidCancellation) {
            $checkout->refundAndCancel(
                $order,
                $validated['refund_method'],
                $validated['refund_reference'] ?? null,
                $validated['refund_notes'] ?? null,
                (int) Auth::id(),
            );
        } else {
            $checkout->updateStatus($order, $validated['status']);
        }

        return redirect()->route('admin.storefront.orders.index')
            ->with('success', $validated['status'] === 'complete'
                ? 'آن لائن آرڈر مکمل کر دیا گیا ہے۔'
                : ($isPaidCancellation
                    ? 'گاہک کی مکمل رقم واپس درج کر کے آرڈر منسوخ اور اسٹاک بحال کر دیا گیا ہے۔'
                    : 'آن لائن آرڈر منسوخ کر کے اسٹاک اور گاہک کا بقایا درست کر دیا گیا ہے۔'));
    }

    public function verifyPayment(
        Request $request,
        StorefrontOrder $order,
        StorefrontCheckoutService $checkout
    ) {
        $storefront = Auth::user()->business?->storefront;
        abort_unless($storefront && $order->storefront_id === $storefront->id, 404);
        $validated = $request->validate([
            'decision' => ['required', Rule::in([
                StorefrontOrder::VERIFICATION_VERIFIED,
                StorefrontOrder::VERIFICATION_REJECTED,
            ])],
            'payment_verification_notes' => [
                Rule::requiredIf($request->input('decision') === StorefrontOrder::VERIFICATION_REJECTED),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);
        $checkout->verifyManualPayment(
            $order,
            $validated['decision'],
            $validated['payment_verification_notes'] ?? null,
            (int) Auth::id(),
        );

        return redirect()->route('admin.storefront.orders.index')
            ->with('success', $validated['decision'] === StorefrontOrder::VERIFICATION_VERIFIED
                ? 'ایزی پیسہ ادائیگی تصدیق کر کے گاہک کے کھاتے میں درج کر دی گئی ہے۔'
                : 'ادائیگی کا دعویٰ مسترد کر دیا گیا ہے۔');
    }

    public function storeReturn(
        Request $request,
        StorefrontOrder $order,
        StorefrontReturnService $returns
    ) {
        $storefront = Auth::user()->business?->storefront;
        abort_unless($storefront && $order->storefront_id === $storefront->id, 404);
        $isPaidRefund = $request->input('return_type') === StorefrontOrderReturn::TYPE_REFUND
            && (float) $order->paid_amount > 0;
        $validated = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:storefront_order_items,id'],
            'return_type' => ['required', Rule::in(array_keys(StorefrontOrderReturn::types()))],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'restock' => ['nullable', 'boolean'],
            'replacement_cloth_color_id' => [
                Rule::requiredIf($request->input('return_type') === StorefrontOrderReturn::TYPE_EXCHANGE),
                'nullable',
                'integer',
                'exists:cloth_colors,id',
            ],
            'refund_method' => [
                Rule::requiredIf($isPaidRefund),
                'nullable',
                Rule::in(array_keys(StorefrontOrderRefund::methods())),
            ],
            'refund_reference' => [
                Rule::requiredIf(
                    $isPaidRefund
                    && $request->input('refund_method') !== StorefrontOrderRefund::METHOD_CASH
                ),
                'nullable',
                'string',
                'max:100',
            ],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $item = $order->items()->findOrFail($validated['order_item_id']);
        $return = $returns->process(
            $order,
            $item,
            $validated['return_type'],
            (float) $validated['quantity'],
            (bool) ($validated['restock'] ?? false),
            isset($validated['replacement_cloth_color_id'])
                ? (int) $validated['replacement_cloth_color_id']
                : null,
            $validated['refund_method'] ?? null,
            $validated['refund_reference'] ?? null,
            $validated['return_notes'] ?? null,
            (int) Auth::id(),
        );

        return redirect()->route('admin.storefront.orders.index')
            ->with('success', $return->type === StorefrontOrderReturn::TYPE_EXCHANGE
                ? 'کپڑے کی جزوی تبدیلی درج کر کے اسٹاک درست کر دیا گیا ہے۔'
                : 'کپڑے کی جزوی واپسی، رقم یا بقایا، اور اسٹاک درست کر دیا گیا ہے۔');
    }
}
