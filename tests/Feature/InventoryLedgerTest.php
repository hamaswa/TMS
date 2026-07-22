<?php

namespace Tests\Feature;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\OnlineOrder;
use App\Models\Purchase;
use App\Models\SaleStock;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
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
    }

    public function test_counter_sale_records_stock_cost_and_inventory_movement(): void
    {
        [$owner, $cloth, $color] = $this->stock(10, 100);
        $customer = Customers::create(['name' => 'Counter Customer', 'phone_number1' => '03001112222', 'user_id' => $owner->id]);

        $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$cloth->cloth_brand_id], 'cloth_type' => [$cloth->cloth_type_id],
            'color' => [$color->color], 'per_meter' => [150], 'clothes_rack' => [null], 'length' => [2],
            'c_name' => $customer->name . '|' . $customer->id, 'payment' => 300, 'remain' => 0,
        ])->assertRedirect();

        $sale = SaleStock::where('user_id', $owner->id)->firstOrFail();
        $this->assertEquals(8, (float) $color->fresh()->length);
        $this->assertEquals(100, (float) $sale->cost_per_meter);
        $this->assertEquals(200, (float) $sale->cost_total);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'counter_sale', 'quantity' => -2, 'balance_after' => 8,
            'reference_id' => $sale->id,
        ]);
        $this->actingAs($owner)->get(route('admin.customers.statement', $customer))
            ->assertOk()
            ->assertViewHas('sales', fn ($sales) => $sales->count() === 1
                && $sales->first()->id === $sale->id
                && $sales->first()->items_count === 1);
    }

    public function test_counter_sale_derives_balance_server_side_and_receipt_works_without_settings(): void
    {
        [$owner, $cloth, $color] = $this->stock(10, 100);
        $owner->update(['name' => 'Fallback Shop']);
        $customer = Customers::create(['name' => 'Balance Customer', 'phone_number1' => '03001112222', 'user_id' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$cloth->cloth_brand_id], 'cloth_type' => [$cloth->cloth_type_id],
            'color' => [$color->color], 'per_meter' => [150], 'clothes_rack' => [null], 'length' => [2],
            'c_name' => $customer->name . '|' . $customer->id, 'payment' => 100, 'remain' => 9999,
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
            'c_name' => $customer->name . '|' . $customer->id, 'payment' => 301, 'remain' => 0,
        ])->assertRedirect(route('admin.sellCloth'))->assertSessionHasErrors('payment');

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
