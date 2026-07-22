<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('mobile_pin')->nullable()->after('phone_number1');
            $table->unsignedTinyInteger('pin_failed_attempts')->default(0)->after('mobile_pin');
            $table->timestamp('pin_locked_until')->nullable()->after('pin_failed_attempts');
            $table->timestamp('pin_changed_at')->nullable()->after('pin_locked_until');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['mobile_pin', 'pin_failed_attempts', 'pin_locked_until', 'pin_changed_at']);
        });
    }
};
