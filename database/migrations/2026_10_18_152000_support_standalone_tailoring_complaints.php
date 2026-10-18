<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('counter_order_complaints', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->index();
            $table->unsignedBigInteger('counter_order_id')->nullable()->change();
            $table->unsignedBigInteger('counter_order_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('counter_order_complaints', function (Blueprint $table) {
            $table->dropColumn('order_id');
        });
    }
};
