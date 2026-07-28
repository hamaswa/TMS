<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionNotificationDelivery;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_subscription_and_record_audited_payment(): void
    {
        [$admin, $owner, $business] = $this->accounts();

        $this->actingAs($admin)->post(route('administrator.subscriptions.store', $owner), [
            'plan_name' => 'Annual Combined',
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-07-31',
            'fee' => 24000,
            'notes' => 'Annual renewal',
        ])->assertRedirect(route('administrator.clients.show', $owner));

        $subscription = BusinessSubscription::where('business_id', $business->id)->firstOrFail();
        $this->actingAs($admin)->post(route('administrator.subscription-payments.store', [$owner, $subscription]), [
            'paid_on' => '2026-08-01',
            'amount' => 10000,
            'payment_method' => 'easypaisa',
            'reference' => 'EP-2026-001',
        ])->assertRedirect(route('administrator.clients.show', $owner));

        $this->assertDatabaseHas('subscription_payments', [
            'business_id' => $business->id,
            'business_subscription_id' => $subscription->id,
            'amount' => 10000,
            'reference' => 'EP-2026-001',
            'recorded_by_user_id' => $admin->id,
        ]);
        $this->actingAs($admin)->get(route('administrator.subscriptions.index'))
            ->assertOk()
            ->assertSeeText('Subscriptions and payments')
            ->assertSeeText('Annual Combined');
        $this->actingAs($owner)->get(route('admin.subscription.index'))
            ->assertOk()
            ->assertSeeText('سبسکرپشن اور ادائیگی')
            ->assertSeeText('ایزی پیسہ')
            ->assertDontSeeText('EasyPaisa')
            ->assertSeeText('24,000.00')
            ->assertSeeText('14,000.00');
    }

    public function test_subscription_payment_cannot_exceed_balance_and_reversal_preserves_record(): void
    {
        [$admin, $owner, $business] = $this->accounts();
        $subscription = $this->subscription($business, 1000, now()->addMonth());

        $response = $this->actingAs($admin)
            ->from(route('administrator.clients.show', $owner))
            ->post(route('administrator.subscription-payments.store', [$owner, $subscription]), [
                'paid_on' => now()->toDateString(),
                'amount' => 1001,
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('administrator.clients.show', $owner))
            ->assertSessionHasErrors(
                ['amount'],
                null,
                'subscription_payment_'.$subscription->id
            );
        $this->assertDatabaseCount('subscription_payments', 0);

        $this->actingAs($admin)
            ->get(route('administrator.clients.show', $owner))
            ->assertOk()
            ->assertSeeText('Payment was not recorded.')
            ->assertSeeText('Payment cannot exceed the outstanding subscription balance of Rs 1,000.00.')
            ->assertSeeText('Maximum outstanding balance: Rs 1,000.00')
            ->assertDontSee('max="1000"', false);

        $this->actingAs($admin)->post(route('administrator.subscription-payments.store', [$owner, $subscription]), [
            'paid_on' => now()->toDateString(),
            'amount' => 600,
            'payment_method' => 'cash',
        ])->assertRedirect();
        $payment = SubscriptionPayment::firstOrFail();

        $this->actingAs($admin)->patch(route('administrator.subscription-payments.reverse', [$owner, $payment]), [
            'reversal_reason' => 'Duplicate cash entry',
        ])->assertRedirect();

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'amount' => 600,
            'reversal_reason' => 'Duplicate cash entry',
            'reversed_by_user_id' => $admin->id,
        ]);
        $this->assertNotNull($payment->fresh()->reversed_at);
        $this->assertSame(1000.0, $subscription->fresh()->balanceDue());
    }

    public function test_expiring_and_expired_clients_see_urdu_warning_without_automatic_suspension(): void
    {
        [, $owner, $business] = $this->accounts();
        $subscription = $this->subscription($business, 1200, now()->addDays(5));

        $this->actingAs($owner)->get(route('admin.home'))
            ->assertOk()
            ->assertSeeText('آپ کی سبسکرپشن جلد ختم ہونے والی ہے')
            ->assertSee(route('admin.subscription.index'), false);

        $subscription->update(['ends_on' => now()->subDay()->toDateString()]);
        $this->actingAs($owner)->get(route('admin.home'))
            ->assertRedirect(route('admin.subscription.required'));
        $this->actingAs($owner)->get(route('admin.subscription.required'))
            ->assertOk()
            ->assertSeeText('کاروباری سبسکرپشن فعال نہیں ہے');
        $this->assertSame(Business::STATUS_ACTIVE, $business->fresh()->status);
    }

    public function test_expiry_command_sends_each_threshold_notification_once(): void
    {
        Carbon::setTestNow('2026-08-01 09:00:00');
        [, $owner, $business] = $this->accounts();
        $subscription = $this->subscription($business, 1200, now()->addDays(10));

        $this->artisan('subscriptions:send-expiry-notices')->assertSuccessful();
        $this->artisan('subscriptions:send-expiry-notices')->assertSuccessful();
        $this->assertCount(1, $owner->fresh()->notifications);
        $this->assertDatabaseHas('subscription_notification_deliveries', [
            'business_subscription_id' => $subscription->id,
            'user_id' => $owner->id,
            'threshold_days' => 14,
        ]);
        $this->actingAs($owner)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSeeText('سبسکرپشن جلد ختم ہونے والی ہے')
            ->assertSeeText('تفصیل دیکھیں');
        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());

        Carbon::setTestNow('2026-08-05 09:00:00');
        $this->artisan('subscriptions:send-expiry-notices')->assertSuccessful();
        $this->assertCount(2, $owner->fresh()->notifications);
        $this->assertSame(2, SubscriptionNotificationDelivery::count());

        Carbon::setTestNow();
    }

    public function test_clients_cannot_access_super_admin_subscription_management(): void
    {
        [, $owner] = $this->accounts();

        $this->actingAs($owner)->get(route('administrator.subscriptions.index'))->assertForbidden();
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
            'name' => 'Subscription Test Business',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        return [$admin, $owner->fresh(), $business];
    }

    private function subscription(Business $business, float $fee, Carbon $endsOn): BusinessSubscription
    {
        return $business->subscriptions()->create([
            'plan_name' => 'Monthly TMS',
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => $endsOn->toDateString(),
            'fee' => $fee,
        ]);
    }
}
