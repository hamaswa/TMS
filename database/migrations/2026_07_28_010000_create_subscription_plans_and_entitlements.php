<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 60)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2)->default(0);
            $table->unsignedSmallInteger('billing_period_days')->default(30);
            $table->unsignedInteger('max_employees')->nullable();
            $table->unsignedInteger('max_business_roles')->nullable();
            $table->unsignedInteger('max_tailors')->nullable();
            $table->boolean('allow_tailoring')->default(true);
            $table->boolean('allow_clothing')->default(true);
            $table->boolean('allow_storefront')->default(false);
            $table->boolean('allow_financial_reports')->default(false);
            $table->boolean('allow_team_management')->default(true);
            $table->boolean('allow_activity_log')->default(false);
            $table->json('allowed_permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('created_by_user_id', 'subscription_plan_creator_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('business_id');
            $table->string('plan_code', 60)->nullable()->after('plan_name');
            $table->unsignedInteger('max_employees')->nullable()->after('fee');
            $table->unsignedInteger('max_business_roles')->nullable()->after('max_employees');
            $table->unsignedInteger('max_tailors')->nullable()->after('max_business_roles');
            $table->boolean('allow_tailoring')->nullable()->after('max_tailors');
            $table->boolean('allow_clothing')->nullable()->after('allow_tailoring');
            $table->boolean('allow_storefront')->nullable()->after('allow_clothing');
            $table->boolean('allow_financial_reports')->nullable()->after('allow_storefront');
            $table->boolean('allow_team_management')->nullable()->after('allow_financial_reports');
            $table->boolean('allow_activity_log')->nullable()->after('allow_team_management');
            $table->json('allowed_permissions')->nullable()->after('allow_activity_log');

            $table->foreign('subscription_plan_id', 'business_subscription_plan_fk')
                ->references('id')->on('subscription_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->dropForeign('business_subscription_plan_fk');
            $table->dropColumn([
                'subscription_plan_id',
                'plan_code',
                'max_employees',
                'max_business_roles',
                'max_tailors',
                'allow_tailoring',
                'allow_clothing',
                'allow_storefront',
                'allow_financial_reports',
                'allow_team_management',
                'allow_activity_log',
                'allowed_permissions',
            ]);
        });

        Schema::dropIfExists('subscription_plans');
    }
};
