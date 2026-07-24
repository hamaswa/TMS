<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('self_registered_at')->nullable()->after('pin_changed_at');
            $table->timestamp('phone_verified_at')->nullable()->after('self_registered_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['self_registered_at', 'phone_verified_at']);
        });
    }
};
