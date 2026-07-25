<?php

namespace App\Services;

use App\Models\Storefront;
use App\Models\StorefrontOrder;
use App\Models\StorefrontPaymentReconciliation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StorefrontPaymentReconciliationService
{
    public function report(
        Storefront $storefront,
        Carbon $start,
        Carbon $end,
        ?string $method = null
    ): array {
        $expected = $storefront->orders()
            ->selectRaw('DATE(payment_verified_at) as settlement_date, payment_method, COUNT(*) as expected_count, SUM(paid_amount) as expected_amount')
            ->where('payment_verification_status', StorefrontOrder::VERIFICATION_VERIFIED)
            ->whereIn('payment_method', StorefrontOrder::manualPaymentMethods())
            ->whereBetween('payment_verified_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->groupBy(DB::raw('DATE(payment_verified_at)'), 'payment_method')
            ->get();

        $reconciliations = StorefrontPaymentReconciliation::query()
            ->with([
                'reconciler:id,name,username',
                'events' => fn ($query) => $query
                    ->with('reconciler:id,name,username')
                    ->latest('id'),
            ])
            ->withCount('events')
            ->where('storefront_id', $storefront->id)
            ->whereDate('settlement_date', '>=', $start->toDateString())
            ->whereDate('settlement_date', '<=', $end->toDateString())
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->get();

        $rows = collect();
        foreach ($expected as $payment) {
            $key = $payment->settlement_date.'|'.$payment->payment_method;
            $rows->put($key, $this->row(
                $payment->settlement_date,
                $payment->payment_method,
                (int) $payment->expected_count,
                (float) $payment->expected_amount,
                null
            ));
        }
        foreach ($reconciliations as $reconciliation) {
            $date = $reconciliation->settlement_date->toDateString();
            $key = $date.'|'.$reconciliation->payment_method;
            $current = $rows->get($key);
            $rows->put($key, $this->row(
                $date,
                $reconciliation->payment_method,
                (int) ($current['expected_count'] ?? 0),
                (float) ($current['expected_amount'] ?? 0),
                $reconciliation
            ));
        }

        $rows = $rows->sortByDesc(fn ($row) => $row['date'].'|'.$row['method'])->values();

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'rows' => $rows,
            'summary' => [
                'expected_amount' => round($rows->sum('expected_amount'), 2),
                'actual_amount' => round($rows->sum('actual_amount'), 2),
                'outstanding_amount' => round($rows->sum('expected_amount') - $rows->sum('actual_amount'), 2),
                'pending_days' => $rows->where('status', 'pending')->count(),
                'variance_days' => $rows->where('status', 'variance')->count(),
                'matched_days' => $rows->where('status', 'matched')->count(),
            ],
        ];
    }

    public function reconcile(
        Storefront $storefront,
        string $date,
        string $method,
        float $actualAmount,
        ?string $reference,
        ?string $notes,
        int $userId
    ): StorefrontPaymentReconciliation {
        if (! in_array($method, StorefrontOrder::manualPaymentMethods(), true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'صرف دستی ادائیگی کے طریقے کی مصالحت کی جا سکتی ہے۔',
            ]);
        }

        return DB::transaction(function () use (
            $storefront,
            $date,
            $method,
            $actualAmount,
            $reference,
            $notes,
            $userId
        ) {
            $orders = $storefront->orders()
                ->whereDate('payment_verified_at', $date)
                ->where('payment_method', $method)
                ->where('payment_verification_status', StorefrontOrder::VERIFICATION_VERIFIED)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'paid_amount']);
            if ($orders->isEmpty()) {
                throw ValidationException::withMessages([
                    'settlement_date' => 'اس تاریخ اور طریقے کے لیے کوئی تصدیق شدہ آن لائن ادائیگی موجود نہیں۔',
                ]);
            }

            $expectedAmount = round((float) $orders->sum('paid_amount'), 2);
            $actualAmount = round($actualAmount, 2);
            $values = [
                'expected_count' => $orders->count(),
                'expected_amount' => $expectedAmount,
                'actual_amount' => $actualAmount,
                'variance_amount' => round($actualAmount - $expectedAmount, 2),
                'external_reference' => $reference,
                'notes' => $notes,
                'reconciled_by_user_id' => $userId,
                'reconciled_at' => now(),
            ];

            $reconciliation = StorefrontPaymentReconciliation::query()
                ->where('storefront_id', $storefront->id)
                ->whereDate('settlement_date', $date)
                ->where('payment_method', $method)
                ->lockForUpdate()
                ->first();
            if ($reconciliation) {
                $reconciliation->update($values);
            } else {
                $reconciliation = StorefrontPaymentReconciliation::create([
                    'storefront_id' => $storefront->id,
                    'settlement_date' => $date,
                    'payment_method' => $method,
                    ...$values,
                ]);
            }
            $reconciliation->events()->create($values);

            return $reconciliation->fresh(['reconciler']);
        }, 3);
    }

    private function row(
        string $date,
        string $method,
        int $expectedCount,
        float $expectedAmount,
        ?StorefrontPaymentReconciliation $reconciliation
    ): array {
        $actualAmount = $reconciliation ? (float) $reconciliation->actual_amount : 0.0;
        $variance = round($actualAmount - $expectedAmount, 2);
        $status = ! $reconciliation
            ? 'pending'
            : (abs($variance) < 0.005 ? 'matched' : 'variance');

        return [
            'date' => $date,
            'method' => $method,
            'method_label' => StorefrontOrder::paymentMethods()[$method] ?? $method,
            'expected_count' => $expectedCount,
            'expected_amount' => round($expectedAmount, 2),
            'actual_amount' => round($actualAmount, 2),
            'variance_amount' => $variance,
            'status' => $status,
            'reconciliation' => $reconciliation,
            'snapshot_changed' => $reconciliation
                && ((int) $reconciliation->expected_count !== $expectedCount
                    || abs((float) $reconciliation->expected_amount - $expectedAmount) >= 0.005),
        ];
    }
}
