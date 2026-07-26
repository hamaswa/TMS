<?php

namespace App\Services;

use App\Models\ClothColor;
use App\Models\StorefrontCart;
use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderRefund;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontCheckoutService
{
    public function __construct(private InventoryService $inventory) {}

    public function checkout(
        StorefrontCart $cart,
        string $fulfillmentMethod,
        ?string $deliveryAddress,
        ?string $customerNote,
        string $paymentMethod = StorefrontOrder::PAYMENT_UNPAID,
        ?string $paymentSenderPhone = null,
        ?string $paymentReference = null,
        array $paymentEvidence = [],
    ): array {
        $trackingToken = Str::random(64);

        $order = DB::transaction(function () use (
            $cart,
            $fulfillmentMethod,
            $deliveryAddress,
            $customerNote,
            $paymentMethod,
            $paymentSenderPhone,
            $paymentReference,
            $paymentEvidence,
            $trackingToken
        ) {
            $lockedCart = StorefrontCart::query()->lockForUpdate()->findOrFail($cart->id);
            if ($lockedCart->checked_out_at || $lockedCart->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'checkout' => __('storefront.messages.cart_used'),
                ]);
            }
            if (! $lockedCart->customer_id) {
                throw ValidationException::withMessages([
                    'checkout' => __('storefront.messages.link_customer'),
                ]);
            }

            $items = $lockedCart->items()
                ->with(['listing.cloth', 'color'])
                ->lockForUpdate()
                ->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['checkout' => __('storefront.messages.cart_empty')]);
            }
            if ($items->contains(fn ($item) => $item->reserved_until->isPast())) {
                throw ValidationException::withMessages([
                    'checkout' => __('storefront.messages.reservation_expired'),
                ]);
            }

            $storefront = $lockedCart->storefront()->with('business')->firstOrFail();
            $ownerId = (int) $storefront->business->owner_user_id;
            $lockedColors = ClothColor::query()
                ->whereIn('id', $items->pluck('cloth_color_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $color = $lockedColors->get($item->cloth_color_id);
                $validListing = $item->listing
                    && $item->listing->storefront_id === $storefront->id
                    && $item->listing->is_published
                    && $item->listing->acceptsOnlineOrders()
                    && $item->listing->acceptsQuantity((float) $item->quantity)
                    && (int) $item->listing->cloth?->user_id === $ownerId
                    && $color
                    && (int) $color->cloth_id === (int) $item->listing->cloth_id;
                if (! $validListing || (float) $color->length < (float) $item->quantity) {
                    throw ValidationException::withMessages([
                        'checkout' => __('storefront.messages.stock_changed'),
                    ]);
                }
            }

            $subtotal = round($items->sum(fn ($item) => $item->line_total), 2);
            $order = StorefrontOrder::create([
                'storefront_id' => $storefront->id,
                'storefront_cart_id' => $lockedCart->id,
                'customer_id' => $lockedCart->customer_id,
                'reference' => $this->reference(),
                'tracking_token_hash' => hash('sha256', $trackingToken),
                'status' => StorefrontOrder::STATUS_PENDING,
                'fulfillment_method' => $fulfillmentMethod,
                'delivery_address' => $fulfillmentMethod === 'delivery' ? $deliveryAddress : null,
                'customer_note' => $customerNote,
                'payment_method' => $paymentMethod,
                'payment_sender_phone' => StorefrontOrder::requiresManualVerification($paymentMethod)
                    ? $paymentSenderPhone : null,
                'payment_reference' => StorefrontOrder::requiresManualVerification($paymentMethod)
                    ? $paymentReference : null,
                ...$paymentEvidence,
                'payment_verification_status' => StorefrontOrder::requiresManualVerification($paymentMethod)
                    ? StorefrontOrder::VERIFICATION_PENDING
                    : StorefrontOrder::VERIFICATION_NOT_REQUIRED,
                'subtotal' => $subtotal,
                'paid_amount' => 0,
                'balance_amount' => $subtotal,
                'placed_at' => now(),
            ]);

            foreach ($items as $cartItem) {
                $color = $lockedColors->get($cartItem->cloth_color_id);
                $cost = (float) $color->average_unit_cost ?: (float) $cartItem->listing->cloth->price;
                $orderItem = $order->items()->create([
                    'clothing_listing_id' => $cartItem->clothing_listing_id,
                    'cloth_id' => $cartItem->listing->cloth_id,
                    'cloth_color_id' => $color->id,
                    'item_name' => $cartItem->listing->display_name,
                    'color' => $color->color,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price_snapshot,
                    'line_total' => $cartItem->line_total,
                    'cost_per_meter' => $cost,
                    'cost_total' => round($cost * (float) $cartItem->quantity, 2),
                ]);
                $this->inventory->issue(
                    $color,
                    (float) $cartItem->quantity,
                    'storefront_order',
                    $orderItem,
                    'Storefront order '.$order->reference,
                    $cost
                );
            }

            $transaction = Transaction::create([
                'remainingBalance' => $subtotal,
                'recivedPayment' => 0,
                'customerId' => $lockedCart->customer_id,
                'userId' => $ownerId,
                'Order_type' => 'Sale',
                'comment' => 'آن لائن آرڈر '.$order->reference,
            ]);
            $order->update(['transaction_id' => $transaction->id]);
            $lockedCart->update(['checked_out_at' => now(), 'last_activity_at' => now()]);
            $lockedCart->items()->delete();

            return $order->fresh(['customer', 'items']);
        }, 3);

        return [$order, $trackingToken];
    }

    public function updateStatus(StorefrontOrder $order, string $status): StorefrontOrder
    {
        return DB::transaction(function () use ($order, $status) {
            $lockedOrder = StorefrontOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($status === StorefrontOrder::STATUS_COMPLETE) {
                if ($lockedOrder->status !== StorefrontOrder::STATUS_PENDING) {
                    throw ValidationException::withMessages(['status' => 'صرف زیرِ انتظار آرڈر مکمل کیا جا سکتا ہے۔']);
                }
                if (StorefrontOrder::requiresManualVerification($lockedOrder->payment_method)
                    && $lockedOrder->payment_verification_status !== StorefrontOrder::VERIFICATION_VERIFIED) {
                    throw ValidationException::withMessages([
                        'status' => 'دستی ادائیگی کی تصدیق کے بعد ہی آرڈر مکمل کریں۔',
                    ]);
                }
                $lockedOrder->update([
                    'status' => StorefrontOrder::STATUS_COMPLETE,
                    'completed_at' => now(),
                ]);

                return $lockedOrder;
            }

            if ($status !== StorefrontOrder::STATUS_CANCELLED || $lockedOrder->status !== StorefrontOrder::STATUS_PENDING) {
                throw ValidationException::withMessages(['status' => 'صرف زیرِ انتظار آرڈر منسوخ کیا جا سکتا ہے۔']);
            }
            if ($lockedOrder->returns()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'جزوی واپسی یا تبدیلی والے آرڈر کو مکمل منسوخ نہیں کیا جا سکتا۔',
                ]);
            }
            if ((float) $lockedOrder->paid_amount > 0) {
                throw ValidationException::withMessages([
                    'status' => 'وصول شدہ ادائیگی والے آرڈر کو منسوخ کرنے سے پہلے رقم واپسی درج کریں۔',
                ]);
            }

            $items = $lockedOrder->items()->orderBy('cloth_color_id')->lockForUpdate()->get();
            $colors = ClothColor::query()
                ->whereIn('id', $items->pluck('cloth_color_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            foreach ($items as $item) {
                $this->inventory->restore(
                    $colors->get($item->cloth_color_id),
                    (float) $item->quantity,
                    'storefront_cancellation',
                    $item,
                    'Cancelled storefront order '.$lockedOrder->reference
                );
            }
            Transaction::create([
                'remainingBalance' => (float) $lockedOrder->balance_amount > 0
                    ? -((float) $lockedOrder->balance_amount)
                    : 0,
                'recivedPayment' => 0,
                'customerId' => $lockedOrder->customer_id,
                'userId' => $lockedOrder->storefront->business->owner_user_id,
                'Order_type' => 'Sale',
                'comment' => 'منسوخ آن لائن آرڈر '.$lockedOrder->reference,
            ]);
            $lockedOrder->update([
                'status' => StorefrontOrder::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'balance_amount' => 0,
            ]);

            return $lockedOrder;
        }, 3);
    }

    public function verifyManualPayment(
        StorefrontOrder $order,
        string $decision,
        ?string $notes,
        int $verifiedByUserId,
    ): StorefrontOrder {
        return DB::transaction(function () use ($order, $decision, $notes, $verifiedByUserId) {
            $lockedOrder = StorefrontOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! StorefrontOrder::requiresManualVerification($lockedOrder->payment_method)) {
                throw ValidationException::withMessages([
                    'payment_verification' => 'اس ادائیگی کے طریقے کے لیے دستی تصدیق درکار نہیں۔',
                ]);
            }
            if ($lockedOrder->status !== StorefrontOrder::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'payment_verification' => 'صرف زیرِ انتظار آرڈر کی ادائیگی تصدیق کی جا سکتی ہے۔',
                ]);
            }
            if ($lockedOrder->payment_verification_status === StorefrontOrder::VERIFICATION_VERIFIED) {
                throw ValidationException::withMessages([
                    'payment_verification' => 'یہ ادائیگی پہلے ہی تصدیق ہو چکی ہے۔',
                ]);
            }

            if ($decision === StorefrontOrder::VERIFICATION_REJECTED) {
                $lockedOrder->update([
                    'payment_verification_status' => StorefrontOrder::VERIFICATION_REJECTED,
                    'payment_verification_notes' => $notes,
                    'payment_verified_by_user_id' => $verifiedByUserId,
                    'payment_verified_at' => null,
                    'payment_rejected_at' => now(),
                ]);

                return $lockedOrder;
            }

            $transaction = Transaction::query()->lockForUpdate()->findOrFail($lockedOrder->transaction_id);
            $amount = (float) $lockedOrder->subtotal;
            $transaction->update([
                'recivedPayment' => $amount,
                'remainingBalance' => 0,
                'comment' => trim(($transaction->comment ? $transaction->comment.' · ' : '')
                    .(StorefrontOrder::paymentMethods()[$lockedOrder->payment_method] ?? $lockedOrder->payment_method)
                    .' تصدیق '.$lockedOrder->payment_reference),
            ]);
            $lockedOrder->update([
                'payment_verification_status' => StorefrontOrder::VERIFICATION_VERIFIED,
                'payment_verification_notes' => $notes,
                'payment_verified_by_user_id' => $verifiedByUserId,
                'payment_verified_at' => now(),
                'payment_rejected_at' => null,
                'paid_amount' => $amount,
                'balance_amount' => 0,
            ]);

            return $lockedOrder;
        }, 3);
    }

    public function refundAndCancel(
        StorefrontOrder $order,
        string $method,
        ?string $externalReference,
        ?string $notes,
        int $processedByUserId,
    ): StorefrontOrder {
        return DB::transaction(function () use (
            $order,
            $method,
            $externalReference,
            $notes,
            $processedByUserId
        ) {
            if (! array_key_exists($method, StorefrontOrderRefund::methods())) {
                throw ValidationException::withMessages([
                    'refund_method' => 'رقم واپسی کا درست طریقہ منتخب کریں۔',
                ]);
            }
            if ($method !== StorefrontOrderRefund::METHOD_CASH && blank($externalReference)) {
                throw ValidationException::withMessages([
                    'refund_reference' => 'غیر نقد رقم واپسی کا حوالہ درج کریں۔',
                ]);
            }

            $lockedOrder = StorefrontOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== StorefrontOrder::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'صرف زیرِ انتظار آرڈر کی رقم واپس کر کے اسے منسوخ کیا جا سکتا ہے۔',
                ]);
            }
            if ((float) $lockedOrder->paid_amount <= 0) {
                throw ValidationException::withMessages([
                    'refund_method' => 'اس آرڈر کے ساتھ کوئی تصدیق شدہ ادائیگی موجود نہیں ہے۔',
                ]);
            }
            if ($lockedOrder->refunds()->exists()) {
                throw ValidationException::withMessages([
                    'refund_method' => 'اس آرڈر کی رقم پہلے ہی واپس کی جا چکی ہے۔',
                ]);
            }
            if ($lockedOrder->returns()->exists()) {
                throw ValidationException::withMessages([
                    'refund_method' => 'جزوی واپسی یا تبدیلی والے آرڈر پر مکمل رقم واپسی نہیں ہو سکتی۔',
                ]);
            }

            $this->restoreCancelledOrderInventory($lockedOrder);
            $amount = round((float) $lockedOrder->paid_amount, 2);
            $refund = $lockedOrder->refunds()->create([
                'reference' => $this->refundReference(),
                'amount' => $amount,
                'method' => $method,
                'external_reference' => $externalReference,
                'notes' => $notes,
                'processed_by_user_id' => $processedByUserId,
                'refunded_at' => now(),
            ]);
            Transaction::create([
                'remainingBalance' => (float) $lockedOrder->balance_amount > 0
                    ? -((float) $lockedOrder->balance_amount)
                    : 0,
                'recivedPayment' => -$amount,
                'customerId' => $lockedOrder->customer_id,
                'userId' => $lockedOrder->storefront->business->owner_user_id,
                'Order_type' => 'Sale',
                'comment' => 'آن لائن آرڈر رقم واپسی '.$lockedOrder->reference.' · '.$refund->reference,
            ]);
            $lockedOrder->update([
                'status' => StorefrontOrder::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'balance_amount' => 0,
            ]);

            return $lockedOrder->fresh('refunds');
        }, 3);
    }

    private function reference(): string
    {
        do {
            $reference = 'TMSO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (StorefrontOrder::where('reference', $reference)->exists());

        return $reference;
    }

    private function refundReference(): string
    {
        do {
            $reference = 'TMSR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (StorefrontOrderRefund::where('reference', $reference)->exists());

        return $reference;
    }

    private function restoreCancelledOrderInventory(StorefrontOrder $lockedOrder): void
    {
        $items = $lockedOrder->items()->orderBy('cloth_color_id')->lockForUpdate()->get();
        $colors = ClothColor::query()
            ->whereIn('id', $items->pluck('cloth_color_id')->unique())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        foreach ($items as $item) {
            $this->inventory->restore(
                $colors->get($item->cloth_color_id),
                (float) $item->quantity,
                'storefront_cancellation',
                $item,
                'Cancelled storefront order '.$lockedOrder->reference
            );
        }
    }
}
