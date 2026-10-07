<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_fields', function (Blueprint $table) {
            $table->foreignId('measurement_template_id')->nullable()->after('user_id')
                ->constrained('measurement_templates')->nullOnDelete();
        });
        Schema::table('options', function (Blueprint $table) {
            $table->foreignId('measurement_template_id')->nullable()->after('user_id')
                ->constrained('measurement_templates')->cascadeOnDelete();
        });

        $optionIndexes = collect(Schema::getIndexes('options'))->pluck('name');
        Schema::table('options', function (Blueprint $table) use ($optionIndexes) {
            if ($optionIndexes->contains('options_owner_type_name_unique')) {
                $table->dropUnique('options_owner_type_name_unique');
            }
            $table->unique(
                ['user_id', 'measurement_template_id', 'option_id', 'Name'],
                'options_owner_template_type_name_unique'
            );
        });

        $templates = DB::table('measurement_templates')->orderBy('user_id')->orderByDesc('is_default')->orderBy('id')->get();
        $claimedFields = [];
        foreach ($templates as $template) {
            $fieldIds = array_map('intval', json_decode($template->custom_field_ids ?: '[]', true) ?: []);
            $templateFieldIds = [];
            foreach ($fieldIds as $fieldId) {
                $field = DB::table('measurement_fields')->where('id', $fieldId)->first();
                if (! $field || (int) $field->user_id !== (int) $template->user_id) {
                    continue;
                }
                if (! isset($claimedFields[$fieldId])) {
                    DB::table('measurement_fields')->where('id', $fieldId)->update([
                        'measurement_template_id' => $template->id,
                    ]);
                    $newFieldId = $fieldId;
                    $claimedFields[$fieldId] = true;
                } else {
                    $newFieldId = DB::table('measurement_fields')->insertGetId([
                        'user_id' => $field->user_id,
                        'measurement_template_id' => $template->id,
                        'label' => $field->label,
                        'key' => $field->key.'_template_'.$template->id,
                        'field_type' => $field->field_type,
                        'unit' => $field->unit,
                        'options' => $field->options,
                        'is_required' => $field->is_required,
                        'is_active' => $field->is_active,
                        'sort_order' => $field->sort_order,
                        'created_at' => $field->created_at,
                        'updated_at' => now(),
                    ]);
                    $customerIds = DB::table('customers')->where('user_id', $template->user_id)
                        ->where('measurement_template_id', $template->id)->pluck('id');
                    $values = DB::table('customer_measurement_values')
                        ->whereIn('customer_id', $customerIds)->where('measurement_field_id', $fieldId)->get();
                    foreach ($values as $value) {
                        DB::table('customer_measurement_values')->updateOrInsert([
                            'customer_id' => $value->customer_id,
                            'measurement_field_id' => $newFieldId,
                        ], [
                            'value' => $value->value,
                            'created_at' => $value->created_at,
                            'updated_at' => now(),
                        ]);
                    }
                }
                $templateFieldIds[] = $newFieldId;
            }
            DB::table('measurement_templates')->where('id', $template->id)->update([
                'custom_field_ids' => json_encode($templateFieldIds),
            ]);

            $systemFields = json_decode($template->system_fields ?: '[]', true) ?: [];
            $types = DB::table('option_types')->whereIn('type', array_map(
                fn ($key) => $key === 'Daaman' ? 'daaman' : $key,
                $systemFields
            ))->get(['id', 'type']);
            foreach ($types as $type) {
                $legacyOptions = DB::table('options')->where('user_id', $template->user_id)
                    ->where('option_id', $type->id)->whereNull('measurement_template_id')->get();
                foreach ($legacyOptions as $option) {
                    DB::table('options')->insertOrIgnore([
                        'user_id' => $option->user_id,
                        'measurement_template_id' => $template->id,
                        'option_id' => $option->option_id,
                        'slug' => $option->slug.'_template_'.$template->id,
                        'Name' => $option->Name,
                        'created_at' => $option->created_at,
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $defaultByOwner = $templates->groupBy('user_id')->map->first();
        DB::table('measurement_fields')->whereNull('measurement_template_id')->orderBy('id')->get()->each(
            function ($field) use ($defaultByOwner): void {
                $template = $defaultByOwner->get($field->user_id);
                if (! $template) {
                    return;
                }
                DB::table('measurement_fields')->where('id', $field->id)->update(['measurement_template_id' => $template->id]);
                $ids = array_map('intval', json_decode($template->custom_field_ids ?: '[]', true) ?: []);
                if (! in_array((int) $field->id, $ids, true)) {
                    $ids[] = (int) $field->id;
                    DB::table('measurement_templates')->where('id', $template->id)->update(['custom_field_ids' => json_encode($ids)]);
                    $template->custom_field_ids = json_encode($ids);
                }
            }
        );
    }

    public function down(): void
    {
        $optionIndexes = collect(Schema::getIndexes('options'))->pluck('name');
        Schema::table('options', function (Blueprint $table) use ($optionIndexes) {
            if ($optionIndexes->contains('options_owner_template_type_name_unique')) {
                $table->dropUnique('options_owner_template_type_name_unique');
            }
            $table->dropConstrainedForeignId('measurement_template_id');
        });
        Schema::table('measurement_fields', function (Blueprint $table) {
            $table->dropConstrainedForeignId('measurement_template_id');
        });
    }
};
