<?php

namespace App\Http\Controllers;

use App\Models\MeasurementField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MeasurementFieldController extends Controller
{
    public function index()
    {
        $fields = MeasurementField::where('user_id', Auth::user()->businessOwnerId())
            ->orderBy('sort_order')->orderBy('label')->get();
        $systemFields = ['لمبائی', 'بازو', 'تیرا', 'سینہ چوڑائی', 'دامن چوڑائی', 'شلوار', 'پائنچہ', 'شلوار گھیر', 'مونڈھا', 'چوٹا'];

        return view('measurement-fields.index', compact('fields', 'systemFields'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateField($request);
        $ownerId = Auth::user()->businessOwnerId();
        $baseKey = Str::slug($validated['label'], '_') ?: 'field';
        $key = $baseKey;
        $suffix = 2;
        while (MeasurementField::where('user_id', $ownerId)->where('key', $key)->exists()) {
            $key = $baseKey.'_'.$suffix++;
        }
        MeasurementField::create($this->attributes($validated) + ['user_id' => $ownerId, 'key' => $key]);

        return back()->with('success', 'نیا پیمائش خانہ شامل کر دیا گیا ہے۔');
    }

    public function update(Request $request, $id)
    {
        $this->ownedField($id)->update($this->attributes($this->validateField($request)));

        return back()->with('success', 'پیمائش خانہ محفوظ ہو گیا ہے۔');
    }

    public function destroy($id)
    {
        $this->ownedField($id)->update(['is_active' => false]);

        return back()->with('success', 'پیمائش خانہ غیر فعال کر دیا گیا ہے۔ پرانا ریکارڈ محفوظ رہے گا۔');
    }

    private function validateField(Request $request): array
    {
        $validated = $request->validate([
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
            'unit' => ($validated['unit'] ?? 'none') === 'none' ? null : $validated['unit'],
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
}
