<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'first_sale_at') || ! Schema::hasTable('sale_stocks')) {
            return;
        }

        DB::table('customers')->where('is_walk_in', false)->update(['first_sale_at' => null]);

        DB::table('sale_stocks')
            ->leftJoin('counter_sale_receipts', 'counter_sale_receipts.id', '=', 'sale_stocks.counter_sale_receipt_id')
            ->select('sale_stocks.c_id', DB::raw('MIN(sale_stocks.sellDate) AS first_sale_at'))
            ->whereNull('sale_stocks.deleted_at')
            ->whereNotNull('sale_stocks.c_id')
            ->where(function ($query) {
                $query->whereNull('sale_stocks.counter_sale_receipt_id')
                    ->orWhere('counter_sale_receipts.status', 'completed');
            })
            ->groupBy('sale_stocks.c_id')
            ->orderBy('sale_stocks.c_id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('customers')
                        ->where('id', $row->c_id)
                        ->where('is_walk_in', false)
                        ->update(['first_sale_at' => $row->first_sale_at]);
                }
            });
    }

    public function down(): void
    {
        // The previous value cannot be reconstructed more accurately than this reconciliation.
    }
};
