<?php

namespace App\Http\Controllers;

use App\Models\StorefrontOrder;
use App\Services\StorefrontPaymentReconciliationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorefrontPaymentReconciliationController extends Controller
{
    public function index(Request $request, StorefrontPaymentReconciliationService $service)
    {
        [$start, $end, $method] = $this->filters($request);
        $report = $service->report($this->storefront(), $start, $end, $method);

        return view('storefront.admin.payment-reconciliation', [
            'report' => $report,
            'methods' => $this->methods(),
            'selectedMethod' => $method,
        ]);
    }

    public function store(Request $request, StorefrontPaymentReconciliationService $service)
    {
        $validated = $request->validate([
            'settlement_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(array_keys($this->methods()))],
            'actual_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'external_reference' => [
                Rule::requiredIf((float) $request->input('actual_amount') > 0),
                'nullable',
                'string',
                'max:100',
            ],
            'notes' => [
                Rule::requiredIf((float) $request->input('actual_amount') === 0.0),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);
        $service->reconcile(
            $this->storefront(),
            $validated['settlement_date'],
            $validated['payment_method'],
            (float) $validated['actual_amount'],
            $validated['external_reference'] ?? null,
            $validated['notes'] ?? null,
            (int) Auth::id(),
        );

        return redirect()->route('admin.payment-reconciliation.index', [
            'start_date' => $validated['settlement_date'],
            'end_date' => $validated['settlement_date'],
            'payment_method' => $validated['payment_method'],
        ])->with('success', 'روزانہ ادائیگی کی مصالحت محفوظ ہو گئی ہے۔');
    }

    public function export(Request $request, StorefrontPaymentReconciliationService $service)
    {
        [$start, $end, $method] = $this->filters($request);
        $report = $service->report($this->storefront(), $start, $end, $method);
        $filename = 'payment-reconciliation-'.$start->toDateString().'-to-'.$end->toDateString().'.csv';

        return response()->streamDownload(function () use ($report) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, [
                'Date',
                'Payment method',
                'Verified payments',
                'Expected amount',
                'Provider amount',
                'Variance',
                'Status',
                'Reference',
                'Reconciled by',
                'Reconciled at',
            ]);
            foreach ($report['rows'] as $row) {
                $reconciliation = $row['reconciliation'];
                fputcsv($stream, [
                    $row['date'],
                    $row['method'],
                    $row['expected_count'],
                    number_format($row['expected_amount'], 2, '.', ''),
                    number_format($row['actual_amount'], 2, '.', ''),
                    number_format($row['variance_amount'], 2, '.', ''),
                    $row['status'],
                    $this->csvCell($reconciliation?->external_reference),
                    $this->csvCell($reconciliation?->reconciler?->name),
                    $reconciliation?->reconciled_at?->toIso8601String(),
                ]);
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'payment_method' => ['nullable', Rule::in(array_keys($this->methods()))],
        ]);
        $start = Carbon::parse($validated['start_date'] ?? now()->startOfMonth()->toDateString());
        $end = Carbon::parse($validated['end_date'] ?? now()->endOfMonth()->toDateString());
        if ($start->diffInDays($end) > 366) {
            throw ValidationException::withMessages([
                'end_date' => 'ایک وقت میں زیادہ سے زیادہ 366 دن کی رپورٹ دیکھیں۔',
            ]);
        }

        return [$start, $end, $validated['payment_method'] ?? null];
    }

    private function storefront()
    {
        $storefront = Auth::user()->business?->storefront;
        abort_unless($storefront, 404);

        return $storefront;
    }

    private function methods(): array
    {
        return array_intersect_key(
            StorefrontOrder::paymentMethods(),
            array_flip(StorefrontOrder::manualPaymentMethods())
        );
    }

    private function csvCell(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
