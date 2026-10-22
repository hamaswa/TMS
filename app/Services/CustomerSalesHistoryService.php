<?php

namespace App\Services;

use App\Models\CounterOrderItem;
use App\Models\Customers;
use App\Models\SaleStock;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Builds the shop purchase rows (legacy sales + cloth stock receipts) shown
 * for a customer on the statement and on the customer orders page.
 */
class CustomerSalesHistoryService
{
    public function forCustomer(Customers $customer, int $ownerId, int $limit = 50): Collection
    {
        $legacySales = $customer->sales()
            ->where('user_id', $ownerId)
            ->with(['detail:id,sale_id,product_name,quantity,price', 'transaction:id,sale_id,recivedPayment,remainingBalance'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($sale) => (object) [
                'id' => $sale->id,
                'source' => 'legacy',
                'display_number' => '#' . $sale->id,
                'counter_order_id' => null,
                'created_at' => $sale->created_at,
                'items_count' => $sale->detail->sum('quantity'),
                'summary' => $sale->detail->pluck('product_name')->filter()->take(3)->implode('، '),
                'amount' => $sale->detail->sum(fn($detail) => (float) $detail->price * (int) $detail->quantity),
                'received' => (float) ($sale->transaction?->recivedPayment ?? 0),
                'balance' => (float) ($sale->transaction?->remainingBalance ?? 0),
                'status' => $sale->status,
                'cancellation_reason' => $sale->cancellation_reason,
            ]);
        $stockGroups = SaleStock::where('user_id', $ownerId)
            ->where('c_id', $customer->id)
            ->with(['type:id,name', 'brand:id,name', 'receipt.returns', 'receipt.payments'])
            ->withSum('returnItems as returned_quantity', 'quantity')
            ->latest()
            ->limit($limit * 4)
            ->get()
            ->groupBy(fn($sale) => $sale->counter_sale_receipt_id
                ? 'receipt-' . $sale->counter_sale_receipt_id
                : 'legacy-' . $sale->created_at?->format('Y-m-d H:i:s'));
        $stockTransactions = Transaction::where('userId', (string) $ownerId)
            ->where('Order_type', 'Sale')
            ->whereIn('sale_id', $stockGroups->map(fn($items) => $items->first()->id))
            ->get()->keyBy('sale_id');
        $counterOrderLinks = CounterOrderItem::query()
            ->where('source_record_type', 'counter_sale_receipt')
            ->whereIn('source_record_id', $stockGroups->map(fn($items) => $items->first()->counter_sale_receipt_id)->filter())
            ->whereHas('counterOrder', fn($query) => $query->where('user_id', $ownerId))
            ->with('counterOrder:id,reference,serial_number')
            ->get()
            ->keyBy('source_record_id');
        $receiptBalances = app(CustomerLedgerService::class)->receiptBalances($ownerId, (int) $customer->id);
        $stockSales = $stockGroups
            ->map(function ($items) use ($stockTransactions, $counterOrderLinks, $receiptBalances) {
                $first = $items->first();
                $transaction = $stockTransactions->get($first->id);
                $counterOrder = $counterOrderLinks->get($first->counter_sale_receipt_id)?->counterOrder;
                $returns = $first->receipt?->returns ?? collect();
                $laterPayments = (float) ($first->receipt?->payments->sum('recivedPayment') ?? 0);
                $amount = $items->sum(fn($item) => (float) $item->selling_price * (float) $item->length)
                    - (float) $returns->sum('return_total');
                $received = (float) ($transaction?->recivedPayment ?? 0) + $laterPayments
                    - (float) $returns->sum(fn($return) => (float) $return->refund_amount + (float) $return->credit_amount);
                $balance = max(0, (float) ($transaction?->remainingBalance ?? 0) - (float) $returns->sum('balance_reduction') - $laterPayments);
                // Use the ledger allocation so general (unlinked) payments also count against older receipts.
                if ($first->receipt && $first->receipt->status !== 'cancelled' && $receiptBalances->has($first->receipt->id)) {
                    $balance = (float) $receiptBalances->get($first->receipt->id);
                    $received = max(0, round($amount - $balance, 2));
                }

                return (object) [
                    'id' => $first->id,
                    'source' => 'stock',
                    'display_number' => $counterOrder?->displayNumber()
                        ?: ($first->receipt?->receipt_number ?: '#' . $first->id),
                    'counter_order_id' => $counterOrder?->id,
                    'created_at' => $first->created_at,
                    'items_count' => $items->count(),
                    'summary' => $items->map(fn($item) => collect([$item->brand?->name, $item->type?->name, $item->color])->filter()->implode(' '))->filter()->take(3)->implode('، '),
                    'amount' => $amount,
                    'received' => $received,
                    'balance' => $balance,
                    'returned_total' => (float) $returns->sum('return_total'),
                    'receipt' => $first->receipt,
                    'items' => $items->sortBy('id')->values(),
                    'status' => $first->receipt?->status ?? 'completed',
                    'cancellation_reason' => $first->receipt?->cancellation_reason,
                ];
            })
            ->values();

        return $legacySales->concat($stockSales)->sortByDesc('created_at')->take($limit)->values();
    }
}
