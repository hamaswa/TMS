<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['parent_id', 'deleted_at'], 'customers_parent_deleted_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('sub_customer', 'orders_sub_customer_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['userId', 'customerId', 'deleted_at'],
                'transactions_owner_customer_deleted_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_owner_customer_deleted_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_sub_customer_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_parent_deleted_index');
        });
    }
};
