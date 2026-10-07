<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cloths', 'name')) {
            Schema::table('cloths', function (Blueprint $table) {
                $table->string('name', 100)->nullable()->after('id');
            });
        }

        DB::table('cloths')->whereNull('name')->orWhere('name', '')->orderBy('id')
            ->eachById(fn ($cloth) => DB::table('cloths')->where('id', $cloth->id)->update([
                'name' => 'سیٹ '.$cloth->id,
            ]));
    }

    public function down(): void
    {
        if (Schema::hasColumn('cloths', 'name')) {
            Schema::table('cloths', fn (Blueprint $table) => $table->dropColumn('name'));
        }
    }
};
