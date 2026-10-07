<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 60)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('sale_session_id')->nullable()->unique()->constrained('sale_sessions')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('draft')->index();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance_amount', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'updated_at']);
        });

        Schema::create('counter_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_order_id')->constrained('counter_orders')->cascadeOnDelete();
            $table->foreignId('linked_item_id')->nullable()->constrained('counter_order_items')->nullOnDelete();
            $table->string('type', 20)->index();
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('cloth_id')->nullable()->constrained('cloths')->nullOnDelete();
            $table->foreignId('measurement_profile_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('measurement_template_id')->nullable()->constrained('measurement_templates')->nullOnDelete();
            $table->foreignId('tailor_id')->nullable()->constrained('tailors')->nullOnDelete();
            $table->unsignedBigInteger('rate_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('length', 12, 2)->nullable();
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->json('details')->nullable();
            $table->text('note')->nullable();
            $table->string('source_record_type', 60)->nullable();
            $table->unsignedBigInteger('source_record_id')->nullable();
            $table->timestamps();
            $table->index(['source_record_type', 'source_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_order_items');
        Schema::dropIfExists('counter_orders');
    }
};
