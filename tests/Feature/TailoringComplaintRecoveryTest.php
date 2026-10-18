<?php

namespace Tests\Feature;

use App\Models\CounterOrderComplaint;
use App\Models\Customers;
use App\Models\Order;
use App\Models\Tailor;
use App\Models\TailoringRecovery;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TailoringComplaintRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_can_be_inspected_assigned_for_rework_and_closed_with_immutable_evidence(): void
    {
        [$owner, $tailor, $order] = $this->order();
        $claim = $this->claim($owner, $order);
        Storage::fake('public');

        $this->actingAs($owner)->post(route('admin.tailoring-complaints.evidence', $claim), [
            'evidence' => UploadedFile::fake()->image('damage.jpg'),
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('admin.tailoring-complaints.decision', $claim), [
            'category' => 'stitching_defect', 'inspection_notes' => 'Side seam is visibly uneven and must be opened.',
            'responsible_party' => 'tailor', 'responsible_tailor_id' => $tailor->id,
            'resolution' => 'rework', 'decision_notes' => 'Restitch and inspect before customer trial.',
            'rework_due_on' => now()->addDays(2)->toDateString(),
        ])->assertRedirect();

        $this->assertSame('rework', $claim->fresh()->status);
        $this->assertSame('stitching', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => 'stitching']);
        $acknowledgeUrl = $order->customers->notifications()->latest()->firstOrFail()->data['acknowledge_url'];
        $this->get($acknowledgeUrl)->assertOk()->assertSeeText('تصدیق محفوظ ہو گئی');
        $this->assertNotNull($claim->fresh()->customer_acknowledged_at);

        $this->actingAs($owner)->post(route('admin.tailoring-complaints.complete-rework', $claim), [
            'completion_note' => 'Restitched and quality checked by owner.',
        ])->assertRedirect();
        $this->assertNotNull($claim->fresh()->closed_at);

        $this->actingAs($owner)->post(route('admin.tailoring-complaints.evidence', $claim), [
            'evidence' => UploadedFile::fake()->image('late.jpg'),
        ])->assertStatus(409);
    }

    public function test_security_deposit_recovery_is_tenant_scoped_and_audited(): void
    {
        [$owner, $tailor, $order] = $this->order();
        $tailor->update(['security_deposit' => 1000]);
        $claim = $this->claim($owner, $order, ['status' => 'approved', 'responsible_tailor_id' => $tailor->id, 'requested_tailoring_refund' => 500]);

        $this->actingAs($owner)->post(route('admin.tailoring-complaints.recovery', $claim), [
            'method' => 'security_deposit', 'tailor_id' => $tailor->id, 'amount' => 350, 'note' => 'Approved damage recovery',
        ])->assertRedirect();

        $this->assertEquals(650, (float) $tailor->fresh()->security_deposit);
        $this->assertDatabaseHas('tailor_security_deposit_transactions', ['tailor_id' => $tailor->id, 'transaction_type' => 'deducted', 'amount' => 350]);
        $this->assertDatabaseHas('tailoring_recoveries', ['complaint_id' => $claim->id, 'method' => 'security_deposit', 'status' => 'recovered']);

        [$other] = $this->order();
        $this->actingAs($other)->post(route('admin.tailoring-complaints.recovery', $claim), [
            'method' => 'shop_absorbed', 'amount' => 1,
        ])->assertNotFound();
    }

    public function test_unpaid_earning_recovery_cannot_exceed_unpaid_tailor_amount(): void
    {
        [$owner, $tailor, $order] = $this->order(['tailor_price' => 500, 'suitQuantity' => 2, 'tailor_paid_amount' => 400]);
        $claim = $this->claim($owner, $order, ['status' => 'approved', 'responsible_tailor_id' => $tailor->id, 'requested_tailoring_refund' => 600]);

        $this->actingAs($owner)->post(route('admin.tailoring-complaints.recovery', $claim), [
            'method' => 'unpaid_earnings', 'tailor_id' => $tailor->id, 'amount' => 601,
        ])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('tailoring_recoveries', 0);

        $this->actingAs($owner)->post(route('admin.tailoring-complaints.recovery', $claim), [
            'method' => 'unpaid_earnings', 'tailor_id' => $tailor->id, 'amount' => 600,
        ])->assertRedirect();
        $this->assertEquals(-600, (float) \App\Models\TailorRecord::where('order_id', $order->id)->sum('amount'));
    }

    public function test_cumulative_recovery_cannot_exceed_approved_customer_loss(): void
    {
        [$owner, $tailor, $order] = $this->order();
        $claim = $this->claim($owner, $order, ['status' => 'settled', 'approved_tailoring_refund' => 150, 'approved_cloth_compensation' => 50]);
        $this->actingAs($owner)->post(route('admin.tailoring-complaints.recovery', $claim), [
            'method' => 'cash_recovered', 'tailor_id' => $tailor->id, 'amount' => 125,
        ])->assertRedirect();
        $this->actingAs($owner)->post(route('admin.tailoring-complaints.recovery', $claim), [
            'method' => 'future_pay', 'tailor_id' => $tailor->id, 'amount' => 76,
        ])->assertSessionHasErrors('amount');
        $this->assertEquals(125, (float) TailoringRecovery::where('complaint_id', $claim->id)->sum('amount'));
    }

    public function test_cancellation_preserves_order_and_posts_refund_audit(): void
    {
        [$owner, , $order] = $this->order(['status' => 'assigned', 'totalPayment' => 2000]);
        Transaction::create(['userId' => $owner->id, 'customerId' => $order->customerId, 'Order_type' => 'Tailor',
            'orderId' => $order->id, 'remainingBalance' => 1500, 'recivedPayment' => 500]);
        Transaction::create(['userId' => $owner->id, 'customerId' => $order->customerId, 'Order_type' => 'Tailor',
            'remainingBalance' => 1000, 'recivedPayment' => 0]);
        $this->actingAs($owner)->post(route('admin.orders.cancel', $order), [
            'reason' => 'Customer cancelled before cutting after an agreed full review.',
            'cancellation_charge' => 100, 'compensation' => 0,
            'applied_to_other_balance' => 400, 'cash_refund' => 0, 'retained_credit' => 0,
        ])->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'cancellation_reason' => 'Customer cancelled before cutting after an agreed full review.']);
        $this->assertDatabaseHas('transactions', ['orderId' => $order->id, 'Order_type' => 'Tailor Complaint Settlement', 'recivedPayment' => 0, 'remainingBalance' => -1500]);
        $this->assertDatabaseHas('transactions', ['orderId' => null, 'Order_type' => 'Tailor Complaint Settlement', 'recivedPayment' => 0, 'remainingBalance' => -400]);
        $this->assertDatabaseHas('order_cancellation_settlements', ['source_type' => 'tailoring_order', 'source_id' => $order->id,
            'paid_on_order' => 500, 'cancellation_charge' => 100, 'applied_to_other_balance' => 400, 'customer_balance_after' => 600]);
        $this->assertDatabaseHas('counter_order_complaints', ['order_id' => $order->id, 'resolution' => 'cancelled', 'status' => 'settled']);

        $this->actingAs($owner)->post(route('admin.orders.cancel', $order), [
            'reason' => 'Attempting to cancel the same order for a second time.', 'cancellation_charge' => 0,
            'compensation' => 0, 'applied_to_other_balance' => 0, 'cash_refund' => 0, 'retained_credit' => 0,
        ])->assertSessionHasErrors('order');
        $this->assertSame(1, Transaction::where('orderId', $order->id)->where('Order_type', 'Tailor Complaint Settlement')->count());
    }

    public function test_zero_paid_cancellation_only_removes_that_orders_unpaid_balance(): void
    {
        [$owner, , $order] = $this->order(['status' => 'assigned', 'totalPayment' => 2000]);
        Transaction::create(['userId' => $owner->id, 'customerId' => $order->customerId, 'Order_type' => 'Tailor',
            'orderId' => $order->id, 'remainingBalance' => 2000, 'recivedPayment' => 0]);
        Transaction::create(['userId' => $owner->id, 'customerId' => $order->customerId, 'Order_type' => 'Tailor',
            'remainingBalance' => 750, 'recivedPayment' => 0]);

        $this->actingAs($owner)->post(route('admin.orders.cancel', $order), [
            'reason' => 'Customer cancelled this unpaid order before any work started.',
            'cancellation_charge' => 0, 'compensation' => 0, 'applied_to_other_balance' => 0,
            'cash_refund' => 0, 'retained_credit' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertEquals(750, (float) Transaction::where('userId', $owner->id)
            ->where('customerId', $order->customerId)->sum('remainingBalance'));
        $this->assertDatabaseHas('order_cancellation_settlements', [
            'source_type' => 'tailoring_order', 'source_id' => $order->id,
            'paid_on_order' => 0, 'cancelled_unpaid_balance' => 2000,
            'cash_refund' => 0, 'customer_balance_after' => 750,
        ]);
    }

    private function order(array $overrides = []): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $customer = Customers::create(['name' => 'Recovery Customer', 'phone_number1' => '03001234567', 'user_id' => $owner->id]);
        $tailor = Tailor::create(['name' => 'Recovery Tailor', 'phone_number1' => '03007654321', 'user_id' => $owner->id]);
        $order = Order::create(array_merge(['customerId' => $customer->id, 'sub_customer' => $customer->id, 'suitQuantity' => 1,
            'totalPayment' => 1500, 'tailorId' => $tailor->id, 'tailor_price' => 500, 'userId' => $owner->id,
            'returnDate' => now()->addWeek()->toDateString(), 'status' => 'ready', 'tailor_paid_amount' => 0], $overrides));
        return [$owner, $tailor, $order];
    }

    private function claim(User $owner, Order $order, array $overrides = []): CounterOrderComplaint
    {
        return CounterOrderComplaint::create(array_merge(['order_id' => $order->id, 'user_id' => $owner->id,
            'created_by_user_id' => $owner->id, 'reason' => 'The completed stitching does not match the approved measurements.',
            'requested_tailoring_refund' => 200, 'requested_cloth_compensation' => 0, 'status' => 'pending'], $overrides));
    }
}
