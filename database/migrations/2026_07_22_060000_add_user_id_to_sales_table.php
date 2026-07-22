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
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        DB::table('sales')->orderBy('id')->eachById(function ($sale) {
            $userId = DB::table('transactions')
                ->where('sale_id', $sale->id)
                ->whereNotNull('userId')
                ->value('userId');

            if ($userId) {
                DB::table('sales')->where('id', $sale->id)->update(['user_id' => $userId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
