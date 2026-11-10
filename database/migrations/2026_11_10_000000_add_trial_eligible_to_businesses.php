<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // New clients start their first plan with a free trial; shops that existed before this do not.
            $table->boolean('trial_eligible')->default(true);
        });

        DB::table('businesses')->update(['trial_eligible' => false]);
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('trial_eligible');
        });
    }
};
