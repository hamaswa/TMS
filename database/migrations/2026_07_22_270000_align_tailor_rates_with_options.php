<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tailorsalaries', function (Blueprint $table) {
            $table->foreignId('options_id')->nullable()->after('tailor_id')
                ->constrained('options')->nullOnDelete();
            $table->string('type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tailorsalaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('options_id');
            $table->string('type')->nullable(false)->change();
        });
    }
};
