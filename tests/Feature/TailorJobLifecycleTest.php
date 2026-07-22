<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Order;
use App\Models\OrderNotificationDelivery;
use App\Models\Tailor;
use App\Models\TailorRecord;
use App\Models\User;
use App\Services\OrderLifecycleNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $owner = User::factory()->create();
        $owner->assignRole($role);
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
