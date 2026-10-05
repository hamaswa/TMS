<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->char('theme_background_color', 7)->default('#f5f7f6')->after('theme_accent_color');
            $table->char('theme_surface_color', 7)->default('#ffffff')->after('theme_background_color');
            $table->char('theme_text_color', 7)->default('#17372e')->after('theme_surface_color');
            $table->string('font_style', 20)->default('modern')->after('theme_text_color');
            $table->string('hero_layout', 20)->default('overlay')->after('font_style');
            $table->string('hero_alignment', 10)->default('start')->after('hero_layout');
            $table->unsignedTinyInteger('hero_overlay_strength')->default(70)->after('hero_alignment');
            $table->string('corner_style', 20)->default('soft')->after('hero_overlay_strength');
            $table->unsignedTinyInteger('product_columns')->default(3)->after('corner_style');
            $table->boolean('show_benefits')->default(true)->after('show_featured_products');
            $table->boolean('show_services')->default(true)->after('show_benefits');
            $table->boolean('show_about')->default(true)->after('show_services');
        });
    }

    public function down(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn([
                'theme_background_color', 'theme_surface_color', 'theme_text_color',
                'font_style', 'hero_layout', 'hero_alignment', 'hero_overlay_strength',
                'corner_style', 'product_columns', 'show_benefits', 'show_services', 'show_about',
            ]);
        });
    }
};
