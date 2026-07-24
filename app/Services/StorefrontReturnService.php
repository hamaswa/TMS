<?php

namespace App\Services;

use App\Models\ClothColor;
use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderItem;
use App\Models\StorefrontOrderRefund;
use App\Models\StorefrontOrderReturn;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontReturnService
{
    public function __construct(private InventoryService $inventory) {}

    public function process(
        StorefrontOrder $order,
        StorefrontOrderItem $item,
        string $type,
        float $quantity,
        bool $restock,
        ?int $replacementColorId,
        ?string $refundMethod,
        ?string $externalReference,
        ?string $notes,
        int $processedByUserId,
    ): StorefrontOrderReturn {
        return DB::transaction(function () use (
            $order,
            $item,
            $type,
            $quantity,
            $restock,
            $replacementColorId,
            $refundMethod,
            $externalReference,
            $notes,
            $processedByUserId,
        ) {
            $lockedOrder = StorefrontOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status === StorefrontOrder::STATUS_CANCELLED || $lockedOrder->refunds()->exists()) {
                throw ValidationException::withMessages([
                    'return_type' => 'منسوخ یا مکمل رقم واپس کیے گئے آرڈر پر جزوی واپسی درج نہیں ہو سکتی۔',
                ]);
            }
            if (StorefrontOrder::requiresManualVerification($lockedOrder->payment_method)
                && $lockedOrder->payment_verification_status !== StorefrontOrder::VERIFICATION_VERIFIED) {
                throw ValidationException::withMessages([
                    'return_type' => 'دستی ادائیگی کی تصدیق کے بعد ہی جزوی واپسی یا تبدیلی درج کریں۔',
                ]);
            }

            $lockedItem = StorefrontOrderItem::query()->lockForUpdate()->findOrFail($item->id);
            if ((int) $lockedItem->storefront_order_id !== (int) $lockedOrder->id) {
                throw ValidationException::withMessages([
                    'order_item_id' => 'منتخب کپڑا اس آرڈر کا حصہ نہیں ہے۔',
                ]);
            }

            $quantity = round($quantity, 2);
            $alreadyProcessed = (float) $lockedItem->returnItems()
                ->lockForUpdate()
                ->get(['id', 'quantity'])
                ->sum('quantity');
            $remainingQuantity = round((float) $lockedItem->quantity - $alreadyProcessed, 2);
            if ($quantity <= 0 || $quantity > $remainingQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'واپسی کی مقدار دستیاب مقدار سے زیادہ نہیں ہو سکتی۔ دستیاب: '.number_format($remainingQuantity, 2).' میٹر۔',
                ]);
            }
            if (! in_array($type, [StorefrontOrderReturn::TYPE_REFUND, StorefrontOrderReturn::TYPE_EXCHANGE], true)) {
                throw ValidationException::withMessages(['return_type' => 'درست کارروائی منتخب کریں۔']);
            }

            $replacementColor = null;
            if ($type === StorefrontOrderReturn::TYPE_EXCHANGE) {
                $replacementCandidate = ClothColor::query()->find($replacementColorId);
                if (! $replacementCandidate
                    || (int) $replacementCandidate->cloth_id !== (int) $lockedItem->cloth_id
                    || (int) $replacementCandidate->id === (int) $lockedItem->cloth_color_id) {
                    throw ValidationException::withMessages([
                        'replacement_cloth_color_id' => 'اسی کپڑے کا کوئی دوسرا درست رنگ منتخب کریں۔',
                    ]);
                }
            }

            $lockedColors = ClothColor::query()
                ->whereIn('id', array_filter([$lockedItem->cloth_color_id, $replacementColorId]))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $originalColor = $lockedColors->get($lockedItem->cloth_color_id);
            $replacementColor = $type === StorefrontOrderReturn::TYPE_EXCHANGE
                ? $lockedColors->get($replacementColorId)
                : null;
            if ($replacementColor) {
                if ((float) $replacementColor->length < $quantity) {
                    throw ValidationException::withMessages([
                        'replacement_cloth_color_id' => 'منتخب متبادل رنگ میں مطلوبہ مقدار دستیاب نہیں ہے۔',
                    ]);
                }
            }

            $lineTotal = round($quantity * (float) $lockedItem->unit_price, 2);
            $isPaid = (float) $lockedOrder->paid_amount > 0;
            if ($type === StorefrontOrderReturn::TYPE_REFUND && $isPaid) {
                if ((float) $lockedOrder->paid_amount !== (float) $lockedOrder->subtotal) {
                    throw ValidationException::withMessages([
                        'refund_method' => 'جزوی ادائیگی والے آرڈر کی واپسی کے لیے پہلے کھاتہ درست کریں۔',
                    ]);
                }
                if (! $refundMethod || ! array_key_exists($refundMethod, StorefrontOrderRefund::methods())) {
                    throw ValidationException::withMessages(['refund_method' => 'رقم واپسی کا درست طریقہ منتخب کریں۔']);
                }
                if ($refundMethod !== StorefrontOrderRefund::METHOD_CASH && blank($externalReference)) {
                    throw ValidationException::withMessages(['refund_reference' => 'غیر نقد رقم واپسی کا حوالہ درج کریں۔']);
                }
            }

            $return = $lockedOrder->returns()->create([
                'reference' => $this->reference(),
                'type' => $type,
                'refund_amount' => $type === StorefrontOrderReturn::TYPE_REFUND ? $lineTotal : 0,
                'refund_method' => $type === StorefrontOrderReturn::TYPE_REFUND && $isPaid ? $refundMethod : null,
                'external_reference' => $type === StorefrontOrderReturn::TYPE_REFUND && $isPaid ? $externalReference : null,
                'notes' => $notes,
                'processed_by_user_id' => $processedByUserId,
                'processed_at' => now(),
            ]);
            $returnItem = $return->items()->create([
                'storefront_order_item_id' => $lockedItem->id,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'restocked' => $restock,
                'replacement_cloth_color_id' => $replacementColor?->id,
                'replacement_quantity' => $replacementColor ? $quantity : null,
            ]);

            if ($restock) {
                $this->inventory->restore(
                    $originalColor,
                    $quantity,
                    'storefront_return',
                    $returnItem,
                    'Storefront return '.$return->reference,
                );
            }
            if ($replacementColor) {
                $this->inventory->issue(
                    $replacementColor,
                    $quantity,
                    'storefront_exchange_issue',
                    $returnItem,
                    'Storefront exchange '.$return->reference,
                );
            }

            if ($type === StorefrontOrderReturn::TYPE_REFUND) {
                Transaction::create([
                    'remainingBalance' => $isPaid ? 0 : -$lineTotal,
                    'recivedPayment' => $isPaid ? -$lineTotal : 0,
                    'customerId' => $lockedOrder->customer_id,
                    'userId' => $lockedOrder->storefront->business->owner_user_id,
                    'Order_type' => 'Sale',
                    'comment' => 'آن لائن آرڈر جزوی واپسی '.$lockedOrder->reference.' · '.$return->reference,
                ]);
                if (! $isPaid) {
                    $lockedOrder->update([
                        'balance_amount' => max(0, round((float) $lockedOrder->balance_amount - $lineTotal, 2)),
                    ]);
                }
            }

            return $return->fresh(['items.orderItem', 'items.replacementColor']);
        }, 3);
    }

    private function reference(): string
    {
        do {
            $reference = 'TMSRT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (StorefrontOrderReturn::where('reference', $reference)->exists());

        return $reference;
    }
}
