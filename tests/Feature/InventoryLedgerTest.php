<?php

namespace Tests\Feature;

use App\Models\BusinessActivityLog;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\CounterSaleReceipt;
use App\Models\Customers;
use App\Models\OnlineOrder;
use App\Models\Purchase;
use App\Models\SaleStock;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_receipt_updates_moving_weighted_average_cost(): void
    {
        [$owner, , $color] = $this->stock(10, 80);
        $supplier = Supplier::create(['user_id' => $owner->id, 'name' => 'Cost Supplier']);
        $this->actingAs($owner)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id, 'purchase_date' => now()->toDateString(),
            'cloth_color_id' => [$color->id], 'quantity' => [10], 'unit_cost' => [120],
        ]);
        $purchase = Purchase::where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)->patch(route('admin.purchases.receive', $purchase))->assertRedirect();

        $color->refresh();
        $this->assertEquals(20, (float) $color->length);
        $this->assertEquals(100, (float) $color->average_unit_cost);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'purchase_receipt', 'quantity' => 10, 'balance_after' => 20,
        ]);
        $this->actingAs($owner)->get(route('admin.stock.index'))
            ->assertOk()
            ->assertSeeText('Rs:100.00')
            ->assertSeeText('Rs:2,000.00');
    }

    public function test_counter_sale_records_stock_cost_and_inventory_movement(): void
    {
        [$owner, $cloth, $color] = $this->stock(10, 100);
        $customer = Customers::create(['name' => 'Counter Customer', 'phone_number1' => '03001112222', 'user_id' => $owner->id]);

        $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$cloth->cloth_brand_id], 'cloth_type' => [$cloth->cloth_type_id],
            'color' => [$color->color], 'per_meter' => [150], 'clothes_rack' => [null], 'length' => [2],
            'c_name' => $customer->name.'|'.$customer->id, 'payment' => 300, 'remain' => 0,
            'payment_method' => 'raast', 'payment_reference' => 'RAAST-300', 'paid_on' => '2026-07-28',
        ])->assertRedirect();

        $sale = SaleStock::where('user_id', $owner->id)->firstOrFail();
        $receipt = CounterSaleReceipt::where('user_id', $owner->id)->firstOrFail();
        $this->assertEquals(8, (float) $color->fresh()->length);
        $this->assertSame($receipt->id, $sale->counter_sale_receipt_id);
        $this->assertSame($sale->id, $receipt->first_sale_stock_id);
        $this->assertStringStartsWith('TMSC-', $receipt->receipt_number);
        $this->assertEquals(100, (float) $sale->cost_per_meter);
        $this->assertEquals(200, (float) $sale->cost_total);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'counter_sale', 'quantity' => -2, 'balance_after' => 8,
            'reference_id' => $sale->id,
        ]);
        $this->assertDatabaseHas('transactions', [
            'customerId' => $customer->id,
            'payment_method' => 'raast',
            'payment_reference' => 'RAAST-300',
            'paid_on' => '2026-07-28 00:00:00',
        ]);
        $this->actingAs($owner)->get(route('admin.customers.statement', $customer))
            ->assertOk()
            ->assertViewHas('sales', fn ($sales) => $sales->count() === 1
                && $sales->first()->id === $sale->id
                && $sales->first()->items_count === 1);
    }

    public function test_counter_sale_cancellation_restores_every_item_and_reverses_customer_and_financial_totals_once(): void
    {
        [$owner, $cloth, $firstColor] = $this->stock(10, 100);
        $secondColor = ClothColor::create([
            'cloth_id' => $cloth->id,
            'color' => 'Navy Blue',
            'length' => 12,
            'average_unit_cost' => 90,
            'user_id' => $owner->id,
        ]);
        $customer = Customers::create([
            'name' => 'Muhammad Hamza',
            'phone_number1' => '03001234567',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$cloth->cloth_brand_id, $cloth->cloth_brand_id],
            'cloth_type' => [$cloth->cloth_type_id, $cloth->cloth_type_id],
            'color' => [$firstColor->color, $secondColor->color],
            'per_meter' => [150, 160],
            'clothes_rack' => ['A-1', 'A-2'],
            'length' => [2, 3],
            'c_name' => $customer->name.'|'.$customer->id,
            'payment' => 300,
            'remain' => 480,
            'payment_method' => 'raast',
            'payment_reference' => 'RAAST-SALE-300',
            'paid_on' => now()->toDateString(),
        ]);

        $receipt = CounterSaleReceipt::where('user_id', $owner->id)->firstOrFail();
        $items = SaleStock::where('counter_sale_receipt_id', $receipt->id)->orderBy('id')->get();
        $response->assertRedirect(route('admin.printStock', [
            'id' => $items->first()->id,
            'customerId' => $customer->id,
        ]));
        $this->assertCount(2, $items);
        $this->assertEquals(8, (float) $firstColor->fresh()->length);
        $this->assertEquals(9, (float) $secondColor->fresh()->length);

        $this->actingAs($owner)->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSeeText($receipt->receipt_number)
            ->assertSeeText('کاؤنٹر فروخت منسوخ کریں')
            ->assertSee('name="cancellation_reason"', false)
            ->assertSee('name="refund_method"', false)
            ->assertSee('assets/js/confirm-modal.js', false)
            ->assertSee('data-confirm="کیا آپ یہ کاؤنٹر فروخت منسوخ کر کے تمام کپڑا اسٹاک اور گاہک کا کھاتہ واپس کرنا چاہتے ہیں؟"', false);

        [$otherOwner] = $this->stock(5, 50);
        $this->actingAs($otherOwner)->patch(route('admin.counter-sales.cancel', $items->first()), [
            'cancellation_reason' => 'Wrong tenant attempt',
            'refund_method' => 'cash',
        ])->assertNotFound();
        $this->assertSame('completed', $receipt->fresh()->status);

        $this->actingAs($owner)->from($response->headers->get('Location'))
            ->patch(route('admin.counter-sales.cancel', $items->first()), [])
            ->assertRedirect($response->headers->get('Location'))
            ->assertSessionHasErrors('cancellation_reason');
        $this->actingAs($owner)->from($response->headers->get('Location'))
            ->patch(route('admin.counter-sales.cancel', $items->first()), [
                'cancellation_reason' => 'Customer returned all cloth',
            ])->assertSessionHasErrors('refund_method');
        $this->actingAs($owner)->from($response->headers->get('Location'))
            ->patch(route('admin.counter-sales.cancel', $items->first()), [
                'cancellation_reason' => 'Customer returned all cloth',
                'refund_method' => 'raast',
            ])->assertSessionHasErrors('refund_reference');

        $cancel = $this->actingAs($owner)->patch(route('admin.counter-sales.cancel', $items->first()), [
            'cancellation_reason' => 'Customer returned all cloth before cutting',
            'refund_method' => 'raast',
            'refund_reference' => 'RAAST-REFUND-300',
        ]);
        $cancel->assertRedirect(route('admin.printStock', [
            'id' => $items->first()->id,
            'customerId' => $customer->id,
        ]));

        $receipt->refresh();
        $this->assertSame('cancelled', $receipt->status);
        $this->assertSame('Customer returned all cloth before cutting', $receipt->cancellation_reason);
        $this->assertSame($owner->id, $receipt->cancelled_by_user_id);
        $this->assertNotNull($receipt->cancelled_at);
        $this->assertDatabaseCount('sale_stocks', 2);
        $this->assertEquals(10, (float) $firstColor->fresh()->length);
        $this->assertEquals(12, (float) $secondColor->fresh()->length);
        $this->assertDatabaseCount('inventory_movements', 4);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'counter_sale_cancellation',
            'cloth_color_id' => $firstColor->id,
            'quantity' => 2,
            'balance_after' => 10,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'counter_sale_cancellation',
            'cloth_color_id' => $secondColor->id,
            'quantity' => 3,
            'balance_after' => 12,
        ]);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', [
            'Order_type' => 'Sale Cancellation',
            'sale_id' => $items->first()->id,
            'customerId' => $customer->id,
            'remainingBalance' => -480,
            'recivedPayment' => -300,
            'payment_method' => 'raast',
            'payment_reference' => 'RAAST-REFUND-300',
        ]);
        $this->assertEquals(0, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));
        $this->assertEquals(0, (float) Transaction::where('customerId', $customer->id)->sum('recivedPayment'));

        $report = app(FinancialReportService::class)->build($owner->id, now()->startOfDay(), now()->endOfDay());
        $this->assertEquals(0, $report['revenue']['کاؤنٹر کپڑا فروخت']);
        $this->assertEquals(0, $report['direct_costs']['کاؤنٹر فروخت کی لاگت']);
        $this->assertEquals(0, $report['summary']['cash_in']);
        $this->assertEquals(0, $report['summary']['receivables']);

        $this->actingAs($owner)->get(route('admin.printStock', [
            'id' => $items->first()->id,
            'customerId' => $customer->id,
        ]))->assertOk()
            ->assertSeeText('یہ کاؤنٹر فروخت منسوخ ہو چکی ہے')
            ->assertSeeText('Customer returned all cloth before cutting')
            ->assertSeeText('اسٹاک اور گاہک کا کھاتہ واپس کر دیا گیا ہے')
            ->assertSeeText('RAAST-REFUND-300')
            ->assertDontSee('name="cancellation_reason"', false);
        $this->actingAs($owner)->get(route('admin.inventory-ledger.index', [
            'movement_type' => 'counter_sale_cancellation',
        ]))->assertOk()->assertSeeText('کاؤنٹر فروخت منسوخی');

        $this->actingAs($owner)->from(route('admin.printStock', [
            'id' => $items->first()->id,
            'customerId' => $customer->id,
        ]))->patch(route('admin.counter-sales.cancel', $items->first()), [
            'cancellation_reason' => 'Duplicate cancellation attempt',
            'refund_method' => 'cash',
        ])->assertSessionHasErrors('cancellation_reason');
        $this->assertEquals(10, (float) $firstColor->fresh()->length);
        $this->assertEquals(12, (float) $secondColor->fresh()->length);
        $this->assertDatabaseCount('inventory_movements', 4);
        $this->assertDatabaseCount('transactions', 2);

        $activity = new BusinessActivityLog([
            'route_name' => 'admin.counter-sales.cancel',
            'method' => 'PATCH',
        ]);
        $this->assertSame('کاؤنٹر فروخت منسوخ کر کے اسٹاک اور کھاتہ واپس کیا', $activity->actionDescription());
    }

    public function test_counter_sale_form_uses_responsive_fields_and_one_customer_section(): void
    {
        [$owner] = $this->stock(10, 100);

        $response = $this->actingAs($owner)->get(route('admin.sellCloth'));

        $response->assertOk()
            ->assertSeeText('گاہک کی معلومات')
            ->assertSeeText('مزید کپڑا شامل کریں')
            ->assertSee('assets/js/form-accessibility.js', false)
            ->assertDontSee('width: 120%', false)
            ->assertDontSee('width: 150%', false);

        $this->assertSame(1, substr_count($response->getContent(), 'name="c_name"'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="phone"'));
    }

    public function test_counter_sale_derives_balance_server_side_and_receipt_works_without_settings(): void
    {
        [$owner, $cloth, $color] = $this->stock(10, 100);
        $owner->update(['name' => 'Fallback Shop']);
        $customer = Customers::create(['name' => 'Balance Customer', 'phone_number1' => '03001112222', 'user_id' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$cloth->cloth_brand_id], 'cloth_type' => [$cloth->cloth_type_id],
            'color' => [$color->color], 'per_meter' => [150], 'clothes_rack' => [null], 'length' => [2],
            'c_name' => $customer->name.'|'.$customer->id, 'payment' => 100, 'remain' => 9999,
        ]);

        $sale = SaleStock::where('user_id', $owner->id)->firstOrFail();
        $response->assertRedirect(route('admin.printStock', ['id' => $sale->id, 'customerId' => $customer->id]));
        $this->actingAs($owner)->get($response->headers->get('Location'))->assertOk()->assertSee('Fallback Shop');
        $this->assertEquals(200, (float) Transaction::where('sale_id', $sale->id)->value('remainingBalance'));
    }

    public function test_counter_sale_rejects_payment_above_calculated_total(): void
    {
        [$owner, $cloth, $color] = $this->stock(10, 100);
        $customer = Customers::create(['name' => 'Overpay Customer', 'phone_number1' => '03001112222', 'user_id' => $owner->id]);

        $this->actingAs($owner)->from(route('admin.sellCloth'))->post(route('admin.sellStock'), [
            'brand_name' => [$cloth->cloth_brand_id], 'cloth_type' => [$cloth->cloth_type_id],
            'color' => [$color->color], 'per_meter' => [150], 'clothes_rack' => [null], 'length' => [2],
            'c_name' => $customer->name.'|'.$customer->id, 'payment' => 301, 'remain' => 0,
        ])->assertRedirect(route('admin.sellCloth'))->assertSessionHasErrors('payment');

        $this->assertEquals(10, (float) $color->fresh()->length);
        $this->assertDatabaseCount('sale_stocks', 0);
    }

    public function test_counter_sale_rejects_insufficient_stock_with_an_urdu_message(): void
    {
        [$owner, $cloth, $color] = $this->stock(10, 100);
        $customer = Customers::create(['name' => 'Stock Guard Customer', 'phone_number1' => '03001112222', 'user_id' => $owner->id]);

        $response = $this->actingAs($owner)->from(route('admin.sellCloth'))->post(route('admin.sellStock'), [
            'brand_name' => [$cloth->cloth_brand_id], 'cloth_type' => [$cloth->cloth_type_id],
            'color' => [$color->color], 'per_meter' => [150], 'clothes_rack' => [null], 'length' => [11],
            'c_name' => $customer->name.'|'.$customer->id, 'payment' => 0, 'remain' => 1650,
        ]);

        $response->assertRedirect(route('admin.sellCloth'))
            ->assertSessionHasErrors(['length.0' => 'منتخب کپڑے کا مطلوبہ اسٹاک دستیاب نہیں ہے۔']);
        $this->actingAs($owner)->get(route('admin.sellCloth'))
            ->assertOk()
            ->assertSeeText('فروخت محفوظ نہیں ہو سکی:')
            ->assertSeeText('منتخب کپڑے کا مطلوبہ اسٹاک دستیاب نہیں ہے۔');
        $this->assertEquals(10, (float) $color->fresh()->length);
        $this->assertDatabaseCount('sale_stocks', 0);
    }

    public function test_online_order_and_cancellation_create_reversing_movements(): void
    {
        [$owner, $cloth, $color] = $this->stock(10, 75);
        Setting::forceCreate(['user_id' => $owner->id, 'name' => 'Ledger Shop', 'shop_slug' => 'ledger_shop', 'status' => 1, 'note' => '', 'address' => '', 'logo' => '', 'contact_no' => '']);
        $customer = $this->userWithRole('user');

        $this->actingAs($customer)->post(route('user.stock.order', ['slug' => 'ledger_shop']), [
            'Stock' => $cloth->id, 'length' => 3, 'color' => $color->color,
        ])->assertOk();
        $order = OnlineOrder::where('user_id', $customer->id)->firstOrFail();
        $this->assertEquals(7, (float) $color->fresh()->length);

        $this->actingAs($customer)->patch(route('user.order.cancel', ['slug' => 'ledger_shop', 'id' => $order->id]))->assertRedirect();
        $this->assertEquals(10, (float) $color->fresh()->length);
        $this->assertDatabaseHas('inventory_movements', ['movement_type' => 'online_order', 'quantity' => -3]);
        $this->assertDatabaseHas('inventory_movements', ['movement_type' => 'online_cancellation', 'quantity' => 3]);

        $this->actingAs($customer)->post(route('user.order.again', ['slug' => 'ledger_shop', 'id' => $order->id]))->assertRedirect();
        $this->assertEquals(7, (float) $color->fresh()->length);
        $this->assertDatabaseHas('inventory_movements', ['movement_type' => 'online_reorder', 'quantity' => -3]);
    }

    public function test_manual_adjustment_updates_average_cost_and_is_tenant_scoped(): void
    {
        [$owner, , $color] = $this->stock(10, 80);
        [$otherOwner, , $otherColor] = $this->stock(5, 20);

        $this->actingAs($owner)->get(route('admin.inventory-ledger.index'))
            ->assertOk()
            ->assertSee('<h1 class="h3 mb-1">', false)
            ->assertSee('inventory-movement-table', false)
            ->assertSee('data-confirm="کیا آپ اسٹاک کی یہ دستی تبدیلی درج کرنا چاہتے ہیں؟"', false)
            ->assertSee('id="adjustment_unit_cost"', false)
            ->assertSee('unitCost.disabled = !increasing', false);
        $this->actingAs($owner)->get(route('admin.inventory-valuation.index'))
            ->assertOk()
            ->assertSee('<h1 class="h3 mb-1">', false)
            ->assertSee('inventory-valuation-table', false)
            ->assertSee('data-label="موجودہ مقدار"', false);

        $this->actingAs($owner)->post(route('admin.inventory-ledger.adjust'), [
            'cloth_color_id' => $color->id, 'direction' => 'increase', 'quantity' => 10,
            'unit_cost' => 120, 'note' => 'Physical count correction',
        ])->assertRedirect();
        $this->assertEquals(20, (float) $color->fresh()->length);
        $this->assertEquals(100, (float) $color->fresh()->average_unit_cost);

        $this->actingAs($owner)->post(route('admin.inventory-ledger.adjust'), [
            'cloth_color_id' => $otherColor->id, 'direction' => 'decrease', 'quantity' => 1,
            'note' => 'Forbidden adjustment',
        ])->assertNotFound();
        $this->assertEquals(5, (float) $otherColor->fresh()->length);

        $this->actingAs($owner)->from(route('admin.inventory-ledger.index'))
            ->post(route('admin.inventory-ledger.adjust'), [
                'cloth_color_id' => $color->id, 'direction' => 'decrease', 'quantity' => 50,
                'note' => 'Too much stock',
            ])->assertRedirect(route('admin.inventory-ledger.index'))
            ->assertSessionHasErrors(['quantity' => 'اتنی مقدار اسٹاک میں موجود نہیں ہے۔']);
        $this->assertEquals(20, (float) $color->fresh()->length);

        $activity = new BusinessActivityLog([
            'route_name' => 'admin.inventory-ledger.adjust',
            'method' => 'POST',
        ]);
        $this->assertSame('اسٹاک کی مقدار درست کی', $activity->actionDescription());
    }

    private function stock(float $length, float $cost): array
    {
        $owner = $this->userWithRole('shop_owner');
        $type = ClothType::create(['name' => fake()->unique()->word(), 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => fake()->unique()->company(), 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id, 'price' => $cost, 'sale_price' => $cost + 50, 'user_id' => $owner->id]);
        $color = ClothColor::create(['cloth_id' => $cloth->id, 'color' => fake()->unique()->safeColorName(), 'length' => $length, 'average_unit_cost' => $cost, 'user_id' => $owner->id]);

        return [$owner, $cloth, $color];
    }

    private function userWithRole(string $name): User
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
