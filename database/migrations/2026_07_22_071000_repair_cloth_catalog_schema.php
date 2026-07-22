<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cloth_brands', 'brand_slug')) {
            Schema::table('cloth_brands', function (Blueprint $table) {
                $table->string('brand_slug')->nullable()->after('name');
            });
        }

        if (!Schema::hasTable('cloth_colors')) {
            Schema::create('cloth_colors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cloth_id')->constrained('cloths')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('color', 100);
                $table->decimal('length', 12, 2)->default(0);
                $table->timestamps();
                $table->unique(['cloth_id', 'color']);
            });
        }

        if (!Schema::hasTable('cloth_images')) {
            Schema::create('cloth_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cloth_id')->constrained('cloths')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('images');
                $table->string('image_color', 100)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cloth_videos')) {
            Schema::create('cloth_videos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cloth_id')->constrained('cloths')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('video');
                $table->string('video_color', 100)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cloth_id')->constrained('cloths')->cascadeOnDelete();
                $table->decimal('length', 12, 2);
                $table->decimal('price', 12, 2);
                $table->string('color', 100);
                $table->string('shop_name')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('online_orders')) {
            Schema::create('online_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('cloth_id')->constrained('cloths')->cascadeOnDelete();
                $table->decimal('length', 12, 2);
                $table->decimal('price', 12, 2);
                $table->string('color', 100)->nullable();
                $table->string('status')->default('pending');
                $table->timestamp('cancel_at')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // This migration repairs legacy installations conditionally. Reversing it
        // could delete tables or columns that existed before the repair ran.
    }
};
