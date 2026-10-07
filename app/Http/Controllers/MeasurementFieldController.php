<?php

namespace App\Http\Controllers;

use App\Models\MeasurementField;
use App\Models\MeasurementTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MeasurementFieldController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.measurement-templates.index')
            ->with('success', 'خصوصی خانے اب متعلقہ لباس ٹیمپلیٹ کے اندر ترتیب دیے جاتے ہیں۔');
    }

    public function store(Request $request)
    {
        $validated = $this->validateField($request);
        $ownerId = Auth::user()->businessOwnerId();
        $template = $this->ownedTemplate((int) $validated['measurement_template_id']);
        $baseKey = Str::slug($validated['label'], '_') ?: 'field';
        $key = $baseKey;
        $suffix = 2;
        while (MeasurementField::where('user_id', $ownerId)->where('key', $key)->exists()) {
            $key = $baseKey.'_'.$suffix++;
        }
        $field = MeasurementField::create($this->attributes($validated) + [
            'user_id' => $ownerId,
            'measurement_template_id' => $template->id,
            'key' => $key,
        ]);
        $template->update([
            'custom_field_ids' => collect($template->custom_field_ids ?? [])->map(fn ($id) => (int) $id)
                ->push($field->id)->unique()->values()->all(),
        ]);

        return redirect()->route('admin.measurement-templates.edit', $template)
            ->with('success', 'اس ٹیمپلیٹ کا نیا خانہ شامل کر دیا گیا ہے۔');
    }

    public function update(Request $request, $id)
    {
        $field = $this->ownedField($id);
        $validated = $this->validateField($request);
        $template = $this->ownedTemplate((int) $validated['measurement_template_id']);
        abort_unless((int) $field->measurement_template_id === $template->id, 404);
        $field->update($this->attributes($validated));

        return redirect()->route('admin.measurement-templates.edit', $field->measurement_template_id)
            ->with('success', 'ٹیمپلیٹ کا خانہ محفوظ ہو گیا ہے۔');
    }

    public function destroy($id)
    {
        $field = $this->ownedField($id);
        $field->update(['is_active' => false]);

        return redirect()->route('admin.measurement-templates.edit', $field->measurement_template_id)
            ->with('success', 'خانہ فارم سے ہٹا دیا گیا ہے؛ پرانے ریکارڈ محفوظ رہیں گے۔');
    }

    private function validateField(Request $request): array
    {
        $validated = $request->validate([
            'measurement_template_id' => ['required', 'integer'],
            'label' => ['required', 'string', 'max:100'],
            'field_type' => ['required', Rule::in(MeasurementField::TYPES)],
            'unit' => ['nullable', Rule::in(MeasurementField::UNITS)],
            'options_text' => ['nullable', 'string', 'max:2000', Rule::requiredIf($request->input('field_type') === 'select')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['field_type'] === 'select' && $this->parseOptions($validated['options_text'] ?? '') === []) {
            throw ValidationException::withMessages([
                'options_text' => 'فہرست کے لیے کم از کم ایک درست اختیار لکھیں۔',
            ]);
        }

        return $validated;
    }

    private function attributes(array $validated): array
    {
        $options = $this->parseOptions($validated['options_text'] ?? '');

        return [
            'label' => $validated['label'],
            'field_type' => $validated['field_type'],
            'unit' => $validated['field_type'] === 'number' && ($validated['unit'] ?? 'none') !== 'none'
                ? $validated['unit']
                : null,
            'options' => $validated['field_type'] === 'select' ? $options : null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function parseOptions(string $options): array
    {
        return collect(preg_split('/[\r\n,،]+/u', $options))
            ->map(fn ($option) => trim($option))->filter()->unique()->values()->all();
    }

    private function ownedField($id): MeasurementField
    {
        return MeasurementField::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
    }

    private function ownedTemplate(int $id): MeasurementTemplate
    {
        return MeasurementTemplate::where('user_id', Auth::user()->businessOwnerId())
            ->where('is_active', true)->findOrFail($id);
    }
}
