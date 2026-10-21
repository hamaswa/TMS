<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ClothCategory;
use App\Models\Order;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Services\ReportDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FinancialReportController extends Controller
{
    public function index(Request $request, FinancialReportService $reports, ReportDashboardService $dashboard)
    {
        $filters = $request->validate([
            'view' => ['nullable', 'in:sales,tailoring'],
            'tab' => ['nullable', 'string', 'max:30'],
            'customers_q' => ['nullable', 'string', 'max:100'],
            'payables_q' => ['nullable', 'string', 'max:100'],
            'sales_category' => ['nullable', 'integer'],
            'order_status' => ['nullable', Rule::in([...Order::STATUSES, 'workshop'])],
        ]);
        [$start, $end] = $this->period($request);
        $ownerId = Auth::user()->businessOwnerId();
        $modules = $this->businessModules(Auth::user());
        $hasClothing = in_array(User::MODULE_CLOTHING, $modules, true);
        $hasTailoring = in_array(User::MODULE_TAILORING, $modules, true);
        $view = $filters['view'] ?? ($hasClothing ? 'sales' : 'tailoring');
        $view = $view === 'sales' && ! $hasClothing ? 'tailoring' : ($view === 'tailoring' && ! $hasTailoring ? 'sales' : $view);

        // The same number of days just before the chosen period, for the % change on each card.
        $days = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $previousEnd = $start->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1);

        $report = $reports->build($ownerId, $start, $end, false, $modules);
        $report += [
            'view' => $view,
            'modules' => $modules,
            'days' => (int) $days,
            'daily' => $dashboard->dailySeries($ownerId, $start, $end, $modules),
        ];

        if ($view === 'sales') {
            $report['current'] = $dashboard->salesMetrics($ownerId, $start, $end, $modules);
            $report['previous'] = $dashboard->salesMetrics($ownerId, $previousStart, $previousEnd, $modules);
            $report['recent'] = $dashboard->recentSales($ownerId, $start, $end);
            $report['lines'] = $dashboard->salesLines($ownerId, $start, $end, $filters['sales_category'] ?? null);
            $report['top_customer'] = $dashboard->topSalesCustomer($ownerId, $start, $end);
            $report['customers'] = $dashboard->customerBalances($ownerId, $end, 'sales', $filters['customers_q'] ?? null);
            $report['payables'] = $reports->payablesQuery($ownerId, $end, $filters['payables_q'] ?? null, $modules)
                ->paginate(15, ['*'], 'payables_page')->withQueryString();
            $report['stock'] = $dashboard->stockByCategory($ownerId);
            // Each main category followed by its sub categories, for the sales filter.
            $report['category_options'] = ClothCategory::where('user_id', $ownerId)->topLevel()->ordered()
                ->with('children.parent')->get()
                ->flatMap(fn (ClothCategory $parent) => collect([$parent])->concat($parent->children));
        } else {
            $report['current'] = $dashboard->tailoringMetrics($ownerId, $start, $end);
            $report['previous'] = $dashboard->tailoringMetrics($ownerId, $previousStart, $previousEnd);
            // Status names follow the shop's own work-status setting (simple or detailed).
            $detailed = Business::tailoringStatusModeForOwner($ownerId) === Business::TAILORING_STATUS_DETAILED;
            $report['status_labels'] = Order::statusLabelsFor($detailed);
            $report['status_filter'] = $detailed
                ? Order::STATUS_LABELS
                : ['unassigned' => Order::SIMPLE_STATUS_LABELS['unassigned'], 'workshop' => Order::SIMPLE_STATUS_LABELS['assigned'],
                    'ready' => Order::SIMPLE_STATUS_LABELS['ready'], 'delivered' => Order::SIMPLE_STATUS_LABELS['delivered'],
                    'cancelled' => Order::SIMPLE_STATUS_LABELS['cancelled']];
            $report['statuses'] = $dashboard->ordersByStatus($ownerId, $start, $end, $detailed);
            $report['recent'] = $dashboard->orders($ownerId, $start, $end, null, 5, 'recent_page');
            $report['orders'] = $dashboard->orders($ownerId, $start, $end, $filters['order_status'] ?? null);
            $report['tailors'] = $dashboard->tailors($ownerId, $start, $end);
            $report['payouts'] = $dashboard->payouts($ownerId, $start, $end);
            $report['top_customer'] = $dashboard->topTailoringCustomer($ownerId, $start, $end);
            $report['customers'] = $dashboard->customerBalances($ownerId, $end, 'tailoring', $filters['customers_q'] ?? null);
        }

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
