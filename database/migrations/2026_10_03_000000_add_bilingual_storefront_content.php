<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->string('display_name_ur', 150)->nullable()->after('display_name');
            $table->string('display_name_en', 150)->nullable()->after('display_name_ur');
            $table->string('tagline_ur', 180)->nullable()->after('tagline');
            $table->string('tagline_en', 180)->nullable()->after('tagline_ur');
            $table->text('description_ur')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ur');
            $table->text('address_ur')->nullable()->after('address');
            $table->text('address_en')->nullable()->after('address_ur');
            $table->string('city_ur', 100)->nullable()->after('city');
            $table->string('city_en', 100)->nullable()->after('city_ur');
        });

        Schema::table('storefront_clothing_listings', function (Blueprint $table) {
            $table->string('public_name_ur', 180)->nullable()->after('public_name');
            $table->string('public_name_en', 180)->nullable()->after('public_name_ur');
            $table->text('description_ur')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ur');
        });

        Schema::table('storefront_tailoring_services', function (Blueprint $table) {
            $table->string('name_ur', 180)->nullable()->after('name');
            $table->string('name_en', 180)->nullable()->after('name_ur');
            $table->text('description_ur')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ur');
        });

        Schema::table('cloth_types', function (Blueprint $table) {
            $table->string('name_ur')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ur');
        });

        Schema::table('cloth_colors', function (Blueprint $table) {
            $table->string('color_ur')->nullable()->after('color');
            $table->string('color_en')->nullable()->after('color_ur');
        });

        DB::table('storefronts')->orderBy('id')->each(function ($storefront) {
            $locale = $storefront->default_locale === 'en' ? 'en' : 'ur';
            DB::table('storefronts')->where('id', $storefront->id)->update([
                'display_name_'.$locale => $storefront->display_name,
                'tagline_'.$locale => $storefront->tagline,
                'description_'.$locale => $storefront->description,
                'address_'.$locale => $storefront->address,
                'city_'.$locale => $storefront->city,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('cloth_colors', fn (Blueprint $table) => $table->dropColumn(['color_ur', 'color_en']));
        Schema::table('cloth_types', fn (Blueprint $table) => $table->dropColumn(['name_ur', 'name_en']));
        Schema::table('storefront_tailoring_services', fn (Blueprint $table) => $table->dropColumn(['name_ur', 'name_en', 'description_ur', 'description_en']));
        Schema::table('storefront_clothing_listings', fn (Blueprint $table) => $table->dropColumn(['public_name_ur', 'public_name_en', 'description_ur', 'description_en']));
        Schema::table('storefronts', fn (Blueprint $table) => $table->dropColumn([
            'display_name_ur', 'display_name_en', 'tagline_ur', 'tagline_en',
            'description_ur', 'description_en', 'address_ur', 'address_en', 'city_ur', 'city_en',
        ]));
    }
};
