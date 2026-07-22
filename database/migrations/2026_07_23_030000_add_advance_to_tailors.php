<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tailors', function (Blueprint $table) {
            $table->decimal('advance', 14, 2)->default(0)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('tailors', function (Blueprint $table) {
            $table->dropColumn('advance');
        });
    }
};
