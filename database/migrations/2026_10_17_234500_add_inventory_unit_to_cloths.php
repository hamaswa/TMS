<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cloths', function (Blueprint $table) {
            $table->string('inventory_unit', 16)->default('meter');
        });
    }

    public function down(): void
    {
        Schema::table('cloths', function (Blueprint $table) {
            $table->dropColumn('inventory_unit');
        });
    }
};
