<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderWorkAssignment;
use App\Models\ProductionWorker;
use App\Models\WorkerCompensationPlan;
use App\Models\WorkerLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderWorkAssignmentController extends Controller
{
    public function index(int $order)
    {
        $order = $this->ownedOrder($order)->load(['customers', 'workAssignments.worker', 'workAssignments.workType']);
        $workers = ProductionWorker::where('user_id', Auth::user()->businessOwnerId())
            ->where('active', true)->with(['skills', 'compensationPlans' => fn ($query) => $query->where('active', true)])
            ->orderBy('name')->get();

        return view('order.workforce', compact('order', 'workers'));
    }

    public function store(Request $request, int $order)
    {
        $order = $this->ownedOrder($order);
        if ($order->status === 'delivered') {
            throw ValidationException::withMessages(['order' => 'حوالہ شدہ آرڈر پر نیا کام تفویض نہیں کیا جا سکتا۔']);
        }
        $ownerId = Auth::user()->businessOwnerId();
        $validated = $request->validate([
            'production_worker_id' => ['required', Rule::exists('production_workers', 'id')->where('user_id', $ownerId)->where('active', true)],
            'work_type_id' => ['required', Rule::exists('work_types', 'id')->where('user_id', $ownerId)->where('active', true)],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $worker = ProductionWorker::where('user_id', $ownerId)->findOrFail($validated['production_worker_id']);
        if (! $worker->skills()->whereKey($validated['work_type_id'])->exists()) {
            throw ValidationException::withMessages(['work_type_id' => 'منتخب کام اس ورکر کی مہارت میں شامل نہیں ہے۔']);
        }
        $plan = WorkerCompensationPlan::where('user_id', $ownerId)
            ->where('production_worker_id', $worker->id)
            ->where('work_type_id', $validated['work_type_id'])
            ->where('active', true)->latest('id')->first();
        if (! $plan) {
            throw ValidationException::withMessages(['production_worker_id' => 'اس کام کے لیے ورکر کی اجرت پہلے مقرر کریں۔']);
        }
        if (! in_array($plan->method, ['per_piece', 'hybrid'], true) || (float) $plan->rate <= 0) {
            throw ValidationException::withMessages(['production_worker_id' => 'آرڈر پر فی عدد اجرت کے لیے فعال فی عدد یا مشترکہ اصول ضروری ہے۔']);
        }
        if ($order->workAssignments()->where('production_worker_id', $worker->id)
            ->where('work_type_id', $validated['work_type_id'])->whereNotIn('status', ['cancelled'])->exists()) {
            throw ValidationException::withMessages(['production_worker_id' => 'یہ کام پہلے ہی اس ورکر کو دیا جا چکا ہے۔']);
        }

        $quantity = (float) $validated['quantity'];
        try {
            OrderWorkAssignment::create([
                'user_id' => $ownerId,
                'order_id' => $order->id,
                'production_worker_id' => $worker->id,
                'work_type_id' => $validated['work_type_id'],
                'compensation_plan_id' => $plan->id,
                'active_assignment_key' => OrderWorkAssignment::activeKey(
                    $ownerId,
                    $order->id,
                    $worker->id,
                    (int) $validated['work_type_id'],
                ),
                'quantity' => $quantity,
                'rate' => (float) $plan->rate,
                'amount' => round($quantity * (float) $plan->rate, 2),
                'status' => 'assigned',
                'assigned_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'active_assignment_key')) {
                throw ValidationException::withMessages(['production_worker_id' => 'یہ کام پہلے ہی اس ورکر کو دیا جا چکا ہے۔']);
            }

            throw $exception;
        }

        return back()->with('success', 'کام ورکر کو تفویض کر دیا گیا ہے۔');
    }

    public function updateStatus(Request $request, int $order, int $assignment)
    {
        $order = $this->ownedOrder($order);
        $assignment = $order->workAssignments()->where('user_id', Auth::user()->businessOwnerId())->findOrFail($assignment);
        $validated = $request->validate(['status' => ['required', Rule::in(['in_progress', 'completed', 'cancelled'])]]);
        $allowed = match ($assignment->status) {
            'assigned' => ['in_progress', 'completed', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            default => [],
        };
        if (! in_array($validated['status'], $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'اس کام کا مرحلہ اس طرح تبدیل نہیں ہو سکتا۔']);
        }

        DB::transaction(function () use ($assignment, $validated) {
            $assignment->update([
                'status' => $validated['status'],
                'active_assignment_key' => $validated['status'] === 'cancelled' ? null : $assignment->active_assignment_key,
                'completed_at' => $validated['status'] === 'completed' ? now() : null,
            ]);
            if ($validated['status'] === 'completed' && (float) $assignment->amount > 0) {
                WorkerLedgerEntry::updateOrCreate(
                    ['legacy_key' => 'assignment-earning:'.$assignment->id],
                    [
                        'user_id' => $assignment->user_id,
                        'production_worker_id' => $assignment->production_worker_id,
                        'assignment_id' => $assignment->id,
                        'entry_type' => 'earning',
                        'amount' => (float) $assignment->amount,
                        'entry_date' => now()->toDateString(),
                        'reference_type' => OrderWorkAssignment::class,
                        'reference_id' => $assignment->id,
                        'notes' => 'تفویض شدہ کام مکمل',
                    ],
                );
            }
        });

        return back()->with('success', 'کام کا مرحلہ تبدیل کر دیا گیا ہے۔');
    }

    private function ownedOrder(int $id): Order
    {
        return Order::where('userId', Auth::user()->businessOwnerId())->findOrFail($id);
    }
}
