<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cloths', 'cloth_category_id')) {
            Schema::table('cloths', function (Blueprint $table) {
                // The app blocks deleting a category that still has cloths; the DB only keeps the link safe.
                $table->foreignId('cloth_category_id')->nullable()->after('cloth_brand_id')
                    ->constrained('cloth_categories')->nullOnDelete();
            });
        }

        // Existing stock was all men's cloth, so every shop with cloths gets a "Men" category.
        $ownerIds = DB::table('cloths')->whereNotNull('user_id')->whereNull('cloth_category_id')
            ->distinct()->pluck('user_id');

        foreach ($ownerIds as $ownerId) {
            $categoryId = DB::table('cloth_categories')->where('user_id', $ownerId)
                ->where('slug', 'men')->value('id');

            if (! $categoryId) {
                $categoryId = DB::table('cloth_categories')->insertGetId([
                    'user_id' => $ownerId,
                    'parent_id' => null,
                    'name' => 'Men',
                    'name_ur' => 'مردانہ',
                    'slug' => 'men',
                    'sort_order' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('cloths')->where('user_id', $ownerId)->whereNull('cloth_category_id')
                ->update(['cloth_category_id' => $categoryId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cloths', 'cloth_category_id')) {
            Schema::table('cloths', fn (Blueprint $table) => $table->dropConstrainedForeignId('cloth_category_id'));
        }
    }
};
