<?php

namespace App\Services;

use App\Models\CounterOrderComplaint;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Tailor;
use App\Models\TailoringRecovery;
use App\Models\TailorRecord;
use App\Models\TailorSecurityDepositTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkerLedgerEntry;
use App\Notifications\TailoringComplaintNotification;
use App\Support\PaymentMethods;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TailoringComplaintService
{
    public function notify(CounterOrderComplaint $complaint, string $event): void
    {
        $this->notifyCustomer($complaint, $event);
    }
    public function cancelOrder(Order $order, User $actor, array $data): CounterOrderComplaint
    {
        return DB::transaction(function () use ($order, $actor, $data) {
            $locked = Order::where('userId', $actor->businessOwnerId())->lockForUpdate()->findOrFail($order->id);
            if (in_array($locked->status, ['delivered', 'cancelled'], true)) {
                throw ValidationException::withMessages(['order' => 'حوالہ شدہ یا پہلے سے منسوخ آرڈر منسوخ نہیں کیا جا سکتا۔']);
            }
            $outstanding = app(CustomerLedgerService::class)->orderBalance($locked);
            $settlement = app(OrderCancellationSettlementService::class)->settle([
                'user_id' => (int) $locked->userId, 'customer_id' => (int) $locked->customerId,
                'source_type' => 'tailoring_order', 'source_id' => (int) $locked->id,
                'order_total' => (float) $locked->totalPayment, 'outstanding' => $outstanding,
                'transaction_type' => 'Tailor Complaint Settlement', 'order_id' => (int) $locked->id,
                'comment_prefix' => 'آرڈر منسوخی: ',
            ], $actor, $data);
            $claim = CounterOrderComplaint::create([
                'order_id' => $locked->id, 'user_id' => $actor->businessOwnerId(), 'created_by_user_id' => $actor->id,
                'reason' => $data['reason'], 'category' => 'order_cancellation', 'status' => 'settled', 'resolution' => 'cancelled',
                'decision_notes' => $data['reason'], 'requested_tailoring_refund' => $settlement->available_credit, 'requested_cloth_compensation' => $settlement->compensation,
                'approved_tailoring_refund' => $settlement->available_credit, 'approved_cloth_compensation' => $settlement->compensation, 'actual_cash_payout' => $settlement->cash_refund,
                'payment_method' => $data['payment_method'] ?? null, 'payment_reference' => $data['payment_reference'] ?? null,
                'approved_by_user_id' => $actor->id, 'settled_by_user_id' => $actor->id, 'approved_at' => now(), 'settled_at' => now(), 'closed_at' => now(),
            ]);
            $from = $locked->status;
            $locked->update(['status' => 'cancelled', 'cancellation_reason' => $data['reason'], 'cancelled_at' => now(), 'cancelled_by_user_id' => $actor->id, 'status_changed_at' => now(), 'rack_no' => null]);
            OrderStatusHistory::create(['order_id' => $locked->id, 'user_id' => $actor->id, 'tailor_id' => $locked->tailorId, 'from_status' => $from, 'to_status' => 'cancelled', 'changed_by_type' => 'shop_owner', 'note' => $data['reason']]);
            $this->notifyCustomer($claim, 'closed');
            return $claim;
        });
    }
    public function decide(CounterOrderComplaint $complaint, User $actor, array $data): CounterOrderComplaint
    {
        return DB::transaction(function () use ($complaint, $actor, $data) {
            $claim = CounterOrderComplaint::where('user_id', $actor->businessOwnerId())->lockForUpdate()->findOrFail($complaint->id);
            if (! in_array($claim->status, CounterOrderComplaint::OPEN_STATUSES, true)) {
                throw ValidationException::withMessages(['complaint' => 'یہ شکایت پہلے ہی بند کی جا چکی ہے۔']);
            }

            $resolution = $data['resolution'];
            $status = match ($resolution) {
                'rework', 'replacement' => 'rework',
                'rejected' => 'rejected',
                'withdrawn' => 'withdrawn',
                default => 'approved',
            };
            if (in_array($resolution, ['rework', 'replacement'], true) && empty($data['rework_due_on'])) {
                throw ValidationException::withMessages(['rework_due_on' => 'دوبارہ کام مکمل کرنے کی تاریخ درج کریں۔']);
            }
            if (($data['responsible_party'] ?? null) === 'tailor' && empty($data['responsible_tailor_id'])) {
                throw ValidationException::withMessages(['responsible_tailor_id' => 'ذمہ دار درزی منتخب کریں۔']);
            }

            $claim->update([
                'status' => $status,
                'category' => $data['category'],
                'inspection_notes' => $data['inspection_notes'],
                'responsible_party' => $data['responsible_party'],
                'responsible_tailor_id' => $data['responsible_tailor_id'] ?? null,
                'resolution' => $resolution,
                'decision_notes' => $data['decision_notes'],
                'rework_due_on' => $data['rework_due_on'] ?? null,
                'approved_by_user_id' => $actor->id,
                'approved_at' => now(),
                'closed_at' => in_array($status, ['rejected', 'withdrawn'], true) ? now() : null,
            ]);

            $linkedOrderId = $this->linkedOrderId($claim);
            if ($status === 'rework' && $linkedOrderId) {
                $order = Order::where('userId', $actor->businessOwnerId())->lockForUpdate()->findOrFail($linkedOrderId);
                $from = $order->status;
                $order->update(['status' => 'stitching', 'status_changed_at' => now(), 'ready_at' => null, 'rack_no' => null]);
                OrderStatusHistory::create(['order_id' => $order->id, 'user_id' => $actor->id, 'tailor_id' => $order->tailorId, 'from_status' => $from, 'to_status' => 'stitching', 'changed_by_type' => 'shop_owner', 'note' => 'شکایت #'.$claim->id.' کے تحت دوبارہ کام: '.$data['decision_notes']]);
            }

            $this->notifyCustomer($claim, $status);
            return $claim->fresh(['evidence', 'recoveries']);
        });
    }

    public function recover(CounterOrderComplaint $complaint, User $actor, array $data): TailoringRecovery
    {
        return DB::transaction(function () use ($complaint, $actor, $data) {
            $claim = CounterOrderComplaint::where('user_id', $actor->businessOwnerId())->lockForUpdate()->findOrFail($complaint->id);
            if (! in_array($claim->status, ['approved', 'settled', 'rework'], true)) {
                throw ValidationException::withMessages(['recovery' => 'ذمہ داری منظور ہونے کے بعد ہی ریکوری درج ہو سکتی ہے۔']);
            }
            $amount = round((float) $data['amount'], 2);
            $liability = round((float) ($claim->approved_tailoring_refund ?? $claim->requested_tailoring_refund)
                + (float) ($claim->approved_cloth_compensation ?? $claim->requested_cloth_compensation), 2);
            $alreadyRecovered = (float) TailoringRecovery::where('complaint_id', $claim->id)->sum('amount');
            if ($amount > round($liability - $alreadyRecovered, 2)) {
                throw ValidationException::withMessages(['amount' => 'کل ریکوری منظور شدہ نقصان سے زیادہ نہیں ہو سکتی۔']);
            }
            $tailor = null;
            if ($data['method'] !== 'shop_absorbed') {
                $tailor = Tailor::where('user_id', $actor->businessOwnerId())->lockForUpdate()->findOrFail($data['tailor_id']);
            }
            if ($data['method'] === 'security_deposit') {
                if ($amount > (float) $tailor->security_deposit) {
                    throw ValidationException::withMessages(['amount' => 'ریکوری موجودہ سیکیورٹی ڈپازٹ سے زیادہ نہیں ہو سکتی۔']);
                }
                $tailor->decrement('security_deposit', $amount);
                $tailor->securityDepositTransactions()->create(['user_id' => $actor->businessOwnerId(), 'transaction_type' => TailorSecurityDepositTransaction::TYPE_DEDUCTED, 'amount' => $amount, 'transaction_date' => now()->toDateString(), 'note' => 'سلائی شکایت #'.$claim->id.' کی ریکوری']);
            }
            $linkedOrderId = $this->linkedOrderId($claim);
            if ($data['method'] === 'unpaid_earnings' && $linkedOrderId) {
                $order = Order::where('userId', $actor->businessOwnerId())->lockForUpdate()->findOrFail($linkedOrderId);
                $available = max(0, $order->tailorAmountDue() - (float) $order->tailor_paid_amount);
                if ($amount > $available) {
                    throw ValidationException::withMessages(['amount' => 'غیر ادا شدہ اجرت سے زیادہ رقم ریکور نہیں کی جا سکتی۔']);
                }
                TailorRecord::create(['tailor_id' => $tailor->id, 'order_id' => $order->id, 'amount' => -$amount, 'comment' => 'complaint_recovery', 'Note' => 'شکایت #'.$claim->id]);
                $worker = $tailor->productionWorker;
                if ($worker) {
                    WorkerLedgerEntry::create(['user_id' => $actor->businessOwnerId(), 'production_worker_id' => $worker->id, 'entry_type' => 'recovery', 'amount' => -$amount, 'entry_date' => now()->toDateString(), 'reference_type' => CounterOrderComplaint::class, 'reference_id' => $claim->id, 'notes' => 'سلائی شکایت ریکوری']);
                }
            }
            return TailoringRecovery::create([
                'complaint_id' => $claim->id, 'order_id' => $linkedOrderId, 'tailor_id' => $tailor?->id,
                'user_id' => $actor->businessOwnerId(), 'recorded_by_user_id' => $actor->id,
                'method' => $data['method'], 'amount' => $amount,
                'status' => in_array($data['method'], ['future_pay'], true) ? 'pending' : 'recovered',
                'note' => $data['note'] ?? null, 'recovered_at' => $data['method'] === 'future_pay' ? null : now(),
            ]);
        });
    }

    public function completeRework(CounterOrderComplaint $complaint, User $actor, string $note): void
    {
        DB::transaction(function () use ($complaint, $actor, $note) {
            $claim = CounterOrderComplaint::where('user_id', $actor->businessOwnerId())->lockForUpdate()->findOrFail($complaint->id);
            if ($claim->status !== 'rework') throw ValidationException::withMessages(['complaint' => 'یہ شکایت دوبارہ کام کے مرحلے میں نہیں ہے۔']);
            $claim->update(['status' => 'closed', 'rework_completed_at' => now(), 'closed_at' => now(), 'decision_notes' => trim($claim->decision_notes."\n".$note)]);
            $this->notifyCustomer($claim, 'closed');
        });
    }

    private function notifyCustomer(CounterOrderComplaint $claim, string $event): void
    {
        $order = $claim->order;
        if (! $order && $claim->item?->source_record_type === 'tailoring_order') {
            $order = Order::find($claim->item->source_record_id);
        }
        $customer = $order?->customers;
        if ($customer) $customer->notify(new TailoringComplaintNotification($claim, $event));
    }

    private function linkedOrderId(CounterOrderComplaint $claim): ?int
    {
        if ($claim->order_id) return (int) $claim->order_id;
        $item = $claim->relationLoaded('item') ? $claim->item : $claim->item()->first();
        return $item?->source_record_type === 'tailoring_order' ? (int) $item->source_record_id : null;
    }
}
