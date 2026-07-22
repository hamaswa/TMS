<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->json('system_fields')->nullable();
            $table->json('custom_field_ids')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('measurement_template_id')->nullable()->after('user_id')
                ->constrained('measurement_templates')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('measurement_template_id')->nullable()->after('sub_customer')
                ->constrained('measurement_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('measurement_template_id'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropConstrainedForeignId('measurement_template_id'));
        Schema::dropIfExists('measurement_templates');
    }
};
