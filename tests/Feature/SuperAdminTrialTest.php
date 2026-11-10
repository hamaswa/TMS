<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionPlanRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminTrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_client_starts_with_free_trial_then_must_pay(): void
    {
        [$admin, $owner, $business] = $this->accounts(trialEligible: true);
        $plan = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();

        $this->actingAs($admin)->get(route('administrator.clients.show', $owner))
            ->assertOk()->assertSee("Start with the plan's free trial", false);

        $this->assign($admin, $owner, $plan, trial: true)
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Free trial of 14 days started'));

        $trial = $business->subscriptions()->firstOrFail();
        $this->assertTrue($trial->is_trial);
        $this->assertSame(0.0, (float) $trial->fee);
        $this->assertSame(now()->addDays(13)->toDateString(), $trial->ends_on->toDateString());
        $this->assertTrue($owner->fresh()->hasModule(User::MODULE_CLOTHING));

        $this->actingAs($owner)->get(route('admin.subscription.index'))
            ->assertOk()
            ->assertSee('آپ مفت آزمائش پر ہیں۔')
            ->assertSee('ادائیگی کریں');

        // Once the trial ends the shop is blocked until it pays.
        $this->travelTo(now()->addDays(14));
        $this->actingAs($owner->fresh())->get(route('admin.home'))->assertRedirect(route('admin.subscription.required'));
        $this->actingAs($owner->fresh())->get(route('admin.subscription.required'))
            ->assertOk()->assertSee('آپ کی مفت آزمائش ختم ہو گئی ہے');
    }

    public function test_paying_during_the_trial_starts_the_paid_plan_when_the_trial_ends(): void
    {
        [$admin, $owner, $business] = $this->accounts(trialEligible: true);
        $plan = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $this->assign($admin, $owner, $plan, trial: true);
        PlatformSetting::put(PlatformSetting::EASYPAISA_ACCOUNT_NAME, 'BuyNStitch Payments');
        PlatformSetting::put(PlatformSetting::EASYPAISA_NUMBER, '0300-1234567');

        // Paying for the trial plan is a first payment, so no upgrade reason is asked.
        $this->actingAs($owner)->post(route('admin.subscription.request', $plan), [
            'payment_method' => 'easypaisa', 'payment_reference' => '12345678901',
        ])->assertSessionHasNoErrors();
        $request = $business->pendingPlanRequest()->firstOrFail();
        $this->assertFalse($request->is_upgrade);

        $paid = app(SubscriptionPlanRequestService::class)->approve($request, $admin);
        $this->assertFalse((bool) $paid->is_trial);
        $this->assertSame(now()->addDays(14)->toDateString(), $paid->starts_on->toDateString());
    }

    public function test_shops_registered_before_trials_get_the_paid_plan_directly(): void
    {
        [$admin, $owner, $business] = $this->accounts(trialEligible: false);
        $plan = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();

        $this->actingAs($admin)->get(route('administrator.clients.show', $owner))
            ->assertOk()->assertDontSee("Start with the plan's free trial", false);

        // Even if the trial box is sent, an existing shop is not given a trial.
        $this->assign($admin, $owner, $plan, trial: true);
        $subscription = $business->subscriptions()->firstOrFail();
        $this->assertFalse((bool) $subscription->is_trial);
        $this->assertSame(1200.0, (float) $subscription->fee);
        $this->assertSame(now()->addDays(29)->toDateString(), $subscription->ends_on->toDateString());
    }

    public function test_trial_is_given_only_once(): void
    {
        [$admin, $owner, $business] = $this->accounts(trialEligible: true);
        $plan = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $this->assign($admin, $owner, $plan, trial: true);

        $this->travelTo(now()->addDays(14));
        $this->assign($admin, $owner, $plan, trial: true);

        $this->assertSame(1, $business->subscriptions()->where('is_trial', true)->count());
        $this->assertSame(1200.0, (float) $business->subscriptions()->latest('id')->firstOrFail()->fee);
    }

    private function assign(User $admin, User $owner, SubscriptionPlan $plan, bool $trial)
    {
        return $this->actingAs($admin)->post(route('administrator.subscriptions.store', $owner), [
            'subscription_plan_id' => $plan->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays($plan->billing_period_days - 1)->toDateString(),
            'fee' => $plan->price,
            ...($trial ? ['start_trial' => 1] : []),
        ])->assertSessionHasNoErrors();
    }

    private function accounts(bool $trialEligible): array
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
            'name' => 'Trial Shop',
            'shop_code' => 'TRL-0001',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
            'trial_eligible' => $trialEligible,
        ]);
        $owner->update(['business_id' => $business->id]);

        return [$admin, $owner->fresh(), $business];
    }
}
