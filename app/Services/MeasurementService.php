<?php

namespace App\Services;

use App\Models\Customers;
use App\Models\MeasurementField;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class MeasurementService
{
    public function activeFields(int $ownerId): Collection
    {
        return MeasurementField::where('user_id', $ownerId)->where('is_active', true)
            ->orderBy('sort_order')->orderBy('label')->get();
    }

    public function rules(Collection $fields): array
    {
        $rules = ['custom_measurements' => ['nullable', 'array']];
        foreach ($fields as $field) {
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];
            if ($field->field_type === 'number') {
                array_push($fieldRules, 'numeric', 'min:0');
            } elseif ($field->field_type === 'select') {
                $fieldRules[] = Rule::in($field->options ?? []);
            } else {
                array_push($fieldRules, 'string', 'max:500');
            }
            $rules['custom_measurements.'.$field->id] = $fieldRules;
        }

        return $rules;
    }

    public function attributes(Collection $fields): array
    {
        return $fields->mapWithKeys(fn ($field) => [
            'custom_measurements.'.$field->id => $field->label,
        ])->all();
    }

    public function syncCustomer(Customers $customer, Collection $fields, array $values): void
    {
        foreach ($fields as $field) {
            $value = $values[$field->id] ?? null;
            if ($value === null || $value === '') {
                $customer->measurementValues()->where('measurement_field_id', $field->id)->delete();
                continue;
            }
            $customer->measurementValues()->updateOrCreate(
                ['measurement_field_id' => $field->id],
                ['value' => (string) $value]
            );
        }
    }
}
