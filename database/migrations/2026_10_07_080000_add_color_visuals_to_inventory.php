<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cloths', 'display_color_codes')) {
            Schema::table('cloths', function (Blueprint $table) {
                $table->json('display_color_codes')->nullable()->after('display_colors');
            });
        }

        if (! Schema::hasColumn('cloth_colors', 'color_hex')) {
            Schema::table('cloth_colors', function (Blueprint $table) {
                $table->string('color_hex', 7)->nullable()->after('color');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cloth_colors', 'color_hex')) {
            Schema::table('cloth_colors', fn (Blueprint $table) => $table->dropColumn('color_hex'));
        }
        if (Schema::hasColumn('cloths', 'display_color_codes')) {
            Schema::table('cloths', fn (Blueprint $table) => $table->dropColumn('display_color_codes'));
        }
    }
};
