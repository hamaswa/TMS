<?php

namespace App\Http\Controllers;

use App\Models\MeasurementField;
use App\Models\MeasurementTemplate;
use App\Models\StandardMeasurementProfile;
use App\Services\MeasurementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StandardMeasurementProfileController extends Controller
{
    public function store(Request $request, int $template)
    {
        $template = $this->ownedTemplate($template);
        $data = $this->validatedProfile($request, $template);
        $template->standardProfiles()->create($data + ['user_id' => $template->user_id, 'is_active' => true]);

        return back()->with('success', 'معیاری پیمائش محفوظ ہو گئی ہے اور متعلقہ آن لائن خدمات میں دستیاب ہے۔');
    }

    public function update(Request $request, int $profile)
    {
        $profile = $this->ownedProfile($profile);
        $profile->update($this->validatedProfile($request, $profile->measurementTemplate, $profile->id) + ['is_active' => true]);

        return back()->with('success', 'معیاری پیمائش کی تبدیلی محفوظ ہو گئی ہے۔ پرانے آرڈر اپنی محفوظ پیمائش برقرار رکھیں گے۔');
    }

    public function destroy(int $profile)
    {
        $profile = $this->ownedProfile($profile);
        $profile->update(['is_active' => false]);

        return back()->with('success', 'معیاری پیمائش غیر فعال کر دی گئی ہے۔ پرانے آرڈر محفوظ رہیں گے۔');
    }

    private function validatedProfile(Request $request, MeasurementTemplate $template, ?int $profileId = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('standard_measurement_profiles')->where('measurement_template_id', $template->id)->ignore($profileId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'values' => ['nullable', 'array'],
            'values.system' => ['nullable', 'array'],
            'values.custom' => ['nullable', 'array'],
        ]);

        $input = $validated['values'] ?? [];
        $rows = [];
        foreach ($template->system_fields ?? [] as $key) {
            $meta = MeasurementService::SYSTEM_FIELDS[$key] ?? null;
            if (! $meta) {
                continue;
            }
            $value = trim((string) data_get($input, 'system.'.$key, ''));
            if ($value === '' || ! is_numeric($value) || (float) $value < 0) {
                throw ValidationException::withMessages([
                    'values.system.'.$key => $meta['label'].' کے لیے درست پیمائش درج کریں۔',
                ]);
            }
            $rows[] = ['source_key' => 'system.'.$key, 'label' => $meta['label'], 'value' => $value, 'unit' => $meta['unit']];
        }

        $fields = MeasurementField::query()
            ->where('user_id', $template->user_id)->where('is_active', true)
            ->whereIn('id', array_map('intval', $template->custom_field_ids ?? []))
            ->orderBy('sort_order')->get();
        foreach ($fields as $field) {
            $value = trim((string) data_get($input, 'custom.'.$field->id, ''));
            if ($field->is_required && $value === '') {
                throw ValidationException::withMessages(['values.custom.'.$field->id => $field->label.' درج کریں۔']);
            }
            if ($value === '') {
                continue;
            }
            if ($field->field_type === 'number' && (! is_numeric($value) || (float) $value < 0)) {
                throw ValidationException::withMessages(['values.custom.'.$field->id => $field->label.' کے لیے درست قدر درج کریں۔']);
            }
            if ($field->field_type === 'select' && ! in_array($value, $field->options ?? [], true)) {
                throw ValidationException::withMessages(['values.custom.'.$field->id => $field->label.' کے لیے دستیاب قدر منتخب کریں۔']);
            }
            $rows[] = [
                'source_key' => 'custom.'.$field->id,
                'label' => $field->label,
                'value' => $value,
                'unit' => $field->unit === 'none' ? '' : $field->unit,
            ];
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['values' => 'کم از کم ایک پیمائش درج کریں۔']);
        }

        return [
            'name' => trim($validated['name']),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'measurement_values' => $rows,
        ];
    }

    private function ownedTemplate(int $id): MeasurementTemplate
    {
        return MeasurementTemplate::where('user_id', Auth::user()->businessOwnerId())->where('is_active', true)->findOrFail($id);
    }

    private function ownedProfile(int $id): StandardMeasurementProfile
    {
        return StandardMeasurementProfile::with('measurementTemplate')
            ->where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }
}
