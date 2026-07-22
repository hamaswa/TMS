<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')
                ->constrained('customers')->nullOnDelete();
        });

        DB::table('sales')->whereNotNull('user_id')->orderBy('id')->eachById(function ($sale) {
            $matches = DB::table('customers')
                ->where('user_id', $sale->user_id)
                ->where('name', $sale->customer_name)
                ->whereNull('deleted_at')
                ->limit(2)
                ->pluck('id');

            if ($matches->count() !== 1) {
                return;
            }

            $customerId = $matches->first();
            DB::table('sales')->where('id', $sale->id)->update(['customer_id' => $customerId]);
            DB::table('transactions')->where('sale_id', $sale->id)->whereNull('customerId')
                ->update(['customerId' => $customerId]);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
