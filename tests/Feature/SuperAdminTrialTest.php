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

    public function test_super_admin_extends_a_running_trial(): void
    {
        [$admin, $owner, $business] = $this->accounts(trialEligible: true);
        $plan = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $this->assign($admin, $owner, $plan, trial: true);
        $trial = $business->subscriptions()->firstOrFail();

        $this->actingAs($admin)->get(route('administrator.clients.show', $owner))
            ->assertOk()->assertSee('Extend free trial');

        $newEnd = $trial->ends_on->copy()->addDays(30)->toDateString();
        $this->actingAs($admin)->patch(route('administrator.subscriptions.extend-trial', [$owner, $trial]), [
            'new_ends_on' => $newEnd,
            'extension_reason' => 'Needs time to set up',
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'extended by 30 days'));

        $trial->refresh();
        $this->assertTrue($trial->is_trial);
        $this->assertSame($newEnd, $trial->ends_on->toDateString());
        $this->assertSame($newEnd, $trial->trial_ends_at->toDateString());
        $this->assertStringContainsString('Needs time to set up', $trial->trialExtensionNotes()[0]);

        $this->actingAs($admin)->get(route('administrator.clients.show', $owner))
            ->assertOk()->assertSee('Trial extended by 30 days', false);

        // Still open on the old end date + 1, blocked after the new one.
        $this->travelTo(now()->addDays(14));
        $this->actingAs($owner->fresh())->get(route('admin.subscription.index'))->assertOk();
        $this->travelTo(\Illuminate\Support\Carbon::parse($newEnd)->addDay());
        $this->actingAs($owner->fresh())->get(route('admin.home'))->assertRedirect(route('admin.subscription.required'));
    }

    public function test_extending_an_ended_trial_reopens_the_shop_from_today(): void
    {
        [$admin, $owner, $business] = $this->accounts(trialEligible: true);
        $plan = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $this->assign($admin, $owner, $plan, trial: true);
        $trial = $business->subscriptions()->firstOrFail();

        $this->travelTo(now()->addDays(20));
        $this->actingAs($owner->fresh())->get(route('admin.home'))->assertRedirect(route('admin.subscription.required'));

        // Counted from today, so 7 days means today and the next 6.
        $this->actingAs($admin)->patch(route('administrator.subscriptions.extend-trial', [$owner, $trial]), [
            'new_ends_on' => now()->addDays(6)->toDateString(),
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'extended by 7 days'));

        $this->assertSame(now()->addDays(6)->toDateString(), $trial->fresh()->ends_on->toDateString());
        $this->assertTrue($business->fresh()->hasActiveSubscriptionAccess());

        // A date that adds nothing is refused.
        $this->actingAs($admin)->patch(route('administrator.subscriptions.extend-trial', [$owner, $trial]), [
            'new_ends_on' => now()->addDays(3)->toDateString(),
        ])->assertSessionHasErrors('new_ends_on', null, 'extendTrial');
    }

    public function test_a_plan_paid_during_the_trial_moves_after_the_extension(): void
    {
        [$admin, $owner, $business] = $this->accounts(trialEligible: true);
        $plan = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $this->assign($admin, $owner, $plan, trial: true);
        $trial = $business->subscriptions()->firstOrFail();
        PlatformSetting::put(PlatformSetting::EASYPAISA_ACCOUNT_NAME, 'BuyNStitch Payments');
        PlatformSetting::put(PlatformSetting::EASYPAISA_NUMBER, '0300-1234567');
        $this->actingAs($owner)->post(route('admin.subscription.request', $plan), [
            'payment_method' => 'easypaisa', 'payment_reference' => '12345678901',
        ])->assertSessionHasNoErrors();
        $paid = app(SubscriptionPlanRequestService::class)->approve($business->pendingPlanRequest()->firstOrFail(), $admin);
        $paidDays = $paid->starts_on->diffInDays($paid->ends_on);

        $this->actingAs($admin)->patch(route('administrator.subscriptions.extend-trial', [$owner, $trial]), [
            'new_ends_on' => $trial->ends_on->copy()->addDays(10)->toDateString(),
        ])->assertSessionHasNoErrors();

        $paid->refresh();
        $this->assertSame($trial->ends_on->copy()->addDays(11)->toDateString(), $paid->starts_on->toDateString());
        $this->assertSame($paidDays, $paid->starts_on->diffInDays($paid->ends_on));

        // Once the paid plan has started the trial can no longer be extended.
        $this->travelTo($paid->starts_on->copy()->addDay());
        $this->actingAs($admin)->patch(route('administrator.subscriptions.extend-trial', [$owner, $trial]), [
            'new_ends_on' => now()->addDays(5)->toDateString(),
        ])->assertSessionHasErrors('new_ends_on', null, 'extendTrial');
    }

    public function test_only_trials_can_be_extended(): void
    {
        [$admin, $owner, $business] = $this->accounts(trialEligible: false);
        $plan = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $this->assign($admin, $owner, $plan, trial: false);
        $subscription = $business->subscriptions()->firstOrFail();

        $this->actingAs($admin)->get(route('administrator.clients.show', $owner))
            ->assertOk()->assertDontSee('Extend free trial');
        $this->actingAs($admin)->patch(route('administrator.subscriptions.extend-trial', [$owner, $subscription]), [
            'new_ends_on' => now()->addDays(60)->toDateString(),
        ])->assertNotFound();
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
