<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('businesses', 'is_demo')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('status')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('businesses', 'is_demo')) {
            Schema::table('businesses', fn (Blueprint $table) => $table->dropColumn('is_demo'));
        }
    }
};
