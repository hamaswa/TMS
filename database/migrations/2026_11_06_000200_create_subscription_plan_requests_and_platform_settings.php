<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platform-wide values the super admin edits, such as the Easypaisa account shops pay into.
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // A shop asking for a plan, with the payment it says it made, until the super admin approves or rejects it.
        Schema::create('subscription_plan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference', 60)->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_original_name')->nullable();
            $table->string('proof_mime_type', 120)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('review_note', 1000)->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('business_subscription_id')->nullable();
            $table->timestamps();

            $table->foreign('subscription_plan_id', 'plan_request_plan_fk')
                ->references('id')->on('subscription_plans')->cascadeOnDelete();
            $table->foreign('business_subscription_id', 'plan_request_subscription_fk')
                ->references('id')->on('business_subscriptions')->nullOnDelete();
            $table->index(['business_id', 'status'], 'plan_request_business_status_idx');
            $table->index(['status', 'created_at'], 'plan_request_status_idx');
            $table->index('payment_reference', 'plan_request_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_requests');
        Schema::dropIfExists('platform_settings');
    }
};
