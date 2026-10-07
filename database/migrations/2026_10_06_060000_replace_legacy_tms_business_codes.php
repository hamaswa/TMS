<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('businesses')
            ->where('shop_code', 'like', 'TMS-%')
            ->orderBy('id')
            ->eachById(function ($business): void {
                DB::table('businesses')
                    ->where('id', $business->id)
                    ->update(['shop_code' => 'SHOP-'.substr($business->shop_code, 4)]);
            });
    }

    public function down(): void
    {
        DB::table('businesses')
            ->where('shop_code', 'like', 'SHOP-%')
            ->orderBy('id')
            ->eachById(function ($business): void {
                DB::table('businesses')
                    ->where('id', $business->id)
                    ->update(['shop_code' => 'TMS-'.substr($business->shop_code, 5)]);
            });
    }
};
