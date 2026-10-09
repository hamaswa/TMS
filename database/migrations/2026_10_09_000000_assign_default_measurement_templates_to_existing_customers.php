<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('measurement_templates')
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('id')
            ->get(['id', 'user_id'])
            ->each(function ($template): void {
                DB::table('customers')
                    ->where('user_id', $template->user_id)
                    ->whereNull('measurement_template_id')
                    ->update(['measurement_template_id' => $template->id]);
            });
    }

    public function down(): void
    {
        // Preserve assignments: customers may have used these templates since migration.
    }
};
