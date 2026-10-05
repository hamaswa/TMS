<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->string('header_layout', 20)->default('menu_first')->after('product_columns');
            $table->boolean('sticky_header')->default(true)->after('header_layout');
            $table->string('hero_height', 20)->default('standard')->after('hero_overlay_strength');
            $table->string('hero_media_type', 20)->default('image')->after('hero_height');
            $table->string('hero_media_fit', 20)->default('cover')->after('hero_media_type');
            $table->string('hero_media_position', 20)->default('center')->after('hero_media_fit');
            $table->string('hero_text_animation', 20)->default('fade_up')->after('hero_media_position');
            $table->string('hero_video_path')->nullable()->after('cover_path');
            $table->string('hero_video_poster_path')->nullable()->after('hero_video_path');
            $table->string('footer_style', 20)->default('columns')->after('navigation_links');
            $table->string('footer_text_ur', 500)->nullable()->after('footer_style');
            $table->string('footer_text_en', 500)->nullable()->after('footer_text_ur');
            $table->string('facebook_url', 500)->nullable()->after('footer_text_en');
            $table->string('instagram_url', 500)->nullable()->after('facebook_url');
            $table->string('tiktok_url', 500)->nullable()->after('instagram_url');
            $table->string('youtube_url', 500)->nullable()->after('tiktok_url');
        });
    }

    public function down(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn([
                'header_layout', 'sticky_header', 'hero_height', 'hero_media_type',
                'hero_media_fit', 'hero_media_position', 'hero_text_animation',
                'hero_video_path', 'hero_video_poster_path', 'footer_style',
                'footer_text_ur', 'footer_text_en', 'facebook_url', 'instagram_url',
                'tiktok_url', 'youtube_url',
            ]);
        });
    }
};
