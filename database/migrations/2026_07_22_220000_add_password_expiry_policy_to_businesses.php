<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedSmallInteger('password_expiry_days')->nullable()->after('clothing_enabled');
            $table->timestamp('password_policy_updated_at')->nullable()->after('password_expiry_days');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['password_expiry_days', 'password_policy_updated_at']);
        });
    }
};
