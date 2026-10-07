<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->select(['userId', 'rack_no'])
            ->whereNotNull('rack_no')
            ->where('rack_no', '!=', '')
            ->distinct()
            ->orderBy('userId')
            ->each(function (object $order): void {
                DB::table('racks')->updateOrInsert(
                    ['user_id' => $order->userId, 'rack_no' => trim((string) $order->rack_no)],
                    ['updated_at' => now(), 'created_at' => now()],
                );
            });
    }

    public function down(): void
    {
        // Existing rack numbers are operational data and are intentionally retained.
    }
};
