<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_templates', function (Blueprint $table) {
            // Ordered form sections, each [title, columns]; empty means one section using layout_columns.
            $table->json('layout_sections')->nullable()->after('layout_columns');
        });
    }

    public function down(): void
    {
        Schema::table('measurement_templates', function (Blueprint $table) {
            $table->dropColumn('layout_sections');
        });
    }
};
