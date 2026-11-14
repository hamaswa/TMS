<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessRole;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Storefront;
use App\Models\Tailor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_is_snapshotted_when_super_admin_assigns_subscription(): void
    {
        [$admin, $owner, $business] = $this->accounts();
        $plan = $this->plan([
            'max_employees' => 3,
            'max_business_roles' => 2,
            'max_tailors' => 4,
            'max_customers' => 500,
            'allowed_permissions' => [BusinessRole::TAILORING_ACCESS, BusinessRole::TEAM_MANAGE],
        ]);

        $this->actingAs($admin)->post(route('administrator.subscriptions.store', $owner), [
            'subscription_plan_id' => $plan->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'fee' => 2500,
        ])->assertRedirect(route('administrator.clients.show', $owner));

        $subscription = BusinessSubscription::where('business_id', $business->id)->firstOrFail();
        $this->assertSame($plan->id, $subscription->subscription_plan_id);
        $this->assertSame('controlled-plan', $subscription->plan_code);
        $this->assertSame(3, $subscription->max_employees);
        $this->assertSame(2, $subscription->max_business_roles);
        $this->assertSame(4, $subscription->max_tailors);
        $this->assertSame(500, $subscription->max_customers);
        $this->assertSame($plan->allowed_permissions, $subscription->allowed_permissions);

        $plan->update(['max_employees' => 50]);
        $this->assertSame(3, $subscription->fresh()->max_employees);
    }

    public function test_standard_plans_are_available_to_super_admin(): void
    {
        $plans = SubscriptionPlan::pluck('code')->all();
        foreach (['starter-tailoring', 'starter-clothing', 'business-pro', 'elite-boutique'] as $tier) {
            $this->assertContains($tier.'-monthly', $plans);
            $this->assertContains($tier.'-annual', $plans);
        }

        $starter = SubscriptionPlan::where('code', 'starter-tailoring-monthly')->firstOrFail();
        $this->assertTrue($starter->allow_tailoring);
        $this->assertFalse($starter->allow_clothing);
        $this->assertSame(500, $starter->max_customers);
        $this->assertNotContains(BusinessRole::CLOTHING_ACCESS, $starter->allowed_permissions);

        $pro = SubscriptionPlan::where('code', 'business-pro-monthly')->firstOrFail();
        $this->assertTrue($pro->allow_tailoring && $pro->allow_clothing && $pro->is_recommended);
        $this->assertNotContains(BusinessRole::ACTIVITY_VIEW, $pro->allowed_permissions);

        $elite = SubscriptionPlan::where('code', 'elite-boutique-annual')->firstOrFail();
        $this->assertNull($elite->max_customers);
        $this->assertSame(365, $elite->billing_period_days);
        $this->assertFalse($elite->is_public);
    }

    public function test_customer_limit_blocks_new_main_customers_but_not_family_profiles_or_walk_ins(): void
    {
        [, $owner, $business] = $this->accounts();
        $this->subscription($business, ['max_customers' => 2]);

        $first = \App\Models\Customers::create(['name' => 'One', 'phone_number1' => '03001110001', 'user_id' => $owner->id]);
        \App\Models\Customers::create(['name' => 'Two', 'phone_number1' => '03001110002', 'user_id' => $owner->id]);

        // Family profiles and the walk-in placeholder do not use up the limit.
        \App\Models\Customers::create(['name' => 'Son', 'phone_number1' => '03001110001', 'user_id' => $owner->id, 'parent_id' => $first->id]);
        \App\Models\Customers::create(['name' => 'Walk-in Customer', 'phone_number1' => '', 'user_id' => $owner->id, 'is_walk_in' => true]);

        try {
            \App\Models\Customers::create(['name' => 'Three', 'phone_number1' => '03001110003', 'user_id' => $owner->id]);
            $this->fail('A third main customer should be refused.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('customer_limit', $e->errors());
        }
        $this->assertSame(0, \App\Models\Customers::where('name', 'Three')->count());

        // The shop's own customer form shows the error instead of saving.
        $this->actingAs($owner)->post(route('admin.Customers.store'), [
            'name' => 'Four', 'contact' => '03001110004', 'basic_save' => 1,
        ])->assertSessionHasErrors('customer_limit');

        // The quick sale-customer form refuses too, and the page says why.
        $this->actingAs($owner)->post(route('admin.addcustomers.sale'), [
            'customer_name' => 'Five', 'customer_num' => '03001110005',
        ])->assertSessionHasErrors('customer_limit');
        $this->actingAs($owner)->get(route('admin.customers.sale'))
            ->assertOk()
            ->assertSeeText('2 گاہکوں کی اجازت ہے');
    }

    public function test_one_time_cash_buyers_from_clothing_sales_do_not_use_up_the_customer_limit(): void
    {
        [, $owner, $business] = $this->accounts();
        $this->subscription($business, ['max_customers' => 2]);
        $service = app(\App\Services\SubscriptionEntitlementService::class);
        $buyer = fn (string $name) => \App\Models\Customers::create([
            'name' => $name, 'phone_number1' => '', 'user_id' => $owner->id, 'acquisition_source' => 'counter_sale',
        ]);
        $sale = fn ($customer, float $due) => \App\Models\Transaction::create([
            'customerId' => $customer->id, 'userId' => $owner->id, 'Order_type' => 'Sale',
            'remainingBalance' => $due, 'recivedPayment' => 1000,
        ]);

        \App\Models\Customers::create(['name' => 'Added by hand', 'phone_number1' => '03001110001', 'user_id' => $owner->id]);
        // Cash buyers are saved even with the shop near its limit and don't count.
        foreach (['Cash A', 'Cash B', 'Cash C'] as $name) {
            $sale($buyer($name), 0);
        }
        $this->assertSame(1, $service->countedCustomers($owner->id));

        // Coming back for a second sale makes a buyer a regular.
        $returning = \App\Models\Customers::where('name', 'Cash A')->first();
        $sale($returning, 0);
        $this->assertSame(2, $service->countedCustomers($owner->id));
        $this->assertSame(2, $service->customerLimitReached($owner->id));

        // At the limit, a new cash buyer is still allowed but one added by hand is refused.
        $sale($buyer('Cash D'), 0);
        $this->assertSame(2, $service->countedCustomers($owner->id));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        \App\Models\Customers::create(['name' => 'Refused', 'phone_number1' => '03001110009', 'user_id' => $owner->id]);
    }

    public function test_sale_buyers_on_udhaar_or_with_tailoring_orders_count_toward_the_limit(): void
    {
        [, $owner, $business] = $this->accounts();
        $this->subscription($business, ['max_customers' => 5]);
        $service = app(\App\Services\SubscriptionEntitlementService::class);

        $onCredit = \App\Models\Customers::create(['name' => 'Udhaar', 'phone_number1' => '', 'user_id' => $owner->id, 'acquisition_source' => 'counter_sale']);
        \App\Models\Transaction::create(['customerId' => $onCredit->id, 'userId' => $owner->id, 'Order_type' => 'Sale', 'remainingBalance' => 500, 'recivedPayment' => 500]);
        $tailoring = \App\Models\Customers::create(['name' => 'Stitching', 'phone_number1' => '', 'user_id' => $owner->id, 'acquisition_source' => 'sales_agent']);
        \App\Models\Order::create(['suitQuantity' => 1, 'totalPayment' => 1500, 'userId' => $owner->id, 'customerId' => $tailoring->id]);
        \App\Models\Customers::create(['name' => 'Old cash buyer', 'phone_number1' => '', 'user_id' => $owner->id, 'acquisition_source' => 'existing_data']);
        \App\Models\Customers::create(['name' => 'Counter order buyer', 'phone_number1' => '', 'user_id' => $owner->id, 'acquisition_source' => 'counter_order']);

        $this->assertSame(2, $service->countedCustomers($owner->id));
    }

    public function test_walk_in_records_have_no_serial_and_show_in_the_customer_list(): void
    {
        [, $owner, $business] = $this->accounts();
        $this->subscription($business, ['max_customers' => 10]);
        $walkIn = \App\Models\Customers::create(['name' => 'Walk-in Customer', 'phone_number1' => '', 'user_id' => $owner->id, 'is_walk_in' => true]);
        \App\Models\Customers::create(['name' => 'Named Buyer', 'phone_number1' => '03001110001', 'user_id' => $owner->id]);
        $badges = fn ($response) => substr_count($response->getContent(), 'class="customer-walk-in-badge"');

        $this->assertNull($walkIn->fresh()->serial_number);
        // The walk-in record is listed and marked walk-in.
        $response = $this->actingAs($owner)->get(route('admin.Customers.index'))
            ->assertOk()
            ->assertSeeText('Walk-in Customer')
            ->assertSeeText('Named Buyer');
        $this->assertSame(1, $badges($response));

        // A named one-time buyer is marked walk-in too, until they become a regular.
        $buyer = \App\Models\Customers::create(['name' => 'Feroz', 'phone_number1' => '03001110002', 'user_id' => $owner->id, 'acquisition_source' => 'counter_order']);
        $this->assertSame(2, $badges($this->actingAs($owner)->get(route('admin.Customers.index'))->assertSeeText('Feroz')));
        \App\Models\Transaction::create(['customerId' => $buyer->id, 'userId' => $owner->id, 'Order_type' => 'Sale', 'remainingBalance' => 300, 'recivedPayment' => 0]);
        $this->assertSame(1, $badges($this->actingAs($owner)->get(route('admin.Customers.index'))->assertSeeText('Feroz')));
    }

    public function test_each_walk_in_sale_gets_its_own_uncounted_record(): void
    {
        [, $owner, $business] = $this->accounts();
        $this->subscription($business, ['max_customers' => 1]);
        \App\Models\Customers::create(['name' => 'Regular', 'phone_number1' => '03001110001', 'user_id' => $owner->id]);

        // At the limit, walk-in sales still get their own numbered records.
        $first = \App\Models\Customers::newWalkIn($owner->id);
        $second = \App\Models\Customers::newWalkIn($owner->id);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(['Walk-in #1', 'Walk-in #2'], [$first->name, $second->name]);
        $this->assertTrue($second->fresh()->is_walk_in);
        $this->assertNull($second->fresh()->serial_number);
        $this->assertSame(1, app(\App\Services\SubscriptionEntitlementService::class)->countedCustomers($owner->id));
    }

    public function test_expired_subscription_restricts_owner_employee_and_tailor_but_preserves_renewal_access(): void
    {
        [, $owner, $business] = $this->accounts();
        $this->subscription($business, ['ends_on' => now()->subDay()->toDateString()]);
        $employee = User::factory()->create([
            'business_id' => $business->id,
            'employee_active' => true,
            'is_business_owner' => false,
        ]);
        $employee->assignRole(Role::firstOrCreate(['name' => 'business_employee', 'guard_name' => 'web']));

        $this->actingAs($owner)->get(route('admin.home'))
            ->assertRedirect(route('admin.subscription.required'));
        $this->actingAs($owner)->get(route('admin.subscription.index'))->assertOk();
        $this->actingAs($owner)->get(route('admin.notifications.index'))->assertOk();

        $this->actingAs($employee)->get(route('admin.home'))
            ->assertRedirect(route('admin.subscription.required'));
        $this->actingAs($employee)->get(route('admin.subscription.required'))
            ->assertOk()
            ->assertSeeText($owner->name);

        $this->assertCount(1, $owner->fresh()->notifications);
        $this->assertDatabaseHas('subscription_notification_deliveries', [
            'user_id' => $owner->id,
            'threshold_days' => -1,
        ]);

        Tailor::create([
            'user_id' => $owner->id,
            'name' => 'Restricted Tailor',
            'phone_number1' => '03001112222',
            'password' => Hash::make('Tailor@2026'),
        ]);
        $this->post('/tailor-login', [
            'shop_code' => $business->shop_code,
            'contact' => '03001112222',
            'password' => 'Tailor@2026',
        ])->assertSessionHas('failed');
    }

    public function test_active_plan_limits_modules_permissions_employees_roles_and_tailors(): void
    {
        [, $owner, $business] = $this->accounts();
        $this->subscription($business, [
            'max_employees' => 1,
            'max_business_roles' => 1,
            'max_tailors' => 1,
            'allow_tailoring' => true,
            'allow_clothing' => false,
            'allow_team_management' => true,
            'allowed_permissions' => [
                BusinessRole::TAILORING_ACCESS,
                BusinessRole::TAILORING_TAILORS,
                BusinessRole::TEAM_MANAGE,
            ],
        ]);
        $role = $business->roles()->create([
            'name' => 'Tailoring Operator',
            'permissions' => [BusinessRole::TAILORING_ACCESS, BusinessRole::TAILORING_TAILORS],
        ]);
        User::factory()->create([
            'business_id' => $business->id,
            'business_role_id' => $role->id,
            'employee_active' => true,
            'is_business_owner' => false,
        ]);
        Tailor::create([
            'user_id' => $owner->id,
            'name' => 'Existing Tailor',
            'phone_number1' => '03002223333',
            'password' => Hash::make('Tailor@2026'),
        ]);

        $this->assertTrue($owner->fresh()->hasModule(User::MODULE_TAILORING));
        $this->assertFalse($owner->fresh()->hasModule(User::MODULE_CLOTHING));
        $this->assertTrue($owner->fresh()->hasBusinessPermission(BusinessRole::TEAM_MANAGE));
        $this->assertFalse($owner->fresh()->hasBusinessPermission(BusinessRole::FINANCE_VIEW));

        $this->actingAs($owner)->post(route('admin.team.roles.store'), [
            'name' => 'Second role',
            'permissions' => [BusinessRole::TAILORING_ACCESS],
        ])->assertSessionHasErrors('role_limit');

        $this->actingAs($owner)->post(route('admin.team.employees.store'), [
            'name' => 'Second Employee',
            'username' => 'second.employee',
            'email' => 'second.employee@example.test',
            'password' => 'Demo@2026',
            'business_role_id' => $role->id,
        ])->assertSessionHasErrors('employee_limit');

        $this->actingAs($owner)->post(route('admin.Tailor.store'), [
            'name' => 'Second Tailor',
            'contact' => '03003334444',
            'password' => 'Tailor@2026',
        ])->assertSessionHasErrors('tailor_limit');

        $this->actingAs($owner)
            ->get(route('admin.Tailor.index'))
            ->assertOk()
            ->assertSeeText('مزید درزی کے لیے پلان اپ گریڈ کریں')
            ->assertDontSee(route('admin.Tailor.create'), false);

        $this->actingAs($owner)
            ->get(route('admin.Tailor.create'))
            ->assertRedirect(route('admin.team.index', ['open' => 'create', 'type' => 'tailor']))
            ->assertSessionHasErrors('tailor_limit');

        $this->assertDatabaseCount('business_roles', 1);
        $this->assertDatabaseCount('tailors', 1);
        $this->assertDatabaseMissing('business_activity_logs', [
            'business_id' => $business->id,
            'route_name' => 'admin.Tailor.store',
        ]);
    }

    public function test_legacy_unconfigured_client_keeps_access_until_first_subscription_is_assigned(): void
    {
        [, $owner, $business] = $this->accounts();

        $this->assertFalse($business->subscriptionIsManaged());
        $this->actingAs($owner)->get(route('admin.home'))->assertOk();
        $this->assertTrue($owner->fresh()->hasModule(User::MODULE_TAILORING));
        $this->assertTrue($owner->fresh()->hasModule(User::MODULE_CLOTHING));
    }

    public function test_repeated_permission_checks_reuse_subscription_queries_within_a_request(): void
    {
        [, , $business] = $this->accounts();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->assertFalse($business->subscriptionIsManaged());
        $this->assertTrue($business->subscriptionAllowsPermission(BusinessRole::TEAM_MANAGE));
        $this->assertTrue($business->subscriptionAllowsPermission(BusinessRole::FINANCE_VIEW));
        $this->assertTrue($business->subscriptionAllowsFeature('allow_tailoring'));

        $subscriptionQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'business_subscriptions'));
        DB::disableQueryLog();

        $this->assertCount(1, $subscriptionQueries);
    }

    public function test_super_admin_manages_plans_and_disabled_feature_permissions_are_removed(): void
    {
        [$admin, $owner] = $this->accounts();

        $this->actingAs($admin)->post(route('administrator.subscription-plans.store'), [
            'name' => 'Tailoring Starter',
            'code' => 'tailoring-starter',
            'price' => 1500,
            'billing_period_days' => 30,
            'max_employees' => 2,
            'max_business_roles' => 2,
            'max_tailors' => 3,
            'features' => ['allow_tailoring', 'allow_team_management'],
            'allowed_permissions' => [
                BusinessRole::TAILORING_ACCESS,
                BusinessRole::TEAM_MANAGE,
                BusinessRole::CLOTHING_SALES,
                BusinessRole::FINANCE_VIEW,
            ],
            'is_active' => 1,
        ])->assertRedirect();

        $plan = SubscriptionPlan::where('code', 'tailoring-starter')->firstOrFail();
        $this->assertSame(
            [BusinessRole::TAILORING_ACCESS, BusinessRole::TEAM_MANAGE],
            $plan->allowed_permissions
        );
        $this->assertFalse($plan->allow_clothing);
        $this->assertFalse($plan->allow_financial_reports);
        $this->actingAs($admin)->get(route('administrator.subscription-plans.index'))
            ->assertOk()
            ->assertSeeText('Tailoring Starter');
        $this->actingAs($owner)->get(route('administrator.subscription-plans.index'))
            ->assertForbidden();
    }

    public function test_simple_plan_form_fills_code_workspaces_and_permissions(): void
    {
        [$admin] = $this->accounts();

        $this->actingAs($admin)->post(route('administrator.subscription-plans.store'), [
            '_form' => 'new',
            'name' => 'Clothing Basic',
            'price' => 900,
            'billing_period_days' => 30,
            'trial_days' => 7,
            'workspace' => 'clothing',
            'max_customers' => 300,
            'features' => ['allow_financial_reports', 'allow_team_management'],
            'permissions_mode' => 'all',
            'allowed_permissions' => [BusinessRole::TAILORING_ACCESS],
            'is_public' => 1,
            'lifecycle_status' => 'active',
        ])->assertSessionHasNoErrors();

        $plan = SubscriptionPlan::where('code', 'clothing-basic')->firstOrFail();
        $this->assertFalse($plan->allow_tailoring);
        $this->assertTrue($plan->allow_clothing);
        $this->assertTrue($plan->is_active);
        $this->assertSame(300, $plan->max_customers);
        $this->assertContains(BusinessRole::CLOTHING_SALES, $plan->allowed_permissions);
        $this->assertContains(BusinessRole::FINANCE_VIEW, $plan->allowed_permissions);
        $this->assertNotContains(BusinessRole::TAILORING_ACCESS, $plan->allowed_permissions);
        $this->assertNotContains(BusinessRole::STOREFRONT_MANAGE, $plan->allowed_permissions);
        $this->assertTrue($plan->allowsAllFeaturePermissions());

        $this->actingAs($admin)->post(route('administrator.subscription-plans.store'), [
            'name' => 'Clothing Basic', 'price' => 900, 'billing_period_days' => 30, 'workspace' => 'both',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(SubscriptionPlan::where('code', 'clothing-basic-2')->exists());

        $this->actingAs($admin)->put(route('administrator.subscription-plans.update', $plan), [
            'name' => 'Clothing Basic', 'price' => 950, 'billing_period_days' => 30, 'workspace' => 'clothing',
            'lifecycle_status' => 'archived',
        ])->assertSessionHasNoErrors();
        $plan->refresh();
        $this->assertSame('clothing-basic', $plan->code);
        $this->assertFalse($plan->is_active);

        $this->actingAs($admin)->get(route('administrator.subscription-plans.index'))
            ->assertOk()
            ->assertSee('plan-modal-'.$plan->id, false)
            ->assertSeeText('Clothing only');
    }

    public function test_public_storefront_requires_active_subscription_and_storefront_entitlement(): void
    {
        [, , $business] = $this->accounts();
        $subscription = $this->subscription($business, ['allow_storefront' => false]);
        Storefront::create([
            'business_id' => $business->id,
            'slug' => 'entitlement-shop',
            'display_name' => 'Entitlement Shop',
            'is_published' => true,
            'moderation_status' => Storefront::MODERATION_ACTIVE,
        ]);

        $this->assertSame(0, Storefront::publiclyVisible()->count());
        $subscription->update(['allow_storefront' => true]);
        $this->assertSame(1, Storefront::publiclyVisible()->count());
        $subscription->update(['ends_on' => now()->subDay()->toDateString()]);
        $this->assertSame(0, Storefront::publiclyVisible()->count());
    }

    private function accounts(): array
    {
        $adminRole = Role::firstOrCreate(['name' => 'administrative', 'guard_name' => 'web']);
        $ownerRole = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole($ownerRole);
        $business = Business::create([
            'name' => 'Entitlement Test Business',
            'shop_code' => 'ENT-0001',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        return [$admin, $owner->fresh(), $business];
    }

    private function plan(array $overrides = []): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Controlled Plan',
            'code' => 'controlled-plan',
            'price' => 2500,
            'billing_period_days' => 30,
            'allow_tailoring' => true,
            'allow_clothing' => false,
            'allow_storefront' => false,
            'allow_financial_reports' => false,
            'allow_team_management' => true,
            'allow_activity_log' => false,
            'is_active' => true,
            ...$overrides,
        ]);
    }

    private function subscription(Business $business, array $overrides = []): BusinessSubscription
    {
        return $business->subscriptions()->create([
            'plan_name' => 'Controlled Plan',
            'plan_code' => 'controlled-plan',
            'starts_on' => now()->subDay()->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'fee' => 2500,
            'allow_tailoring' => true,
            'allow_clothing' => true,
            'allow_storefront' => true,
            'allow_financial_reports' => true,
            'allow_team_management' => true,
            'allow_activity_log' => true,
            'allowed_permissions' => null,
            ...$overrides,
        ]);
    }
}
