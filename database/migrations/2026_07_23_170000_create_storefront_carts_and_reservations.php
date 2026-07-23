<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storefront_carts')) {
            Schema::create('storefront_carts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->char('token_hash', 64)->unique();
                $table->dateTime('expires_at')->index();
                $table->dateTime('last_activity_at');
                $table->timestamps();
                $table->index(['storefront_id', 'customer_id']);
            });
        }

        if (! Schema::hasTable('storefront_cart_items')) {
            Schema::create('storefront_cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('storefront_cart_id')->constrained('storefront_carts')->cascadeOnDelete();
                $table->foreignId('clothing_listing_id')->constrained('storefront_clothing_listings')->cascadeOnDelete();
                $table->foreignId('cloth_color_id')->constrained('cloth_colors')->cascadeOnDelete();
                $table->decimal('quantity', 12, 2);
                $table->decimal('unit_price_snapshot', 12, 2);
                $table->dateTime('reserved_until')->index();
                $table->timestamps();
                $table->unique(
                    ['storefront_cart_id', 'clothing_listing_id', 'cloth_color_id'],
                    'storefront_cart_item_unique'
                );
                $table->index(['cloth_color_id', 'reserved_until'], 'storefront_active_reservation_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_cart_items');
        Schema::dropIfExists('storefront_carts');
    }
};
