<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cloths', 'color_tracking_mode')) {
            Schema::table('cloths', function (Blueprint $table) {
                $table->string('color_tracking_mode', 20)->default('none')->after('sale_price');
            });

            DB::table('cloths')->whereExists(function ($query) {
                $query->selectRaw('1')->from('cloth_colors')
                    ->whereColumn('cloth_colors.cloth_id', 'cloths.id')
                    ->whereNotIn(DB::raw('LOWER(TRIM(cloth_colors.color))'), ['عام', 'general', 'unspecified']);
            })->update(['color_tracking_mode' => 'per_color']);
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'is_walk_in')) {
                $table->boolean('is_walk_in')->default(false)->after('parent_id')->index();
            }
            if (! Schema::hasColumn('customers', 'first_sale_at')) {
                $table->timestamp('first_sale_at')->nullable()->after('is_walk_in')->index();
            }
            if (! Schema::hasColumn('customers', 'acquisition_source')) {
                $table->string('acquisition_source', 40)->nullable()->after('first_sale_at');
            }
        });

        DB::table('customers')
            ->whereRaw("LOWER(TRIM(name)) IN ('walk-in customer', 'walk in customer', 'walk-in', 'walk in')")
            ->update(['is_walk_in' => true]);

        if (Schema::hasTable('sale_stocks')) {
            DB::table('sale_stocks')->select('c_id', DB::raw('MIN(sellDate) AS first_sale_at'))
                ->whereNotNull('c_id')->groupBy('c_id')->orderBy('c_id')->chunk(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('customers')->where('id', $row->c_id)->where('is_walk_in', false)->update([
                            'first_sale_at' => $row->first_sale_at,
                            'acquisition_source' => 'existing_data',
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['is_walk_in', 'first_sale_at', 'acquisition_source'],
                fn (string $column) => Schema::hasColumn('customers', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        if (Schema::hasColumn('cloths', 'color_tracking_mode')) {
            Schema::table('cloths', fn (Blueprint $table) => $table->dropColumn('color_tracking_mode'));
        }
    }
};
