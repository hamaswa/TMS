<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('storefronts')) {
            return;
        }

        Schema::create('storefronts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('display_name');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('public_phone', 50)->nullable();
            $table->string('public_email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->boolean('show_clothing')->default(false);
            $table->boolean('show_tailoring')->default(false);
            $table->boolean('inquiries_enabled')->default(true);
            $table->boolean('pickup_enabled')->default(true);
            $table->boolean('delivery_enabled')->default(false);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefronts');
    }
};
