<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cloth_categories')) {
            return;
        }

        Schema::create('cloth_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Two levels only (parent → child); the app blocks deleting a parent that still has children.
            $table->foreignId('parent_id')->nullable()->constrained('cloth_categories')->nullOnDelete();
            $table->string('name', 100);
            $table->string('name_ur', 100)->nullable();
            $table->string('slug', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->unique(['user_id', 'parent_id', 'name']);
            $table->index(['user_id', 'parent_id', 'is_active', 'sort_order'], 'cloth_categories_tree_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloth_categories');
    }
};
