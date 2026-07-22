<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('employee_active');
            $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
            $table->timestamp('password_reset_at')->nullable()->after('password_changed_at');
            $table->foreignId('password_reset_by_user_id')->nullable()->after('password_reset_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('password_reset_by_user_id');
            $table->dropColumn(['must_change_password', 'password_changed_at', 'password_reset_at']);
        });
    }
};
