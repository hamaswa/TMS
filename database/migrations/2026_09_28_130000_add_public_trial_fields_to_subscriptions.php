<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('marketing_summary', 500)->nullable()->after('description');
            $table->unsignedSmallInteger('trial_days')->default(0)->after('billing_period_days');
            $table->boolean('is_public')->default(false)->after('allowed_permissions');
            $table->boolean('is_recommended')->default(false)->after('is_public');
            $table->unsignedSmallInteger('display_order')->default(0)->after('is_recommended');
            $table->string('lifecycle_status', 20)->default('active')->after('display_order');
        });

        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('fee');
            $table->timestamp('trial_started_at')->nullable()->after('is_trial');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_started_at');
            $table->timestamp('trial_converted_at')->nullable()->after('trial_ends_at');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->string('signup_mobile_normalized', 20)->nullable()->after('name');
            $table->timestamp('self_registered_at')->nullable()->after('signup_mobile_normalized');
            $table->unique('signup_mobile_normalized', 'business_signup_mobile_unique');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique('business_signup_mobile_unique');
            $table->dropColumn(['signup_mobile_normalized', 'self_registered_at']);
        });
        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_trial', 'trial_started_at', 'trial_ends_at', 'trial_converted_at']);
        });
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'marketing_summary',
                'trial_days',
                'is_public',
                'is_recommended',
                'display_order',
                'lifecycle_status',
            ]);
        });
    }
};
