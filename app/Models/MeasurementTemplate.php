<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MeasurementTemplate extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'system_fields', 'custom_field_ids',
        'is_builtin', 'system_field_labels', 'field_layout', 'layout_columns', 'layout_sections', 'is_default', 'is_active',
    ];

    protected $casts = [
        'system_fields' => 'array',
        'system_field_labels' => 'array',
        'custom_field_ids' => 'array',
        'is_builtin' => 'boolean',
        'field_layout' => 'array',
        'layout_columns' => 'integer',
        'layout_sections' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Most sections a form may have; layout order numbers leave 1000 per section. */
    public const MAX_SECTIONS = 8;

    /** Sewing preference fields sit in the left column unless the layout says otherwise. */
    public const PREFERENCE_SOURCES = [
        'system.necktype', 'system.sleeve', 'system.Daaman', 'system.jeab',
        'system.swingtype', 'system.button', 'system.plate_type',
    ];

    /**
     * The form's sections in order, each ['title' => ?string, 'columns' => 1|2].
     * Templates saved before sections existed are one section with their column count.
     */
    public function sections(): array
    {
        $sections = collect($this->layout_sections ?? [])
            ->take(self::MAX_SECTIONS)
            ->map(fn ($section) => [
                'title' => filled($section['title'] ?? null) ? trim((string) $section['title']) : null,
                'columns' => (int) ($section['columns'] ?? 2) === 1 ? 1 : 2,
            ])->values()->all();

        return $sections ?: [['title' => null, 'columns' => (int) ($this->layout_columns ?? 2) === 1 ? 1 : 2]];
    }

    /**
     * Arranges saved measurement rows (keyed by source) the way the template lays out its form, for printing:
     * a list of non-empty sections, each with its title, column count and the right and left column keys in order.
     * Rows the layout does not mention go at the end of the first section in their usual column.
     */
    public static function arrangeForPrint(?self $template, Collection $rows): array
    {
        $sections = $template ? $template->sections() : [['title' => null, 'columns' => 2]];
        $layout = collect($template?->field_layout ?? [])->keyBy('source');
        $lastSection = count($sections) - 1;

        $placed = $rows->keys()->values()->map(function ($source, $i) use ($layout, $sections, $lastSection) {
            $saved = $layout->get($source);
            $section = min(max((int) ($saved['section'] ?? 0), 0), $lastSection);
            $column = $saved['column'] ?? (in_array($source, self::PREFERENCE_SOURCES, true) ? 'left' : 'right');
            // A one-column section prints everything in its single (right) column.
            if ($sections[$section]['columns'] === 1 || ! in_array($column, ['left', 'right'], true)) {
                $column = 'right';
            }

            return [
                'key' => $source,
                'section' => $section,
                'column' => $column,
                'order' => $saved ? (int) ($saved['order'] ?? 0) : 100000 + $i,
                'i' => $i,
            ];
        });

        $sortRule = [['order', 'asc'], ['i', 'asc']];

        return collect($sections)->map(function (array $section, int $index) use ($placed, $sortRule) {
            $inSection = $placed->where('section', $index);

            return $section + [
                'right' => $inSection->where('column', 'right')->sortBy($sortRule)->pluck('key')->values()->all(),
                'left' => $inSection->where('column', 'left')->sortBy($sortRule)->pluck('key')->values()->all(),
            ];
        })->filter(fn (array $section) => $section['right'] || $section['left'])->values()->all();
    }

    public function customers()
    {
        return $this->hasMany(Customers::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function standardProfiles()
    {
        return $this->hasMany(StandardMeasurementProfile::class)
            ->orderBy('sort_order')->orderBy('name');
    }

    public function customFields()
    {
        return $this->hasMany(MeasurementField::class)->orderBy('sort_order')->orderBy('label');
    }

    public function templateOptions()
    {
        return $this->hasMany(Options::class)->orderBy('Name');
    }
}
