<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_order_id')->constrained('storefront_orders')->restrictOnDelete();
            $table->string('reference', 40)->unique();
            $table->string('type', 20);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->string('refund_method', 30)->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at');
            $table->timestamps();
            $table->index(['storefront_order_id', 'processed_at']);
        });

        Schema::create('storefront_order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_order_return_id')->constrained('storefront_order_returns')->cascadeOnDelete();
            $table->foreignId('storefront_order_item_id')->constrained('storefront_order_items')->restrictOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->boolean('restocked')->default(true);
            $table->foreignId('replacement_cloth_color_id')->nullable()->constrained('cloth_colors')->restrictOnDelete();
            $table->decimal('replacement_quantity', 14, 2)->nullable();
            $table->timestamps();
            $table->index(['storefront_order_item_id', 'quantity'], 'sf_return_item_qty_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_order_return_items');
        Schema::dropIfExists('storefront_order_returns');
    }
};
