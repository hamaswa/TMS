<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_cancellation_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id');
            $table->decimal('order_total', 12, 2);
            $table->decimal('paid_on_order', 12, 2);
            $table->decimal('cancelled_unpaid_balance', 12, 2);
            $table->decimal('other_balance_before', 12, 2)->default(0);
            $table->decimal('cancellation_charge', 12, 2)->default(0);
            $table->decimal('compensation', 12, 2)->default(0);
            $table->decimal('available_credit', 12, 2)->default(0);
            $table->decimal('applied_to_other_balance', 12, 2)->default(0);
            $table->decimal('cash_refund', 12, 2)->default(0);
            $table->decimal('retained_credit', 12, 2)->default(0);
            $table->decimal('customer_balance_after', 12, 2)->default(0);
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('reason');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'source_type', 'source_id'], 'cancellation_settlement_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_cancellation_settlements');
    }
};
