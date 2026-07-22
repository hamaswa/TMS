<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cloth_colors', 'average_unit_cost')) {
            Schema::table('cloth_colors', function (Blueprint $table) {
                $table->decimal('average_unit_cost', 14, 4)->default(0);
            });
            foreach (DB::table('cloth_colors')->select('id', 'cloth_id')->get() as $color) {
                $price = DB::table('cloths')->where('id', $color->cloth_id)->value('price') ?? 0;
                DB::table('cloth_colors')->where('id', $color->id)->update(['average_unit_cost' => $price]);
            }
        }
        if (! Schema::hasColumn('inventory_movements', 'balance_after')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->decimal('balance_after', 14, 2)->nullable()->after('quantity');
            });
        }
        Schema::table('sale_stocks', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_stocks', 'cloth_type_id')) $table->unsignedBigInteger('cloth_type_id')->nullable();
            if (! Schema::hasColumn('sale_stocks', 'cloth_brand_id')) $table->unsignedBigInteger('cloth_brand_id')->nullable();
            if (! Schema::hasColumn('sale_stocks', 'color')) $table->string('color', 100)->nullable();
            if (! Schema::hasColumn('sale_stocks', 'c_id')) $table->unsignedBigInteger('c_id')->nullable();
            if (! Schema::hasColumn('sale_stocks', 'cloth_id')) $table->unsignedBigInteger('cloth_id')->nullable();
            if (! Schema::hasColumn('sale_stocks', 'cloth_color_id')) $table->unsignedBigInteger('cloth_color_id')->nullable();
            if (! Schema::hasColumn('sale_stocks', 'cost_per_meter')) $table->decimal('cost_per_meter', 14, 4)->default(0);
            if (! Schema::hasColumn('sale_stocks', 'cost_total')) $table->decimal('cost_total', 14, 2)->default(0);
        });
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'tailorId')) $table->unsignedBigInteger('tailorId')->nullable();
            if (! Schema::hasColumn('transactions', 'comment')) $table->text('comment')->nullable();
            if (! Schema::hasColumn('transactions', 'Order_type')) $table->string('Order_type')->nullable();
            if (! Schema::hasColumn('transactions', 'sale_id')) $table->unsignedBigInteger('sale_id')->nullable();
        });
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'shop_slug')) $table->string('shop_slug')->nullable();
            if (! Schema::hasColumn('settings', 'contact')) $table->string('contact')->nullable();
        });
    }

    public function down(): void
    {
        // Keep costing history when rolling back a legacy installation.
    }
};
