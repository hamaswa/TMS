<?php

use App\Services\TailoringOptionDefaultsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('option_source', 20)->default('default')->after('clothing_access');
        });

        Schema::table('options', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('Name');
        });

        // A shop's own choice may share a name with a seeded one it no longer sees.
        Schema::table('options', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'measurement_template_id', 'option_id', 'Name', 'is_default'],
                'options_owner_template_type_name_source_unique'
            );
        });
        Schema::table('options', fn (Blueprint $table) => $table->dropUnique('options_owner_template_type_name_unique'));

        // Seeded rows carry these slug prefixes until renamed.
        DB::table('options')
            ->where(fn ($query) => $query->where('slug', 'like', 'default-%')->orWhere('slug', 'like', 'sewing-type-%'))
            ->update(['is_default' => true]);

        // Seeding skipped names a shop had already typed, so those rows stand in for the seeded ones.
        foreach (app(TailoringOptionDefaultsService::class)->defaultOptionTypes() as $slug => $defaults) {
            $typeId = DB::table('option_types')->whereNull('user_id')->where('slug', $slug)->value('id');
            if ($typeId) {
                DB::table('options')->where('option_id', $typeId)
                    ->whereIn('Name', $defaults['choices'])
                    ->update(['is_default' => true]);
            }
        }
    }

    public function down(): void
    {
        // The narrower unique index cannot return while a name exists in both lists.
        DB::table('options')->where('is_default', false)
            ->whereExists(fn ($query) => $query->select(DB::raw(1))->from('options as seeded')
                ->whereColumn('seeded.user_id', 'options.user_id')
                ->whereColumn('seeded.measurement_template_id', 'options.measurement_template_id')
                ->whereColumn('seeded.option_id', 'options.option_id')
                ->whereColumn('seeded.Name', 'options.Name')
                ->where('seeded.is_default', true))
            ->get(['id'])->pluck('id')->chunk(500)
            ->each(fn ($ids) => DB::table('options')->whereIn('id', $ids)->delete());

        Schema::table('options', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'measurement_template_id', 'option_id', 'Name'],
                'options_owner_template_type_name_unique'
            );
        });
        Schema::table('options', function (Blueprint $table) {
            $table->dropUnique('options_owner_template_type_name_source_unique');
            $table->dropColumn('is_default');
        });

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('option_source'));
    }
};
