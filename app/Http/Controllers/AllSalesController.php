<?php

namespace App\Http\Controllers;

use App\Models\ClothCategory;
use App\Models\CounterOrderItem;
use App\Models\Customers;
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
    public function index(Request $request, ReportDashboardService $dashboard)
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

        // Counter orders and customers behind this page's rows, for the receipt / customer actions.
        $receiptIds = $lines->getCollection()->pluck('receipt_id')->filter()->unique();
        $counterOrders = $receiptIds->isEmpty() ? collect() : CounterOrderItem::query()
            ->where('source_record_type', 'counter_sale_receipt')
            ->whereIn('source_record_id', $receiptIds)
            ->whereHas('counterOrder', fn ($query) => $query->where('user_id', $ownerId))
            ->pluck('counter_order_id', 'source_record_id');
        $customerIds = Customers::where('user_id', $ownerId)
            ->whereIn('id', $lines->getCollection()->pluck('customer_id')->filter()->unique())
            ->pluck('id')->flip();
            // dd($customerIds);
        $lines->setCollection($lines->getCollection()->map(fn (array $line) => $line + [
            'counter_order_id' => $line['receipt_id'] ? $counterOrders->get($line['receipt_id']) : null,
            'has_customer' => $line['customer_id'] && $customerIds->has($line['customer_id']),
        ]));

        $categoryOptions = ClothCategory::where('user_id', $ownerId)->topLevel()->ordered()
            ->with('children.parent')->get()
            ->flatMap(fn (ClothCategory $parent) => collect([$parent])->concat($parent->children));

        return view('sales.all', compact('lines', 'filters', 'categoryOptions'));
    }
}
