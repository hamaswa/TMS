<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('shop_code', 30)->nullable()->after('name')->unique();
        });

        DB::table('businesses')->orderBy('id')->eachById(function ($business) {
            DB::table('businesses')->where('id', $business->id)->update([
                'shop_code' => sprintf('TMS-%06d', $business->id),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique(['shop_code']);
            $table->dropColumn('shop_code');
        });
    }
};
