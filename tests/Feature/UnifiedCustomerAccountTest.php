<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedCustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_sale_uses_existing_customer_and_combines_shop_and_tailoring_balance(): void
    {
        $owner = $this->owner();
        $customer = Customers::create([
            'name' => 'Unified Customer',
            'phone_number1' => '03001239876',
            'user_id' => $owner->id,
        ]);
        Transaction::create([
            'customerId' => $customer->id,
            'userId' => $owner->id,
            'Order_type' => 'Tailor',
            'recivedPayment' => 600,
            'remainingBalance' => 400,
        ]);

        $this->actingAs($owner)->post(route('admin.sale.store'), [
            'customer_id' => $customer->id,
            'name' => ['Shirt'],
            'quantity' => [1],
            'price' => [500],
            'received_payment' => 200,
            'remaining_balance' => 300,
        ])->assertRedirect();

        $sale = Sale::firstOrFail();
        $this->assertSame($customer->id, $sale->customer_id);
        $this->assertDatabaseHas('transactions', [
            'sale_id' => $sale->id,
            'customerId' => $customer->id,
            'Order_type' => 'Sale',
            'remainingBalance' => 300,
        ]);

        $this->actingAs($owner)->get(route('admin.customers.statement', $customer))
            ->assertOk()
            ->assertSeeText('Unified Customer')
            ->assertSeeText('Rs 700.00')
            ->assertSeeText('ٹیلرنگ بقایا')
            ->assertSeeText('دکان بقایا')
            ->assertSeeText('فروخت #'.$sale->id);
    }

    public function test_minimal_shop_customer_is_visible_in_shared_tailoring_customer_list(): void
    {
        $owner = $this->owner();
        Customers::create([
            'name' => 'Shop First Customer',
            'phone_number1' => '03001110000',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)->get(route('admin.Customers.index'))
            ->assertOk()
            ->assertSeeText('Shop First Customer');
    }

    public function test_counter_sale_rejects_customer_from_another_business(): void
    {
        $owner = $this->owner();
        $other = $this->owner();
        $customer = Customers::create([
            'name' => 'Other Customer',
            'phone_number1' => '03009998888',
            'user_id' => $other->id,
        ]);

        $this->actingAs($owner)->post(route('admin.sale.store'), [
            'customer_id' => $customer->id,
            'name' => ['Shirt'],
            'quantity' => [1],
            'price' => [500],
            'received_payment' => 200,
            'remaining_balance' => 300,
        ])->assertNotFound();

        $this->assertDatabaseCount('sales', 0);
    }

    private function owner(): User
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true, 'clothing_access' => true]);
        $owner->assignRole($role);

        return $owner;
    }
}
