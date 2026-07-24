<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_order_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_order_id')->unique()->constrained('storefront_orders')->restrictOnDelete();
            $table->string('reference', 40)->unique();
            $table->decimal('amount', 14, 2);
            $table->string('method', 30);
            $table->string('external_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('refunded_at');
            $table->timestamps();
            $table->index(['method', 'refunded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_order_refunds');
    }
};
