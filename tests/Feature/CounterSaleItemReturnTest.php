<?php

namespace Tests\Feature;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\CounterSaleReceipt;
use App\Models\CounterSaleReturn;
use App\Models\Customers;
use App\Models\OrderCancellationSettlement;
use App\Models\SaleStock;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CounterSaleItemReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_item_can_be_returned_and_a_later_full_cancel_only_reverses_the_rest(): void
    {
        $owner = $this->userWithRole('shop_owner');
        $type = ClothType::create(['name' => 'Lawn', 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => 'Alkaram', 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id, 'price' => 100, 'sale_price' => 150, 'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR, 'user_id' => $owner->id]);
        $white = ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'White', 'length' => 10, 'average_unit_cost' => 100, 'user_id' => $owner->id]);
        $navy = ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'Navy', 'length' => 12, 'average_unit_cost' => 90, 'user_id' => $owner->id]);
        $customer = Customers::create(['name' => 'Junaid', 'phone_number1' => '03001234567', 'user_id' => $owner->id]);

        // 2m @150 + 3m @160 = 780, customer pays 600 and owes 180.
        $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$brand->id, $brand->id],
            'cloth_type' => [$type->id, $type->id],
            'color' => ['White', 'Navy'],
            'per_meter' => [150, 160],
            'clothes_rack' => ['A-1', 'A-2'],
            'length' => [2, 3],
            'c_name' => $customer->name.'|'.$customer->id,
            'payment' => 600,
            'remain' => 180,
            'payment_method' => 'cash',
            'paid_on' => now()->toDateString(),
        ]);
        $receipt = CounterSaleReceipt::where('user_id', $owner->id)->firstOrFail();
        [$whiteItem, $navyItem] = SaleStock::where('counter_sale_receipt_id', $receipt->id)->orderBy('id')->get()->all();
        $this->assertEquals(9, (float) $navy->fresh()->length);

        $this->actingAs($owner)->get(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'shop']))
            ->assertOk()
            ->assertSeeText('کون سا آئٹم واپس آیا؟')
            ->assertSee('name="items['.$navyItem->id.']"', false)
            ->assertSee('Rs 480.00', false);

        // The customer orders page lists the cloth sale next to tailoring, with the same return popup.
        $this->actingAs($owner)->get(route('admin.customer.orders', $customer->id))
            ->assertOk()
            ->assertSeeText('کپڑے کی خریداری')
            ->assertSeeText('کون سا آئٹم واپس آیا؟')
            ->assertSee('name="items['.$navyItem->id.']"', false)
            ->assertSee('class="tab-pane fade show active" id="tailoringTab"', false);
        $this->actingAs($owner)->get(route('admin.customer.orders', ['customer' => $customer->id, 'tab' => 'sales']))
            ->assertOk()
            ->assertSee('class="tab-pane fade show active" id="salesTab"', false);

        // Return only the navy cloth: 480 clears the 180 still owed, 300 goes back as cash.
        $this->actingAs($owner)->from(route('admin.customers.statement', $customer->id))
            ->post(route('admin.counter-sales.returns.store', $receipt), [
                'items' => [$whiteItem->id => 0, $navyItem->id => 3],
                'reason' => 'Navy colour did not suit',
                'restock' => 1,
                'settlement_type' => 'refund',
                'payment_method' => 'cash',
            ])->assertRedirect(route('admin.customers.statement', $customer->id))
            ->assertSessionHasNoErrors();

        $return = CounterSaleReturn::where('counter_sale_receipt_id', $receipt->id)->sole();
        $this->assertEquals(480, (float) $return->return_total);
        $this->assertEquals(180, (float) $return->balance_reduction);
        $this->assertEquals(300, (float) $return->refund_amount);
        $this->assertEquals(12, (float) $navy->fresh()->length);
        $this->assertEquals(8, (float) $white->fresh()->length);
        $this->assertSame('completed', $receipt->fresh()->status);

        // The same cloth can't be returned twice.
        $this->actingAs($owner)->post(route('admin.counter-sales.returns.store', $receipt), [
            'items' => [$navyItem->id => 1],
            'reason' => 'Trying again',
            'restock' => 1,
        ])->assertSessionHasErrors('items.'.$navyItem->id);

        // Cancelling the whole receipt now only covers the white cloth: 300 paid, nothing owed.
        $this->actingAs($owner)->patch(route('admin.counter-sales.cancel', $whiteItem), [
            'cancellation_reason' => 'Customer returned the rest',
            'refund_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $settlement = OrderCancellationSettlement::where('source_id', $receipt->id)->sole();
        $this->assertEquals(300, (float) $settlement->order_total);
        $this->assertEquals(300, (float) $settlement->paid_on_order);
        $this->assertEquals(0, (float) $settlement->cancelled_unpaid_balance);
        $this->assertEquals(300, (float) $settlement->cash_refund);
        $this->assertEquals(10, (float) $white->fresh()->length);
        $this->assertEquals(12, (float) $navy->fresh()->length, 'navy cloth must not be restocked twice');
        $this->assertSame('cancelled', $receipt->fresh()->status);
        $this->assertEquals(0, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));
    }

    public function test_customer_can_keep_the_balance_and_get_the_full_return_value_back_up_to_what_they_paid(): void
    {
        $owner = $this->userWithRole('shop_owner');
        $type = ClothType::create(['name' => 'Lawn', 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => 'Alkaram', 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id, 'price' => 100, 'sale_price' => 150, 'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR, 'user_id' => $owner->id]);
        ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'White', 'length' => 10, 'average_unit_cost' => 100, 'user_id' => $owner->id]);
        ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'Navy', 'length' => 12, 'average_unit_cost' => 90, 'user_id' => $owner->id]);
        $customer = Customers::create(['name' => 'Junaid', 'phone_number1' => '03001234567', 'user_id' => $owner->id]);

        // 2m @150 + 3m @160 = 780, customer pays 600 and owes 180.
        $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$brand->id, $brand->id],
            'cloth_type' => [$type->id, $type->id],
            'color' => ['White', 'Navy'],
            'per_meter' => [150, 160],
            'clothes_rack' => ['A-1', 'A-2'],
            'length' => [2, 3],
            'c_name' => $customer->name.'|'.$customer->id,
            'payment' => 600,
            'remain' => 180,
            'payment_method' => 'cash',
            'paid_on' => now()->toDateString(),
        ]);
        $receipt = CounterSaleReceipt::where('user_id', $owner->id)->firstOrFail();
        [$whiteItem, $navyItem] = SaleStock::where('counter_sale_receipt_id', $receipt->id)->orderBy('id')->get()->all();

        // Keep the 180 balance: the full 480 goes back in cash.
        $this->actingAs($owner)->post(route('admin.counter-sales.returns.store', $receipt), [
            'items' => [$navyItem->id => 3],
            'reason' => 'Navy colour did not suit',
            'restock' => 1,
            'apply_to_balance' => 0,
            'settlement_type' => 'refund',
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $first = CounterSaleReturn::where('counter_sale_receipt_id', $receipt->id)->sole();
        $this->assertEquals(0, (float) $first->balance_reduction);
        $this->assertEquals(480, (float) $first->refund_amount);
        $this->assertEquals(180, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));

        // Only 120 of the 600 paid is left, so returning the 300 white cloth refunds 120 and clears the 180.
        $this->actingAs($owner)->post(route('admin.counter-sales.returns.store', $receipt), [
            'items' => [$whiteItem->id => 2],
            'reason' => 'Returned the rest too',
            'restock' => 1,
            'apply_to_balance' => 0,
            'settlement_type' => 'refund',
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $second = CounterSaleReturn::where('counter_sale_receipt_id', $receipt->id)->latest('id')->first();
        $this->assertEquals(180, (float) $second->balance_reduction);
        $this->assertEquals(120, (float) $second->refund_amount);
        $this->assertEquals(0, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));
    }

    public function test_payment_can_be_taken_against_a_sale_receipt_from_the_orders_page(): void
    {
        $owner = $this->userWithRole('shop_owner');
        $type = ClothType::create(['name' => 'Lawn', 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => 'Alkaram', 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id, 'price' => 100, 'sale_price' => 150, 'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR, 'user_id' => $owner->id]);
        ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'White', 'length' => 10, 'average_unit_cost' => 100, 'user_id' => $owner->id]);
        ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'Navy', 'length' => 12, 'average_unit_cost' => 90, 'user_id' => $owner->id]);
        $customer = Customers::create(['name' => 'Junaid', 'phone_number1' => '03001234567', 'user_id' => $owner->id]);

        // 2m @150 + 3m @160 = 780, customer pays 600 and owes 180.
        $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$brand->id, $brand->id],
            'cloth_type' => [$type->id, $type->id],
            'color' => ['White', 'Navy'],
            'per_meter' => [150, 160],
            'clothes_rack' => ['A-1', 'A-2'],
            'length' => [2, 3],
            'c_name' => $customer->name.'|'.$customer->id,
            'payment' => 600,
            'remain' => 180,
            'payment_method' => 'cash',
            'paid_on' => now()->toDateString(),
        ]);
        $receipt = CounterSaleReceipt::where('user_id', $owner->id)->firstOrFail();
        [$whiteItem, $navyItem] = SaleStock::where('counter_sale_receipt_id', $receipt->id)->orderBy('id')->get()->all();

        $this->actingAs($owner)->get(route('admin.customer.orders', $customer->id))
            ->assertOk()
            ->assertSee('data-receiptid="'.$receipt->id.'"', false);

        // More than the receipt still owes is refused.
        $this->actingAs($owner)->postJson(route('admin.DirectPayment'), [
            'customer_id' => $customer->id, 'counter_sale_receipt_id' => $receipt->id, 'DirectPayment' => 200,
        ])->assertStatus(422);

        $this->actingAs($owner)->postJson(route('admin.DirectPayment'), [
            'customer_id' => $customer->id, 'counter_sale_receipt_id' => $receipt->id, 'DirectPayment' => 100,
        ])->assertOk();

        $payment = Transaction::where('Order_type', 'Payment')->sole();
        $this->assertSame($receipt->id, (int) $payment->counter_sale_receipt_id);
        $this->assertEquals(80, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));
        $this->actingAs($owner)->get(route('admin.customer.orders', ['customer' => $customer->id, 'tab' => 'sales']))
            ->assertSee('Rs 700.00', false)
            ->assertSee('data-remaining="80"', false);

        // A return after that payment only clears the 80 still owed; the other 400 is paid back.
        $this->actingAs($owner)->post(route('admin.counter-sales.returns.store', $receipt), [
            'items' => [$navyItem->id => 3],
            'reason' => 'Navy colour did not suit',
            'restock' => 1,
            'settlement_type' => 'refund',
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        $return = CounterSaleReturn::where('counter_sale_receipt_id', $receipt->id)->sole();
        $this->assertEquals(80, (float) $return->balance_reduction);
        $this->assertEquals(400, (float) $return->refund_amount);
        $this->assertEquals(0, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));

        // Cancelling the rest finds nothing left owed on the receipt.
        $this->actingAs($owner)->patch(route('admin.counter-sales.cancel', $whiteItem), [
            'cancellation_reason' => 'Customer returned the rest',
            'refund_method' => 'cash',
        ])->assertSessionHasNoErrors();
        $settlement = OrderCancellationSettlement::where('source_id', $receipt->id)->sole();
        $this->assertEquals(0, (float) $settlement->cancelled_unpaid_balance);
        $this->assertEquals(300, (float) $settlement->paid_on_order);
    }

    public function test_a_general_payment_pays_the_oldest_sale_and_caps_receipt_payments(): void
    {
        $owner = $this->userWithRole('shop_owner');
        $type = ClothType::create(['name' => 'Lawn', 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => 'Alkaram', 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id, 'price' => 100, 'sale_price' => 150, 'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR, 'user_id' => $owner->id]);
        ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'White', 'length' => 20, 'average_unit_cost' => 100, 'user_id' => $owner->id]);
        $customer = Customers::create(['name' => 'Umar', 'phone_number1' => '03001234567', 'user_id' => $owner->id]);
        $sell = fn (int $paid, int $owed) => $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$brand->id], 'cloth_type' => [$type->id], 'color' => ['White'], 'per_meter' => [150],
            'clothes_rack' => ['A-1'], 'length' => [2], 'c_name' => $customer->name.'|'.$customer->id,
            'payment' => $paid, 'remain' => $owed, 'payment_method' => 'cash', 'paid_on' => now()->toDateString(),
        ]);

        // Two 300 sales, each owing 200.
        $sell(100, 200);
        $sell(100, 200);
        [$older, $newer] = CounterSaleReceipt::where('user_id', $owner->id)->orderBy('id')->get()->all();

        // A general payment (not tied to a sale) pays the oldest sale first.
        $this->actingAs($owner)->postJson(route('admin.DirectPayment'), [
            'customer_id' => $customer->id, 'DirectPayment' => 150,
        ])->assertOk();

        $this->actingAs($owner)->get(route('admin.customer.orders', ['customer' => $customer->id, 'tab' => 'sales']))
            ->assertSee('data-remaining="50"', false)
            ->assertSee('data-remaining="200"', false);

        $this->actingAs($owner)->postJson(route('admin.DirectPayment'), [
            'customer_id' => $customer->id, 'counter_sale_receipt_id' => $older->id, 'DirectPayment' => 60,
        ])->assertStatus(422);
        $this->actingAs($owner)->postJson(route('admin.DirectPayment'), [
            'customer_id' => $customer->id, 'counter_sale_receipt_id' => $older->id, 'DirectPayment' => 50,
        ])->assertOk();
        $this->assertEquals(200, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));
    }

    public function test_a_paid_return_can_be_taken_off_the_customers_previous_dues(): void
    {
        $owner = $this->userWithRole('shop_owner');
        $type = ClothType::create(['name' => 'Lawn', 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => 'Alkaram', 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id, 'price' => 100, 'sale_price' => 150, 'color_tracking_mode' => Cloth::COLOR_TRACKING_PER_COLOR, 'user_id' => $owner->id]);
        ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'White', 'length' => 20, 'average_unit_cost' => 100, 'user_id' => $owner->id]);
        $customer = Customers::create(['name' => 'Ayesha', 'phone_number1' => '03001234567', 'user_id' => $owner->id]);
        $sell = fn (int $paid, int $owed) => $this->actingAs($owner)->post(route('admin.sellStock'), [
            'brand_name' => [$brand->id], 'cloth_type' => [$type->id], 'color' => ['White'], 'per_meter' => [150],
            'clothes_rack' => ['A-1'], 'length' => [2], 'c_name' => $customer->name.'|'.$customer->id,
            'payment' => $paid, 'remain' => $owed, 'payment_method' => 'cash', 'paid_on' => now()->toDateString(),
        ]);

        // An older 300 sale still owes 200; a new 300 sale is paid in full.
        $sell(100, 200);
        $sell(300, 0);
        [$older, $newer] = CounterSaleReceipt::where('user_id', $owner->id)->orderBy('id')->get()->all();
        $newerItem = SaleStock::where('counter_sale_receipt_id', $newer->id)->sole();

        $this->actingAs($owner)->get(route('admin.customer.orders', ['customer' => $customer->id, 'tab' => 'sales']))
            ->assertOk()
            ->assertSee('data-other-dues="200"', false)
            ->assertSeeText('پچھلے بقایا میں کاٹیں');

        // Returning 1m (150) of the paid sale clears 150 of the older dues instead of paying cash out.
        $this->actingAs($owner)->post(route('admin.counter-sales.returns.store', $newer), [
            'items' => [$newerItem->id => 1], 'reason' => 'Customer wants it adjusted',
            'restock' => 1, 'settlement_type' => 'credit',
        ])->assertSessionHasNoErrors();

        $balances = app(\App\Services\CustomerLedgerService::class)->receiptBalances($owner->id, $customer->id);
        $this->assertEquals(50, $balances->get($older->id));
        $this->assertEquals(50, (float) Transaction::where('customerId', $customer->id)->sum('remainingBalance'));
    }

    private function userWithRole(string $name): User
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
