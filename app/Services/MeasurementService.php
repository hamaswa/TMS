<?php

namespace App\Services;

use App\Models\Customers;
use App\Models\CustomerMeasurementHistory;
use App\Models\MeasurementField;
use App\Models\MeasurementTemplate;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class MeasurementService
{
    public const SYSTEM_FIELDS = [
        'length' => ['label' => 'لمبائی', 'unit' => 'inch'],
        'arms' => ['label' => 'بازو', 'unit' => 'inch'],
        'teraa' => ['label' => 'تیرا', 'unit' => 'inch'],
        'senaChorai' => ['label' => 'سینہ چوڑائی', 'unit' => 'inch'],
        'damanchorai' => ['label' => 'دامن چوڑائی', 'unit' => 'inch'],
        'shalwar' => ['label' => 'شلوار', 'unit' => 'inch'],
        'pancha' => ['label' => 'پائنچہ', 'unit' => 'inch'],
        'shalwarGheer' => ['label' => 'شلوار گھیر', 'unit' => 'inch'],
        'shoulder' => ['label' => 'مونڈھا', 'unit' => 'inch'],
        'chuta' => ['label' => 'چوٹا', 'unit' => 'inch'],
    ];

    public function activeFields(int $ownerId): Collection
    {
        return MeasurementField::where('user_id', $ownerId)->where('is_active', true)
            ->orderBy('sort_order')->orderBy('label')->get();
    }

    public function fieldsForTemplate(Collection $fields, ?MeasurementTemplate $template): Collection
    {
        if (! $template) {
            return $fields;
        }

        $selected = array_map('intval', $template->custom_field_ids ?? []);

        return $fields->filter(fn ($field) => in_array((int) $field->id, $selected, true))->values();
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

    public function snapshotOrder(Order $order, Customers $customer, ?MeasurementTemplate $template = null): void
    {
        $template ??= $order->measurementTemplate;
        $rows = $this->measurementRows($customer, (int) $order->userId, $template);

        $order->measurementValues()->delete();
        $order->measurementValues()->createMany($rows->all());
    }

    public function measurementRows(Customers $customer, int $ownerId, ?MeasurementTemplate $template = null): Collection
    {
        $rows = collect();
        $sortOrder = 0;
        foreach (self::SYSTEM_FIELDS as $key => $meta) {
            if ($template && ! in_array($key, $template->system_fields ?? [], true)) {
                continue;
            }
            $value = $customer->{$key};
            if ($value !== null && $value !== '') {
                $rows->push([
                    'measurement_field_id' => null,
                    'source_key' => 'system.'.$key,
                    'label' => $meta['label'],
                    'value' => (string) $value,
                    'unit' => $meta['unit'],
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $customValues = $customer->measurementValues()->with('field')
            ->whereHas('field', fn ($query) => $query
                ->where('user_id', $ownerId)->where('is_active', true))
            ->when($template, fn ($query) => $query->whereIn('measurement_field_id', $template->custom_field_ids ?? []))
            ->get()->sortBy(fn ($value) => [$value->field->sort_order, $value->field->label]);

        foreach ($customValues as $customValue) {
            $rows->push([
                'measurement_field_id' => $customValue->measurement_field_id,
                'source_key' => 'custom.'.$customValue->measurement_field_id,
                'label' => $customValue->field->label,
                'value' => $customValue->value,
                'unit' => $customValue->field->unit,
                'sort_order' => 1000 + $customValue->field->sort_order,
            ]);
        }

        return $rows;
    }

    public function measurementFingerprint(Collection $rows, ?MeasurementTemplate $template = null): string
    {
        return hash('sha256', json_encode([
            'template_id' => $template?->id,
            'values' => $rows->map(fn (array $row) => [
                'source_key' => $row['source_key'],
                'value' => (string) $row['value'],
                'unit' => $row['unit'],
            ])->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    public function recordHistory(
        Customers $customer,
        int $ownerId,
        ?MeasurementTemplate $template,
        ?int $recordedByUserId,
        string $source = 'customer_update',
    ): ?CustomerMeasurementHistory {
        return $this->recordHistoryRows(
            $customer,
            $this->measurementRows($customer, $ownerId, $template),
            $template,
            $recordedByUserId,
            $source,
        );
    }

    public function recordHistoryRows(
        Customers $customer,
        Collection $rows,
        ?MeasurementTemplate $template,
        ?int $recordedByUserId,
        string $source,
    ): ?CustomerMeasurementHistory {
        if ($rows->isEmpty()) {
            return null;
        }

        $history = $customer->measurementHistories()->create([
            'user_id' => $customer->user_id,
            'measurement_template_id' => $template?->id,
            'recorded_by_user_id' => $recordedByUserId,
            'source' => $source,
        ]);
        $history->values()->createMany($rows->all());

        return $history;
    }
}
