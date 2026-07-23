<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialReportController extends Controller
{
    public function index(Request $request, FinancialReportService $reports)
    {
        $filters = $request->validate([
            'receivables_q' => ['nullable', 'string', 'max:100'],
            'payables_q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'in:10,25,50,100'],
        ]);
        [$start, $end] = $this->period($request);
        $modules = $this->businessModules(Auth::user());
        $report = $reports->build(Auth::user()->businessOwnerId(), $start, $end, false, $modules);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $report['receivables'] = $reports->receivablesQuery(Auth::user()->businessOwnerId(), $end, $filters['receivables_q'] ?? null, $modules)
            ->paginate($perPage, ['*'], 'receivables_page')->withQueryString();
        $report['payables'] = $reports->payablesQuery(Auth::user()->businessOwnerId(), $end, $filters['payables_q'] ?? null, $modules)
            ->paginate($perPage, ['*'], 'payables_page')->withQueryString();

        return view('financial-reports.index', compact('report', 'filters'));
    }

    public function export(Request $request, string $section, FinancialReportService $reports)
    {
        abort_unless(in_array($section, ['summary', 'receivables', 'payables'], true), 404);
        [$start, $end] = $this->period($request);
        $report = $reports->build(
            Auth::user()->businessOwnerId(),
            $start,
            $end,
            true,
            $this->businessModules(Auth::user())
        );
        $filename = $section . '-' . $start->toDateString() . '-to-' . $end->toDateString() . '.csv';

        return response()->streamDownload(function () use ($section, $report) {
            $stream = fopen('php://output', 'w');
            if ($section === 'summary') {
                fputcsv($stream, ['Metric', 'Amount']);
                foreach ($report['summary'] as $metric => $amount) fputcsv($stream, [ucwords(str_replace('_', ' ', $metric)), number_format($amount, 2, '.', '')]);
            } else {
                fputcsv($stream, [ucfirst(rtrim($section, 's')), 'Phone', 'Balance']);
                foreach ($report[$section] as $row) fputcsv($stream, [$this->csvCell($row['name']), $this->csvCell($row['phone']), number_format($row['balance'], 2, '.', '')]);
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function period(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        return [
            Carbon::parse($validated['start_date'] ?? now()->startOfMonth()->toDateString()),
            Carbon::parse($validated['end_date'] ?? now()->endOfMonth()->toDateString()),
        ];
    }

    private function csvCell(?string $value): string
    {
        $value = (string) $value;
        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }

    private function businessModules(User $user): array
    {
        $business = $user->business;

        return array_values(array_filter([
            ($business?->tailoring_enabled ?? $user->hasModule(User::MODULE_TAILORING))
                ? User::MODULE_TAILORING
                : null,
            ($business?->clothing_enabled ?? $user->hasModule(User::MODULE_CLOTHING))
                ? User::MODULE_CLOTHING
                : null,
        ]));
    }
}
