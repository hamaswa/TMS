<?php

namespace App\Services;

use App\Models\ClothCategory;
use App\Models\ClothColor;
use App\Models\Customers;
use App\Models\OnlineOrder;
use App\Models\Order;
use App\Models\SaleStock;
use App\Models\StorefrontOrder;
use App\Models\Tailor;
use App\Models\TailorRecord;
use App\Models\Transaction;
use App\Models\Workers;
use App\Support\PaymentMethods;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data for the reports page, split into a cloth sales view and a tailoring view.
 */
class ReportDashboardService
{
    public const SALE_TYPES = ['Sale', 'Sale Cancellation', 'Sale Return'];
    public const TAILOR_TYPES = ['Tailor', 'Tailor_Advance_Cut'];

    public function __construct(
        private FinancialReportService $finance,
        private CustomerLedgerService $ledger,
    ) {
    }

    /** Daily cloth sales and stitching income for the trend chart, counted like the finance report. */
    public function dailySeries(int $userId, CarbonInterface $start, CarbonInterface $end, array $modules): array
    {
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay();
        $daily = [];
        for ($day = $from->copy(); $day->lte($to) && count($daily) < 400; $day->addDay()) {
            $daily[$day->toDateString()] = ['cloth' => 0.0, 'tailoring' => 0.0];
        }
        $add = function ($query, string $column, string $amount, string $key) use (&$daily) {
            $query->selectRaw("DATE({$column}) AS day, SUM({$amount}) AS amount")
                ->groupBy(DB::raw("DATE({$column})"))->get()
                ->each(function ($row) use (&$daily, $key) {
                    if (isset($daily[$row->day])) {
                        $daily[$row->day][$key] += (float) $row->amount;
                    }
                });
        };

        if (in_array('clothing', $modules, true)) {
            $add(SaleStock::financiallyActive()->where('user_id', $userId)->whereBetween('sellDate', [$from, $to]), 'sellDate', 'selling_price * length', 'cloth');
            $add(DB::table('saledetails')->join('sales', 'saledetails.sale_id', '=', 'sales.id')
                ->where('sales.user_id', $userId)->where('sales.status', '!=', 'cancelled')
                ->whereBetween('sales.created_at', [$from, $to]), 'sales.created_at', 'saledetails.quantity * saledetails.price', 'cloth');
            $add(OnlineOrder::where('admin_user_id', $userId)->whereBetween('created_at', [$from, $to])
                ->whereRaw('LOWER(status) != ?', ['cancelled']), 'created_at', 'price * length', 'cloth');
            $add(StorefrontOrder::whereHas('storefront.business', fn ($query) => $query->where('owner_user_id', $userId))
                ->whereBetween('placed_at', [$from, $to])->where('status', '!=', StorefrontOrder::STATUS_CANCELLED), 'placed_at', 'subtotal', 'cloth');
        }
        if (in_array('tailoring', $modules, true)) {
            $add($this->activeOrders($userId)->whereBetween('created_at', [$from, $to]), 'created_at', 'totalPayment', 'tailoring');
        }

        return $daily;
    }

    // ---------------------------------------------------------------- Sales

    /** Headline numbers for cloth sales in a period; also used for the previous period's comparison. */
    public function salesMetrics(int $userId, CarbonInterface $start, CarbonInterface $end, array $modules): array
    {
        $revenue = $this->finance->build($userId, $start, $end, false, $modules)['revenue'];
        unset($revenue['ٹیلرنگ آرڈرز']);
        $categories = $this->finance->salesByCategory($userId, $start, $end, $modules);
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay();
        $bills = SaleStock::financiallyActive()->where('user_id', $userId)->whereBetween('sellDate', [$from, $to])
            ->selectRaw('COUNT(DISTINCT COALESCE(counter_sale_receipt_id, -id)) AS bills')->value('bills');

        return [
            'revenue' => array_sum($revenue),
            'meters' => $categories['totals'][ClothCategory::UNIT_METER]['net'] ?? 0,
            'suits' => $categories['totals'][ClothCategory::UNIT_SUIT]['net'] ?? 0,
            'received' => $this->received($userId, $start, $end, 'sales'),
            'bills' => (int) $bills,
            'revenue_sources' => $revenue,
            'categories' => $categories,
        ];
    }

    /** The latest sale bills: one row per counter receipt (or per sale without one). */
    public function recentSales(int $userId, CarbonInterface $start, CarbonInterface $end, int $limit = 5): Collection
    {
        $bills = SaleStock::financiallyActive()->where('user_id', $userId)
            ->whereBetween('sellDate', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy(DB::raw('COALESCE(counter_sale_receipt_id, -id)'))
            ->selectRaw('MAX(counter_sale_receipt_id) AS receipt_id, MAX(sellDate) AS sold_at, MAX(c_name) AS customer')
            ->selectRaw('COUNT(*) AS items, SUM(selling_price * length) AS amount')
            ->orderByDesc('sold_at')->limit($limit)->get();
        $methods = Transaction::where('userId', $userId)->whereIn('counter_sale_receipt_id', $bills->pluck('receipt_id')->filter())
            ->where('Order_type', 'Sale')->pluck('payment_method', 'counter_sale_receipt_id');

        return $bills->map(fn ($bill) => [
            'date' => $bill->sold_at,
            'customer' => $bill->customer ?: 'عام گاہک',
            'items' => (int) $bill->items,
            'amount' => (float) $bill->amount,
            'method' => PaymentMethods::label($methods[$bill->receipt_id] ?? 'cash'),
        ]);
    }

    /** Every cloth sale line in the period, optionally for one category (and its sub categories). */
    public function salesLines(int $userId, CarbonInterface $start, CarbonInterface $end, ?int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        $categories = ClothCategory::with('parent')->where('user_id', $userId)->get()->keyBy('id');

        return SaleStock::financiallyActive()
            ->leftJoin('cloths', 'cloths.id', '=', 'sale_stocks.cloth_id')
            ->where('sale_stocks.user_id', $userId)
            ->whereBetween('sale_stocks.sellDate', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($categoryId, fn ($query) => $query->whereIn('cloths.cloth_category_id',
                $categories->filter(fn ($category) => $category->id === $categoryId || $category->parent_id === $categoryId)->keys()))
            ->orderByDesc('sale_stocks.sellDate')->orderByDesc('sale_stocks.id')
            ->select(['sale_stocks.id', 'sale_stocks.sellDate', 'sale_stocks.c_name', 'sale_stocks.color', 'sale_stocks.length', 'sale_stocks.selling_price'])
            ->addSelect(['cloths.name AS cloth_name', 'cloths.stock_unit', 'cloths.cloth_category_id'])
            ->paginate($perPage, ['*'], 'sales_page')->withQueryString()
            ->through(fn ($line) => [
                'date' => $line->sellDate,
                'name' => $line->cloth_name ?: 'سیٹ',
                'color' => $line->color,
                'category' => $categories->get($line->cloth_category_id)?->fullName() ?? '—',
                'quantity' => (float) $line->length,
                'unit' => $line->stock_unit ?: ClothCategory::UNIT_METER,
                'amount' => (float) $line->selling_price * (float) $line->length,
                'customer' => $line->c_name ?: 'عام گاہک',
            ]);
    }

    /** Biggest cloth customer in the period. */
    public function topSalesCustomer(int $userId, CarbonInterface $start, CarbonInterface $end): ?array
    {
        $row = SaleStock::financiallyActive()->where('user_id', $userId)->whereNotNull('c_name')->where('c_name', '!=', '')
            ->whereBetween('sellDate', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('c_name')->selectRaw('c_name, SUM(selling_price * length) AS amount, COUNT(DISTINCT COALESCE(counter_sale_receipt_id, -id)) AS bills')
            ->orderByDesc('amount')->first();

        return $row ? ['name' => $row->c_name, 'amount' => (float) $row->amount, 'count' => (int) $row->bills] : null;
    }

    /** Stock on hand per category, in each category's unit. */
    public function stockByCategory(int $userId): Collection
    {
        $categories = ClothCategory::with('parent')->where('user_id', $userId)->get()->keyBy('id');

        return ClothColor::query()->join('cloths', 'cloths.id', '=', 'cloth_colors.cloth_id')
            ->where('cloths.user_id', $userId)->whereNull('cloths.deleted_at')
            ->groupBy('cloths.cloth_category_id', 'cloths.stock_unit')
            ->selectRaw('cloths.cloth_category_id AS category_id, cloths.stock_unit AS unit, COUNT(DISTINCT cloths.id) AS sets')
            ->selectRaw('SUM(cloth_colors.length) AS quantity, SUM(cloth_colors.length * COALESCE(cloth_colors.average_unit_cost, 0)) AS value')
            ->get()
            ->map(fn ($row) => [
                'category' => $categories->get($row->category_id)?->fullName() ?? 'بغیر کیٹیگری',
                'unit' => $row->unit ?: ClothCategory::UNIT_METER,
                'sets' => (int) $row->sets,
                'quantity' => (float) $row->quantity,
                'value' => (float) $row->value,
            ])->sortBy('category')->values();
    }

    // ------------------------------------------------------------- Tailoring

    public function tailoringMetrics(int $userId, CarbonInterface $start, CarbonInterface $end): array
    {
        $orders = $this->activeOrders($userId)->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('COUNT(*) AS orders, SUM(CASE WHEN suitQuantity >= 1 THEN suitQuantity ELSE 1 END) AS suits, SUM(totalPayment) AS revenue')
            ->first();

        return [
            'revenue' => (float) ($orders->revenue ?? 0),
            'orders' => (int) ($orders->orders ?? 0),
            'suits' => (int) ($orders->suits ?? 0),
            'received' => $this->received($userId, $start, $end, 'tailoring'),
        ];
    }

    /**
     * Orders placed in the period per status, labelled the way the shop chose under its work statuses
     * (simple mode joins the workshop steps into one). Cancelled orders are included so nothing goes missing.
     */
    public function ordersByStatus(int $userId, CarbonInterface $start, CarbonInterface $end, bool $detailed): Collection
    {
        $counts = Order::where('userId', $userId)->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $labels = Order::statusLabelsFor($detailed);

        return collect(Order::STATUSES)
            ->groupBy(fn ($status) => $labels[$status] ?? $status)
            ->map(fn ($statuses, $label) => [
                'label' => $label,
                'status' => $statuses->first(),
                'count' => (int) $statuses->sum(fn ($status) => $counts[$status] ?? 0),
            ])
            ->filter(fn ($item) => $item['count'] > 0)->values();
    }

    public function orders(int $userId, CarbonInterface $start, CarbonInterface $end, ?string $status, int $perPage = 15, string $pageName = 'orders_page'): LengthAwarePaginator
    {
        return Order::query()
            ->leftJoin('customers', 'customers.id', '=', 'orders.customerId')
            ->leftJoin('tailors', 'tailors.id', '=', 'orders.tailorId')
            ->where('orders.userId', $userId)
            ->whereBetween('orders.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($status === 'workshop', fn ($query) => $query->whereIn('orders.status', Order::SIMPLE_WORKSHOP_STATUSES))
            ->when($status && $status !== 'workshop', fn ($query) => $query->where('orders.status', $status))
            ->orderByDesc('orders.created_at')->orderByDesc('orders.id')
            ->select(['orders.id', 'orders.suitNum', 'orders.suitQuantity', 'orders.totalPayment', 'orders.status', 'orders.returnDate', 'orders.created_at'])
            ->addSelect(['customers.name AS customer_name', 'tailors.name AS tailor_name'])
            ->paginate($perPage, ['*'], $pageName)->withQueryString();
    }

    /**
     * Each tailor's work: suits and charges in the period, plus everything still owed up to the end date
     * (stitching charges on their orders minus what has been paid against those orders).
     */
    public function tailors(int $userId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $suits = 'CASE WHEN orders.suitQuantity >= 1 THEN orders.suitQuantity ELSE 1 END';
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay();

        return Tailor::withTrashed()
            ->joinSub(DB::table('orders')
                ->selectRaw('orders.tailorId AS tailor_id')
                ->selectRaw("SUM(CASE WHEN orders.created_at >= ? THEN 1 ELSE 0 END) AS period_jobs", [$from])
                ->selectRaw("SUM(CASE WHEN orders.created_at >= ? THEN {$suits} ELSE 0 END) AS period_suits", [$from])
                ->selectRaw("SUM(CASE WHEN orders.created_at >= ? THEN COALESCE(orders.tailor_price, 0) * {$suits} ELSE 0 END) AS period_earned", [$from])
                ->selectRaw("SUM(COALESCE(orders.tailor_price, 0) * {$suits}) AS earned, SUM(COALESCE(orders.tailor_paid_amount, 0)) AS paid")
                ->where('orders.userId', $userId)->whereNotNull('orders.tailorId')
                ->where(fn ($query) => $query->whereNull('orders.status')->orWhere('orders.status', '!=', 'cancelled'))
                ->where('orders.created_at', '<=', $to)
                ->groupBy('orders.tailorId'), 'tailor_work', 'tailor_work.tailor_id', '=', 'tailors.id')
            ->where('tailors.user_id', $userId)
            ->select(['tailors.id', 'tailors.name', 'tailors.phone_number1'])
            ->selectRaw('tailor_work.period_jobs, tailor_work.period_suits, tailor_work.period_earned, tailor_work.earned, tailor_work.paid, (tailor_work.earned - tailor_work.paid) AS balance')
            ->get()
            ->filter(fn ($tailor) => $tailor->period_jobs > 0 || $tailor->balance > 0.009)
            ->sortByDesc(fn ($tailor) => [(int) $tailor->period_suits, (float) $tailor->balance])
            ->values();
    }

    /** Money given to tailors and workers in the period: tailor payments, worker payments and salaries. */
    public function payouts(int $userId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay();
        $rows = collect();
        TailorRecord::with('tailor:id,name')->whereHas('tailor', fn ($query) => $query->withTrashed()->where('user_id', $userId))
            ->whereBetween('created_at', [$from, $to])->latest()->limit(200)->get()
            ->each(fn ($row) => $rows->push(['date' => $row->created_at->toDateString(), 'name' => $row->tailor?->name ?? 'درزی', 'kind' => 'درزی کو ادائیگی', 'note' => $row->comment, 'amount' => (float) $row->amount]));
        DB::table('worker_ledger_entries')->join('production_workers', 'production_workers.id', '=', 'worker_ledger_entries.production_worker_id')
            ->where('worker_ledger_entries.user_id', $userId)->whereNull('production_workers.legacy_tailor_id')
            ->where('worker_ledger_entries.entry_type', 'payment')
            ->whereBetween('worker_ledger_entries.entry_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('worker_ledger_entries.entry_date')->limit(200)
            ->get(['worker_ledger_entries.entry_date', 'worker_ledger_entries.amount', 'production_workers.name'])
            ->each(fn ($row) => $rows->push(['date' => $row->entry_date, 'name' => $row->name, 'kind' => 'ورکر کو ادائیگی', 'note' => null, 'amount' => abs((float) $row->amount)]));
        Workers::where('user_id', $userId)->whereBetween('dateentered', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('dateentered')->limit(200)->get()
            ->each(fn ($row) => $rows->push(['date' => $row->dateentered, 'name' => $row->Worker_Name, 'kind' => 'تنخواہ', 'note' => null, 'amount' => (float) $row->Worker_salary]));

        return $rows->sortByDesc(fn ($row) => (string) $row['date'])->values();
    }

    /** Biggest stitching customer in the period. */
    public function topTailoringCustomer(int $userId, CarbonInterface $start, CarbonInterface $end): ?array
    {
        $row = $this->activeOrders($userId)->join('customers', 'customers.id', '=', 'orders.customerId')
            ->whereBetween('orders.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('customers.id', 'customers.name')
            ->selectRaw('customers.name, SUM(orders.totalPayment) AS amount, COUNT(*) AS orders')
            ->orderByDesc('amount')->first();

        return $row ? ['name' => $row->name, 'amount' => (float) $row->amount, 'count' => (int) $row->orders] : null;
    }

    // ---------------------------------------------------------------- Shared

    /**
     * Customers who still owe for one side (cloth "sales" or "tailoring"), using the customer ledger's
     * allocation so a customer's payments are never counted on both sides.
     */
    public function customerBalances(int $userId, CarbonInterface $end, string $side, ?string $search, int $perPage = 15, string $pageName = 'customers_page'): array
    {
        $balances = $this->ledger->sideBalances($userId, $end)->filter(fn ($balance) => $balance[$side] > 0.009);
        $customers = Customers::where('user_id', $userId)->whereIn('id', $balances->keys())
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")->orWhere('phone_number1', 'like', "%{$search}%")))
            ->get(['id', 'name', 'phone_number1'])
            ->map(fn ($customer) => [
                'name' => $customer->name,
                'phone' => $customer->phone_number1,
                'balance' => $balances[$customer->id][$side],
                'total' => $balances[$customer->id]['total'],
            ])->sortByDesc('balance')->values();
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $paginator = new LengthAwarePaginator($customers->forPage($page, $perPage)->values(), $customers->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        return [
            'list' => $paginator->withQueryString(),
            'total' => $balances->sum($side),
            'count' => $balances->count(),
        ];
    }

    /** Money customers paid in the period towards cloth sales or stitching. */
    private function received(int $userId, CarbonInterface $start, CarbonInterface $end, string $side): float
    {
        return (float) Transaction::where('userId', $userId)
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->where(fn ($query) => $side === 'sales'
                ? $query->whereIn('Order_type', self::SALE_TYPES)
                    ->orWhere(fn ($payment) => $payment->where('Order_type', 'Payment')->whereNotNull('counter_sale_receipt_id'))
                : $query->whereIn('Order_type', self::TAILOR_TYPES)
                    ->orWhere(fn ($payment) => $payment->where('Order_type', 'Payment')->whereNotNull('orderId')))
            ->sum('recivedPayment');
    }

    private function activeOrders(int $userId)
    {
        return Order::where('orders.userId', $userId)
            ->where(fn ($query) => $query->whereNull('orders.status')->orWhere('orders.status', '!=', 'cancelled'));
    }
}
