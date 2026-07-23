<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_id')->constrained()->restrictOnDelete();
            $table->foreignId('storefront_cart_id')->nullable()->constrained('storefront_carts')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('transaction_id')->nullable()->index();
            $table->string('reference', 30)->unique();
            $table->char('tracking_token_hash', 64);
            $table->string('status', 30)->default('pending');
            $table->string('fulfillment_method', 30);
            $table->text('delivery_address')->nullable();
            $table->text('customer_note')->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance_amount', 14, 2);
            $table->dateTime('placed_at');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['storefront_id', 'status', 'placed_at']);
            $table->index(['customer_id', 'placed_at']);
        });

        Schema::create('storefront_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_order_id')->constrained('storefront_orders')->cascadeOnDelete();
            $table->foreignId('clothing_listing_id')->nullable()->constrained('storefront_clothing_listings')->nullOnDelete();
            $table->foreignId('cloth_id')->constrained('cloths')->restrictOnDelete();
            $table->foreignId('cloth_color_id')->constrained('cloth_colors')->restrictOnDelete();
            $table->string('item_name');
            $table->string('color', 100);
            $table->decimal('quantity', 14, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->decimal('cost_per_meter', 14, 4)->default(0);
            $table->decimal('cost_total', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('storefront_carts', function (Blueprint $table) {
            $table->dateTime('checked_out_at')->nullable()->after('last_activity_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('storefront_carts', function (Blueprint $table) {
            $table->dropColumn('checked_out_at');
        });
        Schema::dropIfExists('storefront_order_items');
        Schema::dropIfExists('storefront_orders');
    }
};
