<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('order_work_assignments')
            ->select(['user_id', 'order_id', 'production_worker_id', 'work_type_id'])
            ->selectRaw('COUNT(*) AS duplicate_count')
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('user_id', 'order_id', 'production_worker_id', 'work_type_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException('Active duplicate order-work assignments must be reviewed before this migration can run. No records were changed.');
        }

        Schema::table('order_work_assignments', function (Blueprint $table) {
            $table->string('active_assignment_key', 191)->nullable()->after('legacy_key')->unique();
        });

        DB::table('order_work_assignments')
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('id')
            ->eachById(function ($assignment) {
                DB::table('order_work_assignments')->where('id', $assignment->id)->update([
                    'active_assignment_key' => implode(':', [
                        $assignment->user_id,
                        $assignment->order_id,
                        $assignment->production_worker_id,
                        $assignment->work_type_id,
                    ]),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('order_work_assignments', function (Blueprint $table) {
            $table->dropUnique(['active_assignment_key']);
            $table->dropColumn('active_assignment_key');
        });
    }
};
