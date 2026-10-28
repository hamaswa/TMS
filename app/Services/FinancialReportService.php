<?php

namespace App\Services;

use App\Models\ClothCategory;
use App\Models\Customers;
use App\Models\DaliyExpenses;
use App\Models\Expenses;
use App\Models\OnlineOrder;
use App\Models\Order;
use App\Models\PurchaseReturn;
use App\Models\SaleStock;
use App\Models\StorefrontOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TailorRecord;
use App\Models\TailorSecurityDepositTransaction;
use App\Models\TailoringRecovery;
use App\Models\Transaction;
use App\Models\Workers;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialReportService
{
    public function build(int $userId, CarbonInterface $start, CarbonInterface $end, bool $includeDetails = true, ?array $modules = null): array
    {
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay();
        $tailoringEnabled = $modules === null || in_array('tailoring', $modules, true);
        $clothingEnabled = $modules === null || in_array('clothing', $modules, true);

        $tailoringRevenue = $tailoringEnabled ? (float) Order::where('userId', (string) $userId)->whereBetween('created_at', [$from, $to])->sum('totalPayment') : 0;
        $counterRevenue = $clothingEnabled ? (float) SaleStock::financiallyActive()->where('user_id', $userId)->whereBetween('sellDate', [$from, $to])->sum(DB::raw('selling_price * length')) : 0;
        // Counter returns come off sales on the day the item came back, like storefront refunds below.
        $counterReturns = $clothingEnabled ? $this->counterReturnsQuery($userId, $from, $to)
            ->selectRaw('COALESCE(SUM(counter_sale_return_items.line_total), 0) AS amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN counter_sale_return_items.restocked = 1 THEN counter_sale_return_items.inventory_quantity * COALESCE(sale_stocks.cost_per_meter, 0) ELSE 0 END), 0) AS cost')
            ->first() : null;
        $counterRevenue -= (float) ($counterReturns->amount ?? 0);
        $manualSalesRevenue = $clothingEnabled ? (float) DB::table('saledetails')->join('sales', 'saledetails.sale_id', '=', 'sales.id')
            ->where('sales.user_id', $userId)->where('sales.status', '!=', 'cancelled')
            ->whereBetween('sales.created_at', [$from, $to])->sum(DB::raw('saledetails.quantity * saledetails.price'))
            : 0;
        $legacyOnlineRevenue = $clothingEnabled ? (float) OnlineOrder::where('admin_user_id', $userId)->whereBetween('created_at', [$from, $to])
            ->whereRaw('LOWER(status) != ?', ['cancelled'])->sum(DB::raw('price * length')) : 0;
        $storefrontRevenue = $clothingEnabled ? (float) StorefrontOrder::whereHas(
            'storefront.business',
            fn ($query) => $query->where('owner_user_id', $userId)
        )->whereBetween('placed_at', [$from, $to])->where('status', '!=', StorefrontOrder::STATUS_CANCELLED)->sum('subtotal') : 0;
        $storefrontReturnRevenue = $clothingEnabled ? (float) DB::table('storefront_order_returns')
            ->join('storefront_orders', 'storefront_orders.id', '=', 'storefront_order_returns.storefront_order_id')
            ->join('storefronts', 'storefronts.id', '=', 'storefront_orders.storefront_id')
            ->join('businesses', 'businesses.id', '=', 'storefronts.business_id')
            ->where('businesses.owner_user_id', $userId)
            ->whereBetween('storefront_order_returns.processed_at', [$from, $to])
            ->sum('storefront_order_returns.refund_amount') : 0;
        $storefrontRevenue -= $storefrontReturnRevenue;
        $onlineRevenue = $legacyOnlineRevenue + $storefrontRevenue;
        $revenue = [];
        if ($tailoringEnabled) {
            $revenue['ٹیلرنگ آرڈرز'] = $tailoringRevenue;
        }
        if ($clothingEnabled) {
            $revenue += ['کاؤنٹر کپڑا فروخت' => $counterRevenue, 'مصنوعات کی فروخت' => $manualSalesRevenue, 'آن لائن آرڈرز' => $onlineRevenue];
        }

        $counterCogs = $clothingEnabled ? (float) SaleStock::financiallyActive()->where('user_id', $userId)->whereBetween('sellDate', [$from, $to])->sum('cost_total') : 0;
        $counterCogs -= (float) ($counterReturns->cost ?? 0);
        $legacyOnlineCogs = $clothingEnabled ? (float) OnlineOrder::where('admin_user_id', $userId)->whereBetween('created_at', [$from, $to])
            ->whereRaw('LOWER(status) != ?', ['cancelled'])->sum('cost_total') : 0;
        $storefrontCogs = $clothingEnabled ? (float) DB::table('storefront_order_items')
            ->join('storefront_orders', 'storefront_orders.id', '=', 'storefront_order_items.storefront_order_id')
            ->join('storefronts', 'storefronts.id', '=', 'storefront_orders.storefront_id')
            ->join('businesses', 'businesses.id', '=', 'storefronts.business_id')
            ->where('businesses.owner_user_id', $userId)
            ->where('storefront_orders.status', '!=', StorefrontOrder::STATUS_CANCELLED)
            ->whereBetween('storefront_orders.placed_at', [$from, $to])
            ->sum('storefront_order_items.cost_total') : 0;
        $storefrontReturnedCogs = $clothingEnabled ? (float) DB::table('storefront_order_return_items')
            ->join('storefront_order_returns', 'storefront_order_returns.id', '=', 'storefront_order_return_items.storefront_order_return_id')
            ->join('storefront_order_items', 'storefront_order_items.id', '=', 'storefront_order_return_items.storefront_order_item_id')
            ->join('storefront_orders', 'storefront_orders.id', '=', 'storefront_order_returns.storefront_order_id')
            ->join('storefronts', 'storefronts.id', '=', 'storefront_orders.storefront_id')
            ->join('businesses', 'businesses.id', '=', 'storefronts.business_id')
            ->where('businesses.owner_user_id', $userId)
            ->where('storefront_order_returns.type', 'refund')
            ->where('storefront_order_return_items.restocked', true)
            ->whereBetween('storefront_order_returns.processed_at', [$from, $to])
            ->sum(DB::raw('storefront_order_return_items.quantity * storefront_order_items.cost_per_meter')) : 0;
        $storefrontCogs -= $storefrontReturnedCogs;
        $onlineCogs = $legacyOnlineCogs + $storefrontCogs;
        $tailorLabor = $tailoringEnabled ? (float) Order::where('userId', (string) $userId)->whereBetween('created_at', [$from, $to])->sum(DB::raw('tailor_price * suitQuantity')) : 0;
        $productionWorkerEarnings = $tailoringEnabled ? (float) DB::table('worker_ledger_entries')
            ->join('production_workers', 'production_workers.id', '=', 'worker_ledger_entries.production_worker_id')
            ->where('worker_ledger_entries.user_id', $userId)
            ->whereNull('production_workers.legacy_tailor_id')
            ->where('worker_ledger_entries.entry_type', 'earning')
            ->whereBetween('worker_ledger_entries.entry_date', [$start->toDateString(), $end->toDateString()])
            ->sum('worker_ledger_entries.amount') : 0;
        $directCosts = [];
        if ($tailoringEnabled) {
            $directCosts['درزی کی مزدوری'] = $tailorLabor;
            $directCosts['پروڈکشن ورکرز کی اجرت'] = $productionWorkerEarnings;
        }
        if ($clothingEnabled) {
            $directCosts += ['کاؤنٹر فروخت کی لاگت' => $counterCogs, 'آن لائن آرڈر کی لاگت' => $onlineCogs];
        }

        $monthlyExpenses = (float) Expenses::where('user_id', $userId)->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum(DB::raw('COALESCE(Monthly_Rent,0) + COALESCE(Monthly_Bill,0) + COALESCE(Extra_Expenses,0)'));
        $dailyExpenses = (float) DaliyExpenses::where('user_id', $userId)->whereBetween('created_at', [$from, $to])->sum('Expense_payment');
        $workerSalaries = (float) Workers::where('user_id', $userId)->whereBetween('dateentered', [$start->toDateString(), $end->toDateString()])->sum('Worker_salary');
        $operatingExpenses = ['کرایہ، بل اور ماہانہ اضافی اخراجات' => $monthlyExpenses, 'روزانہ اخراجات' => $dailyExpenses, 'ملازمین کی تنخواہیں' => $workerSalaries];

        $totalRevenue = array_sum($revenue);
        $totalDirectCosts = array_sum($directCosts);
        $totalOperatingExpenses = array_sum($operatingExpenses);

        $transactionTypes = array_values(array_filter([
            $tailoringEnabled ? 'Tailor' : null,
            $clothingEnabled ? 'Sale' : null,
            $clothingEnabled ? 'Sale Cancellation' : null,
            ($tailoringEnabled || $clothingEnabled) ? 'Payment' : null,
        ]));
        $customerReceipts = (float) Transaction::where('userId', (string) $userId)->whereIn('Order_type', $transactionTypes)
            ->whereBetween('created_at', [$from, $to])->sum('recivedPayment');
        $securityDepositsReceived = $tailoringEnabled ? (float) TailorSecurityDepositTransaction::where('user_id', $userId)
            ->where('transaction_type', TailorSecurityDepositTransaction::TYPE_RECEIVED)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount') : 0;
        $securityDepositsRefunded = $tailoringEnabled ? (float) TailorSecurityDepositTransaction::where('user_id', $userId)
            ->where('transaction_type', TailorSecurityDepositTransaction::TYPE_REFUNDED)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount') : 0;
        $securityDepositsHeld = $tailoringEnabled
            ? (float) TailorSecurityDepositTransaction::where('user_id', $userId)
                ->where('transaction_date', '<=', $end->toDateString())
                ->selectRaw('COALESCE(SUM(CASE WHEN transaction_type = ? THEN amount ELSE -amount END), 0) AS balance', [
                    TailorSecurityDepositTransaction::TYPE_RECEIVED,
                ])
                ->value('balance')
            : 0;
        $tailoringRecoveries = $tailoringEnabled && Schema::hasTable('tailoring_recoveries')
            ? (float) TailoringRecovery::where('user_id', $userId)->where('status', 'recovered')
                ->whereBetween('created_at', [$start, $end])->where('method', '!=', 'shop_absorbed')->sum('amount')
            : 0;
        $tailoringLossAbsorbed = $tailoringEnabled && Schema::hasTable('tailoring_recoveries')
            ? (float) TailoringRecovery::where('user_id', $userId)->where('method', 'shop_absorbed')
                ->whereBetween('created_at', [$start, $end])->sum('amount')
            : 0;
        $supplierPayments = $clothingEnabled ? (float) SupplierPayment::where('user_id', $userId)->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->sum('amount') : 0;
        $tailorPayments = $tailoringEnabled ? (float) TailorRecord::whereHas('tailor', fn ($query) => $query->where('user_id', $userId))
            ->whereBetween('created_at', [$from, $to])->sum('amount') : 0;
        $productionWorkerPayments = $tailoringEnabled ? (float) DB::table('worker_ledger_entries')
            ->join('production_workers', 'production_workers.id', '=', 'worker_ledger_entries.production_worker_id')
            ->where('worker_ledger_entries.user_id', $userId)
            ->whereNull('production_workers.legacy_tailor_id')
            ->where('worker_ledger_entries.entry_type', 'payment')
            ->whereBetween('worker_ledger_entries.entry_date', [$start->toDateString(), $end->toDateString()])
            ->sum(DB::raw('ABS(worker_ledger_entries.amount)')) : 0;
        $cashIn = $customerReceipts + $securityDepositsReceived;
        $cashOut = $supplierPayments + $tailorPayments + $productionWorkerPayments + $monthlyExpenses + $dailyExpenses + $workerSalaries + $securityDepositsRefunded;

        $receivablesQuery = $this->receivablesQuery($userId, $end, null, $modules);
        $payablesQuery = $this->payablesQuery($userId, $end, null, $modules);
        $receivablesTotal = (float) DB::query()->fromSub((clone $receivablesQuery)->toBase(), 'receivable_balances')->sum('balance');
        $payablesTotal = (float) DB::query()->fromSub((clone $payablesQuery)->toBase(), 'payable_balances')->sum('balance');
        $receivables = $includeDetails
            ? $receivablesQuery->get()->map(fn ($customer) => [
                'id' => $customer->id, 'name' => $customer->name,
                'phone' => $customer->phone_number1, 'balance' => (float) $customer->balance,
            ])
            : collect();
        $payables = $includeDetails
            ? $payablesQuery->get()->map(fn ($supplier) => [
                'id' => $supplier->id, 'name' => $supplier->name,
                'phone' => $supplier->phone, 'balance' => (float) $supplier->balance,
            ])
            : collect();

        $purchaseValue = $clothingEnabled ? (float) DB::table('purchase_items')->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.user_id', $userId)->where('purchases.status', 'received')->whereBetween('purchases.received_at', [$from, $to])->sum('purchase_items.line_total')
            : 0;
        $purchaseReturns = $clothingEnabled ? (float) PurchaseReturn::where('user_id', $userId)->whereBetween('return_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount') : 0;
        $inventoryValue = $clothingEnabled ? (float) DB::table('cloth_colors')->where('user_id', $userId)->sum(DB::raw('length * average_unit_cost')) : 0;

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'revenue' => $revenue, 'direct_costs' => $directCosts, 'operating_expenses' => $operatingExpenses,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'gross_profit' => $totalRevenue - $totalDirectCosts,
                'net_profit' => $totalRevenue - $totalDirectCosts - $totalOperatingExpenses,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_cash_flow' => $cashIn - $cashOut,
                'receivables' => $receivablesTotal,
                'payables' => $payablesTotal,
                'security_deposits_held' => $securityDepositsHeld,
                'tailoring_recoveries' => $tailoringRecoveries,
                'tailoring_loss_absorbed' => $tailoringLossAbsorbed,
                'security_deposits_received' => $securityDepositsReceived,
                'security_deposits_refunded' => $securityDepositsRefunded,
                'inventory_value' => $inventoryValue,
                'purchases' => $purchaseValue,
                'purchase_returns' => $purchaseReturns,
            ],
            'cash_in_breakdown' => array_filter([
                'گاہکوں سے وصولی' => $customerReceipts,
                'درزیوں سے وصول شدہ سیکیورٹی ڈپازٹ' => $tailoringEnabled ? $securityDepositsReceived : null,
            ], fn ($value) => $value !== null),
            'cash_out_breakdown' => array_filter([
                'سپلائر ادائیگیاں' => $clothingEnabled ? $supplierPayments : null,
                'درزی ادائیگیاں' => $tailoringEnabled ? $tailorPayments : null,
                'پروڈکشن ورکرز کو ادائیگیاں' => $tailoringEnabled ? $productionWorkerPayments : null,
                'درزیوں کو واپس کی گئی سیکیورٹی' => $tailoringEnabled ? $securityDepositsRefunded : null,
            ], fn ($value) => $value !== null) + $operatingExpenses,
            'receivables' => $receivables,
            'payables' => $payables,
        ];
    }

    public function receivablesQuery(int $userId, CarbonInterface $end, ?string $search = null, ?array $modules = null)
    {
        $to = $end->copy()->endOfDay();
        // Every customer's balance in one grouped pass over the shop's transactions. The transaction
        // ids are stored as text, so the owner is compared as text (keeps its index usable) and the
        // customer id is cast to a number before joining customers.
        $balances = Transaction::query()
            ->selectRaw('CAST(customerId AS UNSIGNED) AS customer_id, SUM(remainingBalance) AS balance')
            ->where('userId', (string) $userId)
            ->whereNotNull('customerId')
            ->where('created_at', '<=', $to)
            ->groupBy('customerId');
        if ($modules !== null) {
            $balances->whereIn('Order_type', array_values(array_filter([
                in_array('tailoring', $modules, true) ? 'Tailor' : null,
                in_array('clothing', $modules, true) ? 'Sale' : null,
                in_array('clothing', $modules, true) ? 'Sale Cancellation' : null,
                count(array_intersect(['tailoring', 'clothing'], $modules)) > 0 ? 'Payment' : null,
            ])));
        }

        return Customers::query()
            ->joinSub($balances, 'customer_balances', 'customer_balances.customer_id', '=', 'customers.id')
            ->select(['customers.id', 'customers.name', 'customers.phone_number1', 'customer_balances.balance'])
            ->where('customers.user_id', $userId)
            ->where('customer_balances.balance', '>', 0)
            ->when($search, fn ($query) => $query->where(function ($nested) use ($search) {
                $nested->where('customers.name', 'like', "%{$search}%")
                    ->orWhere('customers.phone_number1', 'like', "%{$search}%");
            }))
            ->orderBy('customers.name');
    }

    public function payablesQuery(int $userId, CarbonInterface $end, ?string $search = null, ?array $modules = null)
    {
        $to = $end->copy()->endOfDay();
        $gross = DB::table('purchases')->join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->selectRaw('purchases.supplier_id, SUM(purchase_items.line_total) AS gross')
            ->where('purchases.user_id', $userId)->where('purchases.status', 'received')
            ->where('purchases.received_at', '<=', $to)->groupBy('purchases.supplier_id');
        $returns = DB::table('purchase_returns')->selectRaw('supplier_id, SUM(total_amount) AS returned')
            ->where('user_id', $userId)->where('return_date', '<=', $end->toDateString())->groupBy('supplier_id');
        $payments = DB::table('supplier_payments')->selectRaw('supplier_id, SUM(amount) AS paid')
            ->where('user_id', $userId)->where('payment_date', '<=', $end->toDateString())->groupBy('supplier_id');
        $balance = 'COALESCE(suppliers.opening_balance, 0) + COALESCE(purchase_gross.gross, 0) - COALESCE(purchase_returns_total.returned, 0) - COALESCE(supplier_payments_total.paid, 0)';

        return Supplier::query()
            ->leftJoinSub($gross, 'purchase_gross', 'purchase_gross.supplier_id', '=', 'suppliers.id')
            ->leftJoinSub($returns, 'purchase_returns_total', 'purchase_returns_total.supplier_id', '=', 'suppliers.id')
            ->leftJoinSub($payments, 'supplier_payments_total', 'supplier_payments_total.supplier_id', '=', 'suppliers.id')
            ->select(['suppliers.id', 'suppliers.name', 'suppliers.phone'])
            ->selectRaw("{$balance} AS balance")
            ->where('suppliers.user_id', $userId)
            ->when($modules !== null && ! in_array('clothing', $modules, true), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereRaw("{$balance} > 0")
            ->when($search, fn ($query) => $query->where(function ($nested) use ($search) {
                $nested->where('suppliers.name', 'like', "%{$search}%")
                    ->orWhere('suppliers.phone', 'like', "%{$search}%");
            }))
            ->orderBy('suppliers.name');
    }

    /**
     * Cloth sold in the period per category, in the unit the category counts stock in
     * (meters for men's cloth, suits for women's 2/3-piece), with returns taken off.
     */
    public function salesByCategory(int $userId, CarbonInterface $start, CarbonInterface $end, ?array $modules = null): array
    {
        if ($modules !== null && ! in_array('clothing', $modules, true)) {
            return ['rows' => collect(), 'totals' => [], 'top_sets' => collect()];
        }
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay();

        $sold = SaleStock::financiallyActive()
            ->leftJoin('cloths', 'cloths.id', '=', 'sale_stocks.cloth_id')
            ->where('sale_stocks.user_id', $userId)
            ->whereBetween('sale_stocks.sellDate', [$from, $to])
            ->groupBy('cloths.cloth_category_id', 'cloths.stock_unit')
            ->selectRaw('cloths.cloth_category_id AS category_id, cloths.stock_unit AS unit')
            ->selectRaw('COUNT(*) AS sales_count, SUM(sale_stocks.length) AS quantity')
            ->selectRaw('SUM(sale_stocks.selling_price * sale_stocks.length) AS amount, SUM(COALESCE(sale_stocks.cost_total, 0)) AS cost')
            ->get();

        $returned = $this->counterReturnsQuery($userId, $from, $to)
            ->leftJoin('cloths', 'cloths.id', '=', 'sale_stocks.cloth_id')
            ->groupBy('cloths.cloth_category_id', 'cloths.stock_unit')
            ->selectRaw('cloths.cloth_category_id AS category_id, cloths.stock_unit AS unit')
            ->selectRaw('SUM(counter_sale_return_items.inventory_quantity) AS quantity, SUM(counter_sale_return_items.line_total) AS amount')
            ->selectRaw('SUM(CASE WHEN counter_sale_return_items.restocked = 1 THEN counter_sale_return_items.inventory_quantity * COALESCE(sale_stocks.cost_per_meter, 0) ELSE 0 END) AS cost')
            ->get();

        $categories = ClothCategory::with('parent')->where('user_id', $userId)
            ->whereIn('id', $sold->pluck('category_id')->merge($returned->pluck('category_id'))->filter()->unique())
            ->get()->keyBy('id');

        $rows = [];
        foreach ([$sold, $returned] as $index => $lines) {
            foreach ($lines as $line) {
                $key = ($line->category_id ?: 0).'-'.($line->unit ?: ClothCategory::UNIT_METER);
                $category = $line->category_id ? $categories->get($line->category_id) : null;
                $rows[$key] ??= [
                    'category' => $category ? $category->fullName() : 'بغیر کیٹیگری',
                    'main' => $category ? ($category->parent ?? $category)->localizedName() : 'بغیر کیٹیگری',
                    'sort' => $category ? [($category->parent ?? $category)->sort_order, $category->sort_order] : [PHP_INT_MAX, 0],
                    'unit' => $line->unit ?: ClothCategory::UNIT_METER,
                    'unit_label' => ClothCategory::unitLabelFor($line->unit),
                    'sales_count' => 0, 'sold' => 0.0, 'returned' => 0.0,
                    'amount' => 0.0, 'returned_amount' => 0.0, 'cost' => 0.0, 'returned_cost' => 0.0,
                ];
                if ($index === 0) {
                    $rows[$key]['sales_count'] += (int) $line->sales_count;
                    $rows[$key]['sold'] += (float) $line->quantity;
                    $rows[$key]['amount'] += (float) $line->amount;
                    $rows[$key]['cost'] += (float) $line->cost;
                } else {
                    $rows[$key]['returned'] += (float) $line->quantity;
                    $rows[$key]['returned_amount'] += (float) $line->amount;
                    $rows[$key]['returned_cost'] += (float) $line->cost;
                }
            }
        }

        $rows = collect($rows)->map(function (array $row) {
            $row['net'] = $row['sold'] - $row['returned'];
            $row['net_amount'] = $row['amount'] - $row['returned_amount'];
            $row['profit'] = $row['net_amount'] - ($row['cost'] - $row['returned_cost']);

            return $row;
        })->sortBy(fn (array $row) => sprintf('%012d-%012d-%s', $row['sort'][0], $row['sort'][1], $row['category']))->values();

        // Meters and suits can't be added together, so each unit gets its own total.
        $totals = $rows->groupBy('unit')->map(fn ($unitRows, $unit) => [
            'unit_label' => ClothCategory::unitLabelFor($unit),
            'net' => $unitRows->sum('net'),
            'net_amount' => $unitRows->sum('net_amount'),
        ])->all();

        // Sets ranked by what the customers kept: anything returned from those sales is taken off.
        $topSets = SaleStock::financiallyActive()
            ->join('cloths', 'cloths.id', '=', 'sale_stocks.cloth_id')
            ->leftJoinSub($this->returnedPerSaleLine($userId, $to), 'line_returns', 'line_returns.sale_stock_id', '=', 'sale_stocks.id')
            ->where('sale_stocks.user_id', $userId)
            ->whereBetween('sale_stocks.sellDate', [$from, $to])
            ->groupBy('cloths.id', 'cloths.name', 'cloths.stock_unit', 'cloths.cloth_category_id')
            ->selectRaw('cloths.id, cloths.name, cloths.stock_unit AS unit, cloths.cloth_category_id AS category_id')
            ->selectRaw('SUM(sale_stocks.length - COALESCE(line_returns.returned_quantity, 0)) AS quantity')
            ->selectRaw('SUM(sale_stocks.selling_price * sale_stocks.length - COALESCE(line_returns.returned_amount, 0)) AS amount')
            ->havingRaw('SUM(sale_stocks.length - COALESCE(line_returns.returned_quantity, 0)) > 0.0001')
            ->orderByDesc('amount')->limit(10)->get();
        $topCategories = ClothCategory::with('parent')->whereIn('id', $topSets->pluck('category_id')->filter())->get()->keyBy('id');
        $topSets = $topSets->map(fn ($set) => [
            'name' => $set->name ?: 'سیٹ '.$set->id,
            'category' => $set->category_id && $topCategories->has($set->category_id) ? $topCategories[$set->category_id]->fullName() : '',
            'quantity' => (float) $set->quantity,
            'unit' => $set->unit ?: ClothCategory::UNIT_METER,
            'unit_label' => ClothCategory::unitLabelFor($set->unit),
            'amount' => (float) $set->amount,
        ]);

        return ['rows' => $rows, 'totals' => $totals, 'top_sets' => $topSets];
    }

    /** Quantity and money that has come back from each counter sale line up to a date, to join on sale_stocks.id. */
    public function returnedPerSaleLine(int $userId, CarbonInterface $to)
    {
        return DB::table('counter_sale_return_items')
            ->join('counter_sale_returns', 'counter_sale_returns.id', '=', 'counter_sale_return_items.counter_sale_return_id')
            ->where('counter_sale_returns.user_id', $userId)
            ->where('counter_sale_returns.created_at', '<=', $to->copy()->endOfDay())
            ->groupBy('counter_sale_return_items.sale_stock_id')
            ->selectRaw('counter_sale_return_items.sale_stock_id')
            ->selectRaw('SUM(counter_sale_return_items.inventory_quantity) AS returned_quantity, SUM(counter_sale_return_items.line_total) AS returned_amount');
    }

    /** Counter return lines booked in a period, joined to the sale line they came from. */
    public function counterReturnsQuery(int $userId, CarbonInterface $from, CarbonInterface $to)
    {
        return DB::table('counter_sale_return_items')
            ->join('counter_sale_returns', 'counter_sale_returns.id', '=', 'counter_sale_return_items.counter_sale_return_id')
            ->join('sale_stocks', 'sale_stocks.id', '=', 'counter_sale_return_items.sale_stock_id')
            ->where('counter_sale_returns.user_id', $userId)
            ->whereBetween('counter_sale_returns.created_at', [$from, $to]);
    }
}
