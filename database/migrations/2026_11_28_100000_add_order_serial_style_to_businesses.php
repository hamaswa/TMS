<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How new counter orders are numbered: "standard" (ORD-000108, suits ORD-000108-01) or "simple" (108, suits 108-1). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('order_serial_style', 20)->default('standard')->after('counter_order_layout');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('order_serial_style');
        });
    }
};
