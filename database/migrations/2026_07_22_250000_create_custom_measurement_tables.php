<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('key');
            $table->string('field_type', 20)->default('number');
            $table->string('unit', 20)->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'key']);
            $table->index(['user_id', 'is_active', 'sort_order']);
        });

        Schema::create('customer_measurement_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('measurement_field_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'measurement_field_id'], 'customer_measurement_field_unique');
        });

        Schema::create('order_measurement_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('measurement_field_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_key');
            $table->string('label');
            $table->text('value')->nullable();
            $table->string('unit', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['order_id', 'source_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_measurement_values');
        Schema::dropIfExists('customer_measurement_values');
        Schema::dropIfExists('measurement_fields');
    }
};
