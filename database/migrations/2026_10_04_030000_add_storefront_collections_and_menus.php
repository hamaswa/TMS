<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_clothing_listings', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('description_en');
        });

        Schema::create('storefront_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 100);
            $table->string('name_ur', 180)->nullable();
            $table->string('name_en', 180)->nullable();
            $table->text('description_ur')->nullable();
            $table->text('description_en')->nullable();
            $table->string('source_type', 30)->default('manual');
            $table->json('rules')->nullable();
            $table->string('sort_mode', 30)->default('manual');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['storefront_id', 'slug']);
            $table->index(['storefront_id', 'is_published', 'sort_order'], 'sf_collection_public_idx');
        });

        Schema::create('storefront_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('storefront_menu_items')->nullOnDelete();
            $table->foreignId('storefront_collection_id')->nullable()->constrained('storefront_collections')->nullOnDelete();
            $table->string('label_ur', 120)->nullable();
            $table->string('label_en', 120)->nullable();
            $table->string('item_type', 30)->default('collection');
            $table->string('url', 1000)->nullable();
            $table->string('location', 20)->default('header');
            $table->boolean('open_in_new_tab')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['storefront_id', 'location', 'is_visible', 'sort_order'], 'sf_menu_visible_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_menu_items');
        Schema::dropIfExists('storefront_collections');
        Schema::table('storefront_clothing_listings', fn (Blueprint $table) => $table->dropColumn('tags'));
    }
};
