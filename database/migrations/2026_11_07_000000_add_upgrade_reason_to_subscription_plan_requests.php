<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plan_requests', function (Blueprint $table) {
            $table->boolean('is_upgrade')->default(false)->after('subscription_plan_id');
            $table->text('upgrade_reason')->nullable()->after('is_upgrade');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plan_requests', function (Blueprint $table) {
            $table->dropColumn(['is_upgrade', 'upgrade_reason']);
        });
    }
};
