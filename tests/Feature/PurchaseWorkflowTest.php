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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_form_has_a_page_heading_and_accessible_item_fields(): void
    {
        [$owner] = $this->draftPurchase();

        $this->actingAs($owner)->get(route('admin.purchases.create'))
            ->assertOk()
            ->assertSee('<h1 class="h4 mb-0">', false)
            ->assertSee('id="purchase-stock-scan"', false)
            ->assertSee('data-stock-code="', false)
            ->assertSee(
                'data-brand-code="'.$this->brandBundleCodeForOwner($owner).'"',
                false
            )
            ->assertSee('value="receive"', false)
            ->assertSee('value="draft"', false)
            ->assertSeeText('محفوظ اور انوینٹری میں شامل کریں')
            ->assertSee('id="purchase-item-count">0', false)
            ->assertSee('data-default-cost="', false)
            ->assertSeeText('نئی قطار شامل کریں')
            ->assertSeeText('نیا سیٹ')
            ->assertSeeText('موجودہ سیٹ')
            ->assertSee('class="purchase-sheet-head"', false)
            ->assertSee('id="add-purchase-row"', false)
            ->assertSee('class="form-control purchase-row-kind"', false)
            ->assertDontSeeText('اندرونی کوڈ')
            ->assertSee('id="purchase-new-set-template"', false)
            ->assertSee('id="purchase-set-template"', false)
            ->assertSee('id="purchase-flat-fields"', false)
            ->assertSee('aria-label="کپڑے کا سیٹ منتخب کریں"', false)
            ->assertSee('aria-label="اصل کل خرید قیمت"', false)
            ->assertSee('aria-label="یہ سیٹ ہٹائیں"', false);
        $this->actingAs($owner)->get(route('admin.purchases.index'))
            ->assertOk()
            ->assertSee('<h1 class="h3 mb-1">', false)
            ->assertSee('purchase-list-table', false)
            ->assertSee('data-label="بقایا"', false)
            ->assertSee('purchase-list-action', false);
    }

    public function test_purchase_can_be_saved_and_received_into_inventory_in_one_flow(): void
    {
        [$owner, $supplier, $color] = $this->draftPurchase();
        Purchase::query()->delete();

        $this->actingAs($owner)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'cloth_color_id' => [$color->id],
            'quantity' => [5],
            'line_total' => [600],
            'submit_action' => 'receive',
        ])->assertRedirect()->assertSessionHas('success', 'خریداری محفوظ ہو گئی، مال انوینٹری میں شامل کر دیا گیا ہے۔');

        $purchase = Purchase::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame('received', $purchase->status);
        $this->assertEquals(15, (float) $color->fresh()->length);
        $this->assertDatabaseHas('inventory_movements', [
            'user_id' => $owner->id,
            'cloth_color_id' => $color->id,
            'movement_type' => 'purchase_receipt',
            'quantity' => 5,
        ]);
    }

    public function test_purchase_can_create_a_new_color_tracked_set_and_receive_it_in_one_flow(): void
    {
        Storage::fake('public');
        [$owner, $supplier] = $this->draftPurchase();
        Purchase::query()->delete();
        $brand = ClothBrand::where('user_id', $owner->id)->firstOrFail();
        $type = ClothType::where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'submit_action' => 'receive',
            'new_sets' => [[
                'name' => 'Fresh Arrival',
                'cloth_brand_id' => $brand->id,
                'cloth_type_id' => $type->id,
                'sale_price' => 650,
                'default_sale_length' => 4.5,
                'tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR,
                'total' => 10000,
                'colors' => [
                    ['name' => 'Navy', 'hex' => '#14213D', 'quantity' => 12, 'image' => UploadedFile::fake()->image('navy.jpg', 120, 120)],
                    ['name' => 'Cream', 'hex' => '#FFFDD0', 'quantity' => 8],
                ],
            ]],
        ])->assertRedirect()->assertSessionHas('success');

        $cloth = Cloth::where('user_id', $owner->id)->where('name', 'Fresh Arrival')->firstOrFail();
        $purchase = Purchase::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame('received', $purchase->status);
        $this->assertEquals(10000, (float) $purchase->total_amount);
        $this->assertCount(2, $purchase->items);
        $this->assertEquals(20, (float) $cloth->colors()->sum('length'));
        $this->assertDatabaseHas('cloth_colors', ['cloth_id' => $cloth->id, 'color' => 'Navy', 'color_hex' => '#14213D', 'length' => 12]);
        $this->assertDatabaseHas('cloth_colors', ['cloth_id' => $cloth->id, 'color' => 'Cream', 'color_hex' => '#FFFDD0', 'length' => 8]);
        $image = $cloth->images()->where('image_color', 'Navy')->firstOrFail();
        Storage::disk('public')->assertExists($image->images);
    }

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
        $this->actingAs($owner)->get(route('admin.purchases.show', $purchase))
            ->assertOk()
            ->assertSee('<h1 class="h3 mb-1">', false)
            ->assertSee('purchase-item-table', false)
            ->assertSee('data-label="واپسی کے بعد رقم"', false)
            ->assertSee('aria-label="واپسی کی مقدار میٹر میں"', false)
            ->assertSee('data-confirm="کیا منتخب مقدار سپلائر کو واپس کر کے اسٹاک اور بقایا کم کرنا ہے؟"', false);

        $this->actingAs($owner)->patch(route('admin.purchases.receive', $purchase))->assertStatus(422);
        $this->assertEquals(15, (float) $color->fresh()->length);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_purchase_cannot_be_saved_with_zero_line_total(): void
    {
        [$owner, $supplier, $color] = $this->draftPurchase();
        Purchase::query()->delete();

        $this->actingAs($owner)->from(route('admin.purchases.create'))->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'cloth_color_id' => [$color->id],
            'quantity' => [4],
            'line_total' => [0],
        ])->assertRedirect(route('admin.purchases.create'))
            ->assertSessionHasErrors(['line_total.0' => 'ہر شامل شدہ کپڑے کی اصل کل خرید قیمت درج کریں۔']);

        $this->assertDatabaseCount('purchases', 0);
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
        $this->actingAs($owner)->get(route('admin.purchases.show', $purchase))
            ->assertOk()
            ->assertSeeText('واپسی کے بعد رقم')
            ->assertSeeText('روپے 300.00');
    }

    public function test_cancelling_draft_preserves_audit_total_but_clears_payable_without_moving_stock(): void
    {
        [$owner, , $color, $purchase] = $this->draftPurchase();

        $this->actingAs($owner)->patch(route('admin.purchases.cancel', $purchase))->assertRedirect();

        $purchase->refresh();
        $this->assertSame('cancelled', $purchase->status);
        $this->assertEquals(500, (float) $purchase->total_amount);
        $this->assertEquals(0, (float) $purchase->balance_amount);
        $this->assertEquals(10, (float) $color->fresh()->length);
        $this->assertDatabaseCount('inventory_movements', 0);
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
            'amount' => 200, 'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer', 'reference' => 'BANK-1',
        ])->assertRedirect();
        $this->assertEquals(200, (float) $purchase->fresh()->paid_amount);
        $this->assertEquals(300, (float) $purchase->fresh()->balance_amount);
        $this->assertDatabaseHas('supplier_payments', [
            'purchase_id' => $purchase->id,
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-1',
        ]);

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
            'amount' => 200, 'payment_date' => now()->toDateString(),
            'payment_method' => 'cheque', 'reference' => 'OPEN-1',
        ])->assertRedirect();

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id, 'purchase_id' => null, 'amount' => 200,
            'payment_method' => 'cheque', 'reference' => 'OPEN-1',
        ]);
        $this->actingAs($owner)->get(route('admin.suppliers.edit', $supplier))
            ->assertOk()
            ->assertSee('<h1 class="h3 mb-1">', false)
            ->assertSee('supplier-payment-table', false)
            ->assertSeeText('OPEN-1')
            ->assertSeeText('چیک')
            ->assertSeeText('روپے 200.00')
            ->assertSee('data-confirm="کیا سپلائر کو یہ ادائیگی درج کرنا چاہتے ہیں؟"', false);
        $this->actingAs($owner)->get(route('admin.suppliers.index'))
            ->assertOk()
            ->assertSee('300.00')
            ->assertSee('<h1 class="h3 mb-1">', false)
            ->assertSee('supplier-table', false)
            ->assertSee('data-label="خریداری بقایا"', false)
            ->assertSee('supplier-actions', false);

        $this->actingAs($owner)->from(route('admin.suppliers.edit', $supplier))
            ->post(route('admin.suppliers.payment', $supplier), [
                'amount' => 301, 'payment_date' => now()->toDateString(),
            ])->assertRedirect(route('admin.suppliers.edit', $supplier))
            ->assertSessionHasErrors(['amount' => 'ادائیگی سپلائر کی موجودہ بقایا رقم سے زیادہ نہیں ہو سکتی۔']);
        $this->assertDatabaseCount('supplier_payments', 1);
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

    private function brandBundleCodeForOwner(User $owner): string
    {
        return ClothBrand::where('user_id', $owner->id)->firstOrFail()->bundle_code;
    }
}
