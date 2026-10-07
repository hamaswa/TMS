<?php

use App\Services\MeasurementService;
use App\Services\TailoringOptionDefaultsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('measurement_templates')) {
            return;
        }

        $ownerIds = collect();
        if (Schema::hasTable('businesses')) {
            $ownerIds = $ownerIds->merge(
                DB::table('businesses')->where('tailoring_enabled', true)->pluck('owner_user_id')
            );
        }
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'tailoring_access')) {
            $ownerIds = $ownerIds->merge(
                DB::table('users')->where('tailoring_access', true)
                    ->when(Schema::hasColumn('users', 'is_business_owner'), fn ($query) => $query->where('is_business_owner', true))
                    ->pluck('id')
            );
        }

        foreach ($ownerIds->filter()->unique() as $ownerId) {
            $hasDefault = DB::table('measurement_templates')->where('user_id', $ownerId)
                ->where('is_active', true)->where('is_default', true)->exists();
            $template = DB::table('measurement_templates')->where('user_id', $ownerId)
                ->where('name', TailoringOptionDefaultsService::DEFAULT_MEASUREMENT_TEMPLATE_NAME)->first();

            if (! $template) {
                DB::table('measurement_templates')->insert([
                    'user_id' => $ownerId,
                    'name' => TailoringOptionDefaultsService::DEFAULT_MEASUREMENT_TEMPLATE_NAME,
                    'description' => 'قمیض، شلوار اور متعلقہ سلائی کی پسند کے لیے مکمل بنیادی ٹیمپلیٹ',
                    'system_fields' => json_encode(array_keys(MeasurementService::SYSTEM_FIELDS), JSON_UNESCAPED_UNICODE),
                    'custom_field_ids' => json_encode([]),
                    'is_default' => ! $hasDefault,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif (! $hasDefault) {
                DB::table('measurement_templates')->where('id', $template->id)->update([
                    'is_default' => true,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Data-only migration: preserve templates that shops may already have used.
    }
};
