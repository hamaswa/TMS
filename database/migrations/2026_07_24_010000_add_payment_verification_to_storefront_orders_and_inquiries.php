<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->string('payment_verification_status', 30)->default('not_required')
                ->after('payment_reference')->index();
            $table->text('payment_verification_notes')->nullable()
                ->after('payment_verification_status');
            $table->foreignId('payment_verified_by_user_id')->nullable()
                ->after('payment_verification_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable()
                ->after('payment_verified_by_user_id');
            $table->timestamp('payment_rejected_at')->nullable()
                ->after('payment_verified_at');
        });

        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->string('payment_verification_status', 30)->default('not_required')
                ->after('payment_reference')->index();
            $table->text('payment_verification_notes')->nullable()
                ->after('payment_verification_status');
            $table->foreignId('payment_verified_by_user_id')->nullable()
                ->after('payment_verification_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable()
                ->after('payment_verified_by_user_id');
            $table->timestamp('payment_rejected_at')->nullable()
                ->after('payment_verified_at');
        });

        DB::table('storefront_orders')
            ->where('payment_method', 'easypaisa')
            ->update(['payment_verification_status' => 'pending']);
        DB::table('storefront_inquiries')
            ->where('payment_method', 'easypaisa')
            ->update(['payment_verification_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by_user_id']);
            $table->dropIndex(['payment_verification_status']);
            $table->dropColumn([
                'payment_verification_status',
                'payment_verification_notes',
                'payment_verified_by_user_id',
                'payment_verified_at',
                'payment_rejected_at',
            ]);
        });

        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by_user_id']);
            $table->dropIndex(['payment_verification_status']);
            $table->dropColumn([
                'payment_verification_status',
                'payment_verification_notes',
                'payment_verified_by_user_id',
                'payment_verified_at',
                'payment_rejected_at',
            ]);
        });
    }
};
