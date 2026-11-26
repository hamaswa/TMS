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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Settings › Order screen: the default dropdown row, or a box per item with a cart. */
class OrderScreenLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_page_uses_the_dropdown_row_until_the_shop_picks_item_boxes(): void
    {
        [$owner, $business, $order] = $this->shop();

        $this->actingAs($owner)->get(route('admin.counter-orders.edit', $order))->assertOk()
            ->assertSee('id="co-add-cloth-button"', false)->assertDontSee('id="co-boxes"', false);

        // The layout is chosen on the one settings page; the old order screen link lands on its section.
        $settingsSection = route('admin.setting.index').'#order-screen';
        $this->actingAs($owner)->get(route('admin.order-screen.edit'))->assertRedirect($settingsSection);
        $this->actingAs($owner)->get(route('admin.setting.index'))->assertOk()
            ->assertSee('id="order-screen"', false)->assertSeeText('آئٹم باکس')
            ->assertSee(route('admin.order-screen.update'), false);
        $this->actingAs($owner)->put(route('admin.order-screen.update'), ['counter_order_layout' => 'grid'])
            ->assertSessionHasNoErrors()->assertRedirect($settingsSection);
        $this->assertSame(Business::ORDER_LAYOUT_GRID, $business->fresh()->counter_order_layout);

        $this->actingAs($owner)->get(route('admin.counter-orders.edit', $order))->assertOk()
            ->assertSee('id="co-boxes"', false)->assertSee('id="co-cart"', false)
            ->assertDontSee('id="co-add-cloth-button"', false)->assertSee('Oud Night');

        $this->actingAs($owner)->put(route('admin.order-screen.update'), ['counter_order_layout' => 'tiles'])
            ->assertSessionHasErrors('counter_order_layout');
    }

    public function test_the_cart_adds_every_picked_item_to_the_order_at_once(): void
    {
        [$owner, , $order, $perfume, $cloth] = $this->shop();

        $this->actingAs($owner)->postJson(route('admin.counter-orders.cloth-items.bulk', $order), ['items' => [
            ['cloth_id' => $perfume->id, 'quantity' => 2, 'total_price' => 4000],
            ['cloth_id' => $cloth->id, 'quantity' => 2, 'length' => 4.5, 'total_price' => 6000],
        ]])->assertOk()->assertJsonPath('redirect', route('admin.counter-orders.edit', $order));

        $items = $order->items()->orderBy('id')->get();
        $this->assertCount(2, $items);
        $this->assertEquals([1, 2, 2000], [(float) $items[0]->length, (float) $items[0]->quantity, (float) $items[0]->unit_price]);
        $this->assertEquals([4.5, 2, 3000], [(float) $items[1]->length, (float) $items[1]->quantity, (float) $items[1]->unit_price]);
        $this->assertEquals(10000, (float) $order->fresh()->subtotal);

        // Sold together, so All Sales and the report show them as one bill.
        $this->actingAs($owner)->post(route('admin.counter-orders.confirm', $order), ['payment' => 10000, 'payment_method' => 'cash'])
            ->assertSessionHasNoErrors();
        $this->actingAs($owner)->get(route('admin.sales.all'))->assertOk()
            ->assertViewHas('lines', fn ($bills) => $bills->total() === 1 && count($bills->first()['items']) === 2
                && abs($bills->first()['amount'] - 10000) < 0.1)
            ->assertSeeText('Oud Night')->assertSeeText('Wash n Wear')->assertSeeText('2 آئٹمز')
            // The order number is shown, and the return popup opens on this page instead of leaving it.
            ->assertSeeText($order->fresh()->displayNumber())->assertSee('data-target="#allSalesReturn', false)
            ->assertSee('class="modal fade rr-modal" id="allSalesReturn', false);

        // The bill can be found by its order number.
        $this->actingAs($owner)->get(route('admin.sales.all', ['q' => $order->fresh()->displayNumber()]))->assertOk()
            ->assertViewHas('lines', fn ($bills) => $bills->total() === 1);
        $this->actingAs($owner)->get(route('admin.sales.all', ['q' => 'ORD-NOPE']))->assertOk()
            ->assertViewHas('lines', fn ($bills) => $bills->total() === 0);
    }

    public function test_cloth_picked_by_the_metre_takes_exactly_those_metres(): void
    {
        [$owner, , $order, , $cloth] = $this->shop();

        // 6.5 m at Rs 666.67 a metre is not a whole number of 4.5 m suits, so it goes as one line of 6.5 m.
        $this->actingAs($owner)->postJson(route('admin.counter-orders.cloth-items.bulk', $order), ['items' => [
            ['cloth_id' => $cloth->id, 'quantity' => 1, 'length' => 6.5, 'total_price' => 4333.36],
        ]])->assertOk();

        $item = $order->items()->firstOrFail();
        $this->assertEquals([6.5, 1, 4333.36], [(float) $item->length, (float) $item->quantity, (float) $item->line_total]);

        // Confirming goes straight to the receipt, which opens the print dialog by itself.
        $this->actingAs($owner)->post(route('admin.counter-orders.confirm', $order), [
            'payment' => 4333.36, 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.counter-orders.print', [$order, 'autoprint' => 1]));
        $this->assertEquals(33.5, (float) $cloth->colors()->first()->length);
        $this->actingAs($owner)->get(route('admin.counter-orders.print', [$order, 'autoprint' => 1]))->assertOk()
            ->assertSee('window.print(); }, 300)', false)->assertSee(route('admin.counter-orders.edit', $order), false);
        $this->actingAs($owner)->get(route('admin.counter-orders.print', $order))->assertOk()
            ->assertDontSee('window.print(); }, 300)', false);
    }

    public function test_lines_of_the_same_stock_are_checked_together(): void
    {
        [$owner, , $order, $perfume] = $this->shop();

        // 3 + 3 perfumes is more than the 5 in stock, even though each line alone fits.
        $this->actingAs($owner)->postJson(route('admin.counter-orders.cloth-items.bulk', $order), ['items' => [
            ['cloth_id' => $perfume->id, 'quantity' => 3, 'total_price' => 6000],
            ['cloth_id' => $perfume->id, 'quantity' => 3, 'total_price' => 6000],
        ]])->assertUnprocessable()->assertJsonValidationErrors('length');

        $this->assertSame(0, $order->items()->count());
    }

    private function shop(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true, 'tailoring_access' => true, 'clothing_access' => true,
            'must_change_password' => false,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Box Shop', 'owner_user_id' => $owner->id,
            'tailoring_enabled' => true, 'clothing_enabled' => true, 'status' => Business::STATUS_ACTIVE,
        ]);
        $owner->update(['business_id' => $business->id]);
        ClothCategory::ensureDefaultFor($owner->id);
        $perfumes = ClothCategory::create(['user_id' => $owner->id, 'name' => 'Perfumes', 'name_ur' => 'پرفیوم', 'slug' => 'perfumes', 'stock_unit' => ClothCategory::UNIT_PIECE]);
        $perfume = Cloth::create([
            'name' => 'Oud Night', 'cloth_category_id' => $perfumes->id, 'price' => 1200, 'sale_price' => 2000,
            'suit_sale_price' => 2000, 'default_sale_length' => 1, 'sale_price_basis' => Cloth::SALE_PRICE_PER_SUIT,
            'color_tracking_mode' => Cloth::COLOR_TRACKING_NONE, 'user_id' => $owner->id,
        ]);
        ClothColor::create(['cloth_id' => $perfume->id, 'color' => 'عام', 'length' => 5, 'average_unit_cost' => 1200, 'user_id' => $owner->id]);
        $cloth = Cloth::create([
            'name' => 'Wash n Wear', 'cloth_type_id' => ClothType::create(['name' => 'Wash', 'user_id' => $owner->id])->id,
            'cloth_brand_id' => ClothBrand::create(['name' => 'Gul', 'user_id' => $owner->id])->id,
            'price' => 500, 'sale_price' => 666.67, 'suit_sale_price' => 3000, 'default_sale_length' => 4.5,
            'sale_price_basis' => Cloth::SALE_PRICE_PER_SUIT, 'color_tracking_mode' => Cloth::COLOR_TRACKING_NONE, 'user_id' => $owner->id,
        ]);
        ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'عام', 'length' => 40, 'average_unit_cost' => 500, 'user_id' => $owner->id]);
        $customer = Customers::create(['name' => 'Box Buyer', 'phone_number1' => '03001114444', 'user_id' => $owner->id]);
        $order = CounterOrder::create([
            'reference' => 'ORD-BOXES', 'user_id' => $owner->id, 'customer_id' => $customer->id,
            'created_by_user_id' => $owner->id, 'status' => CounterOrder::STATUS_DRAFT,
        ]);

        return [$owner->fresh(), $business, $order, $perfume, $cloth];
    }
}
