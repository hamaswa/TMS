<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storefront_tailoring_services')) {
            Schema::create('storefront_tailoring_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
                $table->string('name', 180);
                $table->text('description')->nullable();
                $table->decimal('price_from', 12, 2)->nullable();
                $table->string('price_unit', 40)->default('فی سوٹ');
                $table->unsignedSmallInteger('estimated_days')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_published')->default(false)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['storefront_id', 'is_published', 'sort_order'], 'storefront_tailoring_public_index');
            });
        }

        if (! Schema::hasTable('storefront_inquiries')) {
            Schema::create('storefront_inquiries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tailoring_service_id')->nullable()->constrained('storefront_tailoring_services')->nullOnDelete();
                $table->string('customer_name', 150);
                $table->string('phone', 50);
                $table->string('email', 150)->nullable();
                $table->string('city', 100)->nullable();
                $table->date('preferred_date')->nullable();
                $table->text('message')->nullable();
                $table->string('status', 30)->default('new')->index();
                $table->text('admin_notes')->nullable();
                $table->timestamp('contacted_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->index(['storefront_id', 'status', 'created_at'], 'storefront_inquiry_queue_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_inquiries');
        Schema::dropIfExists('storefront_tailoring_services');
    }
};
