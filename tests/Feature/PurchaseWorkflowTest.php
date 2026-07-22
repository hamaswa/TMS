<?php

namespace Tests\Feature;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receiving_purchase_increases_stock_and_creates_ledger_entry_once(): void
    {
        [$owner, , $color, $purchase] = $this->draftPurchase();

        $this->actingAs($owner)->patch(route('admin.purchases.receive', $purchase))->assertRedirect();

        $this->assertEquals(15, (float) $color->fresh()->length);
        $this->assertSame('received', $purchase->fresh()->status);
        $this->assertDatabaseHas('inventory_movements', [
            'user_id' => $owner->id, 'cloth_color_id' => $color->id,
            'movement_type' => 'purchase_receipt', 'quantity' => 5,
        ]);

        $this->actingAs($owner)->patch(route('admin.purchases.receive', $purchase))->assertStatus(422);
        $this->assertEquals(15, (float) $color->fresh()->length);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_purchase_return_reduces_stock_payable_and_writes_negative_ledger_entry(): void
    {
        [$owner, , $color, $purchase] = $this->draftPurchase();
        $this->actingAs($owner)->patch(route('admin.purchases.receive', $purchase));
        $item = $purchase->items()->firstOrFail();

        $this->actingAs($owner)->post(route('admin.purchases.return', $purchase), [
            'purchase_item_id' => $item->id, 'quantity' => 2,
            'return_date' => now()->toDateString(), 'note' => 'Damaged roll',
        ])->assertRedirect();

        $this->assertEquals(13, (float) $color->fresh()->length);
        $this->assertEquals(300, (float) $purchase->fresh()->total_amount);
        $this->assertEquals(300, (float) $purchase->fresh()->balance_amount);
        $this->assertDatabaseHas('inventory_movements', [
            'cloth_color_id' => $color->id, 'movement_type' => 'purchase_return', 'quantity' => -2,
        ]);
        $this->assertDatabaseHas('purchase_return_items', ['purchase_item_id' => $item->id, 'quantity' => 2]);
    }

    public function test_return_is_rejected_when_current_stock_is_insufficient(): void
    {
        [$owner, , $color, $purchase] = $this->draftPurchase();
        $this->actingAs($owner)->patch(route('admin.purchases.receive', $purchase));
        $color->update(['length' => 1]);

        $this->actingAs($owner)->from(route('admin.purchases.show', $purchase))
            ->post(route('admin.purchases.return', $purchase), [
                'purchase_item_id' => $purchase->items()->firstOrFail()->id,
                'quantity' => 2, 'return_date' => now()->toDateString(),
            ])->assertRedirect(route('admin.purchases.show', $purchase))->assertSessionHasErrors('quantity');

        $this->assertEquals(1, (float) $color->fresh()->length);
        $this->assertDatabaseCount('purchase_returns', 0);
    }

    public function test_supplier_payment_cannot_exceed_purchase_balance(): void
    {
        [$owner, , , $purchase] = $this->draftPurchase();
        $this->actingAs($owner)->patch(route('admin.purchases.receive', $purchase));

        $this->actingAs($owner)->post(route('admin.purchases.payment', $purchase), [
            'amount' => 200, 'payment_date' => now()->toDateString(), 'reference' => 'BANK-1',
        ])->assertRedirect();
        $this->assertEquals(200, (float) $purchase->fresh()->paid_amount);
        $this->assertEquals(300, (float) $purchase->fresh()->balance_amount);

        $this->actingAs($owner)->from(route('admin.purchases.show', $purchase))
            ->post(route('admin.purchases.payment', $purchase), [
                'amount' => 301, 'payment_date' => now()->toDateString(),
            ])->assertRedirect(route('admin.purchases.show', $purchase))->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('supplier_payments', 1);
    }

    public function test_purchase_and_supplier_records_are_isolated_between_shops(): void
    {
        [$owner] = $this->draftPurchase();
        [, $otherSupplier, , $otherPurchase] = $this->draftPurchase();

        $this->actingAs($owner)->get(route('admin.purchases.show', $otherPurchase))->assertNotFound();
        $this->actingAs($owner)->patch(route('admin.purchases.receive', $otherPurchase))->assertNotFound();
        $this->actingAs($owner)->get(route('admin.suppliers.edit', $otherSupplier))->assertNotFound();
        $this->actingAs($owner)->post(route('admin.suppliers.payment', $otherSupplier), [
            'amount' => 1, 'payment_date' => now()->toDateString(),
        ])->assertNotFound();
    }

    public function test_general_supplier_payment_reduces_opening_payable(): void
    {
        [$owner, $supplier] = $this->draftPurchase();
        $supplier->update(['opening_balance' => 500]);

        $this->actingAs($owner)->post(route('admin.suppliers.payment', $supplier), [
            'amount' => 200, 'payment_date' => now()->toDateString(), 'reference' => 'OPEN-1',
        ])->assertRedirect();

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id, 'purchase_id' => null, 'amount' => 200, 'reference' => 'OPEN-1',
        ]);
        $this->actingAs($owner)->get(route('admin.suppliers.index'))->assertOk()->assertSee('300.00');
    }

    private function draftPurchase(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create();
        $owner->assignRole($role);
        $type = ClothType::create(['name' => fake()->unique()->word(), 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => fake()->unique()->company(), 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id, 'price' => 80, 'sale_price' => 120, 'user_id' => $owner->id]);
        $color = ClothColor::create(['cloth_id' => $cloth->id, 'color' => fake()->unique()->safeColorName(), 'length' => 10, 'user_id' => $owner->id]);
        $supplier = Supplier::create(['user_id' => $owner->id, 'name' => fake()->unique()->company()]);

        $this->actingAs($owner)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id, 'purchase_date' => now()->toDateString(),
            'cloth_color_id' => [$color->id], 'quantity' => [5], 'unit_cost' => [100],
        ])->assertRedirect();
        $purchase = Purchase::where('user_id', $owner->id)->firstOrFail();

        return [$owner, $supplier, $color, $purchase];
    }
}
