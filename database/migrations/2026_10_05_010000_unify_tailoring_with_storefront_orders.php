<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('storefront_tailoring_services', 'standard_sizes')) {
            Schema::table('storefront_tailoring_services', function (Blueprint $table) {
                $table->json('standard_sizes')->nullable()->after('measurement_methods');
            });
        }

        if (! Schema::hasTable('storefront_cart_tailoring_items')) {
            Schema::create('storefront_cart_tailoring_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('storefront_cart_id')->constrained('storefront_carts')->cascadeOnDelete();
                $table->foreignId('tailoring_service_id')->constrained('storefront_tailoring_services')->cascadeOnDelete();
                $table->foreignId('clothing_cart_item_id')->nullable()->constrained('storefront_cart_items')->nullOnDelete();
                $table->foreignId('measurement_template_id')->nullable()->constrained('measurement_templates')->nullOnDelete();
                $table->string('measurement_method', 40);
                $table->string('standard_size', 60)->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price_snapshot', 14, 2);
                $table->json('measurement_values')->nullable();
                $table->text('notes')->nullable();
                $table->date('preferred_date')->nullable();
                $table->timestamps();
                $table->index(['storefront_cart_id', 'tailoring_service_id'], 'cart_tailoring_service_index');
            });
        }

        if (! Schema::hasTable('storefront_order_tailoring_items')) {
            Schema::create('storefront_order_tailoring_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('storefront_order_id')->constrained('storefront_orders')->cascadeOnDelete();
                $table->foreignId('tailoring_service_id')->nullable()->constrained('storefront_tailoring_services')->nullOnDelete();
                $table->foreignId('clothing_order_item_id')->nullable()->constrained('storefront_order_items')->nullOnDelete();
                $table->foreignId('measurement_template_id')->nullable()->constrained('measurement_templates')->nullOnDelete();
                $table->string('service_name');
                $table->string('measurement_method', 40);
                $table->string('standard_size', 60)->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 14, 2);
                $table->decimal('line_total', 14, 2);
                $table->unsignedInteger('estimated_days')->nullable();
                $table->json('measurement_values')->nullable();
                $table->text('notes')->nullable();
                $table->date('preferred_date')->nullable();
                $table->timestamps();
                $table->index(['storefront_order_id', 'tailoring_service_id'], 'order_tailoring_service_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_order_tailoring_items');
        Schema::dropIfExists('storefront_cart_tailoring_items');
        if (Schema::hasColumn('storefront_tailoring_services', 'standard_sizes')) {
            Schema::table('storefront_tailoring_services', fn (Blueprint $table) => $table->dropColumn('standard_sizes'));
        }
    }
};
