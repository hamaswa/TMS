<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->string('payment_method', 30)->default('unpaid')->after('customer_note')->index();
            $table->string('payment_sender_phone', 50)->nullable()->after('payment_method');
            $table->string('payment_reference', 100)->nullable()->after('payment_sender_phone');
        });

        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->string('payment_method', 30)->default('unpaid')->after('message')->index();
            $table->string('payment_sender_phone', 50)->nullable()->after('payment_method');
            $table->string('payment_reference', 100)->nullable()->after('payment_sender_phone');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropColumn(['payment_method', 'payment_sender_phone', 'payment_reference']);
        });

        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropColumn(['payment_method', 'payment_sender_phone', 'payment_reference']);
        });
    }
};
