<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('counter_order_complaints', function (Blueprint $table) {
            $table->decimal('approved_tailoring_refund', 12, 2)->nullable();
            $table->decimal('approved_cloth_compensation', 12, 2)->nullable();
            $table->decimal('actual_cash_payout', 12, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->unsignedBigInteger('settled_by_user_id')->nullable();
            $table->timestamp('settled_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('counter_order_complaints', function (Blueprint $table) {
            $table->dropColumn(['approved_tailoring_refund','approved_cloth_compensation','actual_cash_payout','payment_method','payment_reference','settled_by_user_id','settled_at']);
        });
    }
};
