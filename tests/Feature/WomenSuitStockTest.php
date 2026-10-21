<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothCategory;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\CounterOrder;
use App\Models\Customers;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WomenSuitStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_category_inherits_its_main_categorys_unit_and_unit_locks_once_used(): void
    {
        [$owner] = $this->shop();
        $this->actingAs($owner)->post(route('admin.clothcategory.store'), [
            'name_ur' => 'بچیاں', 'name' => 'Girls', 'stock_unit' => ClothCategory::UNIT_SUIT,
        ])->assertSessionHasNoErrors();
        $women = ClothCategory::where('user_id', $owner->id)->where('slug', 'girls')->firstOrFail();

        $this->actingAs($owner)->post(route('admin.clothcategory.store'), [
            'name_ur' => 'تھری پیس', 'name' => '3 Piece', 'parent_id' => $women->id,
        ])->assertSessionHasNoErrors();
        $threePiece = ClothCategory::where('parent_id', $women->id)->firstOrFail();
        $this->assertSame(ClothCategory::UNIT_SUIT, $threePiece->stock_unit);

        $this->actingAs($owner)->get(route('admin.clothcategory.index'))->assertOk()->assertSeeText('سوٹ');

        Cloth::create(['user_id' => $owner->id, 'cloth_category_id' => $threePiece->id, 'price' => 100]);
        $this->actingAs($owner)->put(route('admin.clothcategory.update', $threePiece), [
            'name_ur' => 'تھری پیس', 'name' => '3 Piece', 'parent_id' => $women->id, 'stock_unit' => ClothCategory::UNIT_METER,
        ])->assertSessionHasErrors('stock_unit');
        $this->assertSame(ClothCategory::UNIT_SUIT, $threePiece->fresh()->stock_unit);
    }

    public function test_purchasing_a_new_womens_suit_set_counts_whole_suits(): void
    {
        [$owner, $type, $brand, $threePiece] = $this->shop();
        $supplier = Supplier::create(['user_id' => $owner->id, 'name' => 'Lawn House']);

        $this->actingAs($owner)->get(route('admin.purchases.create'))
            ->assertOk()->assertSee('data-unit="suit"', false)->assertSeeText('کل سوٹ');

        $payload = fn ($quantity) => [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'submit_action' => 'receive',
            'new_sets' => [[
                'name' => 'Lawn Collection',
                'cloth_brand_id' => $brand->id,
                'cloth_type_id' => $type->id,
                'cloth_category_id' => $threePiece->id,
                'suit_sale_price' => 3500,
                'tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR,
                'total' => 70000,
                'colors' => [
                    ['name' => 'Red', 'quantity' => $quantity],
                    ['name' => 'Blue', 'quantity' => 15],
                ],
            ]],
        ];

        $this->actingAs($owner)->post(route('admin.purchases.store'), $payload(2.5))
            ->assertSessionHasErrors('new_sets.0.quantity');

        $this->actingAs($owner)->post(route('admin.purchases.store'), $payload(10))
            ->assertRedirect()->assertSessionHas('success');

        $cloth = Cloth::where('user_id', $owner->id)->where('name', 'Lawn Collection')->firstOrFail();
        $this->assertSame($threePiece->id, $cloth->cloth_category_id);
        $this->assertSame(ClothCategory::UNIT_SUIT, $cloth->stock_unit);
        $this->assertEquals(1, (float) $cloth->default_sale_length);
        $this->assertEquals(3500, (float) $cloth->sale_price);
        $this->assertEquals(25, (float) $cloth->colors()->sum('length'));
        $this->assertEquals(2800, (float) $cloth->price);
    }

    public function test_adding_a_womens_suit_from_the_cloth_form_has_no_cutting_length(): void
    {
        [$owner, $type, $brand, $threePiece] = $this->shop();

        $this->actingAs($owner)->get(route('admin.cloth.create'))
            ->assertOk()->assertSee('id="cloth_category_id"', false)->assertSeeText('Women › 3 Piece');

        $this->actingAs($owner)->post(route('admin.cloth.store'), [
            'inventory_grid' => 1,
            'name' => 'Eid Suit',
            'cloth_category_id' => $threePiece->id,
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'price' => 2500,
            'suit_sale_price' => 4000,
            'stock_mode' => 'shared',
            'shared_length' => 12,
            'online_availability' => 'pos_only',
        ])->assertRedirect(route('admin.cloth.index'));

        $cloth = Cloth::where('user_id', $owner->id)->where('name', 'Eid Suit')->firstOrFail();
        $this->assertSame(ClothCategory::UNIT_SUIT, $cloth->stock_unit);
        $this->assertEquals(1, (float) $cloth->default_sale_length);
        $this->assertEquals(12, (float) $cloth->colors()->sum('length'));

        $this->actingAs($owner)->get(route('admin.cloth.index'))->assertOk()->assertSeeText('12 سوٹ');
    }

    public function test_selling_a_womens_suit_takes_whole_suits_from_stock(): void
    {
        [$owner, $type, $brand, $threePiece] = $this->shop();
        $cloth = Cloth::create([
            'name' => 'Eid Suit', 'cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id,
            'cloth_category_id' => $threePiece->id, 'price' => 2500, 'sale_price' => 4000,
            'suit_sale_price' => 4000, 'default_sale_length' => 1, 'sale_price_basis' => Cloth::SALE_PRICE_PER_SUIT,
            'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR, 'user_id' => $owner->id,
        ]);
        $this->assertSame(ClothCategory::UNIT_SUIT, $cloth->stock_unit);
        $stock = ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'Red', 'length' => 5, 'average_unit_cost' => 2500, 'user_id' => $owner->id]);
        $customer = Customers::create(['name' => 'Ayesha', 'phone_number1' => '03001112222', 'user_id' => $owner->id]);
        $order = CounterOrder::create([
            'reference' => 'ORD-WOMEN-SUIT', 'user_id' => $owner->id, 'customer_id' => $customer->id,
            'created_by_user_id' => $owner->id, 'status' => CounterOrder::STATUS_DRAFT,
        ]);

        $this->actingAs($owner)->get(route('admin.counter-orders.edit', $order))
            ->assertOk()->assertSee('data-unit="suit"', false)->assertSee('<optgroup label="Women › 3 Piece">', false);

        // More suits than in stock is refused in suits, not meters.
        $this->actingAs($owner)->post(route('admin.counter-orders.cloth-items.store', $order), [
            'cloth_id' => $cloth->id, 'color' => 'Red', 'quantity' => 6, 'length' => 4.5, 'total_price' => 24000,
        ])->assertSessionHasErrors(['length' => 'اسٹاک میں صرف 5 سوٹ دستیاب ہے؛ 6 سوٹ درکار ہیں۔']);

        // A stray length from the form is ignored; a packed suit is always 1 unit.
        $this->actingAs($owner)->post(route('admin.counter-orders.cloth-items.store', $order), [
            'cloth_id' => $cloth->id, 'color' => 'Red', 'quantity' => 2, 'length' => 4.5, 'total_price' => 8000,
        ])->assertSessionHasNoErrors();
        $item = $order->items()->firstOrFail();
        $this->assertEquals(1, (float) $item->length);
        $this->assertEquals(2, (float) $item->quantity);
        $this->assertEquals(4000, (float) $item->unit_price);

        $this->actingAs($owner)->post(route('admin.counter-orders.confirm', $order), [
            'payment' => 0, 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(3, (float) $stock->fresh()->length);
    }

    /** A shop with Men (meters) and Women › 3 Piece (suits). */
    private function shop(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true, 'tailoring_access' => true, 'clothing_access' => true,
            'must_change_password' => false,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Ladies Shop', 'owner_user_id' => $owner->id,
            'tailoring_enabled' => true, 'clothing_enabled' => true, 'status' => Business::STATUS_ACTIVE,
        ]);
        $owner->update(['business_id' => $business->id]);
        $type = ClothType::create(['name' => 'Lawn', 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => 'Summer', 'user_id' => $owner->id]);
        ClothCategory::ensureDefaultFor($owner->id);
        $women = ClothCategory::create(['user_id' => $owner->id, 'name' => 'Women', 'name_ur' => 'زنانہ', 'slug' => 'women', 'stock_unit' => ClothCategory::UNIT_SUIT]);
        $threePiece = ClothCategory::create([
            'user_id' => $owner->id, 'parent_id' => $women->id, 'name' => '3 Piece', 'name_ur' => 'تھری پیس',
            'slug' => 'women-3-piece', 'stock_unit' => ClothCategory::UNIT_SUIT,
        ]);

        return [$owner->fresh(), $type, $brand, $threePiece];
    }
}
