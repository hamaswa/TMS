<?php

namespace Tests\Feature;

use App\Models\ProductionWorker;
use App\Models\User;
use App\Models\WorkType;
use App\Models\WorkerLedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionWorkerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_a_cutter_without_creating_an_employee_login(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get(route('admin.production-workers.create'))->assertOk()->assertSeeText('نیا پروڈکشن ورکر');
        $cutting = WorkType::where('user_id', $owner->id)->where('code', 'cutting')->firstOrFail();

        $this->actingAs($owner)->post(route('admin.production-workers.store'), [
            'name' => 'اکرم کٹنگ ماسٹر',
            'phone' => '03007778888',
            'relationship_type' => 'contractor',
            'work_type_ids' => [$cutting->id],
            'notes' => 'فی سوٹ کٹنگ',
        ])->assertRedirect();

        $worker = ProductionWorker::where('name', 'اکرم کٹنگ ماسٹر')->firstOrFail();
        $this->assertNull($worker->legacy_tailor_id);
        $this->assertSame('contractor', $worker->relationship_type);
        $this->assertTrue($worker->skills()->whereKey($cutting->id)->exists());
        $this->assertDatabaseMissing('users', ['phone' => '03007778888']);
    }

    public function test_client_can_set_piece_rate_and_payment_is_limited_to_worker_balance(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get(route('admin.production-workers.create'))->assertOk();
        $cutting = WorkType::where('user_id', $owner->id)->where('code', 'cutting')->firstOrFail();
        $worker = ProductionWorker::create([
            'user_id' => $owner->id, 'name' => 'اکرم', 'relationship_type' => 'contractor',
        ]);
        $worker->skills()->attach($cutting);

        $otherType = WorkType::where('user_id', $owner->id)->where('code', 'ironing')->firstOrFail();
        $this->actingAs($owner)->post(route('admin.production-workers.compensation.store', $worker), [
            'work_type_id' => $otherType->id,
            'method' => 'per_piece',
            'rate' => 25,
        ])->assertSessionHasErrors('work_type_id');
        $this->assertDatabaseMissing('worker_compensation_plans', [
            'production_worker_id' => $worker->id, 'work_type_id' => $otherType->id,
        ]);

        $this->actingAs($owner)->post(route('admin.production-workers.compensation.store', $worker), [
            'work_type_id' => $cutting->id,
            'method' => 'per_piece',
            'rate' => 50,
            'fixed_salary' => 0,
            'commission_percent' => 0,
            'effective_from' => now()->toDateString(),
        ])->assertRedirect();
        $this->assertDatabaseHas('worker_compensation_plans', [
            'production_worker_id' => $worker->id, 'work_type_id' => $cutting->id,
            'method' => 'per_piece', 'rate' => 50, 'active' => true,
        ]);

        $this->actingAs($owner)->get(route('admin.production-workers.show', $worker))
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSeeText('اس ورکر کی کوئی واجب الادا رقم نہیں')
            ->assertSeeText('ہر تیار شدہ عدد کی اجرت درج کریں')
            ->assertSee('data-compensation-field="rate"', false)
            ->assertDontSeeText('ادائیگی محفوظ کریں');

        WorkerLedgerEntry::create([
            'user_id' => $owner->id, 'production_worker_id' => $worker->id,
            'entry_type' => 'earning', 'amount' => 100, 'entry_date' => now()->toDateString(),
        ]);
        $this->actingAs($owner)->get(route('admin.production-workers.show', $worker))
            ->assertOk()
            ->assertSeeText('ادائیگی محفوظ کریں');

        $this->actingAs($owner)->from(route('admin.production-workers.show', $worker))
            ->post(route('admin.production-workers.payments.store', $worker), [
                'amount' => 120, 'entry_date' => now()->toDateString(),
            ])->assertRedirect(route('admin.production-workers.show', $worker))->assertSessionHasErrors('amount');

        $this->actingAs($owner)->from(route('admin.production-workers.show', $worker))
            ->post(route('admin.production-workers.payments.store', $worker), [
                'amount' => 10,
                'entry_date' => now()->toDateString(),
                'payment_method' => 'easypaisa',
            ])->assertRedirect(route('admin.production-workers.show', $worker))
            ->assertSessionHasErrors('payment_reference');

        $this->actingAs($owner)->post(route('admin.production-workers.payments.store', $worker), [
            'amount' => 60, 'entry_date' => now()->toDateString(),
            'payment_method' => 'easypaisa', 'payment_reference' => 'EP-WORKER-60',
            'notes' => 'نقد ادائیگی',
        ])->assertRedirect();
        $this->assertDatabaseHas('worker_ledger_entries', [
            'production_worker_id' => $worker->id,
            'entry_type' => 'payment',
            'payment_method' => 'easypaisa',
            'payment_reference' => 'EP-WORKER-60',
        ]);
        $this->actingAs($owner)->from(route('admin.production-workers.show', $worker))
            ->post(route('admin.production-workers.payments.store', $worker), [
                'amount' => 50, 'entry_date' => now()->toDateString(),
            ])->assertRedirect(route('admin.production-workers.show', $worker))->assertSessionHasErrors('amount');
        $this->assertEquals(40, (float) $worker->ledgerEntries()->sum('amount'));
        $this->assertDatabaseCount('worker_ledger_entries', 2);
    }

    public function test_production_workers_are_tenant_scoped(): void
    {
        $owner = $this->owner();
        $otherOwner = $this->owner();
        $worker = ProductionWorker::create([
            'user_id' => $owner->id, 'name' => 'رشید', 'relationship_type' => 'contractor',
        ]);

        $this->actingAs($otherOwner)->get(route('admin.production-workers.show', $worker))->assertNotFound();
        $this->actingAs($otherOwner)->put(route('admin.production-workers.update', $worker), [
            'name' => 'Changed', 'relationship_type' => 'employee', 'work_type_ids' => [999],
        ])->assertNotFound();
    }

    private function owner(): User
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);

        return $owner;
    }
}
