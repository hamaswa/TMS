<?php

namespace App\Services;

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
use App\Models\Transaction;
use App\Models\Workers;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    public function build(int $userId, CarbonInterface $start, CarbonInterface $end, bool $includeDetails = true, ?array $modules = null): array
    {
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay();
        $tailoringEnabled = $modules === null || in_array('tailoring', $modules, true);
        $clothingEnabled = $modules === null || in_array('clothing', $modules, true);

        $tailoringRevenue = $tailoringEnabled ? (float) Order::where('userId', $userId)->whereBetween('created_at', [$from, $to])->sum('totalPayment') : 0;
        $counterRevenue = $clothingEnabled ? (float) SaleStock::where('user_id', $userId)->whereBetween('sellDate', [$from, $to])->sum(DB::raw('selling_price * length')) : 0;
        $manualSalesRevenue = $clothingEnabled ? (float) DB::table('saledetails')->join('sales', 'saledetails.sale_id', '=', 'sales.id')
            ->where('sales.user_id', $userId)->whereBetween('sales.created_at', [$from, $to])->sum(DB::raw('saledetails.quantity * saledetails.price'))
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

        $counterCogs = $clothingEnabled ? (float) SaleStock::where('user_id', $userId)->whereBetween('sellDate', [$from, $to])->sum('cost_total') : 0;
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
        $tailorLabor = $tailoringEnabled ? (float) Order::where('userId', $userId)->whereBetween('created_at', [$from, $to])->sum(DB::raw('tailor_price * suitQuantity')) : 0;
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
            ($tailoringEnabled || $clothingEnabled) ? 'Payment' : null,
        ]));
        $customerReceipts = (float) Transaction::where('userId', $userId)->whereIn('Order_type', $transactionTypes)
            ->whereBetween('created_at', [$from, $to])->sum('recivedPayment');
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
        $cashOut = $supplierPayments + $tailorPayments + $productionWorkerPayments + $monthlyExpenses + $dailyExpenses + $workerSalaries;

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
                'cash_in' => $customerReceipts,
                'cash_out' => $cashOut,
                'net_cash_flow' => $customerReceipts - $cashOut,
                'receivables' => $receivablesTotal,
                'payables' => $payablesTotal,
                'inventory_value' => $inventoryValue,
                'purchases' => $purchaseValue,
                'purchase_returns' => $purchaseReturns,
            ],
            'cash_out_breakdown' => array_filter([
                'سپلائر ادائیگیاں' => $clothingEnabled ? $supplierPayments : null,
                'درزی ادائیگیاں' => $tailoringEnabled ? $tailorPayments : null,
                'پروڈکشن ورکرز کو ادائیگیاں' => $tailoringEnabled ? $productionWorkerPayments : null,
            ], fn ($value) => $value !== null) + $operatingExpenses,
            'receivables' => $receivables,
            'payables' => $payables,
        ];
    }

    public function receivablesQuery(int $userId, CarbonInterface $end, ?string $search = null, ?array $modules = null)
    {
        $to = $end->copy()->endOfDay();
        $balance = Transaction::query()->selectRaw('COALESCE(SUM(remainingBalance), 0)')
            ->whereColumn('customerId', 'customers.id')
            ->where('userId', $userId)
            ->where('created_at', '<=', $to);
        if ($modules !== null) {
            $balance->whereIn('Order_type', array_values(array_filter([
                in_array('tailoring', $modules, true) ? 'Tailor' : null,
                in_array('clothing', $modules, true) ? 'Sale' : null,
                count(array_intersect(['tailoring', 'clothing'], $modules)) > 0 ? 'Payment' : null,
            ])));
        }

        return Customers::query()
            ->select(['customers.id', 'customers.name', 'customers.phone_number1'])
            ->selectSub(clone $balance, 'balance')
            ->where('customers.user_id', $userId)
            ->whereRaw('('.$balance->toSql().') > 0', $balance->getBindings())
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
}
