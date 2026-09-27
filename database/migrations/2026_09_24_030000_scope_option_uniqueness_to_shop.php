<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('options'))->pluck('name');

        Schema::table('options', function (Blueprint $table) use ($indexes) {
            if ($indexes->contains('options_slug_unique')) {
                $table->dropUnique('options_slug_unique');
            }

            if ($indexes->contains('options_name_unique')) {
                $table->dropUnique('options_name_unique');
            }
        });

        $indexes = collect(Schema::getIndexes('options'))->pluck('name');
        if (! $indexes->contains('options_owner_type_name_unique')) {
            Schema::table('options', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'option_id', 'Name'],
                    'options_owner_type_name_unique'
                );
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('options'))->pluck('name');

        if ($indexes->contains('options_owner_type_name_unique')) {
            Schema::table('options', function (Blueprint $table) {
                $table->dropUnique('options_owner_type_name_unique');
            });
        }
    }
};
