<?php

namespace Tests\Feature;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothCategory;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\DaliyExpenses;
use App\Models\Expenses;
use App\Models\OnlineOrder;
use App\Models\Order;
use App\Models\ProductionWorker;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleStock;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Tailor;
use App\Models\TailorRecord;
use App\Models\TailorSecurityDepositTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkerLedgerEntry;
use App\Models\Workers;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_report_reconciles_profit_cash_receivables_and_payables(): void
    {
        [$owner, $customer, $tailor, $cloth, $color] = $this->baseData();
        $date = now()->startOfMonth()->addDay();

        $order = Order::create([
            'customerId' => $customer->id, 'sub_customer' => $customer->id, 'suitQuantity' => 2,
            'totalPayment' => 1000, 'tailorId' => $tailor->id, 'tailor_price' => 200,
            'userId' => $owner->id, 'returnDate' => $date->copy()->addDays(5)->toDateString(), 'created_at' => $date,
        ]);
        Transaction::create([
            'customerId' => $customer->id, 'orderId' => $order->id, 'userId' => $owner->id,
            'Order_type' => 'Tailor', 'recivedPayment' => 600, 'remainingBalance' => 400, 'created_at' => $date,
        ]);
        Transaction::create([
            'customerId' => $customer->id, 'userId' => $owner->id,
            'Order_type' => 'Payment', 'recivedPayment' => 100, 'remainingBalance' => -100, 'created_at' => $date,
        ]);
        SaleStock::create([
            'user_id' => $owner->id, 'cloth_id' => $cloth->id, 'cloth_color_id' => $color->id,
            'cloth_type_id' => $cloth->cloth_type_id, 'cloth_brand_id' => $cloth->cloth_brand_id,
            'color' => $color->color, 'length' => 2, 'selling_price' => 150, 'cost_per_meter' => 80,
            'cost_total' => 160, 'sellDate' => $date, 'created_at' => $date,
        ]);
        $sale = Sale::create(['user_id' => $owner->id, 'customer_name' => 'Walk-in', 'created_at' => $date]);
        $sale->detail()->create(['product_name' => 'Accessory', 'quantity' => 2, 'price' => 50]);
        $onlineCustomer = $this->userWithRole('user');
        OnlineOrder::create([
            'user_id' => $onlineCustomer->id, 'admin_user_id' => $owner->id, 'cloth_id' => $cloth->id,
            'length' => 2, 'price' => 100, 'cost_per_meter' => 50, 'cost_total' => 100,
            'color' => $color->color, 'status' => 'pending', 'created_at' => $date,
        ]);
        Expenses::create(['user_id' => $owner->id, 'Monthly_Rent' => 100, 'Monthly_Bill' => 50, 'Extra_Expenses' => 0, 'expense_date' => $date]);
        DaliyExpenses::create(['user_id' => $owner->id, 'Expense_name' => 'Tea', 'Expense_payment' => 20, 'created_at' => $date]);
        Workers::create(['user_id' => $owner->id, 'Worker_Name' => 'Helper', 'Worker_salary' => 30, 'dateentered' => $date]);
        TailorRecord::create(['tailor_id' => $tailor->id, 'order_id' => $order->id, 'amount' => 100, 'comment' => 'salary', 'created_at' => $date]);
        TailorSecurityDepositTransaction::create([
            'tailor_id' => $tailor->id, 'user_id' => $owner->id, 'transaction_type' => 'received',
            'amount' => 1000, 'transaction_date' => $date, 'created_at' => $date,
        ]);
        TailorSecurityDepositTransaction::create([
            'tailor_id' => $tailor->id, 'user_id' => $owner->id, 'transaction_type' => 'refunded',
            'amount' => 300, 'transaction_date' => $date, 'created_at' => $date,
        ]);

        $cutter = ProductionWorker::create([
            'user_id' => $owner->id, 'name' => 'Report Cutter', 'relationship_type' => 'contractor', 'active' => true,
        ]);
        WorkerLedgerEntry::create([
            'user_id' => $owner->id, 'production_worker_id' => $cutter->id, 'entry_type' => 'earning',
            'amount' => 50, 'entry_date' => $date,
        ]);
        WorkerLedgerEntry::create([
            'user_id' => $owner->id, 'production_worker_id' => $cutter->id, 'entry_type' => 'payment',
            'amount' => -20, 'entry_date' => $date,
        ]);

        $supplier = Supplier::create(['user_id' => $owner->id, 'name' => 'Report Supplier', 'opening_balance' => 50]);
        $purchase = Purchase::create([
            'user_id' => $owner->id, 'supplier_id' => $supplier->id, 'purchase_number' => 'PO-REPORT-1',
            'purchase_date' => $date, 'status' => 'received', 'total_amount' => 450,
            'paid_amount' => 100, 'balance_amount' => 350, 'received_at' => $date,
        ]);
        $item = $purchase->items()->create([
            'cloth_id' => $cloth->id, 'cloth_color_id' => $color->id, 'color' => $color->color,
            'quantity' => 5, 'unit_cost' => 100, 'line_total' => 500, 'received_quantity' => 5,
        ]);
        PurchaseReturn::create([
            'user_id' => $owner->id, 'supplier_id' => $supplier->id, 'purchase_id' => $purchase->id,
            'return_number' => 'PR-REPORT-1', 'return_date' => $date, 'total_amount' => 50,
        ]);
        SupplierPayment::create([
            'user_id' => $owner->id, 'supplier_id' => $supplier->id, 'purchase_id' => $purchase->id,
            'payment_date' => $date, 'amount' => 100,
        ]);

        $other = $this->userWithRole('shop_owner');
        Order::create(['suitQuantity' => 1, 'totalPayment' => 9999, 'userId' => $other->id, 'created_at' => $date]);

        $report = app(FinancialReportService::class)->build($owner->id, now()->startOfMonth(), now()->endOfMonth());

        $this->assertEquals(1600, $report['summary']['total_revenue']);
        $this->assertEquals(890, $report['summary']['gross_profit']);
        $this->assertEquals(690, $report['summary']['net_profit']);
        $this->assertEquals(1700, $report['summary']['cash_in']);
        $this->assertEquals(720, $report['summary']['cash_out']);
        $this->assertEquals(980, $report['summary']['net_cash_flow']);
        $this->assertEquals(700, $report['summary']['security_deposits_held']);
        $this->assertEquals(1000, $report['cash_in_breakdown']['درزیوں سے وصول شدہ سیکیورٹی ڈپازٹ']);
        $this->assertEquals(300, $report['cash_out_breakdown']['درزیوں کو واپس کی گئی سیکیورٹی']);
        $this->assertEquals(50, $report['direct_costs']['پروڈکشن ورکرز کی اجرت']);
        $this->assertEquals(20, $report['cash_out_breakdown']['پروڈکشن ورکرز کو ادائیگیاں']);
        $this->assertEquals(300, $report['summary']['receivables']);
        $this->assertEquals(400, $report['summary']['payables']);
        $this->assertEquals(500, $report['summary']['purchases']);
        $this->assertEquals(50, $report['summary']['purchase_returns']);
    }

    public function test_dashboard_and_csv_export_are_shop_owner_only_and_tenant_scoped(): void
    {
        [$owner, $customer] = $this->baseData();
        $stockSeller = $this->userWithRole('stock_seller');
        Transaction::create([
            'customerId' => $customer->id,
            'userId' => $owner->id,
            'Order_type' => 'Payment',
            'remainingBalance' => 50,
            'recivedPayment' => 0,
        ]);

        $this->actingAs($owner)->get(route('admin.financial-reports.index'))
            ->assertOk()
            ->assertSeeText('03009990000');

        $this->actingAs($owner)->get(route('admin.financial-reports.index'))
            ->assertOk()->assertSee('مالیاتی ڈیش بورڈ')->assertSee('نفع و نقصان');
        $this->actingAs($owner)->get(route('admin.financial-reports.export', ['section' => 'summary']))
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($stockSeller)->get(route('admin.financial-reports.index'))->assertForbidden();
    }

    public function test_report_has_sales_and_tailoring_views_each_with_its_own_tabs(): void
    {
        [$owner] = $this->baseData();

        // Sales opens first, on its category tab.
        $this->actingAs($owner)->get(route('admin.financial-reports.index'))
            ->assertOk()
            ->assertSeeText('رپورٹس')
            ->assertSee('data-rp-tab="all-sales"', false)
            ->assertSee('id="rp-panel-categories" role="tabpanel" aria-labelledby="rp-tab-categories" >', false)
            ->assertSee('id="rp-panel-customers" role="tabpanel" aria-labelledby="rp-tab-customers"  hidden', false)
            ->assertDontSee('data-rp-tab="tailors"', false);

        // The tailoring view has its own tabs.
        $this->actingAs($owner)->get(route('admin.financial-reports.index', ['view' => 'tailoring']))
            ->assertOk()
            ->assertSeeText('حالیہ سلائی آرڈرز')
            ->assertSee('data-rp-tab="tailors"', false)
            ->assertSee('data-rp-tab="payouts"', false)
            ->assertSee('id="rp-panel-orders" role="tabpanel" aria-labelledby="rp-tab-orders" >', false);

        // A customer search comes back to the customers tab, and keeps it in the forms.
        $this->actingAs($owner)->get(route('admin.financial-reports.index', ['view' => 'tailoring', 'customers_q' => 'x']))
            ->assertOk()
            ->assertSee('id="rp-panel-customers" role="tabpanel" aria-labelledby="rp-tab-customers" >', false)
            ->assertSee('name="tab" value="customers"', false);

        $this->actingAs($owner)->get(route('admin.financial-reports.index', ['tab' => 'stock']))
            ->assertOk()
            ->assertSee('id="rp-panel-stock" role="tabpanel" aria-labelledby="rp-tab-stock" >', false);
    }

    public function test_sales_tab_reports_each_category_in_meters_or_suits_net_of_returns(): void
    {
        [$owner, , , $menCloth, $menColor] = $this->baseData();
        $date = now()->startOfMonth()->addDay();
        $men = ClothCategory::create(['user_id' => $owner->id, 'name' => 'Men', 'name_ur' => 'مردانہ', 'slug' => 'men']);
        $women = ClothCategory::create(['user_id' => $owner->id, 'name' => 'Women', 'name_ur' => 'زنانہ', 'slug' => 'women', 'stock_unit' => 'suit']);
        $threePiece = ClothCategory::create(['user_id' => $owner->id, 'parent_id' => $women->id, 'name' => '3 Piece', 'name_ur' => 'تھری پیس', 'slug' => 'women-3-piece', 'stock_unit' => 'suit']);
        $menCloth->update(['cloth_category_id' => $men->id]);
        $suitCloth = Cloth::create([
            'name' => 'Gulnaar Lawn', 'cloth_type_id' => $menCloth->cloth_type_id, 'cloth_brand_id' => $menCloth->cloth_brand_id,
            'cloth_category_id' => $threePiece->id, 'price' => 3200, 'sale_price' => 4250, 'user_id' => $owner->id,
        ]);
        $suitColor = ClothColor::create(['cloth_id' => $suitCloth->id, 'color' => 'Rose', 'length' => 20, 'average_unit_cost' => 3200, 'user_id' => $owner->id]);
        $sale = fn (Cloth $cloth, ClothColor $color, float $quantity, float $price, float $cost) => SaleStock::create([
            'user_id' => $owner->id, 'cloth_id' => $cloth->id, 'cloth_color_id' => $color->id,
            'cloth_type_id' => $cloth->cloth_type_id, 'cloth_brand_id' => $cloth->cloth_brand_id, 'color' => $color->color,
            'length' => $quantity, 'selling_price' => $price, 'cost_per_meter' => $cost, 'cost_total' => $quantity * $cost,
            'sellDate' => $date, 'created_at' => $date,
        ]);
        $sale($menCloth, $menColor, 4.5, 150, 80);
        $sale($menCloth, $menColor, 3, 150, 80);
        $returnedSuitSale = $sale($suitCloth, $suitColor, 3, 4250, 3200);

        // One of the three suits came back.
        $receipt = \App\Models\CounterSaleReceipt::create(['receipt_number' => 'RCPT-TEST-1', 'user_id' => $owner->id, 'status' => 'completed']);
        $returnId = \Illuminate\Support\Facades\DB::table('counter_sale_returns')->insertGetId([
            'reference' => 'RET-TEST-1', 'user_id' => $owner->id, 'counter_sale_receipt_id' => $receipt->id,
            'return_total' => 4250, 'reason' => 'Wrong colour', 'created_at' => $date, 'updated_at' => $date,
        ]);
        \Illuminate\Support\Facades\DB::table('counter_sale_return_items')->insert([
            'counter_sale_return_id' => $returnId, 'sale_stock_id' => $returnedSuitSale->id, 'quantity' => 1,
            'inventory_quantity' => 1, 'unit_price' => 4250, 'line_total' => 4250, 'restocked' => true,
            'created_at' => $date, 'updated_at' => $date,
        ]);

        $sales = app(FinancialReportService::class)->salesByCategory($owner->id, now()->startOfMonth(), now()->endOfMonth());
        $rows = $sales['rows']->keyBy('category');

        $this->assertEquals(7.5, $rows['Men']['net']);
        $this->assertSame('میٹر', $rows['Men']['unit_label']);
        $this->assertEquals(1125, $rows['Men']['net_amount']);
        $this->assertEquals(1125 - 600, $rows['Men']['profit']);
        $this->assertEquals(3, $rows['Women › 3 Piece']['sold']);
        $this->assertEquals(1, $rows['Women › 3 Piece']['returned']);
        $this->assertEquals(2, $rows['Women › 3 Piece']['net']);
        $this->assertSame('سوٹ', $rows['Women › 3 Piece']['unit_label']);
        $this->assertEquals(8500, $rows['Women › 3 Piece']['net_amount']);
        $this->assertEquals(8500 - 6400, $rows['Women › 3 Piece']['profit']);
        $this->assertEquals(2, $sales['totals']['suit']['net']);

        // The returned suit also comes off revenue, its cost, the trend chart and the top sets.
        $report = app(FinancialReportService::class)->build($owner->id, now()->startOfMonth(), now()->endOfMonth(), false);
        $this->assertEquals(1125 + 8500, $report['revenue']['کاؤنٹر کپڑا فروخت']);
        $this->assertEquals(600 + 6400, $report['direct_costs']['کاؤنٹر فروخت کی لاگت']);
        $daily = app(\App\Services\ReportDashboardService::class)->dailySeries($owner->id, now()->startOfMonth(), now()->endOfMonth(), ['clothing']);
        $this->assertEquals(1125 + 8500, $daily[$date->toDateString()]['cloth']);
        $topSuit = $sales['top_sets']->firstWhere('name', 'Gulnaar Lawn');
        $this->assertEquals(2, $topSuit['quantity']);
        $this->assertEquals(8500, $topSuit['amount']);

        $this->actingAs($owner)->get(route('admin.financial-reports.index', ['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->endOfMonth()->toDateString()]))
            ->assertOk()
            ->assertSee('id="rp-panel-categories" role="tabpanel" aria-labelledby="rp-tab-categories" >', false)
            ->assertSeeText('Women › 3 Piece')->assertSeeText('7.50');

        // The full sales list can be narrowed to one main category, which includes its sub categories.
        $this->actingAs($owner)->get(route('admin.financial-reports.index', ['sales_category' => $women->id]))
            ->assertOk()
            ->assertViewHas('report', fn ($report) => $report['lines']->total() === 1)
            ->assertSee('id="rp-panel-all-sales" role="tabpanel" aria-labelledby="rp-tab-all-sales" >', false);
    }

    public function test_tailoring_view_splits_customer_dues_and_shows_tailor_work_and_payouts(): void
    {
        [$owner, $customer, $tailor] = $this->baseData();
        $order = Order::create([
            'customerId' => $customer->id, 'sub_customer' => $customer->id, 'suitQuantity' => 2, 'totalPayment' => 3000,
            'tailorId' => $tailor->id, 'tailor_price' => 400, 'tailor_paid_amount' => 300, 'status' => 'stitching',
            'userId' => $owner->id, 'returnDate' => now()->addWeek()->toDateString(),
        ]);
        // 3000 stitching bill, 1000 of it paid against the order.
        Transaction::create(['customerId' => $customer->id, 'userId' => $owner->id, 'orderId' => $order->id, 'Order_type' => 'Tailor', 'recivedPayment' => 0, 'remainingBalance' => 3000]);
        Transaction::create(['customerId' => $customer->id, 'userId' => $owner->id, 'orderId' => $order->id, 'Order_type' => 'Payment', 'recivedPayment' => 1000, 'remainingBalance' => -1000]);
        // A separate cloth sale of 500 still unpaid.
        Transaction::create(['customerId' => $customer->id, 'userId' => $owner->id, 'Order_type' => 'Sale', 'recivedPayment' => 0, 'remainingBalance' => 500]);
        TailorRecord::create(['tailor_id' => $tailor->id, 'amount' => 300, 'comment' => 'weekly']);

        $balances = app(\App\Services\CustomerLedgerService::class)->sideBalances($owner->id);
        $this->assertEquals(2000, $balances[$customer->id]['tailoring']);
        $this->assertEquals(500, $balances[$customer->id]['sales']);
        $this->assertEquals(2500, $balances[$customer->id]['total']);

        $dashboard = app(\App\Services\ReportDashboardService::class);
        $metrics = $dashboard->tailoringMetrics($owner->id, now()->startOfMonth(), now()->endOfMonth());
        $this->assertSame(1, $metrics['orders']);
        $this->assertSame(2, $metrics['suits']);
        $this->assertEquals(3000, $metrics['revenue']);
        $this->assertEquals(1000, $metrics['received']);
        $tailors = $dashboard->tailors($owner->id, now()->startOfMonth(), now()->endOfMonth());
        $this->assertEquals(800, $tailors->first()->earned);
        $this->assertEquals(500, $tailors->first()->balance);
        $this->assertEquals(300, $dashboard->payouts($owner->id, now()->startOfMonth(), now()->endOfMonth())->sum('amount'));

        $this->actingAs($owner)->get(route('admin.financial-reports.index', ['view' => 'tailoring']))
            ->assertOk()
            ->assertViewHas('report', fn ($report) => $report['customers']['total'] == 2000)
            // Without a detailed work-status setting, workshop steps show as one simple status.
            ->assertSeeText('کارخانے میں ہے')
            ->assertSeeText('Report Tailor')
            ->assertSeeText('weekly');
        $this->actingAs($owner)->get(route('admin.financial-reports.index', ['view' => 'sales']))
            ->assertOk()
            ->assertViewHas('report', fn ($report) => $report['customers']['total'] == 500);
    }

    public function test_cancelled_manual_sale_is_excluded_and_its_reversal_neutralizes_cash_and_receivable(): void
    {
        [$owner, $customer] = $this->baseData();
        $sale = Sale::create([
            'user_id' => $owner->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'status' => 'cancelled',
            'cancellation_reason' => 'Duplicate invoice',
            'cancelled_at' => now(),
            'cancelled_by_user_id' => $owner->id,
        ]);
        $sale->detail()->create(['product_name' => 'Cancelled accessory', 'quantity' => 1, 'price' => 500]);
        Transaction::create([
            'userId' => $owner->id, 'customerId' => $customer->id, 'sale_id' => $sale->id,
            'Order_type' => 'Sale', 'recivedPayment' => 200, 'remainingBalance' => 300,
        ]);
        Transaction::create([
            'userId' => $owner->id, 'customerId' => $customer->id, 'sale_id' => $sale->id,
            'Order_type' => 'Sale Cancellation', 'recivedPayment' => -200, 'remainingBalance' => -300,
        ]);

        $report = app(FinancialReportService::class)->build($owner->id, now()->startOfMonth(), now()->endOfMonth());

        $this->assertEquals(0, $report['revenue']['مصنوعات کی فروخت']);
        $this->assertEquals(0, $report['summary']['total_revenue']);
        $this->assertEquals(0, $report['summary']['cash_in']);
        $this->assertEquals(0, $report['summary']['receivables']);
    }

    private function baseData(): array
    {
        $owner = $this->userWithRole('shop_owner');
        $customer = Customers::create(['name' => 'Report Customer', 'phone_number1' => '03009990000', 'user_id' => $owner->id]);
        $tailor = Tailor::create(['name' => 'Report Tailor', 'phone_number1' => '03008880000', 'password' => bcrypt('password'), 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Report Type', 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => 'Report Brand', 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id, 'price' => 80, 'sale_price' => 150, 'user_id' => $owner->id]);
        $color = ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'Blue', 'length' => 10, 'average_unit_cost' => 80, 'user_id' => $owner->id]);

        return [$owner, $customer, $tailor, $cloth, $color];
    }

    private function userWithRole(string $name): User
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
