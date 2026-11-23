<?php

namespace App\Http\Controllers;

use App\Models\ClothCategory;
use App\Models\Customers;
use App\Services\CustomerSalesHistoryService;
use App\Services\ReportDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Every cloth sale line on one page, with a ⋮ menu per row.
 * The reports page keeps its own "تمام فروخت" tab; this is the standalone list.
 */
class AllSalesController extends Controller
{
    public function index(Request $request, ReportDashboardService $dashboard, CustomerSalesHistoryService $history)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sales_category' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $ownerId = Auth::user()->businessOwnerId();

        // No dates = every record the shop has.
        $start = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::create(2000, 1, 1);
        $end = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : now();

        $lines = $dashboard->salesLines($ownerId, $start, $end, $filters['sales_category'] ?? null, 25, $filters['q'] ?? null);

        // Customers behind this page's bills, and the same receipt rows the customer page uses,
        // so ⋮ › واپسی / منسوخی opens the return popup right here.
        $customers = Customers::where('user_id', $ownerId)
            ->whereIn('id', $lines->getCollection()->pluck('customer_id')->filter()->unique())
            ->get()->keyBy('id');
        $returnRows = $history->forReceipts($ownerId, $lines->getCollection()->pluck('receipt_id')->filter()->unique()->values());
        $lines->setCollection($lines->getCollection()->map(fn (array $line) => $line + [
            'has_customer' => $line['customer_id'] && $customers->has($line['customer_id']),
            'customer_model' => $line['customer_id'] ? $customers->get($line['customer_id']) : null,
            'return_row' => $line['receipt_id'] ? $returnRows->get($line['receipt_id']) : null,
        ]));

        $categoryOptions = ClothCategory::where('user_id', $ownerId)->topLevel()->ordered()
            ->with('children.parent')->get()
            ->flatMap(fn (ClothCategory $parent) => collect([$parent])->concat($parent->children));

        return view('sales.all', compact('lines', 'filters', 'categoryOptions'));
    }
}
