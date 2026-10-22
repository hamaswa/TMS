<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const INDEXES = [
        // Dashboards sum a shop's cloth sales by day and list the latest ones.
        'sale_stocks' => ['sale_stocks_user_sell_date_idx' => ['user_id', 'sellDate']],
        'orders' => [
            // A customer's orders, and the tailor portal's orders by date.
            'orders_customer_idx' => ['customerId'],
            'orders_tailor_created_idx' => ['tailorId', 'created_at'],
        ],
        'transactions' => [
            // Payments of one order or sale, and a tailor's ledger within a shop.
            'transactions_order_idx' => ['orderId'],
            'transactions_sale_idx' => ['sale_id'],
            'transactions_owner_tailor_idx' => ['userId', 'tailorId'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (! Schema::hasIndex($tableName, $name)) {
                    Schema::table($tableName, fn (Blueprint $table) => $table->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($tableName, $name)) {
                    Schema::table($tableName, fn (Blueprint $table) => $table->dropIndex($name));
                }
            }
        }
    }
};
