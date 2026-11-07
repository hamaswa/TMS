<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyUiLocale;
use App\Models\Business;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopPlanRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_plans_page_has_monthly_and_yearly_tabs_with_savings(): void
    {
        [, $owner] = $this->accounts();

        $this->actingAs($owner)->get(route('admin.subscription.index'))
            ->assertOk()
            ->assertSee('data-period="30"', false)
            ->assertSee('data-period="365"', false)
            ->assertSee('data-period-panel="365"', false)
            ->assertSee('ماہانہ')
            ->assertSee('سالانہ')
            ->assertSee('<strong>24,000</strong>', false)
            ->assertSee('روپے 6,000 کی بچت')
            ->assertSee('مقبول');

        $this->actingAs($owner)->withSession([ApplyUiLocale::SESSION_KEY => 'en'])->get(route('admin.subscription.index'))
            ->assertOk()
            ->assertSee('Monthly')
            ->assertSee('Yearly')
            ->assertSee('Popular')
            ->assertSee('Choose billing period');
    }

    public function test_without_easypaisa_select_confirms_then_sends_a_plain_request(): void
    {
        [, $owner, $business] = $this->accounts();
        $plan = SubscriptionPlan::where('code', 'business-pro-monthly')->firstOrFail();

        $this->actingAs($owner)->get(route('admin.subscription.index'))
            ->assertSee('id="pay-modal"', false)
            ->assertSee('data-pay-online="0"', false)
            ->assertSee('پلان کی درخواست بھیجیں')
            ->assertDontSee('id="payment_reference"', false);
        $this->actingAs($owner)->post(route('admin.subscription.request', $plan))
            ->assertRedirect(route('admin.subscription.index').'#plans')
            ->assertSessionHasNoErrors();

        $request = $business->pendingPlanRequest()->firstOrFail();
        $this->assertSame($plan->id, $request->subscription_plan_id);
        $this->assertNull($request->payment_reference);
    }

    public function test_shop_pays_with_easypaisa_and_super_admin_approves(): void
    {
        [$admin, $owner, $business] = $this->accounts();
        $this->setUpEasypaisa();
        $plan = SubscriptionPlan::where('code', 'business-pro-annual')->firstOrFail();

        $this->actingAs($owner)->get(route('admin.subscription.index'))
            ->assertOk()
            ->assertSee('pay-modal', false)
            ->assertSee('0300-1234567')
            ->assertSee('BuyNStitch Payments')
            ->assertSee(route('platform.easypaisa-qr'), false)
            ->assertSee('data-pay-plan="'.$plan->id.'"', false)
            ->assertSee('data-pay-online="1"', false);
        $this->actingAs($owner)->get(route('platform.easypaisa-qr'))->assertOk();

        $this->actingAs($owner)->from(route('admin.home'))
            ->followingRedirects()
            ->post(route('admin.subscription.request', $plan), ['plan_id' => $plan->id])
            ->assertOk()
            ->assertSee('ادائیگی کی ٹرانزیکشن آئی ڈی درج کریں۔')
            ->assertSee('openPay($(\'[data-pay-plan="'.$plan->id.'"]\').first());', false)
            ->assertSee('action="'.route('admin.subscription.request', $plan).'"', false);
        $this->assertSame(0, $business->planRequests()->count());

        $this->actingAs($owner)->post(route('admin.subscription.request', $plan), [
            'plan_id' => $plan->id,
            'payment_reference' => ' 12345678901 ',
            'payment_proof' => UploadedFile::fake()->image('receipt.png', 400, 800),
        ])->assertSessionHasNoErrors()->assertSessionHas('plan_requested');

        $request = SubscriptionPlanRequest::where('business_id', $business->id)->firstOrFail();
        $this->assertSame(SubscriptionPlanRequest::STATUS_PENDING, $request->status);
        $this->assertSame('12345678901', $request->payment_reference);
        $this->assertSame('24000.00', $request->amount);
        Storage::disk('local')->assertExists($request->proof_path);

        $this->actingAs($owner)->get(route('admin.subscription.index'))
            ->assertSee('ادائیگی زیر جائزہ')
            ->assertSee('12345678901');

        $this->actingAs($admin)->get(route('administrator.subscriptions.index', ['state' => 'requested']))
            ->assertOk()
            ->assertSee('Payment to check: Business Pro (Annual) · Rs 24,000');
        $this->actingAs($admin)->get(route('administrator.clients.show', $owner))
            ->assertOk()
            ->assertSee('Payment request waiting for you')
            ->assertSee('12345678901')
            ->assertSee(route('administrator.plan-requests.proof', $request), false);
        $this->actingAs($admin)->get(route('administrator.plan-requests.proof', $request))->assertOk();
        $this->actingAs($owner)->get(route('administrator.plan-requests.proof', $request))->assertForbidden();

        $this->actingAs($admin)->post(route('administrator.plan-requests.approve', $request))
            ->assertRedirect(route('administrator.clients.show', $owner->id));

        $request->refresh();
        $this->assertSame(SubscriptionPlanRequest::STATUS_APPROVED, $request->status);
        $subscription = $business->fresh()->currentSubscription();
        $this->assertSame($request->business_subscription_id, $subscription->id);
        $this->assertSame($plan->id, $subscription->subscription_plan_id);
        $this->assertSame(2500, $subscription->max_customers);
        $this->assertSame(now()->addDays(364)->toDateString(), $subscription->ends_on->toDateString());
        $this->assertSame(0.0, $subscription->balanceDue());
        $this->assertDatabaseHas('subscription_payments', [
            'business_subscription_id' => $subscription->id,
            'payment_method' => 'easypaisa',
            'reference' => '12345678901',
            'amount' => 24000,
        ]);

        $this->actingAs($admin)->post(route('administrator.plan-requests.approve', $request))->assertStatus(422);

        // The same transaction ID cannot pay for a second plan.
        $this->actingAs($owner)->post(route('admin.subscription.request', $plan), [
            'plan_id' => $plan->id,
            'payment_reference' => '12345678901',
        ])->assertSessionHasErrors('payment_reference');
    }

    public function test_rejected_payment_shows_reason_and_shop_can_try_again(): void
    {
        [$admin, $owner, $business] = $this->accounts();
        $this->setUpEasypaisa();
        $plan = SubscriptionPlan::where('code', 'starter-tailoring-monthly')->firstOrFail();

        $this->actingAs($owner)->post(route('admin.subscription.request', $plan), ['payment_reference' => 'ABC123456'])
            ->assertSessionHasNoErrors();
        $request = $business->pendingPlanRequest()->firstOrFail();

        $this->actingAs($admin)->post(route('administrator.plan-requests.reject', $request), [])
            ->assertSessionHasErrors('reason', null, 'plan_request_'.$request->id);
        $this->actingAs($admin)->post(route('administrator.plan-requests.reject', $request), ['reason' => 'TID not found'])
            ->assertRedirect(route('administrator.clients.show', $owner->id));

        $this->assertSame(SubscriptionPlanRequest::STATUS_REJECTED, $request->fresh()->status);
        $this->assertFalse($business->fresh()->subscriptionIsManaged());
        $this->actingAs($owner)->get(route('admin.subscription.index'))
            ->assertSee('آپ کی ادائیگی کی تصدیق نہیں ہو سکی۔')
            ->assertSee('TID not found');

        $this->actingAs($owner)->post(route('admin.subscription.request', $plan), ['payment_reference' => 'ABC123456'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $business->planRequests()->pending()->count());
    }

    public function test_new_choice_replaces_open_request_and_manual_subscription_closes_it(): void
    {
        [$admin, $owner, $business] = $this->accounts();
        $this->setUpEasypaisa();
        $starter = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $pro = SubscriptionPlan::where('code', 'business-pro-monthly')->firstOrFail();

        $this->actingAs($owner)->post(route('admin.subscription.request', $starter), ['payment_reference' => '11111111111']);
        $this->actingAs($owner)->post(route('admin.subscription.request', $pro), ['payment_reference' => '22222222222']);
        $this->assertSame(1, $business->planRequests()->pending()->count());
        $this->assertSame($pro->id, $business->pendingPlanRequest()->first()->subscription_plan_id);
        $this->assertSame(1, $business->planRequests()->where('status', SubscriptionPlanRequest::STATUS_CLOSED)->count());

        $this->actingAs($admin)->post(route('administrator.subscriptions.store', $owner), [
            'subscription_plan_id' => $pro->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays(29)->toDateString(),
            'fee' => 2500,
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, $business->planRequests()->pending()->count());
    }

    public function test_raast_style_transaction_ids_are_accepted(): void
    {
        [, $owner, $business] = $this->accounts();
        $this->setUpEasypaisa();
        $plan = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();

        foreach (['RAAST 4537', 'RAAST--45372891', '-RAAST45372891', 'TID#123456'] as $bad) {
            $this->actingAs($owner)->post(route('admin.subscription.request', $plan), ['payment_reference' => $bad])
                ->assertSessionHasErrors('payment_reference');
        }

        $this->actingAs($owner)->post(route('admin.subscription.request', $plan), ['payment_reference' => 'raast-45372891'])
            ->assertSessionHasNoErrors();
        $this->assertSame('RAAST-45372891', $business->pendingPlanRequest()->firstOrFail()->payment_reference);
    }

    public function test_approved_upgrade_starts_today_and_replaces_the_running_plan(): void
    {
        [$admin, $owner, $business] = $this->accounts();
        $this->setUpEasypaisa();
        $starter = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $pro = SubscriptionPlan::where('code', 'business-pro-monthly')->firstOrFail();
        $this->actingAs($admin)->post(route('administrator.subscriptions.store', $owner), [
            'subscription_plan_id' => $starter->id,
            'starts_on' => now()->subDays(5)->toDateString(),
            'ends_on' => now()->addDays(24)->toDateString(),
            'fee' => $starter->price,
        ])->assertSessionHasNoErrors();
        $old = $business->subscriptions()->firstOrFail();

        $this->actingAs($owner)->post(route('admin.subscription.request', $pro), [
            'payment_reference' => 'RAAST-45372891',
            'upgrade_reason' => 'Need the online shop.',
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('administrator.plan-requests.approve', $business->pendingPlanRequest()->firstOrFail()))
            ->assertSessionHasNoErrors();

        $current = $business->fresh()->currentSubscription();
        $this->assertSame($pro->id, $current->subscription_plan_id);
        $this->assertSame(now()->toDateString(), $current->starts_on->toDateString());
        $this->assertSame(now()->subDay()->toDateString(), $old->fresh()->ends_on->toDateString());
        $this->assertTrue($owner->fresh()->hasModule(User::MODULE_TAILORING));
        $this->actingAs($owner)->get(route('admin.subscription.index'))->assertOk()->assertSee('موجودہ پلان: Business Pro');

        // The super admin history marks the replaced period and the upgrade clearly.
        $this->actingAs($admin)->get(route('administrator.clients.show', $owner))
            ->assertOk()
            ->assertSee('Upgraded away')
            ->assertSee('Replaced by upgrade to Business Pro')
            ->assertSee('Upgrade reason:')
            ->assertSee('Need the online shop.');
    }

    public function test_queued_next_plan_does_not_hide_the_plan_running_today(): void
    {
        [$admin, $owner, $business] = $this->accounts();
        $starter = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $pro = SubscriptionPlan::where('code', 'business-pro-monthly')->firstOrFail();
        $business->subscriptions()->create([
            'subscription_plan_id' => $starter->id, 'plan_name' => $starter->name, 'fee' => 1200,
            'starts_on' => now()->toDateString(), 'ends_on' => now()->addDays(29)->toDateString(),
            ...$starter->entitlementSnapshot(),
        ]);
        $business->subscriptions()->create([
            'subscription_plan_id' => $pro->id, 'plan_name' => $pro->name, 'fee' => 2500,
            'starts_on' => now()->addDays(30)->toDateString(), 'ends_on' => now()->addDays(59)->toDateString(),
            ...$pro->entitlementSnapshot(),
        ]);

        $this->assertSame($starter->id, $business->fresh()->currentSubscription()?->subscription_plan_id);
        $this->assertTrue($owner->fresh()->hasModule(User::MODULE_CLOTHING));
    }

    public function test_super_admin_is_alerted_once_about_each_new_plan_request(): void
    {
        [$admin, $owner] = $this->accounts();
        $plan = SubscriptionPlan::where('code', 'business-pro-monthly')->firstOrFail();

        $this->actingAs($admin)->get(route('administrator.subscriptions.index'))
            ->assertOk()
            ->assertSee('id="plan-request-alert"', false)
            ->assertDontSee('"shop":"Plan Request Shop"', false);

        $this->actingAs($owner)->post(route('admin.subscription.request', $plan))->assertSessionHasNoErrors();

        // Shown on the next page load (e.g. right after login)…
        $this->actingAs($admin)->get(route('administrator.subscriptions.index'))
            ->assertOk()
            ->assertSee('"shop":"Plan Request Shop"', false);
        // …and not again after that.
        $this->actingAs($admin->fresh())->getJson(route('administrator.plan-requests.unannounced'))
            ->assertOk()->assertExactJson(['requests' => []]);

        // A request sent while the admin is on a page arrives through the live check.
        $this->actingAs($owner)->post(route('admin.subscription.request', SubscriptionPlan::where('code', 'elite-boutique-monthly')->firstOrFail()))
            ->assertSessionHasNoErrors();
        $this->actingAs($admin->fresh())->getJson(route('administrator.plan-requests.unannounced'))
            ->assertOk()
            ->assertJsonPath('requests.0.shop', 'Plan Request Shop')
            ->assertJsonPath('requests.0.plan', 'Elite Boutique');

        // Shop owners never get the admin alert.
        $this->actingAs($owner)->get(route('admin.subscription.index'))->assertDontSee('id="plan-request-alert"', false);
        $this->actingAs($owner)->getJson(route('administrator.plan-requests.unannounced'))->assertForbidden();
    }

    public function test_upgrading_to_a_pricier_plan_needs_a_reason_that_super_admin_sees(): void
    {
        [$admin, $owner, $business] = $this->accounts();
        $starter = SubscriptionPlan::where('code', 'starter-clothing-monthly')->firstOrFail();
        $pro = SubscriptionPlan::where('code', 'business-pro-monthly')->firstOrFail();
        $starterTailoring = SubscriptionPlan::where('code', 'starter-tailoring-monthly')->firstOrFail();
        $this->actingAs($admin)->post(route('administrator.subscriptions.store', $owner), [
            'subscription_plan_id' => $starter->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays(29)->toDateString(),
            'fee' => $starter->price,
        ])->assertSessionHasNoErrors();

        // Pricier plans say "upgrade"; a same-price plan is a normal choice.
        $page = $this->actingAs($owner)->get(route('admin.subscription.index'))->assertOk()->assertSee('اپ گریڈ کریں');
        $this->assertStringContainsString('data-pay-upgrade="1"', $page->getContent());
        $this->assertFalse($starterTailoring->isUpgradeFrom($starter));
        $this->assertTrue($pro->isUpgradeFrom($starter));

        $this->actingAs($owner)->post(route('admin.subscription.request', $pro))
            ->assertSessionHasErrors('upgrade_reason');
        $this->assertSame(0, $business->planRequests()->pending()->count());

        $this->actingAs($owner)->post(route('admin.subscription.request', $pro), [
            'upgrade_reason' => 'We need the online shop and more staff.',
        ])->assertSessionHasNoErrors();
        $request = $business->pendingPlanRequest()->firstOrFail();
        $this->assertTrue($request->is_upgrade);
        $this->assertSame('We need the online shop and more staff.', $request->upgrade_reason);

        $this->actingAs($admin)->get(route('administrator.clients.show', $owner))
            ->assertOk()
            ->assertSee('Upgrade reason')
            ->assertSee('We need the online shop and more staff.');

        // A same-price switch goes through without a reason.
        $this->actingAs($owner)->post(route('admin.subscription.request', $starterTailoring))->assertSessionHasNoErrors();
        $this->assertFalse($business->pendingPlanRequest()->firstOrFail()->is_upgrade);
    }

    public function test_archived_plan_cannot_be_requested(): void
    {
        [, $owner, $business] = $this->accounts();
        $plan = SubscriptionPlan::where('code', 'elite-boutique-monthly')->firstOrFail();
        $plan->update(['lifecycle_status' => SubscriptionPlan::LIFECYCLE_ARCHIVED, 'is_active' => false]);

        $this->actingAs($owner)->post(route('admin.subscription.request', $plan))->assertNotFound();
        $this->assertSame(0, $business->planRequests()->count());
    }

    public function test_super_admin_saves_easypaisa_details(): void
    {
        [$admin, $owner] = $this->accounts();

        $this->actingAs($owner)->get(route('administrator.payment-settings.edit'))->assertForbidden();
        $this->actingAs($admin)->get(route('administrator.payment-settings.edit'))->assertOk()->assertSee('Easypaisa');

        $this->actingAs($admin)->put(route('administrator.payment-settings.update'), [
            'account_name' => 'BuyNStitch Payments',
            'number' => '12345',
        ])->assertSessionHasErrors('number');

        $this->actingAs($admin)->put(route('administrator.payment-settings.update'), [
            'account_name' => 'BuyNStitch Payments',
            'number' => '03001234567',
            'qr' => UploadedFile::fake()->image('qr.png', 300, 300),
        ])->assertSessionHasNoErrors();

        $details = PlatformSetting::easypaisa();
        $this->assertSame('0300-1234567', $details['number']);
        Storage::disk('local')->assertExists($details['qr_path']);

        $this->actingAs($admin)->put(route('administrator.payment-settings.update'), [
            'account_name' => 'BuyNStitch Payments',
            'number' => '0300-1234567',
            'remove_qr' => 1,
        ])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($details['qr_path']);
        $this->assertNull(PlatformSetting::easypaisa()['qr_path']);
    }

    private function setUpEasypaisa(): void
    {
        PlatformSetting::put(PlatformSetting::EASYPAISA_ACCOUNT_NAME, 'BuyNStitch Payments');
        PlatformSetting::put(PlatformSetting::EASYPAISA_NUMBER, '0300-1234567');
        PlatformSetting::put(
            PlatformSetting::EASYPAISA_QR_PATH,
            UploadedFile::fake()->image('qr.png', 200, 200)->store('platform/easypaisa', 'local')
        );
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
            'name' => 'Plan Request Shop',
            'shop_code' => 'REQ-0001',
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
