<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('tailoring_access')->default(true)->after('password');
            $table->boolean('clothing_access')->default(true)->after('tailoring_access');
        });

        DB::table('users')->update(['tailoring_access' => true, 'clothing_access' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tailoring_access', 'clothing_access']);
        });
    }
};
