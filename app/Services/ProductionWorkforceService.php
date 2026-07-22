<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderWorkAssignment;
use App\Models\ProductionWorker;
use App\Models\Tailor;
use App\Models\TailorRecord;
use App\Models\Tailorsalary;
use App\Models\WorkType;
use App\Models\WorkerCompensationPlan;
use App\Models\WorkerLedgerEntry;

class ProductionWorkforceService
{
    public function syncTailor(Tailor $tailor): ProductionWorker
    {
        $workType = WorkType::updateOrCreate(
            ['user_id' => $tailor->user_id, 'code' => 'stitching'],
            ['name' => 'سلائی', 'category' => 'production', 'is_system' => true, 'active' => true],
        );
        $worker = ProductionWorker::updateOrCreate(
            ['legacy_tailor_id' => $tailor->id],
            [
                'user_id' => $tailor->user_id,
                'name' => $tailor->name,
                'phone' => $tailor->phone_number1,
                'email' => $tailor->email,
                'relationship_type' => 'contractor',
                'active' => true,
            ],
        );
        $worker->skills()->syncWithoutDetaching([$workType->id]);
        $tailor->tailorsalary()->each(fn (Tailorsalary $rate) => $this->syncRate($rate, $worker, $workType));

        return $worker;
    }

    public function syncRate(
        Tailorsalary $rate,
        ?ProductionWorker $worker = null,
        ?WorkType $workType = null,
    ): WorkerCompensationPlan {
        $rate->loadMissing('tailor');
        $worker ??= $this->syncTailorWithoutRates($rate->tailor);
        $workType ??= WorkType::firstOrCreate(
            ['user_id' => $rate->tailor->user_id, 'code' => 'stitching'],
            ['name' => 'سلائی', 'category' => 'production', 'is_system' => true, 'active' => true],
        );
        $worker->skills()->syncWithoutDetaching([$workType->id]);

        return WorkerCompensationPlan::updateOrCreate(
            ['legacy_tailor_rate_id' => $rate->id],
            [
                'user_id' => $rate->tailor->user_id,
                'production_worker_id' => $worker->id,
                'work_type_id' => $workType->id,
                'method' => 'per_piece',
                'rate' => (float) $rate->price,
                'fixed_salary' => 0,
                'commission_percent' => 0,
                'active' => true,
            ],
        );
    }

    public function retireRate(Tailorsalary $rate): void
    {
        WorkerCompensationPlan::where('legacy_tailor_rate_id', $rate->id)->update(['active' => false]);
    }

    public function syncOrder(Order $order): ?OrderWorkAssignment
    {
        $order->loadMissing('tailor');
        if (! $order->tailor || ! $order->tailorId) {
            return null;
        }

        $worker = $this->syncTailor($order->tailor);
        $workType = WorkType::where('user_id', $order->userId)->where('code', 'stitching')->firstOrFail();
        $plan = $order->rateId
            ? WorkerCompensationPlan::where('legacy_tailor_rate_id', $order->rateId)->first()
            : null;
        $quantity = max(1, (float) $order->suitQuantity);
        $amount = round($quantity * (float) $order->tailor_price, 2);
        $completed = in_array($order->status, ['ready', 'delivered'], true);
        $status = $completed ? 'completed' : (in_array($order->status, ['cutting', 'stitching', 'trial'], true) ? 'in_progress' : 'assigned');

        $assignment = OrderWorkAssignment::updateOrCreate(
            ['legacy_key' => 'tailor-order:'.$order->id],
            [
                'user_id' => (int) $order->userId,
                'order_id' => $order->id,
                'production_worker_id' => $worker->id,
                'work_type_id' => $workType->id,
                'compensation_plan_id' => $plan?->id,
                'active_assignment_key' => OrderWorkAssignment::activeKey(
                    (int) $order->userId,
                    $order->id,
                    $worker->id,
                    $workType->id,
                ),
                'quantity' => $quantity,
                'rate' => (float) $order->tailor_price,
                'amount' => $amount,
                'status' => $status,
                'assigned_at' => $order->created_at ?? now(),
                'completed_at' => $completed ? ($order->ready_at ?? $order->delivered_at ?? now()) : null,
                'notes' => $order->remarks,
            ],
        );

        if ($completed) {
            WorkerLedgerEntry::updateOrCreate(
                ['legacy_key' => 'tailor-order-earning:'.$order->id],
                [
                    'user_id' => (int) $order->userId,
                    'production_worker_id' => $worker->id,
                    'assignment_id' => $assignment->id,
                    'entry_type' => 'earning',
                    'amount' => $amount,
                    'entry_date' => optional($assignment->completed_at)->toDateString() ?? now()->toDateString(),
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'notes' => 'سلائی کا کام مکمل',
                ],
            );
        }

        return $assignment;
    }

    public function recordTailorPayment(Order $order, TailorRecord $record): void
    {
        $assignment = $this->syncOrder($order);
        if (! $assignment) {
            return;
        }

        WorkerLedgerEntry::updateOrCreate(
            ['legacy_key' => 'tailor-record-payment:'.$record->id],
            [
                'user_id' => (int) $order->userId,
                'production_worker_id' => $assignment->production_worker_id,
                'assignment_id' => $assignment->id,
                'entry_type' => 'payment',
                'amount' => -abs((float) $record->amount),
                'entry_date' => optional($record->created_at)->toDateString() ?? now()->toDateString(),
                'reference_type' => TailorRecord::class,
                'reference_id' => $record->id,
                'notes' => 'درزی کو ادائیگی',
            ],
        );
    }

    private function syncTailorWithoutRates(Tailor $tailor): ProductionWorker
    {
        return ProductionWorker::updateOrCreate(
            ['legacy_tailor_id' => $tailor->id],
            [
                'user_id' => $tailor->user_id,
                'name' => $tailor->name,
                'phone' => $tailor->phone_number1,
                'email' => $tailor->email,
                'relationship_type' => 'contractor',
                'active' => true,
            ],
        );
    }
}
