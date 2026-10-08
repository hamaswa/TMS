<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_order_serial_sequences', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
        });

        Schema::table('counter_orders', function (Blueprint $table) {
            $table->string('serial_number', 32)->nullable()->after('reference');
            $table->unsignedInteger('next_item_sequence')->default(1)->after('serial_number');
            $table->unique(['user_id', 'serial_number']);
        });

        Schema::table('counter_order_items', function (Blueprint $table) {
            $table->unsignedInteger('item_sequence')->nullable()->after('counter_order_id');
            $table->string('item_serial', 96)->nullable()->after('item_sequence');
            $table->unique(['counter_order_id', 'item_sequence']);
        });
    }

    public function down(): void
    {
        Schema::table('counter_order_items', function (Blueprint $table) {
            $table->dropUnique(['counter_order_id', 'item_sequence']);
            $table->dropColumn(['item_sequence', 'item_serial']);
        });

        Schema::table('counter_orders', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'serial_number']);
            $table->dropColumn(['serial_number', 'next_item_sequence']);
        });

        Schema::dropIfExists('counter_order_serial_sequences');
    }
};
