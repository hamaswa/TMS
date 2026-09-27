<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloths', function (Blueprint $table) {
            $table->string('set_code', 40)->nullable()->unique()->after('id');
        });

        DB::table('cloths')->whereNull('set_code')->orderBy('id')->eachById(function ($cloth) {
            DB::table('cloths')->where('id', $cloth->id)->update([
                'set_code' => 'BNS-'.str_pad((string) ($cloth->user_id ?? 0), 4, '0', STR_PAD_LEFT).'-'.str_pad((string) $cloth->id, 6, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(6)),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('cloths', function (Blueprint $table) {
            $table->dropUnique(['set_code']);
            $table->dropColumn('set_code');
        });
    }
};
