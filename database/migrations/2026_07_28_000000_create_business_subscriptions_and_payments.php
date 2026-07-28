<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('plan_name', 100)->default('TMS Subscription');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('fee', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 1000)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'ends_on'], 'business_subscription_end_idx');
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('business_subscription_id')->constrained('business_subscriptions')->cascadeOnDelete();
            $table->date('paid_on');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 30);
            $table->string('reference', 150)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reversal_reason', 1000)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'paid_on'], 'subscription_payment_date_idx');
        });

        Schema::create('subscription_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_subscription_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->smallInteger('threshold_days');
            $table->timestamp('delivered_at');
            $table->timestamps();

            $table->foreign('business_subscription_id', 'subscription_notice_subscription_fk')
                ->references('id')
                ->on('business_subscriptions')
                ->cascadeOnDelete();
            $table->unique(
                ['business_subscription_id', 'user_id', 'threshold_days'],
                'subscription_notice_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_notification_deliveries');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('business_subscriptions');
    }
};
