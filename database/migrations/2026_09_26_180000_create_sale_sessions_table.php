<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('active')->index();
            $table->unsignedInteger('revision')->default(0);
            $table->string('customer_mode', 20)->default('existing');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->json('customer_data')->nullable();
            $table->json('items')->nullable();
            $table->json('payment_data')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('counter_sale_receipt_id')->nullable()->constrained('counter_sale_receipts')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('attention_requested_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'updated_at']);
            $table->index(['agent_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_sessions');
    }
};
