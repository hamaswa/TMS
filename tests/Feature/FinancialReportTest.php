<?php

namespace Tests\Feature;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\DaliyExpenses;
use App\Models\Expenses;
use App\Models\OnlineOrder;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\ProductionWorker;
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
