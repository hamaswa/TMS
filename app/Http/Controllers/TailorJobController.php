<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Order;
use App\Models\OrderNotificationDelivery;
use App\Models\OrderStatusHistory;
use App\Models\rack as Rack;
use App\Models\Tailor;
use App\Models\TailorRecord;
use App\Models\Tailorsalary;
use App\Services\OrderLifecycleNotificationService;
use App\Services\ProductionWorkforceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TailorJobController extends Controller
{
    public function adminIndex()
    {
        return redirect()->route('admin.order.total');
    }

    public function assignTailor(Request $request, int $order)
    {
        $validated = $request->validateWithBag('tailorAssignment'.$order, [
            'tailor_id' => ['required', 'integer'],
            'tailor_price' => ['required', 'regex:/^\d+-.+$/', 'max:255'],
            'confirm_reassign' => ['nullable', 'boolean'],
        ], [
            'tailor_id.required' => 'درزی منتخب کریں۔',
            'tailor_price.required' => 'سلائی کی شرح منتخب کریں۔',
        ]);
        $ownerId = Auth::user()->businessOwnerId();
        $tailor = Tailor::where('user_id', $ownerId)->findOrFail($validated['tailor_id']);
        [$rateId] = explode('-', $validated['tailor_price'], 2);
        $tailorRate = Tailorsalary::where('tailor_id', $tailor->id)->findOrFail($rateId);
        $tailorPrice = $tailorRate->price;

        DB::transaction(function () use ($order, $ownerId, $tailor, $rateId, $tailorPrice, $validated) {
            $job = Order::where('userId', $ownerId)->lockForUpdate()->findOrFail($order);
            $isReassignment = $job->status !== 'unassigned' || $job->tailorId;
            if ($isReassignment && ! ($validated['confirm_reassign'] ?? false)) {
                throw ValidationException::withMessages([
                    'tailor_id' => 'درزی پہلے سے مقرر ہے۔ تبدیلی کی تصدیق کرکے دوبارہ کوشش کریں۔',
                ]);
            }
            if ($isReassignment && (in_array($job->status, ['ready', 'delivered'], true) || (float) $job->tailor_paid_amount > 0)) {
                throw ValidationException::withMessages([
                    'tailor_id' => 'تیار، حوالہ شدہ یا درزی کو ادا شدہ کام کا درزی یہاں تبدیل نہیں کیا جا سکتا۔',
                ]);
            }
            $previousTailor = $job->tailor?->name ?: 'مقرر نہیں';
            $fromStatus = $job->status;
            $job->update([
                'tailorId' => $tailor->id,
                'rateId' => $rateId,
                'tailor_price' => $tailorPrice,
                'status' => $isReassignment ? $job->status : 'assigned',
                'status_changed_at' => now(),
            ]);
            OrderStatusHistory::create([
                'order_id' => $job->id,
                'user_id' => Auth::id(),
                'tailor_id' => $tailor->id,
                'from_status' => $fromStatus,
                'to_status' => $isReassignment ? $fromStatus : 'assigned',
                'changed_by_type' => 'shop_owner',
                'note' => $isReassignment
                    ? "درزی/شرح تبدیل: {$previousTailor} سے {$tailor->name}، رقم Rs. ".number_format((float) $tailorPrice, 2)
                    : 'درزی اور سلائی شرح مقرر کی گئی۔',
            ]);
            app(ProductionWorkforceService::class)->syncOrder($job->fresh());
        });

        $message = 'درزی اور سلائی شرح محفوظ ہو گئی ہے۔';
        if ($request->expectsJson()) {
            $job = Order::with('tailor')->where('userId', $ownerId)->findOrFail($order);

            return response()->json([
                'message' => $message,
                'tailor' => [
                    'id' => $job->tailor?->id,
                    'name' => $job->tailor?->name,
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    public function tailorIndex()
    {
        $tailor = $this->sessionTailor();
        $detailedWorkflow = $this->usesDetailedWorkflow((int) $tailor->user_id);
        $orders = $this->jobQuery($tailor->user_id)
            ->where('tailorId', $tailor->id)
            ->orderByRaw("CASE WHEN status = 'delivered' THEN 1 ELSE 0 END")
            ->orderBy('returnDate')
            ->paginate(25);

        return view('tailor-jobs.index', [
            'orders' => $orders,
            'tailors' => collect([$tailor]),
            'isTailor' => true,
            'detailedWorkflow' => $detailedWorkflow,
            'filters' => [],
            'stats' => $this->statsFor($tailor->user_id, $tailor->id),
            'racks' => Rack::where('user_id', $tailor->user_id)->orderBy('rack_no')->get(),
        ]);
    }

    public function storeRack(Request $request)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $validated = $request->validate([
            'rack_no' => [
                'required', 'string', 'max:100',
                Rule::unique('racks', 'rack_no')->where('user_id', $ownerId),
            ],
        ], [
            'rack_no.required' => 'ریک نمبر درج کریں۔',
            'rack_no.unique' => 'یہ ریک نمبر پہلے سے موجود ہے۔',
        ]);

        $rack = new Rack(['rack_no' => trim($validated['rack_no'])]);
        $rack->user_id = $ownerId;
        $rack->save();

        return back()->with('success', 'نیا ریک نمبر شامل کر دیا گیا ہے۔');
    }

    public function updateStatus(Request $request, int $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
            'rack_no' => ['nullable', 'string', 'max:100'],
        ]);

        $actor = Auth::check() ? 'shop_owner' : 'tailor';
        $job = $this->ownedJob($order);
        $nextStatus = $validated['status'];
        if ($job->status === 'ready' && $nextStatus !== 'delivered' && blank($validated['note'] ?? null)) {
            throw ValidationException::withMessages([
                'note' => 'تیار آرڈر واپس کارخانے میں بھیجنے کی وجہ درج کریں۔',
            ]);
        }
        $rackNo = $this->validatedReadyRack($validated['rack_no'] ?? null, (int) $job->userId, $nextStatus);

        if (! in_array($nextStatus, $job->nextStatuses(), true)) {
            throw ValidationException::withMessages([
                'status' => "The job cannot move from {$job->status} to {$nextStatus}.",
            ]);
        }

        if ($actor === 'tailor' && $nextStatus === 'delivered') {
            throw ValidationException::withMessages([
                'status' => 'Only the shop owner can mark an order as delivered.',
            ]);
        }

        DB::transaction(function () use ($job, $nextStatus, $validated, $actor, $rackNo) {
            $job = Order::lockForUpdate()->findOrFail($job->id);
            $fromStatus = $job->status;

            if (! in_array($nextStatus, $job->nextStatuses(), true)) {
                throw ValidationException::withMessages(['status' => 'The job status changed. Refresh and try again.']);
            }

            $updates = ['status' => $nextStatus, 'status_changed_at' => now()];
            if ($nextStatus === 'cutting' && ! $job->started_at) {
                $updates['started_at'] = now();
            }
            if ($nextStatus === 'ready') {
                $updates['ready_at'] = now();
                $updates['rack_no'] = $rackNo;
            }
            if ($fromStatus === 'ready' && $nextStatus !== 'delivered') {
                $updates['ready_at'] = null;
                $updates['rack_no'] = null;
            }
            if ($nextStatus === 'delivered') {
                $updates['delivered_at'] = now();
            }
            $job->update($updates);
            app(ProductionWorkforceService::class)->syncOrder($job);

            OrderStatusHistory::create([
                'order_id' => $job->id,
                'user_id' => Auth::id(),
                'tailor_id' => $job->tailorId,
                'from_status' => $fromStatus,
                'to_status' => $nextStatus,
                'changed_by_type' => $actor,
                'note' => $validated['note'] ?? null,
            ]);
        });

        $delivery = app(OrderLifecycleNotificationService::class)->send(
            $job->fresh(),
            $nextStatus,
            $validated['note'] ?? null,
        );
        $message = 'کام کا مرحلہ اپ ڈیٹ کر دیا گیا ہے۔';
        if ($delivery && $delivery->status !== 'sent') {
            $message .= ' گاہک کی اطلاع پر توجہ درکار ہے۔';
        }

        return $this->statusUpdateResponse($request, $job->fresh(), $message, true);
    }

    public function updateLegacyStatus(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer'],
            'order_status' => ['required', Rule::in(['start', 'complete', 'deliver'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'rack_no' => ['nullable', 'string', 'max:100'],
        ]);
        $nextStatus = match ($validated['order_status']) {
            'start' => 'cutting',
            'complete' => 'ready',
            'deliver' => 'delivered',
        };
        $job = $this->ownedJob((int) $validated['order_id']);
        if ($job->status === 'ready' && $nextStatus === 'cutting' && blank($validated['note'] ?? null)) {
            throw ValidationException::withMessages([
                'note' => 'تیار آرڈر واپس کارخانے میں بھیجنے کی وجہ درج کریں۔',
            ]);
        }
        $actor = Auth::check() ? 'shop_owner' : 'tailor';
        $ownerId = (int) $job->userId;
        $rackNo = $this->validatedReadyRack($validated['rack_no'] ?? null, $ownerId, $nextStatus);

        if ($job->status === 'unassigned' || ! $job->tailorId) {
            throw ValidationException::withMessages([
                'order_status' => 'کام شروع کرنے سے پہلے درزی مقرر کریں۔',
            ]);
        }

        if ($nextStatus === 'delivered' && $actor !== 'shop_owner') {
            throw ValidationException::withMessages([
                'order_status' => 'صرف دکان کا مالک آرڈر گاہک کے حوالے شدہ کے طور پر محفوظ کر سکتا ہے۔',
            ]);
        }

        if ($nextStatus === 'delivered' && $job->status !== 'ready') {
            throw ValidationException::withMessages([
                'order_status' => 'صرف تیار آرڈر کو گاہک کے حوالے کیا جا سکتا ہے۔',
            ]);
        }

        if ($job->status === $nextStatus) {
            return $this->statusUpdateResponse(
                $request,
                $job,
                'آرڈر کی حالت پہلے ہی منتخب شدہ حالت پر ہے۔',
                false,
            );
        }

        DB::transaction(function () use ($job, $nextStatus, $actor, $ownerId, $rackNo, $validated) {
            $job = Order::where('userId', $ownerId)
                ->lockForUpdate()
                ->findOrFail($job->id);
            $fromStatus = $job->status;

            if ($nextStatus === 'delivered' && $fromStatus !== 'ready') {
                throw ValidationException::withMessages([
                    'order_status' => 'آرڈر کی حالت تبدیل ہو چکی ہے۔ صفحہ تازہ کر کے دوبارہ کوشش کریں۔',
                ]);
            }

            $updates = ['status' => $nextStatus, 'status_changed_at' => now()];

            if ($nextStatus === 'cutting' && ! $job->started_at) {
                $updates['started_at'] = now();
            }
            if ($nextStatus === 'ready') {
                $updates['ready_at'] = now();
                $updates['rack_no'] = $rackNo;
            }
            if ($fromStatus === 'ready' && $nextStatus === 'cutting') {
                $updates['ready_at'] = null;
                $updates['rack_no'] = null;
            }
            if ($nextStatus === 'delivered') {
                $updates['delivered_at'] = now();
            }

            $job->update($updates);
            app(ProductionWorkforceService::class)->syncOrder($job);

            OrderStatusHistory::create([
                'order_id' => $job->id,
                'user_id' => Auth::id(),
                'tailor_id' => $job->tailorId,
                'from_status' => $fromStatus,
                'to_status' => $nextStatus,
                'changed_by_type' => $actor,
                'note' => $validated['note'] ?? null,
            ]);
        });

        $delivery = app(OrderLifecycleNotificationService::class)->send(
            $job->fresh(),
            $nextStatus,
            $validated['note'] ?? null,
        );
        $message = 'آرڈر کی حالت اپ ڈیٹ کر دی گئی ہے۔';
        if ($delivery && $delivery->status !== 'sent') {
            $message .= ' گاہک کی اطلاع پر توجہ درکار ہے۔';
        }

        return $this->statusUpdateResponse($request, $job->fresh(), $message, false);
    }

    private function statusUpdateResponse(Request $request, Order $job, string $message, bool $detailed)
    {
        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        $job->loadMissing('tailor');
        $isDelivered = $job->status === 'delivered';
        $isReady = $job->status === 'ready';
        $isOverdue = ! $isReady && ! $isDelivered && $job->returnDate && now()->startOfDay()->gt($job->returnDate);
        $labels = $detailed ? Order::STATUS_LABELS : [
            'unassigned' => 'درزی مقرر ہونا باقی',
            'assigned' => 'کارخانے میں ہے',
            'cutting' => 'کارخانے میں ہے',
            'stitching' => 'کارخانے میں ہے',
            'trial' => 'کارخانے میں ہے',
            'ready' => 'تیار ہے',
            'delivered' => 'حوالہ شدہ',
        ];
        $nextActions = $detailed
            ? $job->nextStatusOptions()
            : match ($job->status) {
                'ready' => [
                    ['value' => 'start', 'label' => 'کارخانے میں ہے'],
                    ['value' => 'deliver', 'label' => 'حوالہ کریں'],
                ],
                'delivered', 'unassigned' => [],
                default => [['value' => 'complete', 'label' => 'تیار ہے']],
            };
        $history = $job->statusHistory()->latest('id')->first();

        return response()->json([
            'message' => $message,
            'order_id' => $job->id,
            'status' => $job->status,
            'label' => $labels[$job->status] ?? $job->status,
            'state' => $isOverdue ? 'overdue' : ($isDelivered ? 'delivered' : ($isReady ? 'ready' : 'workshop')),
            'next_actions' => $nextActions,
            'tailor_change_allowed' => ! $isReady && ! $isDelivered && (float) $job->tailor_paid_amount <= 0,
            'work_locked' => $isReady || $isDelivered,
            'history' => $history ? [
                'from' => $labels[$history->from_status] ?? $history->from_status,
                'to' => $labels[$history->to_status] ?? $history->to_status,
                'note' => $history->note,
                'time' => optional($history->created_at)->format('d-m-Y h:i A'),
            ] : null,
        ]);
    }

    private function validatedReadyRack(?string $rackNo, int $ownerId, string $nextStatus): ?string
    {
        if ($nextStatus !== 'ready') {
            return null;
        }

        $rackNo = trim((string) $rackNo);
        if ($rackNo === '') {
            return null;
        }

        return Rack::where('user_id', $ownerId)->where('rack_no', $rackNo)->value('rack_no')
            ?? throw ValidationException::withMessages([
                'rack_no' => 'منتخب ریک نمبر دستیاب نہیں ہے۔',
            ]);
    }

    public function updatePayment(Request $request, int $order)
    {
        $job = Order::where('userId', Auth::user()->businessOwnerId())->findOrFail($order);
        if ($job->status === 'unassigned' || ! $job->tailorId) {
            throw ValidationException::withMessages([
                'paid_amount' => 'ادائیگی درج کرنے سے پہلے درزی مقرر کریں۔',
            ]);
        }
        $earned = $job->tailorAmountDue();
        $validated = $request->validateWithBag('tailorPayment'.$job->id, [
            'paid_amount' => ['required', 'numeric', 'min:' . (float) $job->tailor_paid_amount, 'max:' . $earned],
        ], [
            'paid_amount.required' => 'ادا شدہ رقم درج کریں۔',
            'paid_amount.numeric' => 'ادا شدہ رقم درست عدد میں درج کریں۔',
            'paid_amount.min' => 'ادا شدہ رقم پہلے سے محفوظ رقم سے کم نہیں ہو سکتی۔',
            'paid_amount.max' => 'ادا شدہ رقم درزی کی کل کمائی سے زیادہ نہیں ہو سکتی۔',
        ]);

        DB::transaction(function () use ($job, $validated, $earned) {
            $job = Order::where('userId', Auth::user()->businessOwnerId())->lockForUpdate()->findOrFail($job->id);
            $newPaid = round((float) $validated['paid_amount'], 2);
            $oldPaid = (float) $job->tailor_paid_amount;
            $status = $newPaid <= 0 ? 'unpaid' : ($newPaid >= $earned ? 'paid' : 'partial');

            $job->update([
                'tailor_paid_amount' => $newPaid,
                'tailor_payment_status' => $status,
            ]);

            if ($newPaid > $oldPaid) {
                $record = TailorRecord::create([
                    'tailor_id' => $job->tailorId,
                    'order_id' => $job->id,
                    'amount' => $newPaid - $oldPaid,
                    'comment' => 'salary',
                    'Note' => 'Job payment recorded from lifecycle board',
                ]);
                app(ProductionWorkforceService::class)->recordTailorPayment($job, $record);
            }
        });

        return back()->with('success', 'درزی کی ادائیگی اپ ڈیٹ کر دی گئی ہے۔');
    }

    public function retryNotification(int $order, int $delivery)
    {
        $job = Order::where('userId', Auth::user()->businessOwnerId())->findOrFail($order);
        $notificationDelivery = OrderNotificationDelivery::where('user_id', Auth::user()->businessOwnerId())
            ->where('order_id', $job->id)
            ->findOrFail($delivery);

        $result = app(OrderLifecycleNotificationService::class)->send(
            $job,
            $notificationDelivery->stage,
        );

        if ($result?->status !== 'sent') {
            return back()->withErrors([
                'notification' => $result?->last_error ?: 'The customer notification could not be sent.',
            ]);
        }

        return back()->with('success', 'گاہک کے ریکارڈ میں اندرونی اطلاع درج کر دی گئی ہے۔');
    }

    private function jobQuery(int $userId)
    {
        return Order::with([
            'tailor', 'customers', 'notificationDeliveries',
            'workAssignments.worker', 'workAssignments.workType',
        ])
            ->withSum([
                'transactions as outstanding_amount' => fn ($query) => $query->where('userId', $userId),
            ], 'remainingBalance')
            ->where('userId', $userId);
    }

    private function ownedJob(int $id): Order
    {
        if (Auth::check()) {
            return Order::where('userId', Auth::user()->businessOwnerId())->findOrFail($id);
        }

        $tailor = $this->sessionTailor();

        return Order::where('userId', $tailor->user_id)
            ->where('tailorId', $tailor->id)
            ->findOrFail($id);
    }

    private function sessionTailor(): Tailor
    {
        abort_unless(session()->has('tailor_id'), 403);

        return Tailor::findOrFail((int) session('tailor_id'));
    }

    private function statsFor(int $userId, ?int $tailorId = null): array
    {
        $query = Order::where('userId', $userId)
            ->when($tailorId, fn ($builder) => $builder->where('tailorId', $tailorId));

        return [
            'active' => (clone $query)->where('status', '!=', 'delivered')->count(),
            'unassigned' => (clone $query)->where('status', 'unassigned')->count(),
            'due_today' => (clone $query)->whereDate('returnDate', today())->where('status', '!=', 'delivered')->count(),
            'overdue' => (clone $query)->whereDate('returnDate', '<', today())->where('status', '!=', 'delivered')->count(),
            'ready' => (clone $query)->where('status', 'ready')->count(),
        ];
    }

    private function usesDetailedWorkflow(int $ownerId): bool
    {
        return Business::tailoringStatusModeForOwner($ownerId) === Business::TAILORING_STATUS_DETAILED;
    }
}
