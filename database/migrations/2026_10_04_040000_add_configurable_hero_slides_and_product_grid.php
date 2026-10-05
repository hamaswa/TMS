<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->unsignedTinyInteger('product_columns_tablet')->default(2)->after('product_columns');
            $table->unsignedTinyInteger('product_columns_mobile')->default(1)->after('product_columns_tablet');
            $table->string('product_image_ratio', 20)->default('portrait')->after('product_columns_mobile');
            $table->boolean('show_product_brand')->default(true)->after('product_image_ratio');
            $table->boolean('show_product_category')->default(true)->after('show_product_brand');
            $table->boolean('show_product_stock')->default(true)->after('show_product_category');
        });

        Schema::create('storefront_hero_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
            $table->string('title_ur', 180)->nullable();
            $table->string('title_en', 180)->nullable();
            $table->text('text_ur')->nullable();
            $table->text('text_en')->nullable();
            $table->string('media_type', 20)->default('image');
            $table->string('image_path')->nullable();
            $table->string('mobile_image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('video_poster_path')->nullable();
            $table->string('alignment', 20)->default('start');
            $table->unsignedTinyInteger('overlay_strength')->default(65);
            $table->string('primary_label_ur', 80)->nullable();
            $table->string('primary_label_en', 80)->nullable();
            $table->string('primary_link_type', 30)->default('none');
            $table->foreignId('primary_collection_id')->nullable()->constrained('storefront_collections')->nullOnDelete();
            $table->string('primary_url', 1000)->nullable();
            $table->string('secondary_label_ur', 80)->nullable();
            $table->string('secondary_label_en', 80)->nullable();
            $table->string('secondary_link_type', 30)->default('none');
            $table->foreignId('secondary_collection_id')->nullable()->constrained('storefront_collections')->nullOnDelete();
            $table->string('secondary_url', 1000)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['storefront_id', 'is_active', 'sort_order'], 'sf_hero_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_hero_slides');
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn([
                'product_columns_tablet', 'product_columns_mobile', 'product_image_ratio',
                'show_product_brand', 'show_product_category', 'show_product_stock',
            ]);
        });
    }
};
