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
        $this->assertSame($plan->allowed_permissions, $subscription->allowed_permissions);

        $plan->update(['max_employees' => 50]);
        $this->assertSame(3, $subscription->fresh()->max_employees);
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

        $this->assertDatabaseCount('business_roles', 1);
        $this->assertDatabaseCount('tailors', 1);
    }

    public function test_legacy_unconfigured_client_keeps_access_until_first_subscription_is_assigned(): void
    {
        [, $owner, $business] = $this->accounts();

        $this->assertFalse($business->subscriptionIsManaged());
        $this->actingAs($owner)->get(route('admin.home'))->assertOk();
        $this->assertTrue($owner->fresh()->hasModule(User::MODULE_TAILORING));
        $this->assertTrue($owner->fresh()->hasModule(User::MODULE_CLOTHING));
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
