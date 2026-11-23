<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // How the order page offers stock: "list" (one dropdown row, as before) or "grid" (a box per item with a cart).
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('counter_order_layout', 10)->default('list')->after('tailoring_status_mode');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('counter_order_layout');
        });
    }
};
