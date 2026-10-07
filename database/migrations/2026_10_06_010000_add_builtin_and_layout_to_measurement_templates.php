<?php

use App\Services\MeasurementService;
use App\Services\TailoringOptionDefaultsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_templates', function (Blueprint $table) {
            $table->boolean('is_builtin')->default(false)->after('custom_field_ids');
            $table->json('field_layout')->nullable()->after('is_builtin');
        });

        $templates = DB::table('measurement_templates')
            ->where('name', TailoringOptionDefaultsService::DEFAULT_MEASUREMENT_TEMPLATE_NAME)
            ->orderBy('id')
            ->get();

        foreach ($templates as $template) {
            DB::table('measurement_templates')
                ->where('user_id', $template->user_id)
                ->update(['is_default' => false]);

            DB::table('measurement_templates')->where('id', $template->id)->update([
                'system_fields' => json_encode(array_keys(MeasurementService::SYSTEM_FIELDS), JSON_UNESCAPED_UNICODE),
                'is_builtin' => true,
                'is_default' => true,
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('measurement_templates', function (Blueprint $table) {
            $table->dropColumn(['is_builtin', 'field_layout']);
        });
    }
};
