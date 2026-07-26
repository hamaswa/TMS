<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Business;
use App\Models\BusinessRole;
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
            'length' => 42,
            'note' => 'Prefers evening collection',
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
            ->assertSeeText('کل مشترکہ بقایا')
            ->assertDontSeeText('ٹیلرنگ بقایا')
            ->assertDontSeeText('دکان بقایا')
            ->assertSeeText('کھاتہ اور ادائیگیاں')
            ->assertSeeText('ٹیلرنگ آرڈرز')
            ->assertSeeText('کپڑے کی خریداری')
            ->assertSeeText('پیمائش');
        $this->actingAs($owner)->get(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'transactions']))
            ->assertOk()
            ->assertSeeText('فروخت #'.$sale->id);
        $this->actingAs($owner)->get(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'measurements']))
            ->assertOk()
            ->assertSeeText('42')
            ->assertSeeText('بنیادی پیمائش');
        $this->actingAs($owner)->get(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'profile']))
            ->assertOk()
            ->assertSeeText('Prefers evening collection');
        $this->actingAs($owner)->get(route('admin.Customers.index'))
            ->assertOk()
            ->assertSeeText('Rs: 700');
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

    public function test_tailoring_only_owner_does_not_see_clothing_customer_sections(): void
    {
        [$owner, $business] = $this->business();
        $business->update(['clothing_enabled' => false]);
        $customer = Customers::create([
            'name' => 'Tailoring Only Customer',
            'phone_number1' => '03001112233',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)->get(route('admin.customers.statement', $customer))
            ->assertOk()
            ->assertSeeText('ٹیلرنگ کا مشترکہ گاہک ریکارڈ')
            ->assertSeeText('ٹیلرنگ کا مجموعہ')
            ->assertDontSeeText('کپڑے کی خریداری')
            ->assertDontSee(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'shop']), false);

        $this->actingAs($owner)->get(route('admin.customers.statement', [
            'id' => $customer->id,
            'tab' => 'shop',
        ]))->assertOk()->assertDontSeeText('کپڑے کی خریداری');
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

    public function test_employee_balance_visibility_is_explicitly_controlled_by_client_role(): void
    {
        [$owner, $business] = $this->business();
        $customer = Customers::create([
            'name' => 'Permission Customer',
            'phone_number1' => '03007770000',
            'user_id' => $owner->id,
        ]);
        Transaction::create([
            'customerId' => $customer->id,
            'userId' => $owner->id,
            'Order_type' => 'Tailor',
            'remainingBalance' => 700,
        ]);
        $hiddenRole = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Customer records only',
            'permissions' => [BusinessRole::TAILORING_ACCESS, BusinessRole::TAILORING_CUSTOMERS],
        ]);
        $visibleRole = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Customer balance viewer',
            'permissions' => [
                BusinessRole::TAILORING_ACCESS,
                BusinessRole::TAILORING_CUSTOMERS,
                BusinessRole::CUSTOMER_BALANCES,
            ],
        ]);
        $hiddenEmployee = $this->employee($business, $hiddenRole);
        $visibleEmployee = $this->employee($business, $visibleRole);

        $this->actingAs($hiddenEmployee)->get(route('admin.customers.statement', $customer))
            ->assertOk()
            ->assertSeeText('بقایا اور ادائیگیاں دیکھنے کی اجازت نہیں')
            ->assertDontSeeText('Rs 700.00');
        $this->actingAs($hiddenEmployee)->post(route('admin.DirectPayment'), [
            'customer_id' => $customer->id,
            'DirectPayment' => 100,
        ])->assertForbidden();

        $this->actingAs($visibleEmployee)->get(route('admin.customers.statement', $customer))
            ->assertOk()
            ->assertSeeText('Rs 700.00');
        $this->actingAs($visibleEmployee)->post(route('admin.DirectPayment'), [
            'customer_id' => $customer->id,
            'DirectPayment' => 100,
            'comment' => 'Shared counter payment',
            'return_to_statement' => 1,
        ])->assertRedirect(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'transactions']));
        $this->assertDatabaseHas('transactions', [
            'customerId' => $customer->id,
            'Order_type' => 'Payment',
            'remainingBalance' => -100,
            'recivedPayment' => 100,
        ]);
        $this->actingAs($visibleEmployee)->get(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'transactions']))
            ->assertOk()
            ->assertSeeText('Rs 600.00')
            ->assertSeeText('Shared counter payment');
        $this->actingAs($owner)->get(route('admin.team.roles.index'))
            ->assertOk()
            ->assertSeeText('گاہک کا مشترکہ بقایا اور ادائیگیاں دیکھیں');
    }

    public function test_unified_profile_tabs_and_payment_action_follow_employee_permissions(): void
    {
        [$owner, $business] = $this->business();
        $customer = Customers::create([
            'name' => 'Shop Profile Customer',
            'phone_number1' => '03005550000',
            'user_id' => $owner->id,
        ]);
        Transaction::create([
            'customerId' => $customer->id,
            'userId' => $owner->id,
            'Order_type' => 'Sale',
            'remainingBalance' => 250,
        ]);
        $shopRole = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Shop profile staff',
            'permissions' => [
                BusinessRole::CLOTHING_ACCESS,
                BusinessRole::CLOTHING_SALES,
                BusinessRole::CUSTOMER_BALANCES,
            ],
        ]);
        $employee = $this->employee($business, $shopRole);

        $this->actingAs($employee)->get(route('admin.customers.statement', $customer))
            ->assertOk()
            ->assertSee(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'shop']), false)
            ->assertSee(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'transactions']), false)
            ->assertDontSee(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'tailoring']), false)
            ->assertDontSee(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'measurements']), false);
        $this->actingAs($employee)->get(route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'transactions']))
            ->assertOk()
            ->assertSee('action="'.route('admin.sale-direct-payment').'"', false)
            ->assertDontSee('action="'.route('admin.DirectPayment').'"', false);
    }

    public function test_balance_only_employee_can_open_the_shared_customer_statement(): void
    {
        [$owner, $business] = $this->business();
        $customer = Customers::create([
            'name' => 'Accounts Desk Customer',
            'phone_number1' => '03006660000',
            'user_id' => $owner->id,
        ]);
        Transaction::create([
            'customerId' => $customer->id,
            'userId' => $owner->id,
            'Order_type' => 'Tailor',
            'remainingBalance' => 875,
        ]);
        $role = BusinessRole::create([
            'business_id' => $business->id,
            'name' => 'Balance desk',
            'permissions' => [BusinessRole::CUSTOMER_BALANCES],
        ]);
        $employee = $this->employee($business, $role);

        $this->actingAs($employee)->get(route('admin.customers.statement', $customer))
            ->assertOk()
            ->assertSeeText('Accounts Desk Customer')
            ->assertSeeText('Rs 875.00');
    }

    private function owner(): User
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true, 'clothing_access' => true]);
        $owner->assignRole($role);

        return $owner;
    }

    private function business(): array
    {
        $owner = $this->owner();
        $owner->forceFill(['is_business_owner' => true])->save();
        $business = Business::create([
            'name' => 'Unified Balance Business',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
        ]);
        $owner->forceFill(['business_id' => $business->id])->save();

        return [$owner, $business];
    }

    private function employee(Business $business, BusinessRole $role): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'business_role_id' => $role->id,
            'is_business_owner' => false,
            'employee_active' => true,
            'tailoring_access' => false,
            'clothing_access' => false,
        ]);
    }
}
