<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('category', 30)->default('production');
            $table->boolean('is_system')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'code']);
        });

        Schema::create('production_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('legacy_tailor_id')->nullable()->unique()->constrained('tailors')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('relationship_type', 30)->default('contractor');
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'active']);
            $table->index(['user_id', 'phone']);
        });

        Schema::create('production_worker_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['production_worker_id', 'work_type_id'], 'worker_skill_unique');
        });

        Schema::create('worker_compensation_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('production_worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('legacy_tailor_rate_id')->nullable()->unique()->constrained('tailorsalaries')->nullOnDelete();
            $table->string('method', 30);
            $table->decimal('rate', 14, 2)->default(0);
            $table->decimal('fixed_salary', 14, 2)->default(0);
            $table->decimal('commission_percent', 7, 4)->default(0);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'active']);
        });

        Schema::create('order_work_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('production_worker_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('compensation_plan_id')->nullable()->constrained('worker_compensation_plans')->nullOnDelete();
            $table->string('legacy_key')->nullable()->unique();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('rate', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status', 30)->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['production_worker_id', 'status']);
        });

        Schema::create('worker_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('production_worker_id')->constrained()->restrictOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('order_work_assignments')->nullOnDelete();
            $table->string('legacy_key')->nullable()->unique();
            $table->string('entry_type', 30);
            $table->decimal('amount', 14, 2);
            $table->date('entry_date');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['production_worker_id', 'entry_date']);
            $table->index(['user_id', 'entry_type']);
        });

        $this->backfillExistingTailors();
    }

    private function backfillExistingTailors(): void
    {
        DB::table('tailors')->orderBy('id')->each(function ($tailor) {
            DB::table('work_types')->updateOrInsert(
                ['user_id' => $tailor->user_id, 'code' => 'stitching'],
                [
                    'name' => 'سلائی',
                    'category' => 'production',
                    'is_system' => true,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
            $workTypeId = DB::table('work_types')
                ->where('user_id', $tailor->user_id)
                ->where('code', 'stitching')
                ->value('id');

            DB::table('production_workers')->updateOrInsert(
                ['legacy_tailor_id' => $tailor->id],
                [
                    'user_id' => $tailor->user_id,
                    'name' => $tailor->name,
                    'phone' => $tailor->phone_number1,
                    'email' => $tailor->email,
                    'relationship_type' => 'contractor',
                    'active' => true,
                    'created_at' => $tailor->created_at ?? now(),
                    'updated_at' => now(),
                ],
            );
            $workerId = DB::table('production_workers')->where('legacy_tailor_id', $tailor->id)->value('id');

            DB::table('production_worker_skills')->updateOrInsert(
                ['production_worker_id' => $workerId, 'work_type_id' => $workTypeId],
                ['created_at' => now(), 'updated_at' => now()],
            );

            DB::table('tailorsalaries')->where('tailor_id', $tailor->id)->orderBy('id')->each(
                function ($rate) use ($tailor, $workerId, $workTypeId) {
                    DB::table('worker_compensation_plans')->updateOrInsert(
                        ['legacy_tailor_rate_id' => $rate->id],
                        [
                            'user_id' => $tailor->user_id,
                            'production_worker_id' => $workerId,
                            'work_type_id' => $workTypeId,
                            'method' => 'per_piece',
                            'rate' => $rate->price ?? 0,
                            'fixed_salary' => 0,
                            'commission_percent' => 0,
                            'active' => true,
                            'created_at' => $rate->created_at ?? now(),
                            'updated_at' => now(),
                        ],
                    );
                },
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_ledger_entries');
        Schema::dropIfExists('order_work_assignments');
        Schema::dropIfExists('worker_compensation_plans');
        Schema::dropIfExists('production_worker_skills');
        Schema::dropIfExists('production_workers');
        Schema::dropIfExists('work_types');
    }
};
