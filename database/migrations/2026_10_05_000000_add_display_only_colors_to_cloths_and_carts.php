<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cloths', 'display_colors')) {
            Schema::table('cloths', function (Blueprint $table) {
                $table->json('display_colors')->nullable()->after('color_tracking_mode');
            });
        }

        if (! Schema::hasColumn('storefront_cart_items', 'selected_color')) {
            // MySQL may use the composite unique index as the supporting index
            // for this foreign key. Give the FK its own stable index before the
            // unique key is replaced with the colour-aware version.
            if (! Schema::hasIndex('storefront_cart_items', 'storefront_cart_items_cart_id_index')) {
                Schema::table('storefront_cart_items', function (Blueprint $table) {
                    $table->index('storefront_cart_id', 'storefront_cart_items_cart_id_index');
                });
            }

            Schema::table('storefront_cart_items', function (Blueprint $table) {
                $table->dropUnique('storefront_cart_item_unique');
                $table->string('selected_color', 100)->default('')->after('cloth_color_id');
                $table->unique(
                    ['storefront_cart_id', 'clothing_listing_id', 'cloth_color_id', 'selected_color'],
                    'storefront_cart_item_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('storefront_cart_items', 'selected_color')) {
            Schema::table('storefront_cart_items', function (Blueprint $table) {
                $table->dropUnique('storefront_cart_item_unique');
                $table->dropColumn('selected_color');
                $table->unique(
                    ['storefront_cart_id', 'clothing_listing_id', 'cloth_color_id'],
                    'storefront_cart_item_unique'
                );
            });
        }

        if (Schema::hasColumn('cloths', 'display_colors')) {
            Schema::table('cloths', fn (Blueprint $table) => $table->dropColumn('display_colors'));
        }
    }
};
