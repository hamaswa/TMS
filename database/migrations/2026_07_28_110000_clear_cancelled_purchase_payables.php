<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchases') || ! Schema::hasColumn('purchases', 'balance_amount')) {
            return;
        }

        DB::table('purchases')
            ->where('status', 'cancelled')
            ->where('balance_amount', '!=', 0)
            ->update(['balance_amount' => 0]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchases') || ! Schema::hasColumn('purchases', 'balance_amount')) {
            return;
        }

        DB::table('purchases')
            ->where('status', 'cancelled')
            ->get(['id', 'total_amount', 'paid_amount'])
            ->each(function ($purchase): void {
                DB::table('purchases')
                    ->where('id', $purchase->id)
                    ->update([
                        'balance_amount' => max(
                            0,
                            (float) $purchase->total_amount - (float) $purchase->paid_amount
                        ),
                    ]);
            });
    }
};
