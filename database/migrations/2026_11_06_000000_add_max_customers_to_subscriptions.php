<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('max_customers')->nullable()->after('max_tailors');
        });

        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('max_customers')->nullable()->after('max_tailors');
        });
    }

    public function down(): void
    {
        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->dropColumn('max_customers');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('max_customers');
        });
    }
};
