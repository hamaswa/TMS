<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Reports list a shop's orders by date range, newest first.
        if (! Schema::hasIndex('orders', 'orders_user_created_idx')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['userId', 'created_at'], 'orders_user_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('orders', 'orders_user_created_idx')) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropIndex('orders_user_created_idx'));
        }
    }
};
