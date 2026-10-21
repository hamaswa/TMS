<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How stock is counted: meter (cut from a roll) or suit (a packed women's 2/3-piece suit sold whole).
        if (! Schema::hasColumn('cloth_categories', 'stock_unit')) {
            Schema::table('cloth_categories', function (Blueprint $table) {
                $table->string('stock_unit', 10)->default('meter')->after('name_ur');
            });
        }

        // Copied from the category when a cloth is created, so later category edits never reinterpret its stock.
        if (! Schema::hasColumn('cloths', 'stock_unit')) {
            Schema::table('cloths', function (Blueprint $table) {
                $table->string('stock_unit', 10)->default('meter')->after('cloth_category_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cloths', 'stock_unit')) {
            Schema::table('cloths', fn (Blueprint $table) => $table->dropColumn('stock_unit'));
        }
        if (Schema::hasColumn('cloth_categories', 'stock_unit')) {
            Schema::table('cloth_categories', fn (Blueprint $table) => $table->dropColumn('stock_unit'));
        }
    }
};
