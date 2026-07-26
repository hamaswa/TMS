<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_clothing_listings', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('is_published');
            $table->boolean('online_order_enabled')->default(true)->after('is_available');
            $table->decimal('minimum_order_quantity', 10, 2)->default(0.25)->after('online_order_enabled');
            $table->decimal('maximum_order_quantity', 10, 2)->nullable()->after('minimum_order_quantity');
            $table->decimal('order_increment', 10, 2)->default(0.25)->after('maximum_order_quantity');
            $table->boolean('preorder_enabled')->default(false)->after('order_increment');
            $table->unsignedSmallInteger('preorder_lead_days')->nullable()->after('preorder_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_clothing_listings', function (Blueprint $table) {
            $table->dropColumn([
                'is_available',
                'online_order_enabled',
                'minimum_order_quantity',
                'maximum_order_quantity',
                'order_increment',
                'preorder_enabled',
                'preorder_lead_days',
            ]);
        });
    }
};
