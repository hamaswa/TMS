<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Order;
use App\Models\OrderWorkAssignment;
use App\Models\ProductionWorker;
use App\Models\User;
use App\Models\WorkType;
use App\Models\WorkerCompensationPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderWorkAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_assigns_cutting_at_the_server_side_piece_rate_and_completion_creates_ledger(): void
    {
        [$owner, $order, $worker, $cutting] = $this->scenario();

        $this->actingAs($owner)->post(route('admin.orders.workforce.store', $order), [
            'production_worker_id' => $worker->id,
            'work_type_id' => $cutting->id,
            'quantity' => 2,
            'rate' => 1,
            'notes' => 'دو سوٹ کی کٹنگ',
        ])->assertRedirect()->assertSessionHas('success');

        $assignment = OrderWorkAssignment::sole();
        $this->assertEquals(50, (float) $assignment->rate);
        $this->assertEquals(100, (float) $assignment->amount);

        $this->actingAs($owner)->patch(route('admin.orders.workforce.status', [$order, $assignment]), [
            'status' => 'completed',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('worker_ledger_entries', [
            'assignment_id' => $assignment->id, 'entry_type' => 'earning', 'amount' => 100,
        ]);

        $this->actingAs($owner)->patch(route('admin.orders.workforce.status', [$order, $assignment]), [
            'status' => 'completed',
        ])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('worker_ledger_entries', 1);
    }

    public function test_assignment_requires_the_workers_skill_and_active_piece_rate(): void
    {
        [$owner, $order, $worker] = $this->scenario();
        $ironing = WorkType::create([
            'user_id' => $owner->id, 'code' => 'ironing', 'name' => 'استری', 'active' => true,
        ]);

        $this->actingAs($owner)->post(route('admin.orders.workforce.store', $order), [
            'production_worker_id' => $worker->id,
            'work_type_id' => $ironing->id,
            'quantity' => 2,
        ])->assertSessionHasErrors('work_type_id');
        $this->assertDatabaseCount('order_work_assignments', 0);
    }

    public function test_delivered_order_cannot_receive_new_work(): void
    {
        [$owner, $order, $worker, $cutting] = $this->scenario();
        $order->update(['status' => 'delivered']);

        $this->actingAs($owner)->post(route('admin.orders.workforce.store', $order), [
            'production_worker_id' => $worker->id,
            'work_type_id' => $cutting->id,
            'quantity' => 2,
        ])->assertSessionHasErrors('order');
        $this->assertDatabaseCount('order_work_assignments', 0);
    }

    public function test_another_business_cannot_assign_its_worker_to_the_order(): void
    {
        [$owner, $order] = $this->scenario();
        [, , $otherWorker, $otherCutting] = $this->scenario();

        $this->actingAs($owner)->post(route('admin.orders.workforce.store', $order), [
            'production_worker_id' => $otherWorker->id,
            'work_type_id' => $otherCutting->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('production_worker_id');
        $this->assertDatabaseCount('order_work_assignments', 0);
    }

    private function scenario(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $customer = Customers::create([
            'name' => 'فیصل محمود', 'phone_number1' => fake()->unique()->numerify('03#########'), 'user_id' => $owner->id,
        ]);
        $order = Order::create([
            'customerId' => $customer->id, 'sub_customer' => $customer->id,
            'suitQuantity' => 2, 'totalPayment' => 4000,
            'returnDate' => now()->addWeek()->toDateString(), 'userId' => $owner->id, 'status' => 'assigned',
        ]);
        $cutting = WorkType::create([
            'user_id' => $owner->id, 'code' => 'cutting', 'name' => 'کٹائی', 'active' => true,
        ]);
        $worker = ProductionWorker::create([
            'user_id' => $owner->id, 'name' => 'اکرم کٹنگ ماسٹر', 'relationship_type' => 'contractor',
        ]);
        $worker->skills()->attach($cutting);
        WorkerCompensationPlan::create([
            'user_id' => $owner->id, 'production_worker_id' => $worker->id,
            'work_type_id' => $cutting->id, 'method' => 'per_piece', 'rate' => 50, 'active' => true,
        ]);

        return [$owner, $order, $worker, $cutting];
    }
}
