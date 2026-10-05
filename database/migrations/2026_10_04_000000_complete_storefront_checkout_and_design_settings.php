<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->string('hero_title_ur', 180)->nullable()->after('cover_path');
            $table->string('hero_title_en', 180)->nullable()->after('hero_title_ur');
            $table->string('hero_text_ur', 500)->nullable()->after('hero_title_en');
            $table->string('hero_text_en', 500)->nullable()->after('hero_text_ur');
            $table->string('announcement_ur', 180)->nullable()->after('hero_text_en');
            $table->string('announcement_en', 180)->nullable()->after('announcement_ur');
            $table->char('theme_primary_color', 7)->default('#126b4f')->after('announcement_en');
            $table->char('theme_accent_color', 7)->default('#d98a12')->after('theme_primary_color');
            $table->boolean('show_nav_categories')->default(true)->after('theme_accent_color');
            $table->boolean('show_nav_brands')->default(true)->after('show_nav_categories');
            $table->boolean('show_featured_products')->default(true)->after('show_nav_brands');
            $table->json('navigation_links')->nullable()->after('show_featured_products');
            $table->string('whatsapp_number', 30)->nullable()->after('public_phone');
        });

        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dateTime('payment_collected_at')->nullable()->after('payment_rejected_at');
            $table->foreignId('payment_collected_by_user_id')->nullable()
                ->after('payment_collected_at')->constrained('users')->nullOnDelete();
            $table->string('payment_collection_reference', 100)->nullable()
                ->after('payment_collected_by_user_id');
            $table->text('payment_collection_notes')->nullable()
                ->after('payment_collection_reference');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dropForeign(['payment_collected_by_user_id']);
            $table->dropColumn([
                'payment_collected_at',
                'payment_collected_by_user_id',
                'payment_collection_reference',
                'payment_collection_notes',
            ]);
        });

        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn([
                'hero_title_ur', 'hero_title_en', 'hero_text_ur', 'hero_text_en',
                'announcement_ur', 'announcement_en', 'theme_primary_color', 'theme_accent_color',
                'show_nav_categories', 'show_nav_brands', 'show_featured_products',
                'navigation_links', 'whatsapp_number',
            ]);
        });
    }
};
