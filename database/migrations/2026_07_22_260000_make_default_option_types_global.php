<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('option_types', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        DB::table('option_types')
            ->whereIn('slug', [
                'add_sewing_type', 'add_shirt_button_type', 'add_neck_type',
                'add_sleeve_opening_type', 'add_pocket_type', 'add_button_type',
                'plate_type', 'add_daaman_type',
            ])
            ->update(['user_id' => null]);
    }

    public function down(): void
    {
        DB::table('option_types')->whereNull('user_id')->update(['user_id' => 1]);

        Schema::table('option_types', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
