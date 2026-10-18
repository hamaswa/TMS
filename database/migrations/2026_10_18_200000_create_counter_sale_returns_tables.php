<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('counter_sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->foreignId('counter_sale_receipt_id')->constrained('counter_sale_receipts');
            $table->decimal('return_total', 12, 2);
            $table->decimal('balance_reduction', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->decimal('credit_amount', 12, 2)->default(0);
            $table->string('settlement_type', 20)->default('none');
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('reason');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('counter_sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_sale_return_id')->constrained('counter_sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_stock_id')->constrained('sale_stocks');
            $table->decimal('quantity', 10, 2);
            $table->decimal('inventory_quantity', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->boolean('restocked')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_sale_return_items');
        Schema::dropIfExists('counter_sale_returns');
    }
};
