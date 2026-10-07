<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('layout_columns')->default(2)->after('field_layout');
        });
    }

    public function down(): void
    {
        Schema::table('measurement_templates', function (Blueprint $table) {
            $table->dropColumn('layout_columns');
        });
    }
};
