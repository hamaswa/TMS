<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy installs created cloth_colors before the repair migration, so
     * length / average_unit_cost can be text columns holding values like ''
     * or '5 meter', which the decimal casts on ClothColor cannot read.
     */
    public function up(): void
    {
        if (! Schema::hasTable('cloth_colors')) {
            return;
        }

        foreach (['length' => 2, 'average_unit_cost' => 4] as $column => $scale) {
            if (! Schema::hasColumn('cloth_colors', $column)) {
                continue;
            }

            DB::table('cloth_colors')->select('id', $column)->orderBy('id')->chunkById(500, function ($rows) use ($column) {
                foreach ($rows as $row) {
                    $raw = $row->{$column};
                    $clean = str_replace([',', ' '], '', trim((string) $raw));
                    $value = is_numeric($clean) ? round((float) $clean, 4) : 0;

                    if ((string) $raw !== (string) $value) {
                        DB::table('cloth_colors')->where('id', $row->id)->update([$column => $value]);
                    }
                }
            });

            Schema::table('cloth_colors', function (Blueprint $table) use ($column, $scale) {
                $table->decimal($column, $scale === 2 ? 12 : 14, $scale)->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        // Values were normalised in place; nothing to restore.
    }
};
