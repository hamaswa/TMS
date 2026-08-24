<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Order;
use App\Models\OrderNotificationDelivery;
use App\Models\OrderStatusHistory;
use App\Models\Tailor;
use App\Models\TailorRecord;
use App\Services\OrderLifecycleNotificationService;
use App\Services\ProductionWorkforceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TailorJobController extends Controller
{
    public function adminIndex(Request $request)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $detailedWorkflow = $this->usesDetailedWorkflow($ownerId);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in($detailedWorkflow ? Order::STATUSES : ['workshop', 'ready'])],
            'tailor_id' => ['nullable', 'integer'],
            'due' => ['nullable', Rule::in(['today', 'overdue'])],
            'q' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', Rule::in(['15', '25', '50', '100'])],
        ]);

        $tailors = Tailor::where('user_id', $ownerId)->orderBy('name')->get();
        if (! empty($filters['tailor_id'])) {
            $tailors->firstWhere('id', (int) $filters['tailor_id']) ?: abort(404);
        }

        $orders = $this->jobQuery($ownerId)
            ->when($detailedWorkflow && filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(! $detailedWorkflow && ($filters['status'] ?? null) === 'workshop', fn ($query) => $query
                ->whereIn('status', ['assigned', 'cutting', 'stitching', 'trial']))
            ->when(! $detailedWorkflow && ($filters['status'] ?? null) === 'ready', fn ($query) => $query->where('status', 'ready'))
            ->when($filters['tailor_id'] ?? null, fn ($query, $tailorId) => $query->where('tailorId', $tailorId))
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('id', $search)
                        ->orWhere('suitNum', 'like', "%{$search}%")
                        ->orWhereHas('customers', fn ($customer) => $customer
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_number1', 'like', "%{$search}%"));
                });
            })
            ->when($filters['from_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when(($filters['due'] ?? null) === 'today', fn ($query) => $query->whereDate('returnDate', today()))
            ->when(($filters['due'] ?? null) === 'overdue', fn ($query) => $query
                ->whereDate('returnDate', '<', today())
                ->where('status', '!=', 'delivered'))
            ->orderByRaw("CASE WHEN status = 'delivered' THEN 1 ELSE 0 END")
            ->orderBy('returnDate')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();

        return view('tailor-jobs.index', [
            'orders' => $orders,
            'tailors' => $tailors,
            'isTailor' => false,
            'detailedWorkflow' => $detailedWorkflow,
            'filters' => $filters,
            'stats' => $this->statsFor($ownerId),
        ]);
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
        ]);
    }

    public function updateStatus(Request $request, int $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $actor = Auth::check() ? 'shop_owner' : 'tailor';
        $job = $this->ownedJob($order);
        $nextStatus = $validated['status'];

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

        DB::transaction(function () use ($job, $nextStatus, $validated, $actor) {
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

        return back()->with('success', $message);
    }

    public function updateLegacyStatus(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer'],
            'order_status' => ['required', Rule::in(['start', 'complete', 'deliver'])],
        ]);
        $nextStatus = match ($validated['order_status']) {
            'start' => 'cutting',
            'complete' => 'ready',
            'deliver' => 'delivered',
        };
        $job = $this->ownedJob((int) $validated['order_id']);
        $actor = Auth::check() ? 'shop_owner' : 'tailor';
        $ownerId = (int) $job->userId;

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
            return back()->with('success', 'آرڈر کی حالت پہلے ہی منتخب شدہ حالت پر ہے۔');
        }

        DB::transaction(function () use ($job, $nextStatus, $actor, $ownerId) {
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
            ]);
        });

        $delivery = app(OrderLifecycleNotificationService::class)->send($job->fresh(), $nextStatus);
        $message = 'آرڈر کی حالت اپ ڈیٹ کر دی گئی ہے۔';
        if ($delivery && $delivery->status !== 'sent') {
            $message .= ' گاہک کی اطلاع پر توجہ درکار ہے۔';
        }

        return back()->with('success', $message);
    }

    public function updatePayment(Request $request, int $order)
    {
        $job = Order::where('userId', Auth::user()->businessOwnerId())->findOrFail($order);
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
        return Order::with(['tailor', 'customers', 'notificationDeliveries'])->where('userId', $userId);
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
