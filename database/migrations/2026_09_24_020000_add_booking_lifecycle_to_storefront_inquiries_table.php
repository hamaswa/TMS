<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('tailoring_service_id')->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->unique()->after('customer_id')->constrained('orders')->nullOnDelete();
            $table->string('booking_pin_hash')->nullable()->after('phone');
            $table->unsignedSmallInteger('suit_quantity')->default(1)->after('measurement_method');
            $table->decimal('estimated_price', 14, 2)->nullable()->after('suit_quantity');
            $table->decimal('payment_claimed_amount', 14, 2)->default(0)->after('payment_reference');
            $table->decimal('final_price', 14, 2)->nullable()->after('service_deposit_amount');
            $table->date('promised_date')->nullable()->after('final_price');
            $table->foreignId('confirmed_by_user_id')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by_user_id')->nullable()->after('confirmed_by_user_id')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('rejected_by_user_id');
            $table->timestamp('confirmed_at')->nullable()->after('contacted_at');
            $table->timestamp('rejected_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['order_id']);
            $table->dropForeign(['confirmed_by_user_id']);
            $table->dropForeign(['rejected_by_user_id']);
            $table->dropUnique(['order_id']);
            $table->dropColumn([
                'customer_id', 'order_id', 'booking_pin_hash', 'suit_quantity', 'estimated_price',
                'payment_claimed_amount', 'final_price', 'promised_date', 'confirmed_by_user_id',
                'rejected_by_user_id', 'rejection_reason', 'confirmed_at', 'rejected_at',
            ]);
        });
    }
};
