<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['userId', 'returnDate'], 'orders_user_return_idx');
            $table->index(['userId', 'status', 'returnDate'], 'orders_user_status_return_idx');
            $table->index(['userId', 'tailorId', 'status'], 'orders_user_tailor_status_idx');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['id', 'user_id'], 'customers_id_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_return_idx');
            $table->dropIndex('orders_user_status_return_idx');
            $table->dropIndex('orders_user_tailor_status_idx');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_id_user_idx');
        });
    }
};
