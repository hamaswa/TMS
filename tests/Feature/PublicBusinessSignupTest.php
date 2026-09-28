<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicBusinessSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_plan_is_visible_and_creates_plan_specific_trial_after_signup(): void
    {
        Notification::fake();
        $plan = $this->publicPlan();

        $this->withSession(['public_locale' => 'en'])->get(route('storefront.business'))
            ->assertOk()
            ->assertSeeText('Starter Shop')
            ->assertSeeText('14-day free trial')
            ->assertSee(route('storefront.business.signup', ['plan' => $plan->id]), false);

        $this->withSession(['public_locale' => 'en'])->get(route('storefront.business.signup', ['plan' => $plan->id]))
            ->assertOk()
            ->assertSeeText('Create your business workspace')
            ->assertSee('name="subscription_plan_id"', false);

        $response = $this->withSession(['public_locale' => 'en'])->post(route('storefront.business.signup.store'), [
            'subscription_plan_id' => $plan->id,
            'owner_name' => 'Public Trial Owner',
            'business_name' => 'Public Trial Shop',
            'email' => 'public-trial@example.test',
            'mobile' => '0300 1234567',
            'modules' => ['clothing'],
            'password' => 'Trialpass1',
            'password_confirmation' => 'Trialpass1',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $owner = User::where('email', 'public-trial@example.test')->firstOrFail();
        $business = $owner->ownedBusiness()->firstOrFail();
        $subscription = $business->latestSubscription()->firstOrFail();

        $this->assertTrue($owner->hasRole('shop_owner'));
        $this->assertSame(Business::STATUS_PENDING, $business->status);
        $this->assertSame('+923001234567', $business->signup_mobile_normalized);
        $this->assertTrue($business->clothing_enabled);
        $this->assertFalse($business->tailoring_enabled);
        $this->assertTrue($subscription->is_trial);
        $this->assertSame(0.0, (float) $subscription->fee);
        $this->assertSame(14, (int) $subscription->starts_on->diffInDays($subscription->ends_on) + 1);
        $this->assertSame($plan->id, $subscription->subscription_plan_id);
        $this->assertTrue($subscription->allow_clothing);
        $this->assertFalse($subscription->allow_tailoring);
        $this->assertNull($business->storefront);
        Notification::assertSentTo($owner, VerifyEmail::class);
    }

    public function test_email_verification_activates_private_trial_but_does_not_publish_storefront(): void
    {
        Notification::fake();
        $plan = $this->publicPlan();
        $this->post(route('storefront.business.signup.store'), [
            'subscription_plan_id' => $plan->id,
            'owner_name' => 'Verified Owner',
            'business_name' => 'Verified Shop',
            'email' => 'verified-trial@example.test',
            'mobile' => '03001234568',
            'modules' => ['clothing'],
            'password' => 'Trialpass1',
            'password_confirmation' => 'Trialpass1',
            'terms' => '1',
        ])->assertRedirect(route('verification.notice'));

        $owner = User::where('email', 'verified-trial@example.test')->firstOrFail();
        $owner->markEmailAsVerified();
        event(new Verified($owner));

        $business = $owner->ownedBusiness()->firstOrFail();
        $this->assertSame(Business::STATUS_ACTIVE, $business->status);
        $this->assertNotNull($business->approved_at);
        $this->assertNull($business->storefront);
        $this->assertDatabaseHas('business_status_histories', [
            'business_id' => $business->id,
            'from_status' => Business::STATUS_PENDING,
            'to_status' => Business::STATUS_ACTIVE,
        ]);
    }

    public function test_trial_cannot_be_repeated_with_same_email_or_mobile_and_private_plan_is_rejected(): void
    {
        Notification::fake();
        $plan = $this->publicPlan();
        $payload = [
            'subscription_plan_id' => $plan->id,
            'owner_name' => 'First Owner',
            'business_name' => 'First Shop',
            'email' => 'first@example.test',
            'mobile' => '03001234569',
            'modules' => ['clothing'],
            'password' => 'Trialpass1',
            'password_confirmation' => 'Trialpass1',
            'terms' => '1',
        ];
        $this->post(route('storefront.business.signup.store'), $payload)->assertRedirect();
        auth()->logout();

        $this->post(route('storefront.business.signup.store'), [
            ...$payload,
            'email' => 'second@example.test',
            'business_name' => 'Second Shop',
        ])->assertSessionHasErrors('mobile');

        $private = SubscriptionPlan::create([
            ...$plan->replicate()->toArray(),
            'name' => 'Private Plan',
            'code' => 'private-plan',
            'is_public' => false,
        ]);
        $this->post(route('storefront.business.signup.store'), [
            ...$payload,
            'subscription_plan_id' => $private->id,
            'email' => 'private@example.test',
            'mobile' => '03001234570',
        ])->assertSessionHasErrors('subscription_plan_id');
    }

    private function publicPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Starter Shop',
            'code' => 'starter-shop',
            'description' => 'A practical starting plan.',
            'marketing_summary' => 'POS, inventory and customer records for a growing shop.',
            'price' => 2500,
            'billing_period_days' => 30,
            'trial_days' => 14,
            'max_employees' => 3,
            'max_business_roles' => 2,
            'max_tailors' => 0,
            'allow_tailoring' => false,
            'allow_clothing' => true,
            'allow_storefront' => true,
            'allow_financial_reports' => false,
            'allow_team_management' => true,
            'allow_activity_log' => false,
            'allowed_permissions' => ['clothing.access', 'clothing.sales', 'clothing.inventory'],
            'is_public' => true,
            'is_recommended' => true,
            'display_order' => 1,
            'lifecycle_status' => SubscriptionPlan::LIFECYCLE_ACTIVE,
            'is_active' => true,
        ]);
    }
}
