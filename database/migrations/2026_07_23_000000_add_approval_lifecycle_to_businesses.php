<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('clothing_enabled');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable()->after('approved_by_user_id');
            $table->foreignId('status_changed_by_user_id')->nullable()->after('status_changed_at')->constrained('users')->nullOnDelete();
            $table->text('status_reason')->nullable()->after('status_changed_by_user_id');
            $table->index('status');
        });

        DB::table('businesses')->update([
            'status' => 'active',
            'approved_at' => now(),
            'status_changed_at' => now(),
        ]);

        Schema::create('business_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_status_histories');
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['status_changed_by_user_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status', 'approved_at', 'approved_by_user_id',
                'status_changed_at', 'status_changed_by_user_id', 'status_reason',
            ]);
        });
    }
};
