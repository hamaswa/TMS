<?php

namespace App\Http\Controllers;

use App\Models\MeasurementField;
use App\Models\MeasurementTemplate;
use App\Services\MeasurementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MeasurementTemplateController extends Controller
{
    public function index()
    {
        $ownerId = Auth::user()->businessOwnerId();
        $templates = MeasurementTemplate::where('user_id', $ownerId)->orderByDesc('is_default')->orderBy('name')->get();
        $customFields = MeasurementField::where('user_id', $ownerId)->where('is_active', true)
            ->orderBy('sort_order')->orderBy('label')->get();
        $systemFields = MeasurementService::SYSTEM_FIELDS;

        return view('measurement-templates.index', compact('templates', 'customFields', 'systemFields'));
    }

    public function store(Request $request)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $validated = $this->validateTemplate($request, $ownerId);

        DB::transaction(function () use ($validated, $ownerId) {
            $makeDefault = $validated['is_default'] || ! MeasurementTemplate::where('user_id', $ownerId)->where('is_active', true)->exists();
            if ($makeDefault) {
                MeasurementTemplate::where('user_id', $ownerId)->update(['is_default' => false]);
            }
            MeasurementTemplate::create($validated + ['user_id' => $ownerId, 'is_default' => $makeDefault, 'is_active' => true]);
        });

        return back()->with('success', 'نیا پیمائش ٹیمپلیٹ محفوظ کر دیا گیا ہے۔');
    }

    public function update(Request $request, int $template)
    {
        $template = $this->ownedTemplate($template);
        $validated = $this->validateTemplate($request, $template->user_id, $template->id);

        DB::transaction(function () use ($validated, $template) {
            if ($validated['is_default']) {
                MeasurementTemplate::where('user_id', $template->user_id)->where('id', '!=', $template->id)->update(['is_default' => false]);
            }
            $template->update($validated + ['is_active' => true]);
        });

        return back()->with('success', 'پیمائش ٹیمپلیٹ محفوظ ہو گیا ہے۔');
    }

    public function destroy(int $template)
    {
        $template = $this->ownedTemplate($template);
        $template->update(['is_active' => false, 'is_default' => false]);

        $replacement = MeasurementTemplate::where('user_id', $template->user_id)->where('is_active', true)->orderBy('name')->first();
        if ($replacement && ! MeasurementTemplate::where('user_id', $template->user_id)->where('is_default', true)->exists()) {
            $replacement->update(['is_default' => true]);
        }

        return back()->with('success', 'ٹیمپلیٹ غیر فعال کر دیا گیا ہے۔ پرانے ریکارڈ محفوظ رہیں گے۔');
    }

    private function validateTemplate(Request $request, int $ownerId, ?int $templateId = null): array
    {
        $systemKeys = array_keys(MeasurementService::SYSTEM_FIELDS);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('measurement_templates')->where('user_id', $ownerId)->ignore($templateId)],
            'description' => ['nullable', 'string', 'max:500'],
            'system_fields' => ['nullable', 'array'],
            'system_fields.*' => [Rule::in($systemKeys)],
            'custom_field_ids' => ['nullable', 'array'],
            'custom_field_ids.*' => ['integer', Rule::exists('measurement_fields', 'id')->where('user_id', $ownerId)->where('is_active', true)],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $selectedSystem = array_values(array_intersect($systemKeys, $validated['system_fields'] ?? []));
        $selectedCustom = array_values(array_unique(array_map('intval', $validated['custom_field_ids'] ?? [])));
        if ($selectedSystem === [] && $selectedCustom === []) {
            throw ValidationException::withMessages(['system_fields' => 'کم از کم ایک پیمائش خانہ منتخب کریں۔']);
        }

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'system_fields' => $selectedSystem,
            'custom_field_ids' => $selectedCustom,
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ];
    }

    private function ownedTemplate(int $id): MeasurementTemplate
    {
        return MeasurementTemplate::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }
}
