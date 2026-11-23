<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothCategory;
use App\Models\ClothColor;
use App\Models\CounterOrder;
use App\Models\Customers;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Non-cloth items such as perfumes and caps, counted and sold by the piece. */
class PieceStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_can_count_stock_in_pieces(): void
    {
        [$owner] = $this->shop();

        $this->actingAs($owner)->post(route('admin.clothcategory.store'), [
            'name_ur' => 'ٹوپیاں', 'name' => 'Caps', 'stock_unit' => ClothCategory::UNIT_PIECE,
        ])->assertSessionHasNoErrors();

        $caps = ClothCategory::where('user_id', $owner->id)->where('slug', 'caps')->firstOrFail();
        $this->assertSame(ClothCategory::UNIT_PIECE, $caps->stock_unit);
        $this->actingAs($owner)->get(route('admin.clothcategory.index'))->assertOk()->assertSeeText('عدد');
    }

    public function test_adding_a_perfume_needs_no_cloth_type_brand_or_length(): void
    {
        [$owner, $perfumes] = $this->shop();

        $this->actingAs($owner)->get(route('admin.cloth.create'))
            ->assertOk()->assertSee('data-unit="piece"', false)->assertSeeText('Accessories › Perfumes');

        $this->actingAs($owner)->post(route('admin.cloth.store'), [
            'inventory_grid' => 1,
            'name' => 'Oud Night',
            'cloth_category_id' => $perfumes->id,
            'price' => 1200,
            'suit_sale_price' => 2000,
            'stock_mode' => 'shared',
            'shared_length' => 1.5,
            'online_availability' => 'pos_only',
        ])->assertSessionHasErrors('shared_length');

        $this->actingAs($owner)->post(route('admin.cloth.store'), [
            'inventory_grid' => 1,
            'name' => 'Oud Night',
            'cloth_category_id' => $perfumes->id,
            'price' => 1200,
            'suit_sale_price' => 2000,
            'stock_mode' => 'shared',
            'shared_length' => 8,
            'online_availability' => 'pos_only',
        ])->assertRedirect(route('admin.cloth.index'));

        $perfume = Cloth::where('user_id', $owner->id)->where('name', 'Oud Night')->firstOrFail();
        $this->assertSame(ClothCategory::UNIT_PIECE, $perfume->stock_unit);
        $this->assertNull($perfume->cloth_type_id);
        $this->assertNull($perfume->cloth_brand_id);
        $this->assertEquals(1, (float) $perfume->default_sale_length);
        $this->assertEquals(2000, (float) $perfume->sale_price);
        $this->assertEquals(8, (float) $perfume->colors()->sum('length'));

        $this->actingAs($owner)->get(route('admin.cloth.index'))->assertOk()->assertSeeText('8 عدد');
    }

    public function test_purchasing_new_caps_counts_whole_pieces_without_type_or_brand(): void
    {
        [$owner, , $caps] = $this->shop();
        $supplier = Supplier::create(['user_id' => $owner->id, 'name' => 'Cap House']);

        $this->actingAs($owner)->get(route('admin.purchases.create'))
            ->assertOk()->assertSee('data-unit="piece"', false)->assertSeeText('کل عدد');

        $payload = fn ($quantity) => [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'submit_action' => 'receive',
            'new_sets' => [[
                'name' => 'Prayer Cap',
                'cloth_category_id' => $caps->id,
                'suit_sale_price' => 300,
                'tracking_mode' => Cloth::COLOR_TRACKING_NONE,
                'quantity' => $quantity,
                'total' => 4000,
            ]],
        ];

        $this->actingAs($owner)->post(route('admin.purchases.store'), $payload(2.5))
            ->assertSessionHasErrors('new_sets.0.quantity');

        $this->actingAs($owner)->post(route('admin.purchases.store'), $payload(20))
            ->assertRedirect()->assertSessionHas('success');

        $cap = Cloth::where('user_id', $owner->id)->where('name', 'Prayer Cap')->firstOrFail();
        $this->assertSame(ClothCategory::UNIT_PIECE, $cap->stock_unit);
        $this->assertNull($cap->cloth_type_id);
        $this->assertEquals(1, (float) $cap->default_sale_length);
        $this->assertEquals(20, (float) $cap->colors()->sum('length'));
        $this->assertEquals(200, (float) $cap->price);

        $purchase = \App\Models\Purchase::where('user_id', $owner->id)->latest('id')->firstOrFail();
        $this->actingAs($owner)->get(route('admin.purchases.show', $purchase))->assertOk()
            ->assertSeeText('خریدے گئے آئٹم')->assertSeeText('20 عدد')->assertSeeText('Accessories › Caps')
            ->assertDontSeeText('کپڑے کا سیٹ');
    }

    public function test_a_new_cloth_set_still_needs_a_type_and_brand(): void
    {
        [$owner] = $this->shop();
        $supplier = Supplier::create(['user_id' => $owner->id, 'name' => 'Cloth House']);
        $men = ClothCategory::where('user_id', $owner->id)->where('slug', 'men')->firstOrFail();

        $this->actingAs($owner)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'submit_action' => 'receive',
            'new_sets' => [[
                'name' => 'Wash n Wear', 'cloth_category_id' => $men->id, 'suit_sale_price' => 2200,
                'default_sale_length' => 4, 'tracking_mode' => Cloth::COLOR_TRACKING_NONE, 'quantity' => 40, 'total' => 20000,
            ]],
        ])->assertSessionHasErrors('new_sets.0.cloth_brand_id');
    }

    public function test_selling_perfumes_takes_whole_pieces_from_stock(): void
    {
        [$owner, $perfumes] = $this->shop();
        $perfume = Cloth::create([
            'name' => 'Oud Night', 'cloth_category_id' => $perfumes->id, 'price' => 1200, 'sale_price' => 2000,
            'suit_sale_price' => 2000, 'default_sale_length' => 1, 'sale_price_basis' => Cloth::SALE_PRICE_PER_SUIT,
            'color_tracking_mode' => Cloth::COLOR_TRACKING_NONE, 'user_id' => $owner->id,
        ]);
        $stock = ClothColor::create(['cloth_id' => $perfume->id, 'color' => 'عام', 'length' => 4, 'average_unit_cost' => 1200, 'user_id' => $owner->id]);
        $customer = Customers::create(['name' => 'Bilal', 'phone_number1' => '03001113333', 'user_id' => $owner->id]);
        $order = CounterOrder::create([
            'reference' => 'ORD-PERFUME', 'user_id' => $owner->id, 'customer_id' => $customer->id,
            'created_by_user_id' => $owner->id, 'status' => CounterOrder::STATUS_DRAFT,
        ]);

        $this->actingAs($owner)->get(route('admin.counter-orders.edit', $order))
            ->assertOk()->assertSee('data-unit="piece"', false)->assertSee('<optgroup label="Accessories › Perfumes">', false);

        $this->actingAs($owner)->post(route('admin.counter-orders.cloth-items.store', $order), [
            'cloth_id' => $perfume->id, 'quantity' => 5, 'length' => 4.5, 'total_price' => 10000,
        ])->assertSessionHasErrors(['length' => 'اسٹاک میں صرف 4 عدد دستیاب ہے؛ 5 عدد درکار ہیں۔']);

        $this->actingAs($owner)->post(route('admin.counter-orders.cloth-items.store', $order), [
            'cloth_id' => $perfume->id, 'quantity' => 3, 'length' => 4.5, 'total_price' => 6000,
        ])->assertSessionHasNoErrors();
        $item = $order->items()->firstOrFail();
        $this->assertEquals(1, (float) $item->length);
        $this->assertEquals(3, (float) $item->quantity);
        $this->assertEquals(2000, (float) $item->unit_price);

        $this->actingAs($owner)->post(route('admin.counter-orders.confirm', $order), [
            'payment' => 0, 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(1, (float) $stock->fresh()->length);
    }

    /** A shop with Men (meters) and Accessories › Perfumes / Caps (pieces). */
    private function shop(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true, 'tailoring_access' => true, 'clothing_access' => true,
            'must_change_password' => false,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Gents Shop', 'owner_user_id' => $owner->id,
            'tailoring_enabled' => true, 'clothing_enabled' => true, 'status' => Business::STATUS_ACTIVE,
        ]);
        $owner->update(['business_id' => $business->id]);
        ClothCategory::ensureDefaultFor($owner->id);
        $accessories = ClothCategory::create(['user_id' => $owner->id, 'name' => 'Accessories', 'name_ur' => 'متفرق', 'slug' => 'accessories', 'stock_unit' => ClothCategory::UNIT_PIECE]);
        $perfumes = ClothCategory::create([
            'user_id' => $owner->id, 'parent_id' => $accessories->id, 'name' => 'Perfumes', 'name_ur' => 'پرفیوم',
            'slug' => 'accessories-perfumes', 'stock_unit' => ClothCategory::UNIT_PIECE,
        ]);
        $caps = ClothCategory::create([
            'user_id' => $owner->id, 'parent_id' => $accessories->id, 'name' => 'Caps', 'name_ur' => 'ٹوپیاں',
            'slug' => 'accessories-caps', 'stock_unit' => ClothCategory::UNIT_PIECE,
        ]);

        return [$owner->fresh(), $perfumes, $caps];
    }
}
