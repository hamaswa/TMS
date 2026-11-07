<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StandardSubscriptionPlansTest extends TestCase
{
    use RefreshDatabase;

    /**
     * What a shop owner on each standard plan can open.
     */
    public static function plans(): array
    {
        return [
            'starter tailoring' => ['starter-tailoring-monthly', [
                'tailoring' => true, 'clothing' => false, 'storefront' => false,
                'reports' => false, 'team' => true, 'activity' => false,
            ]],
            'starter clothing' => ['starter-clothing-monthly', [
                'tailoring' => false, 'clothing' => true, 'storefront' => false,
                'reports' => false, 'team' => true, 'activity' => false,
            ]],
            'business pro' => ['business-pro-monthly', [
                'tailoring' => true, 'clothing' => true, 'storefront' => true,
                'reports' => true, 'team' => true, 'activity' => false,
            ]],
            'elite boutique' => ['elite-boutique-monthly', [
                'tailoring' => true, 'clothing' => true, 'storefront' => true,
                'reports' => true, 'team' => true, 'activity' => true,
            ]],
            'elite boutique annual' => ['elite-boutique-annual', [
                'tailoring' => true, 'clothing' => true, 'storefront' => true,
                'reports' => true, 'team' => true, 'activity' => true,
            ]],
        ];
    }

    #[DataProvider('plans')]
    public function test_plan_turns_features_on_and_off_for_shop_owner(string $code, array $expected): void
    {
        [$admin, $owner, $business] = $this->accounts();
        $plan = SubscriptionPlan::where('code', $code)->firstOrFail();

        $this->actingAs($admin)->post(route('administrator.subscriptions.store', $owner), [
            'subscription_plan_id' => $plan->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays($plan->billing_period_days)->toDateString(),
            'fee' => $plan->price,
        ])->assertSessionHasNoErrors();

        $subscription = $business->fresh()->currentSubscription();
        $this->assertSame($plan->max_customers, $subscription->max_customers);
        $this->assertSame($plan->max_employees, $subscription->max_employees);

        $owner = $owner->fresh();
        $this->assertSame($expected['tailoring'], $owner->hasModule(User::MODULE_TAILORING), 'tailoring workspace');
        $this->assertSame($expected['clothing'], $owner->hasModule(User::MODULE_CLOTHING), 'clothing workspace');

        $pages = [
            'storefront' => 'admin.storefront.edit',
            'reports' => 'admin.financial-reports.index',
            'team' => 'admin.team.index',
            'activity' => 'admin.activity.index',
        ];
        foreach ($pages as $feature => $route) {
            $status = $this->actingAs($owner)->get(route($route))->status();
            if ($expected[$feature]) {
                $this->assertNotSame(403, $status, "{$feature} should be available on {$code}");
            } else {
                $this->assertSame(403, $status, "{$feature} should be blocked on {$code}");
            }
        }

        // Online orders follow the online shop feature, even when an old storefront still exists.
        $status = $this->actingAs($owner)->get(route('admin.storefront.orders.index'))->status();
        $expected['storefront'] ? $this->assertNotSame(403, $status) : $this->assertSame(403, $status, "online orders should be blocked on {$code}");

        // The team page only offers to add tailors and other workers when the plan includes tailoring.
        $team = $this->actingAs($owner)->get(route('admin.team.index'));
        $workerForm = 'action="'.route('admin.production-workers.store').'"';
        $expected['tailoring'] ? $team->assertSee($workerForm, false) : $team->assertDontSee($workerForm, false);
    }

    private function accounts(): array
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'administrative', 'guard_name' => 'web']));
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole(Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']));
        $business = Business::create([
            'name' => 'Standard Plan Business',
            'shop_code' => 'STD-0001',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        return [$admin, $owner->fresh(), $business];
    }
}
