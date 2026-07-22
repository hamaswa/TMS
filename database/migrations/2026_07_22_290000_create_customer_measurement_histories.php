<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_measurement_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('measurement_template_id')->nullable()->constrained('measurement_templates')->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 30)->default('customer_update');
            $table->timestamps();
            $table->index(['user_id', 'customer_id', 'created_at'], 'customer_measurement_history_lookup');
        });

        Schema::create('customer_measurement_history_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_measurement_history_id');
            $table->foreign('customer_measurement_history_id', 'measurement_history_values_history_fk')
                ->references('id')->on('customer_measurement_histories')->cascadeOnDelete();
            $table->foreignId('measurement_field_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_key');
            $table->string('label');
            $table->text('value')->nullable();
            $table->string('unit', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['customer_measurement_history_id', 'source_key'], 'customer_measurement_history_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_measurement_history_values');
        Schema::dropIfExists('customer_measurement_histories');
    }
};
