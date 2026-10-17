<?php

namespace App\Http\Controllers;

use App\Models\MeasurementField;
use App\Models\OptionType;
use App\Models\MeasurementTemplate;
use App\Services\MeasurementService;
use App\Services\TailoringOptionDefaultsService;
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
        $templates = MeasurementTemplate::with('standardProfiles')
            ->where('user_id', $ownerId)->orderByDesc('is_default')->orderBy('name')->get();

        return view('measurement-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('measurement-templates.builder', $this->builderData());
    }

    public function edit(int $template)
    {
        $template = $this->ownedTemplate($template)->load('standardProfiles');

        return view('measurement-templates.builder', $this->builderData($template));
    }

    public function store(Request $request)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $validated = $this->validateTemplate($request, $ownerId);

        $template = DB::transaction(function () use ($validated, $ownerId) {
            return MeasurementTemplate::create($validated + [
                'user_id' => $ownerId,
                'is_builtin' => false,
                'is_default' => false,
                'is_active' => true,
            ]);
        });

        return redirect()->route('admin.measurement-templates.edit', $template)
            ->with('success', 'نیا پیمائش ٹیمپلیٹ محفوظ کر دیا گیا ہے۔');
    }

    public function update(Request $request, int $template)
    {
        $template = $this->ownedTemplate($template);
        $validated = $this->validateTemplate($request, $template->user_id, $template->id, $template->is_builtin);

        DB::transaction(function () use ($validated, $template) {
            if ($template->is_builtin) {
                $validated['name'] = TailoringOptionDefaultsService::DEFAULT_MEASUREMENT_TEMPLATE_NAME;
                $validated['system_fields'] = array_keys(MeasurementService::SYSTEM_FIELDS);
            }
            $template->update($validated + [
                'is_default' => $template->is_builtin,
                'is_active' => true,
            ]);
        });

        return redirect()->route('admin.measurement-templates.edit', $template)
            ->with('success', 'پیمائش ٹیمپلیٹ محفوظ ہو گیا ہے۔');
    }

    public function updateFieldTitle(Request $request, int $template)
    {
        $template = $this->ownedTemplate($template);
        $data = $request->validate([
            'field_key' => ['required', 'string', Rule::in(array_keys(MeasurementService::SYSTEM_FIELDS))],
            'title' => ['required', 'string', 'max:100'],
        ]);

        $labels = $template->system_field_labels ?? [];
        $title = trim($data['title']);
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'خانے کا نام درج کریں۔']);
        }
        if ($title === MeasurementService::SYSTEM_FIELDS[$data['field_key']]['label']) {
            unset($labels[$data['field_key']]);
        } else {
            $labels[$data['field_key']] = $title;
        }
        $template->update(['system_field_labels' => $labels]);

        return response()->json(['field_key' => $data['field_key'], 'title' => $title]);
    }

    public function destroy(int $template)
    {
        $template = $this->ownedTemplate($template);
        if ($template->is_builtin) {
            return back()->withErrors(['template' => 'بنیادی مردانہ شلوار قمیض ٹیمپلیٹ ہمیشہ دستیاب رہتا ہے اور غیر فعال نہیں کیا جا سکتا۔']);
        }
        $template->update(['is_active' => false, 'is_default' => false]);

        return back()->with('success', 'ٹیمپلیٹ غیر فعال کر دیا گیا ہے۔ پرانے ریکارڈ محفوظ رہیں گے۔');
    }

    private function validateTemplate(Request $request, int $ownerId, ?int $templateId = null, bool $isBuiltin = false): array
    {
        $systemKeys = array_keys(MeasurementService::SYSTEM_FIELDS);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('measurement_templates')->where('user_id', $ownerId)->ignore($templateId)],
            'description' => ['nullable', 'string', 'max:500'],
            'system_fields' => ['nullable', 'array'],
            'system_fields.*' => [Rule::in($systemKeys)],
            'custom_field_ids' => ['nullable', 'array'],
            'custom_field_ids.*' => ['integer', Rule::exists('measurement_fields', 'id')->where('user_id', $ownerId)->where('is_active', true)],
            'field_layout' => ['nullable', 'array'],
            'field_layout.*.source' => ['required', 'string', 'max:100'],
            'field_layout.*.column' => ['required', Rule::in(['right', 'left'])],
            'field_layout.*.order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'layout_columns' => ['nullable', 'integer', Rule::in([1, 2])],
        ]);

        $selectedSystem = $isBuiltin
            ? $systemKeys
            : array_values(array_intersect($systemKeys, $validated['system_fields'] ?? []));
        if ($templateId) {
            MeasurementField::where('user_id', $ownerId)->whereNull('measurement_template_id')
                ->whereIn('id', array_map('intval', $validated['custom_field_ids'] ?? []))
                ->update(['measurement_template_id' => $templateId]);
        }
        $selectedCustom = $templateId
            ? MeasurementField::where('user_id', $ownerId)->where('measurement_template_id', $templateId)
            ->where('is_active', true)->orderBy('sort_order')->orderBy('label')->pluck('id')->map(fn($id) => (int) $id)->all()
            : [];
        if ($selectedSystem === [] && $selectedCustom === []) {
            throw ValidationException::withMessages(['system_fields' => 'کم از کم ایک پیمائش خانہ منتخب کریں۔']);
        }

        $allowedSources = collect($selectedSystem)->map(fn(string $key) => 'system.' . $key)
            ->merge(collect($selectedCustom)->map(fn(int $id) => 'custom.' . $id))->all();
        $layout = collect($validated['field_layout'] ?? [])
            ->filter(fn(array $item) => in_array($item['source'], $allowedSources, true))
            ->unique('source')
            ->values()
            ->map(fn(array $item, int $index) => [
                'source' => $item['source'],
                'column' => $item['column'],
                'order' => (int) ($item['order'] ?? (($index + 1) * 10)),
            ]);

        $customTypes = MeasurementField::whereIn('id', $selectedCustom)->pluck('field_type', 'id');

        foreach ($allowedSources as $index => $source) {
            if ($layout->contains('source', $source)) {
                continue;
            }
            $systemKey = str_starts_with($source, 'system.') ? substr($source, 7) : null;
            $customId = str_starts_with($source, 'custom.') ? (int) substr($source, 7) : null;

            $column = $systemKey
                ? ((MeasurementService::SYSTEM_FIELDS[$systemKey]['unit'] ?? '') === '' ? 'left' : 'right')
                : (($customTypes[$customId] ?? null) === 'select' ? 'left' : 'right');

            $layout->push([
                'source' => $source,
                'column' => $column,
                'order' => ($index + 1) * 10,
            ]);
        }

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'system_fields' => $selectedSystem,
            'custom_field_ids' => $selectedCustom,
            'field_layout' => $layout->sortBy('order')->values()->all(),
            'layout_columns' => (int) ($validated['layout_columns'] ?? 2),
        ];
    }

    private function ownedTemplate(int $id): MeasurementTemplate
    {
        return MeasurementTemplate::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function builderData(?MeasurementTemplate $template = null): array
    {
        $ownerId = Auth::user()->businessOwnerId();
        $systemFields = collect(MeasurementService::SYSTEM_FIELDS)->mapWithKeys(function (array $meta, string $key) use ($template) {
            $meta['label'] = $template?->system_field_labels[$key] ?? $meta['label'];
            return [$key => $meta];
        })->all();
        $systemOptionTypes = [];
        if ($template) {
            $keys = collect($systemFields)->filter(fn (array $field) => $field['unit'] === '')->keys();
            $types = OptionType::where(function ($query) use ($ownerId) {
                $query->whereNull('user_id')->orWhere('user_id', $ownerId);
            })->whereIn('type', $keys->map(fn ($key) => $key === 'Daaman' ? 'daaman' : $key)->all())
                ->with(['options' => fn ($query) => $query->where('user_id', $ownerId)->where('measurement_template_id', $template->id)->orderBy('Name')])->get();
            foreach ($types as $type) {
                $key = $type->type === 'daaman' ? 'Daaman' : $type->type;
                $systemOptionTypes[$key] = [
                    'id' => $type->id,
                    'updateBase' => route('admin.Options.update', ['Option' => '__ID__']),
                    'deleteBase' => route('admin.Options.destroy', ['Option' => '__ID__']),
                    'options' => $type->options->map(fn ($option) => [
                        'id' => $option->id, 'name' => $option->Name,
                        'update' => route('admin.Options.update', $option),
                        'destroy' => route('admin.Options.destroy', $option),
                    ])->values()->all(),
                ];
            }
        }

        return [
            'template' => $template,
            'systemOptionTypes' => $systemOptionTypes,
            'measurementFields' => collect($systemFields)->filter(fn(array $field) => $field['unit'] !== ''),
            'preferenceFields' => collect($systemFields)->filter(fn(array $field) => $field['unit'] === ''),
            'systemFields' => $systemFields,
            'customFields' => $template
                ? MeasurementField::where('user_id', $ownerId)->where('measurement_template_id', $template->id)
                ->where('is_active', true)->orderBy('sort_order')->orderBy('label')->get()
                : collect(),
        ];
    }
}
