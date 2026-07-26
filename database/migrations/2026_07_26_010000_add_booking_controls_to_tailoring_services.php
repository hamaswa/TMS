<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_tailoring_services', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('is_published');
            $table->boolean('accepts_inquiries')->default(true)->after('is_available');
            $table->string('deposit_type', 20)->default('none')->after('estimated_days');
            $table->decimal('deposit_value', 12, 2)->nullable()->after('deposit_type');
            $table->json('measurement_methods')->nullable()->after('deposit_value');
            $table->unsignedSmallInteger('weekly_booking_limit')->nullable()->after('measurement_methods');
        });

        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->string('measurement_method', 30)->nullable()->after('preferred_date');
            $table->string('service_deposit_type', 20)->nullable()->after('measurement_method');
            $table->decimal('service_deposit_value', 12, 2)->nullable()->after('service_deposit_type');
            $table->decimal('service_deposit_amount', 12, 2)->nullable()->after('service_deposit_value');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->dropColumn([
                'measurement_method',
                'service_deposit_type',
                'service_deposit_value',
                'service_deposit_amount',
            ]);
        });

        Schema::table('storefront_tailoring_services', function (Blueprint $table) {
            $table->dropColumn([
                'is_available',
                'accepts_inquiries',
                'deposit_type',
                'deposit_value',
                'measurement_methods',
                'weekly_booking_limit',
            ]);
        });
    }
};
