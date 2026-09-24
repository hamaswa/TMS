<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloths', function (Blueprint $table) {
            $table->string('stock_code', 60)->nullable()->after('user_id');
        });

        DB::table('cloths')->orderBy('id')->chunkById(200, function ($cloths) {
            foreach ($cloths as $cloth) {
                DB::table('cloths')->where('id', $cloth->id)->update([
                    'stock_code' => sprintf('CLT-%d-%06d', (int) $cloth->user_id, (int) $cloth->id),
                ]);
            }
        });

        Schema::table('cloths', function (Blueprint $table) {
            $table->unique('stock_code', 'cloths_stock_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cloths', function (Blueprint $table) {
            $table->dropUnique('cloths_stock_code_unique');
            $table->dropColumn('stock_code');
        });
    }
};
