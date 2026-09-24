<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\OfflineOperation;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Tailor;
use App\Services\OrderLifecycleNotificationService;
use App\Services\ProductionWorkforceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OfflineWorkspaceController extends Controller
{
    public function fallback()
    {
        return response()
            ->view('offline')
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commands' => ['required', 'array', 'max:50'],
            'commands.*.id' => ['required', 'uuid'],
            'commands.*.type' => ['required', Rule::in(['order.status.change'])],
            'commands.*.aggregate_id' => ['required', 'integer', 'min:1'],
            'commands.*.base_version' => ['required', Rule::in(Order::STATUSES)],
            'commands.*.payload' => ['required', 'array'],
            'commands.*.payload.status' => ['required', Rule::in(Order::STATUSES)],
            'commands.*.payload.note' => ['nullable', 'string', 'max:1000'],
            'commands.*.created_at' => ['nullable', 'date'],
        ]);

        $actor = $this->actor();
        $results = [];

        foreach ($validated['commands'] as $command) {
            $results[] = $this->processCommand($command, $actor);
        }

        return response()->json([
            'results' => $results,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function processCommand(array $command, array $actor): array
    {
        return DB::transaction(function () use ($command, $actor) {
            $existing = OfflineOperation::where('operation_uuid', $command['id'])->first();
            if ($existing) {
                if ($existing->actor_type !== $actor['type'] || (int) $existing->actor_id !== $actor['id']) {
                    return $this->conflictResult($command, 'operation_owner_mismatch', null);
                }

                return $existing->result ?? [
                    'id' => $command['id'],
                    'status' => $existing->status,
                ];
            }

            $query = Order::where('userId', $actor['owner_id']);
            if ($actor['type'] === 'tailor') {
                $query->where('tailorId', $actor['id']);
            }

            $order = $query->lockForUpdate()->find($command['aggregate_id']);
            if (! $order) {
                return $this->recordResult($command, $actor, OfflineOperation::STATUS_CONFLICT, [
                    'id' => $command['id'],
                    'status' => OfflineOperation::STATUS_CONFLICT,
                    'reason' => 'order_unavailable',
                    'message' => 'یہ آرڈر اب دستیاب نہیں یا آپ کے اختیار میں نہیں ہے۔',
                ]);
            }

            $target = $command['payload']['status'];
            $base = $command['base_version'];
            $current = (string) $order->status;

            if ($actor['type'] === 'tailor' && $target === 'delivered') {
                return $this->recordConflict($command, $actor, $order, 'tailor_cannot_deliver', 'صرف دکان کا مالک آرڈر گاہک کے حوالے کر سکتا ہے۔');
            }

            if ($current === $target) {
                return $this->recordResult($command, $actor, OfflineOperation::STATUS_APPLIED, [
                    'id' => $command['id'],
                    'status' => OfflineOperation::STATUS_APPLIED,
                    'already_applied' => true,
                    'aggregate_id' => $order->id,
                    'current_status' => $current,
                    'message' => 'یہ تبدیلی پہلے ہی محفوظ ہو چکی تھی۔',
                ]);
            }

            if ($current !== $base && $this->isAheadOf($current, $target)) {
                return $this->recordResult($command, $actor, OfflineOperation::STATUS_SUPERSEDED, [
                    'id' => $command['id'],
                    'status' => OfflineOperation::STATUS_SUPERSEDED,
                    'aggregate_id' => $order->id,
                    'current_status' => $current,
                    'message' => 'سرور پر آرڈر اس مرحلے سے آگے جا چکا ہے؛ پرانی تبدیلی محفوظ نہیں کی گئی۔',
                ]);
            }

            $canApply = $this->canApplyStatusTransition($order, $target, $actor['owner_id']);
            if (! $canApply) {
                return $this->recordConflict(
                    $command,
                    $actor,
                    $order,
                    $current === $base ? 'invalid_transition' : 'concurrent_status_change',
                    'آرڈر کی حالت کسی دوسرے آلے پر تبدیل ہو چکی ہے۔ تازہ معلومات دیکھ کر فیصلہ کریں۔',
                );
            }

            $fromStatus = $current;
            $updates = ['status' => $target, 'status_changed_at' => now()];
            if ($target === 'cutting' && ! $order->started_at) {
                $updates['started_at'] = now();
            }
            if ($target === 'ready') {
                $updates['ready_at'] = now();
            }
            if ($target === 'delivered') {
                $updates['delivered_at'] = now();
            }

            $order->update($updates);
            app(ProductionWorkforceService::class)->syncOrder($order);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => $actor['type'] === 'user' ? $actor['id'] : null,
                'tailor_id' => $order->tailorId,
                'from_status' => $fromStatus,
                'to_status' => $target,
                'changed_by_type' => $actor['type'] === 'user' ? 'shop_owner' : 'tailor',
                'note' => $command['payload']['note'] ?? null,
            ]);

            $orderId = (int) $order->id;
            $note = $command['payload']['note'] ?? null;
            DB::afterCommit(function () use ($orderId, $target, $note) {
                $freshOrder = Order::find($orderId);
                if ($freshOrder) {
                    app(OrderLifecycleNotificationService::class)->send($freshOrder, $target, $note);
                }
            });

            return $this->recordResult($command, $actor, OfflineOperation::STATUS_APPLIED, [
                'id' => $command['id'],
                'status' => OfflineOperation::STATUS_APPLIED,
                'aggregate_id' => $order->id,
                'previous_status' => $fromStatus,
                'current_status' => $target,
                'rebased' => $fromStatus !== $base,
                'message' => $fromStatus !== $base
                    ? 'نئی سرور حالت کے مطابق تبدیلی محفوظ کر دی گئی ہے۔'
                    : 'آف لائن تبدیلی محفوظ کر دی گئی ہے۔',
            ]);
        });
    }

    private function recordConflict(array $command, array $actor, Order $order, string $reason, string $message): array
    {
        return $this->recordResult($command, $actor, OfflineOperation::STATUS_CONFLICT, [
            'id' => $command['id'],
            'status' => OfflineOperation::STATUS_CONFLICT,
            'reason' => $reason,
            'aggregate_id' => $order->id,
            'base_status' => $command['base_version'],
            'requested_status' => $command['payload']['status'],
            'current_status' => $order->status,
            'allowed_statuses' => $order->nextStatuses(),
            'message' => $message,
        ]);
    }

    private function conflictResult(array $command, string $reason, ?Order $order): array
    {
        return [
            'id' => $command['id'],
            'status' => OfflineOperation::STATUS_CONFLICT,
            'reason' => $reason,
            'aggregate_id' => $order?->id ?? $command['aggregate_id'],
            'message' => 'یہ آف لائن تبدیلی موجودہ صارف کے ذریعے مکمل نہیں ہو سکتی۔',
        ];
    }

    private function recordResult(array $command, array $actor, string $status, array $result): array
    {
        OfflineOperation::create([
            'operation_uuid' => $command['id'],
            'owner_user_id' => $actor['owner_id'],
            'actor_type' => $actor['type'],
            'actor_id' => $actor['id'],
            'action' => $command['type'],
            'aggregate_type' => 'order',
            'aggregate_id' => $command['aggregate_id'],
            'base_version' => $command['base_version'],
            'payload' => $command['payload'],
            'status' => $status,
            'result' => $result,
            'processed_at' => now(),
        ]);

        return $result;
    }

    private function actor(): array
    {
        if (Auth::check()) {
            return [
                'type' => 'user',
                'id' => (int) Auth::id(),
                'owner_id' => (int) Auth::user()->businessOwnerId(),
            ];
        }

        abort_unless(session()->has('tailor_id'), 403);
        $tailor = Tailor::findOrFail((int) session('tailor_id'));

        return [
            'type' => 'tailor',
            'id' => (int) $tailor->id,
            'owner_id' => (int) $tailor->user_id,
        ];
    }

    private function isAheadOf(string $current, string $target): bool
    {
        $rank = array_flip(Order::STATUSES);

        return isset($rank[$current], $rank[$target])
            && $current !== 'trial'
            && $target !== 'trial'
            && $rank[$current] > $rank[$target];
    }

    private function canApplyStatusTransition(Order $order, string $target, int $ownerId): bool
    {
        if (Business::tailoringStatusModeForOwner($ownerId) === Business::TAILORING_STATUS_DETAILED) {
            return in_array($target, $order->nextStatuses(), true);
        }

        return match ($target) {
            'cutting' => in_array($order->status, ['assigned', 'ready'], true),
            'ready' => in_array($order->status, ['assigned', 'cutting', 'stitching', 'trial'], true),
            'delivered' => $order->status === 'ready',
            default => false,
        };
    }
}
