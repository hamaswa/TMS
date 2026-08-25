<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class CustomerLedgerService
{
    public function orderBalances(int $ownerId, int $customerId): Collection
    {
        $allocation = $this->allocate($ownerId, $customerId);

        return collect($allocation['order_balances'])
            ->map(fn (int $cents) => round($cents / 100, 2));
    }

    public function orderBalance(Order $order): float
    {
        $balances = $this->orderBalances((int) $order->userId, (int) $order->customerId);

        if ($balances->has((int) $order->id)) {
            return max(0, (float) $balances->get((int) $order->id));
        }

        return max(0, round(
            (float) $order->totalPayment - (float) $order->transactions()->sum('recivedPayment'),
            2
        ));
    }

    public function receiptSummary(Order $order): array
    {
        $allocation = $this->allocate((int) $order->userId, (int) $order->customerId);
        $orderId = (int) $order->id;

        if (! array_key_exists($orderId, $allocation['order_first_transaction_ids'])) {
            $orderBalance = $this->orderBalance($order);

            return [$orderBalance, 0.0, $orderBalance];
        }

        $firstOrderTransactionId = $allocation['order_first_transaction_ids'][$orderId];
        $previousCents = 0;

        foreach ($allocation['charges'] as $charge) {
            if ($charge['transaction_id'] < $firstOrderTransactionId) {
                $previousCents += $charge['remaining'];
            }
        }

        $previousBalance = max(0, round($previousCents / 100, 2));
        $orderBalance = max(0, round(($allocation['order_balances'][$orderId] ?? 0) / 100, 2));

        return [
            round($previousBalance + $orderBalance, 2),
            $previousBalance,
            $orderBalance,
        ];
    }

    public function trackingSummary(Order $order): array
    {
        $allocation = $this->allocate((int) $order->userId, (int) $order->customerId);
        $orderId = (int) $order->id;
        $customerBalance = max(0, round($allocation['ledger_balance'] / 100, 2));

        if (! array_key_exists($orderId, $allocation['order_first_transaction_ids'])) {
            return [$customerBalance, $this->orderBalance($order)];
        }

        $orderBalance = max(0, round(($allocation['order_balances'][$orderId] ?? 0) / 100, 2));

        return [
            max(0, round($customerBalance - $orderBalance, 2)),
            $orderBalance,
        ];
    }

    private function allocate(int $ownerId, int $customerId): array
    {
        $transactions = Transaction::where('userId', $ownerId)
            ->where('customerId', $customerId)
            ->orderBy('id')
            ->get(['id', 'orderId', 'Order_type', 'remainingBalance']);

        $charges = [];
        $sharedCredit = 0;
        $orderCredits = [];
        $orderFirstTransactionIds = [];
        $ledgerBalance = 0;

        foreach ($transactions as $transaction) {
            $orderId = filled($transaction->orderId) ? (int) $transaction->orderId : null;
            $amount = (int) round((float) $transaction->remainingBalance * 100);
            $ledgerBalance += $amount;

            if ($orderId && $transaction->Order_type === 'Tailor') {
                $orderFirstTransactionIds[$orderId] ??= (int) $transaction->id;
            }

            if ($amount > 0) {
                if ($orderId && ($orderCredits[$orderId] ?? 0) > 0) {
                    $applied = min($amount, $orderCredits[$orderId]);
                    $amount -= $applied;
                    $orderCredits[$orderId] -= $applied;
                }

                if ($sharedCredit > 0) {
                    $applied = min($amount, $sharedCredit);
                    $amount -= $applied;
                    $sharedCredit -= $applied;
                }

                $charges[] = [
                    'transaction_id' => (int) $transaction->id,
                    'order_id' => $orderId,
                    'remaining' => $amount,
                ];

                continue;
            }

            if ($amount >= 0) {
                continue;
            }

            $credit = abs($amount);
            $credit = $this->applyCredit($charges, $credit, $orderId);

            if ($credit > 0) {
                if ($orderId) {
                    $orderCredits[$orderId] = ($orderCredits[$orderId] ?? 0) + $credit;
                } else {
                    $sharedCredit += $credit;
                }
            }
        }

        $orderBalances = array_fill_keys(array_keys($orderFirstTransactionIds), 0);
        foreach ($charges as $charge) {
            if ($charge['order_id'] && array_key_exists($charge['order_id'], $orderBalances)) {
                $orderBalances[$charge['order_id']] += $charge['remaining'];
            }
        }

        return [
            'charges' => $charges,
            'ledger_balance' => $ledgerBalance,
            'order_balances' => $orderBalances,
            'order_first_transaction_ids' => $orderFirstTransactionIds,
        ];
    }

    private function applyCredit(array &$charges, int $credit, ?int $orderId): int
    {
        foreach ($charges as &$charge) {
            if ($credit <= 0) {
                break;
            }

            if ($orderId && $charge['order_id'] !== $orderId) {
                continue;
            }

            if ($charge['remaining'] <= 0) {
                continue;
            }

            $applied = min($credit, $charge['remaining']);
            $charge['remaining'] -= $applied;
            $credit -= $applied;
        }
        unset($charge);

        return $credit;
    }
}
