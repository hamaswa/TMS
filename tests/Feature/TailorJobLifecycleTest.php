<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customers;
use App\Models\Order;
use App\Models\OrderNotificationDelivery;
use App\Models\Tailor;
use App\Models\TailorRecord;
use App\Models\User;
use App\Services\OrderLifecycleNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TailorJobLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_owner_can_progress_a_job_and_an_audit_event_is_created(): void
    {
        [$owner, $tailor, $order] = $this->job();

        $this->actingAs($owner)->patch(route('admin.tailor-jobs.status', $order), [
            'status' => 'cutting',
            'note' => 'Pattern prepared',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cutting']);
        $this->assertNotNull($order->fresh()->started_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'assigned',
            'to_status' => 'cutting',
            'changed_by_type' => 'shop_owner',
            'note' => 'Pattern prepared',
        ]);
        $this->assertDatabaseHas('order_notification_deliveries', [
            'order_id' => $order->id,
            'customer_id' => $order->sub_customer,
            'stage' => 'cutting',
            'status' => 'sent',
            'attempt_count' => 1,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => Customers::class,
            'notifiable_id' => $order->sub_customer,
            'type' => 'tailor_job_status',
        ]);
    }

    public function test_weekly_orders_status_control_switches_between_workshop_and_ready(): void
    {
        [$owner, , $order] = $this->job(['status' => 'cutting']);

        $this->actingAs($owner)->post(route('admin.order.status'), [
            'order_id' => $order->id,
            'order_status' => 'complete',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('ready', $order->fresh()->status);

        $this->actingAs($owner)->post(route('admin.order.status'), [
            'order_id' => $order->id,
            'order_status' => 'start',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('cutting', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'ready',
            'to_status' => 'cutting',
            'changed_by_type' => 'shop_owner',
        ]);
    }

    public function test_workshop_page_uses_two_statuses_and_tailor_can_mark_an_order_ready(): void
    {
        [$owner, $tailor, $order] = $this->job(['status' => 'assigned']);

        $this->actingAs($owner)
            ->get(route('admin.tailor-jobs.index', ['status' => 'workshop']))
            ->assertOk()
            ->assertSee('name="order_status"', false)
            ->assertSee('value="start"', false)
            ->assertSee('value="complete"', false);

        $this->app['auth']->guard()->logout();
        $session = [
            'tailor-login-success' => $tailor->name,
            'tailor' => 'tailor',
            'tailor_id' => $tailor->id,
        ];

        $this->withSession($session)->post(route('tailor.order.status'), [
            'order_id' => $order->id,
            'order_status' => 'complete',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('ready', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'assigned',
            'to_status' => 'ready',
            'changed_by_type' => 'tailor',
        ]);

        $this->withSession($session)
            ->from(route('tailor.jobs.index'))
            ->post(route('tailor.order.status'), [
                'order_id' => $order->id,
                'order_status' => 'deliver',
            ])
            ->assertRedirect(route('tailor.jobs.index'))
            ->assertSessionHasErrors('order_status');

        $this->assertSame('ready', $order->fresh()->status);
    }

    public function test_simple_workflow_allows_the_shop_owner_to_deliver_a_ready_order(): void
    {
        [$owner, , $order] = $this->job(['status' => 'ready']);

        $customerOrders = $this->actingAs($owner)
            ->getJson(route('admin.getCustomer', ['id' => $order->customerId]))
            ->assertOk()
            ->json();
        $historyOrder = collect($customerOrders)->firstWhere('orderId', $order->id);

        $this->assertSame('تیار ہے', $historyOrder['button']);
        $this->assertSame([
            ['value' => 'start', 'label' => 'کارخانے میں ہے'],
            ['value' => 'complete', 'label' => 'تیار ہے'],
        ], $historyOrder['nextStatuses']);
        $this->assertTrue($historyOrder['canMarkDelivered']);
        $this->assertFalse($historyOrder['isDelivered']);

        $this->actingAs($owner)->post(route('admin.order.status'), [
            'order_id' => $order->id,
            'order_status' => 'deliver',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->delivered_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'ready',
            'to_status' => 'delivered',
            'changed_by_type' => 'shop_owner',
        ]);

        $deliveredOrders = $this->actingAs($owner)
            ->getJson(route('admin.getCustomer', ['id' => $order->customerId]))
            ->assertOk()
            ->json();
        $deliveredOrder = collect($deliveredOrders)->firstWhere('orderId', $order->id);

        $this->assertSame('تیار ہے', $deliveredOrder['button']);
        $this->assertSame([], $deliveredOrder['nextStatuses']);
        $this->assertSame('order-stage-ready', $deliveredOrder['btnClass']);
        $this->assertFalse($deliveredOrder['canMarkDelivered']);
        $this->assertTrue($deliveredOrder['isDelivered']);
    }

    public function test_tailor_order_history_uses_the_selected_workflow_statuses(): void
    {
        [$owner, $tailor, $order] = $this->job(['status' => 'assigned']);

        $this->actingAs($owner)
            ->get(route('admin.tailor-orders', $tailor))
            ->assertOk()
            ->assertSeeText('کارخانے میں ہے')
            ->assertDontSeeText('درزی مقرر');

        $order->update(['status' => 'delivered']);

        $this->actingAs($owner)
            ->get(route('admin.tailor-orders', $tailor))
            ->assertOk()
            ->assertSeeText('تیار ہے')
            ->assertSeeText('گاہک کے حوالے ہو گیا')
            ->assertSee('to-status is-delivered', false);

        $owner->business->update(['tailoring_status_mode' => Business::TAILORING_STATUS_DETAILED]);

        $this->actingAs($owner)
            ->get(route('admin.tailor-orders', $tailor))
            ->assertOk()
            ->assertSeeText('حوالہ شدہ')
            ->assertDontSeeText('گاہک کے حوالے ہو گیا');
    }

    public function test_shop_can_enable_detailed_statuses_for_workshop_weekly_orders_and_qr(): void
    {
        [$owner, , $order] = $this->job(['status' => 'stitching']);

        $this->actingAs($owner)->put(route('admin.tailoring-workflow.update'), [
            'tailoring_status_mode' => Business::TAILORING_STATUS_DETAILED,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('businesses', [
            'owner_user_id' => $owner->id,
            'tailoring_status_mode' => Business::TAILORING_STATUS_DETAILED,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.tailor-jobs.index', ['status' => 'stitching']))
            ->assertOk()
            ->assertSeeText('سلائی')
            ->assertSee('name="status"', false)
            ->assertDontSee('name="order_status"', false);

        $this->actingAs($owner)
            ->get(route('admin.order.total', ['week' => $order->returnDate]))
            ->assertOk()
            ->assertSeeText('سلائی')
            ->assertSee(route('admin.tailor-jobs.status', $order), false);

        $this->actingAs($owner)
            ->get(route('admin.Customers.index'))
            ->assertOk()
            ->assertSee('name="status"', false)
            ->assertSee('data-action-base="'.url('admin/tailor-jobs').'"', false)
            ->assertDontSee('name="order_status"', false);

        $customerOrders = $this->actingAs($owner)
            ->getJson(route('admin.getCustomer', ['id' => $order->customerId]))
            ->assertOk()
            ->json();
        $historyOrder = collect($customerOrders)->firstWhere('orderId', $order->id);

        $this->assertSame('سلائی', $historyOrder['button']);
        $this->assertSame(Order::nextStatusOptionsFor('stitching'), $historyOrder['nextStatuses']);

        $trackingUrl = URL::signedRoute('orders.track', ['order' => $order->id]);
        $this->get($trackingUrl)
            ->assertOk()
            ->assertSeeText('سلائی')
            ->assertDontSeeText('کارخانے میں ہے');
    }

    public function test_lifecycle_notification_delivery_is_idempotent(): void
    {
        [$owner, , $order] = $this->job(['status' => 'cutting']);
        $service = app(OrderLifecycleNotificationService::class);

        $service->send($order, 'cutting', 'First attempt');
        $service->send($order, 'cutting', 'Duplicate attempt');
        $order->customers->delete();
        $service->send($order, 'cutting', 'Customer later unavailable');

        $this->assertSame(1, OrderNotificationDelivery::where('order_id', $order->id)->count());
        $this->assertSame(1, $order->customers->notifications()->count());
        $this->assertSame(1, OrderNotificationDelivery::first()->attempt_count);
        $this->assertSame('sent', OrderNotificationDelivery::first()->status);
    }

    public function test_missing_customer_is_logged_and_can_be_safely_retried_after_restore(): void
    {
        [$owner, , $order] = $this->job();
        $customer = $order->customers;
        $customer->delete();

        $this->actingAs($owner)->patch(route('admin.tailor-jobs.status', $order), [
            'status' => 'cutting',
        ])->assertRedirect();

        $delivery = OrderNotificationDelivery::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('skipped', $delivery->status);
        $this->assertSame('cutting', $order->fresh()->status);

        $customer->restore();
        $this->actingAs($owner)->post(
            route('admin.tailor-jobs.notifications.retry', [$order, $delivery]),
        )->assertRedirect()->assertSessionHas('success');

        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertSame(1, $customer->notifications()->count());
    }

    public function test_job_cannot_skip_a_required_lifecycle_stage(): void
    {
        [$owner, , $order] = $this->job();

        $this->actingAs($owner)->from(route('admin.tailor-jobs.index'))
            ->patch(route('admin.tailor-jobs.status', $order), ['status' => 'stitching'])
            ->assertRedirect(route('admin.tailor-jobs.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame('assigned', $order->fresh()->status);
    }

    public function test_trial_rework_is_labelled_clearly_without_duplicate_status_options(): void
    {
        $options = Order::nextStatusOptionsFor('trial');

        $this->assertSame(['stitching', 'ready'], array_column($options, 'value'));
        $this->assertSame(count($options), count(array_unique(array_column($options, 'value'))));
        $this->assertSame('سلائی پر واپس (ترمیم / دوبارہ کام)', $options[0]['label']);
        $this->assertSame('تیار', $options[1]['label']);
    }

    public function test_tailor_can_only_update_their_own_jobs_and_cannot_mark_delivery(): void
    {
        [, $tailor, $order] = $this->job(['status' => 'ready']);
        [, $otherTailor, $otherOrder] = $this->job();

        $session = [
            'tailor-login-success' => $tailor->name,
            'tailor' => 'tailor',
            'tailor_id' => $tailor->id,
        ];

        $this->withSession($session)
            ->patch(route('tailor.jobs.status', $otherOrder), ['status' => 'cutting'])
            ->assertNotFound();

        $this->withSession($session)
            ->from(route('tailor.jobs.index'))
            ->patch(route('tailor.jobs.status', $order), ['status' => 'delivered'])
            ->assertRedirect(route('tailor.jobs.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame('ready', $order->fresh()->status);
        $this->assertSame('assigned', $otherOrder->fresh()->status);
    }

    public function test_tailor_can_progress_their_own_job_and_history_allows_a_session_actor(): void
    {
        [, $tailor, $order] = $this->job(['status' => 'assigned']);
        $session = [
            'tailor-login-success' => $tailor->name,
            'tailor' => 'tailor',
            'tailor_id' => $tailor->id,
        ];

        $this->withSession($session)
            ->patch(route('tailor.jobs.status', $order), [
                'status' => 'cutting',
                'note' => 'کپڑا کاٹ دیا گیا ہے',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('cutting', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => null,
            'tailor_id' => $tailor->id,
            'changed_by_type' => 'tailor',
            'from_status' => 'assigned',
            'to_status' => 'cutting',
        ]);
    }

    public function test_tailor_payment_updates_status_and_creates_an_audit_record(): void
    {
        [$owner, $tailor, $order] = $this->job([
            'suitQuantity' => 2,
            'tailor_price' => 500,
        ]);

        $this->actingAs($owner)->patch(route('admin.tailor-jobs.payment', $order), [
            'paid_amount' => 400,
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('partial', $order->tailor_payment_status);
        $this->assertEquals(400, (float) $order->tailor_paid_amount);
        $this->assertDatabaseHas('tailor_records', [
            'tailor_id' => $tailor->id,
            'order_id' => $order->id,
            'amount' => 400,
            'comment' => 'salary',
        ]);

        $this->actingAs($owner)->patch(route('admin.tailor-jobs.payment', $order), [
            'paid_amount' => 1000,
        ])->assertRedirect();

        $this->assertSame('paid', $order->fresh()->tailor_payment_status);
        $this->assertEquals(1000, (float) TailorRecord::where('order_id', $order->id)->sum('amount'));
    }

    public function test_tailor_overpayment_shows_a_local_urdu_error_and_preserves_saved_amount(): void
    {
        [$owner, , $order] = $this->job([
            'suitQuantity' => 2,
            'tailor_price' => 500,
            'tailor_paid_amount' => 400,
        ]);

        $response = $this->actingAs($owner)
            ->from(route('admin.tailor-jobs.index'))
            ->patch(route('admin.tailor-jobs.payment', $order), ['paid_amount' => 1100]);

        $response->assertRedirect(route('admin.tailor-jobs.index'))
            ->assertSessionHasErrors(
                'paid_amount',
                'ادا شدہ رقم درزی کی کل کمائی سے زیادہ نہیں ہو سکتی۔',
                'tailorPayment'.$order->id,
            );
        $this->assertEquals(400, (float) $order->fresh()->tailor_paid_amount);

        $this->actingAs($owner)
            ->get(route('admin.tailor-jobs.index'))
            ->assertOk()
            ->assertSeeText('ادا شدہ رقم درزی کی کل کمائی سے زیادہ نہیں ہو سکتی۔')
            ->assertSeeText('SMS یا واٹس ایپ فراہم کنندہ منسلک نہیں ہے۔')
            ->assertSeeText('اندرونی اطلاع');
    }

    public function test_shop_owner_cannot_access_another_shops_job(): void
    {
        [$owner] = $this->job();
        [, , $otherOrder] = $this->job();

        $this->actingAs($owner)
            ->patch(route('admin.tailor-jobs.status', $otherOrder), ['status' => 'cutting'])
            ->assertNotFound();
    }

    private function job(array $overrides = []): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true, 'is_business_owner' => true]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => $owner->name,
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => false,
            'status' => Business::STATUS_ACTIVE,
        ]);
        $owner->forceFill(['business_id' => $business->id])->save();
        $tailor = Tailor::create([
            'name' => fake()->name(),
            'phone_number1' => fake()->unique()->numerify('03#########'),
            'password' => bcrypt('password'),
            'user_id' => $owner->id,
        ]);
        $customer = Customers::create([
            'name' => fake()->name(),
            'phone_number1' => fake()->unique()->numerify('03#########'),
            'user_id' => $owner->id,
        ]);
        $order = Order::create(array_merge([
            'customerId' => $customer->id,
            'sub_customer' => $customer->id,
            'suitQuantity' => 1,
            'totalPayment' => 2000,
            'tailorId' => $tailor->id,
            'tailor_price' => 500,
            'returnDate' => now()->addDays(3)->toDateString(),
            'userId' => $owner->id,
            'status' => 'assigned',
            'tailor_paid_amount' => 0,
            'tailor_payment_status' => 'unpaid',
        ], $overrides));

        return [$owner, $tailor, $order];
    }
}
