<?php

namespace App\Services;

use App\Models\ClothColor;
use App\Models\CounterSaleReceipt;
use App\Models\CounterSaleReturn;
use App\Models\SaleStock;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PaymentMethods;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CounterSaleReturnService
{
    public function __construct(private InventoryService $inventory) {}

    public function process(CounterSaleReceipt $receipt, User $actor, array $quantities, array $data): CounterSaleReturn
    {
        return DB::transaction(function () use ($receipt, $actor, $quantities, $data) {
            $ownerId = $actor->businessOwnerId();
            $lockedReceipt = CounterSaleReceipt::where('user_id', $ownerId)->lockForUpdate()->findOrFail($receipt->id);
            if ($lockedReceipt->status === 'cancelled') {
                throw ValidationException::withMessages(['receipt' => 'منسوخ شدہ رسید سے آئٹم واپس نہیں ہو سکتا۔']);
            }

            $selected = collect($quantities)->map(fn ($value) => round((float) $value, 2))->filter(fn ($value) => $value > 0);
            if ($selected->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'کم از کم ایک آئٹم اور واپسی مقدار منتخب کریں۔']);
            }
            $items = SaleStock::where('user_id', $ownerId)->where('counter_sale_receipt_id', $lockedReceipt->id)
                ->whereIn('id', $selected->keys())->withSum('returnItems as returned_quantity', 'quantity')->lockForUpdate()->get()->keyBy('id');
            if ($items->count() !== $selected->count()) {
                throw ValidationException::withMessages(['items' => 'منتخب آئٹم اس رسید کا حصہ نہیں ہے۔']);
            }

            $lines = [];
            $returnTotal = 0.0;
            foreach ($selected as $itemId => $quantity) {
                $item = $items->get((int) $itemId);
                $soldQuantity = $item->sale_price_basis === 'per_suit' && (float) $item->suit_quantity > 0
                    ? (float) $item->suit_quantity : (float) $item->length;
                $remaining = round($soldQuantity - (float) ($item->returned_quantity ?? 0), 2);
                if ($quantity > $remaining) {
                    throw ValidationException::withMessages(["items.$itemId" => 'واپسی مقدار دستیاب مقدار سے زیادہ ہے۔ دستیاب: '.number_format($remaining, 2)]);
                }
                $unitPrice = $item->sale_price_basis === 'per_suit' && (float) $item->suit_unit_price > 0
                    ? (float) $item->suit_unit_price : (float) $item->selling_price;
                $inventoryQuantity = $item->sale_price_basis === 'per_suit' && (float) $item->suit_length > 0
                    ? round($quantity * (float) $item->suit_length, 2) : $quantity;
                $lineTotal = round($quantity * $unitPrice, 2);
                $returnTotal = round($returnTotal + $lineTotal, 2);
                $lines[] = compact('item', 'quantity', 'unitPrice', 'inventoryQuantity', 'lineTotal');
            }

            $original = Transaction::where('userId', $ownerId)->where('counter_sale_receipt_id', $lockedReceipt->id)
                ->where('Order_type', 'Sale')->lockForUpdate()->first();
            $laterPayments = (float) $lockedReceipt->payments()->sum('recivedPayment');
            $originalOutstanding = max(0, (float) ($original?->remainingBalance ?? 0));
            $previousReturns = $lockedReceipt->returns()->lockForUpdate()->get();
            $currentOutstanding = max(0, round($originalOutstanding - (float) $previousReturns->sum('balance_reduction') - $laterPayments, 2));
            $customerId = $lockedReceipt->customer_id ?? $original?->customerId;
            // Everything the customer owes the shop, this receipt included.
            $totalDues = $customerId
                ? max($currentOutstanding, round((float) Transaction::where('userId', $ownerId)->where('customerId', $customerId)
                    ->lockForUpdate()->sum('remainingBalance'), 2))
                : $currentOutstanding;

            // New total dues = total dues - return value + cash given. Whatever isn't paid out in cash comes
            // off the dues, this receipt first, then older ones; the dues can't go below zero.
            $refund = round((float) ($data['refund_amount'] ?? 0), 2);
            $minRefund = max(0, round($returnTotal - $totalDues, 2));
            if ($refund < $minRefund) {
                throw ValidationException::withMessages(['refund_amount' => 'گاہک کو کم از کم Rs '.number_format($minRefund, 2).' دینے ہیں (واپسی Rs '
                    .number_format($returnTotal, 2).' − بقایا Rs '.number_format($totalDues, 2).')']);
            }
            if ($refund > $returnTotal) {
                throw ValidationException::withMessages(['refund_amount' => 'آئٹم کی قیمت Rs '.number_format($returnTotal, 2).' ہے، اس سے زیادہ نہیں دے سکتے']);
            }
            if ($refund > 0 && (blank($data['payment_method'] ?? null)
                || (PaymentMethods::requiresReference($data['payment_method']) && blank($data['payment_reference'] ?? null)))) {
                throw ValidationException::withMessages(['payment_method' => 'رقم واپسی کا طریقہ اور ضروری حوالہ درج کریں۔']);
            }
            $offDues = round($returnTotal - $refund, 2);
            $balanceReduction = min($offDues, $currentOutstanding);
            $credit = round($offDues - $balanceReduction, 2);
            $settlementType = match (true) {
                $refund > 0 && $credit > 0 => 'mixed',
                $refund > 0 => 'refund',
                $credit > 0 => 'credit',
                default => 'none',
            };
            $return = CounterSaleReturn::create([
                'reference' => $this->reference(), 'user_id' => $ownerId,
                'customer_id' => $customerId,
                'counter_sale_receipt_id' => $lockedReceipt->id, 'return_total' => $returnTotal,
                'balance_reduction' => $balanceReduction, 'refund_amount' => $refund, 'credit_amount' => $credit,
                'settlement_type' => $settlementType, 'payment_method' => $refund > 0 ? $data['payment_method'] : null,
                'payment_reference' => $refund > 0 ? ($data['payment_reference'] ?? null) : null,
                'reason' => $data['reason'], 'processed_by_user_id' => $actor->id,
            ]);

            foreach ($lines as $line) {
                $returnItem = $return->items()->create([
                    'sale_stock_id' => $line['item']->id, 'quantity' => $line['quantity'],
                    'inventory_quantity' => $line['inventoryQuantity'], 'unit_price' => $line['unitPrice'],
                    'line_total' => $line['lineTotal'], 'restocked' => (bool) $data['restock'],
                ]);
                if ($data['restock']) {
                    $color = ClothColor::where('user_id', $ownerId)->lockForUpdate()->findOrFail($line['item']->cloth_color_id);
                    $this->inventory->restore($color, $line['inventoryQuantity'], 'counter_sale_item_return', $returnItem, 'Item return '.$return->reference);
                }
            }

            $transaction = Transaction::create([
                'remainingBalance' => -$balanceReduction - $credit, 'recivedPayment' => $refund > 0 ? -$refund : 0,
                'Order_type' => 'Sale Return', 'sale_id' => $lockedReceipt->first_sale_stock_id,
                'counter_sale_receipt_id' => $lockedReceipt->id, 'customerId' => $return->customer_id, 'userId' => $ownerId,
                'payment_method' => $return->payment_method, 'payment_reference' => $return->payment_reference,
                'paid_on' => now()->toDateString(), 'comment' => 'آئٹم واپسی '.$return->reference.' · '.$return->reason,
            ]);
            $return->update(['transaction_id' => $transaction->id]);
            return $return->fresh(['items.saleStock', 'receipt']);
        }, 3);
    }

    private function reference(): string
    {
        do { $reference = 'RET-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)); }
        while (CounterSaleReturn::where('reference', $reference)->exists());
        return $reference;
    }
}
