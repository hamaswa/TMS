<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storefront_clothing_listings')) {
            Schema::create('storefront_clothing_listings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cloth_id')->constrained('cloths')->cascadeOnDelete();
                $table->string('public_name', 180)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_published')->default(false)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['storefront_id', 'cloth_id']);
                $table->index(['storefront_id', 'is_published', 'sort_order'], 'storefront_clothing_public_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_clothing_listings');
    }
};
