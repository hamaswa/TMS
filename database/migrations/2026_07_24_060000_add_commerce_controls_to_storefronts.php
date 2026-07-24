<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->boolean('online_ordering_enabled')->default(true)->after('inquiries_enabled');
            $table->boolean('unpaid_orders_enabled')->default(true)->after('online_ordering_enabled');
            $table->boolean('cod_enabled')->default(true)->after('unpaid_orders_enabled');
            $table->boolean('easypaisa_enabled')->default(true)->after('cod_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn([
                'online_ordering_enabled',
                'unpaid_orders_enabled',
                'cod_enabled',
                'easypaisa_enabled',
            ]);
        });
    }
};
