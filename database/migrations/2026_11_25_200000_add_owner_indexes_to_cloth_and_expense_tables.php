<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * These tables are always read for one shop (user_id = the business owner) but had no index
 * starting with user_id, so every lookup scanned every shop's rows.
 */
return new class extends Migration {
    private const INDEXES = [
        // Stock list, cloth pickers and stock reports.
        'cloths' => ['cloths_user_deleted_idx' => ['user_id', 'deleted_at']],
        // Brand and type dropdowns on the stock, purchase and counter order screens.
        'cloth_brands' => ['cloth_brands_user_idx' => ['user_id']],
        'cloth_types' => ['cloth_types_user_idx' => ['user_id']],
        // Financial report and earnings sums by date range.
        'expenses' => ['expenses_user_date_idx' => ['user_id', 'expense_date']],
        'daliy_expenses' => ['daliy_expenses_user_created_idx' => ['user_id', 'created_at']],
        'workers' => ['workers_user_date_idx' => ['user_id', 'dateentered']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
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
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($tableName, $name)) {
                    Schema::table($tableName, fn (Blueprint $table) => $table->dropIndex($name));
                }
            }
        }
    }
};
