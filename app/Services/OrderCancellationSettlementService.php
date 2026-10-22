<?php

namespace App\Services;

use App\Models\OrderCancellationSettlement;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PaymentMethods;
use Illuminate\Validation\ValidationException;

class OrderCancellationSettlementService
{
    public function settle(array $source, User $actor, array $data): OrderCancellationSettlement
    {
        $money = fn ($value) => round((float) ($value ?? 0), 2);
        $total = $money($source['order_total']);
        $outstanding = min($total, max(0, $money($source['outstanding'])));
        $paid = max(0, $money($total - $outstanding));
        $ledgerBalance = $money(Transaction::where('userId', (string) $source['user_id'])
            ->where('customerId', $source['customer_id'] === null ? null : (string) $source['customer_id'])->sum('remainingBalance'));
        $otherBalance = max(0, $money($ledgerBalance - $outstanding));
        $charge = $money($data['cancellation_charge'] ?? 0);
        $compensation = $money($data['compensation'] ?? 0);
        $applied = $money($data['applied_to_other_balance'] ?? 0);
        $retained = $money($data['retained_credit'] ?? 0);

        if ($charge > $paid) {
            throw ValidationException::withMessages(['cancellation_charge' => 'منسوخی کٹوتی اس آرڈر پر ادا شدہ رقم سے زیادہ نہیں ہو سکتی۔']);
        }
        $available = $money($paid - $charge + $compensation);
        $refund = array_key_exists('cash_refund', $data) && $data['cash_refund'] !== null
            ? $money($data['cash_refund'])
            : $money($available - $applied - $retained);
        if ($applied > $otherBalance) {
            throw ValidationException::withMessages(['applied_to_other_balance' => 'پرانے بقایا میں منتقل رقم موجودہ پرانے بقایا سے زیادہ نہیں ہو سکتی۔']);
        }
        if (abs($available - $applied - $refund - $retained) > 0.009) {
            throw ValidationException::withMessages(['cash_refund' => 'دستیاب رقم کو پرانے بقایا، نقد واپسی اور محفوظ کریڈٹ میں مکمل تقسیم کریں۔']);
        }
        if ($refund > 0 && blank($data['payment_method'] ?? null)) {
            throw ValidationException::withMessages([($source['payment_method_field'] ?? 'payment_method') => 'رقم واپسی کا طریقہ درج کریں۔']);
        }
        if ($refund > 0 && PaymentMethods::requiresReference($data['payment_method']) && blank($data['payment_reference'] ?? null)) {
            throw ValidationException::withMessages([($source['payment_reference_field'] ?? 'payment_reference') => 'منتخب طریقے کا حوالہ درج کریں۔']);
        }
        if (OrderCancellationSettlement::where('user_id', $source['user_id'])->where('source_type', $source['source_type'])->where('source_id', $source['source_id'])->exists()) {
            throw ValidationException::withMessages(['cancellation_reason' => 'اس آرڈر کی منسوخی کا تصفیہ پہلے ہی محفوظ ہے۔']);
        }

        $transaction = Transaction::create([
            'remainingBalance' => -$outstanding,
            'recivedPayment' => $refund > 0 ? -$refund : 0,
            'Order_type' => $source['transaction_type'],
            'sale_id' => $source['sale_id'] ?? null,
            'counter_sale_receipt_id' => $source['counter_sale_receipt_id'] ?? null,
            'orderId' => $source['order_id'] ?? null,
            'customerId' => $source['customer_id'],
            'userId' => $source['user_id'],
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'paid_on' => now()->toDateString(),
            'comment' => $source['comment_prefix'].$data['reason'],
        ]);

        if ($applied + $retained > 0) {
            Transaction::create([
                'remainingBalance' => -$applied - $retained,
                'recivedPayment' => 0,
                'Order_type' => $source['transaction_type'],
                'customerId' => $source['customer_id'],
                'userId' => $source['user_id'],
                'paid_on' => now()->toDateString(),
                'comment' => 'منسوخی کریڈٹ: پرانا بقایا '.$applied.'، محفوظ کریڈٹ '.$retained,
            ]);
        }

        return OrderCancellationSettlement::create([
            'user_id' => $source['user_id'], 'customer_id' => $source['customer_id'],
            'source_type' => $source['source_type'], 'source_id' => $source['source_id'],
            'order_total' => $total, 'paid_on_order' => $paid, 'cancelled_unpaid_balance' => $outstanding,
            'other_balance_before' => $otherBalance, 'cancellation_charge' => $charge,
            'compensation' => $compensation, 'available_credit' => $available,
            'applied_to_other_balance' => $applied, 'cash_refund' => $refund, 'retained_credit' => $retained,
            'customer_balance_after' => $money($ledgerBalance - $outstanding - $applied - $retained),
            'payment_method' => $data['payment_method'] ?? null, 'payment_reference' => $data['payment_reference'] ?? null,
            'reason' => $data['reason'], 'transaction_id' => $transaction->id, 'created_by_user_id' => $actor->id,
        ]);
    }
}
