<?php

namespace Tests\Feature;

use App\Models\OrderWorkAssignment;
use App\Models\ProductionWorker;
use App\Models\Tailor;
use App\Models\User;
use App\Models\WorkType;
use App\Models\WorkerCompensationPlan;
use App\Models\WorkerLedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionWorkforceTest extends TestCase
{
    use RefreshDatabase;

    public function test_additive_workforce_schema_keeps_worker_identity_pay_and_access_separate(): void
    {
        foreach ([
            'work_types', 'production_workers', 'production_worker_skills',
            'worker_compensation_plans', 'order_work_assignments', 'worker_ledger_entries',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $owner = User::factory()->create();
        $tailor = Tailor::create([
            'name' => 'رشید محمود', 'phone_number1' => '03001234567',
            'password' => bcrypt('Tailor@2026'), 'user_id' => $owner->id,
        ]);
        $worker = ProductionWorker::create([
            'user_id' => $owner->id, 'legacy_tailor_id' => $tailor->id,
            'name' => $tailor->name, 'phone' => $tailor->phone_number1,
            'relationship_type' => 'contractor',
        ]);
        $stitching = WorkType::create([
            'user_id' => $owner->id, 'code' => 'stitching', 'name' => 'سلائی', 'is_system' => true,
        ]);
        $worker->skills()->attach($stitching);
        $plan = WorkerCompensationPlan::create([
            'user_id' => $owner->id, 'production_worker_id' => $worker->id,
            'work_type_id' => $stitching->id, 'method' => 'per_piece', 'rate' => 400,
        ]);

        $this->assertSame('contractor', $worker->relationship_type);
        $this->assertNull($worker->getAttribute('business_role_id'));
        $this->assertTrue($worker->skills->contains($stitching));
        $this->assertEquals(400, (float) $plan->rate);
        $this->assertSame($worker->id, $tailor->productionWorker->id);
    }

    public function test_assignment_snapshots_rates_and_ledger_supports_earnings_and_payments(): void
    {
        $this->assertContains('legacy_key', (new OrderWorkAssignment())->getFillable());
        $this->assertContains('rate', (new OrderWorkAssignment())->getFillable());
        $this->assertContains('amount', (new OrderWorkAssignment())->getFillable());
        $this->assertContains('fixed_salary', (new WorkerCompensationPlan())->getFillable());
        $this->assertContains('commission_percent', (new WorkerCompensationPlan())->getFillable());
        $this->assertContains('entry_type', (new WorkerLedgerEntry())->getFillable());
    }
}
