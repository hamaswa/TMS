{{--
    Prints saved measurements the way the template lays out its form: section by section,
    each with its own title and one or two columns, fields in the same order as the builder.
    Needs $allMeasurements (rows keyed by source_key, each with label and value) and $tpl (the template or null).
--}}
@php
    $printSections = \App\Models\MeasurementTemplate::arrangeForPrint($tpl ?? null, $allMeasurements);
@endphp

@foreach ($printSections as $sectionIndex => $printSection)
    @php
        $leftFields = $printSection['left'];
        $rightFields = $printSection['right'];
        $rows = max(count($leftFields), count($rightFields));
        // Columns keep their two-column width even in a one-column section, so numbers line up across sections.
        $colWidth = '45%';
    @endphp

    @if ($sectionIndex > 0)
        <div class="measurement-section-divider"></div>
    @endif
    @if ($printSection['title'])
        <div class="measurement-section-title">{{ $printSection['title'] }}</div>
    @endif

    @for ($i = 0; $i < $rows; $i++)
        @php
            // A right-column text value with nothing beside it (always so in a one-column section) gets the
            // whole line; a plain number stays in the number column so it lines up with the numbers above.
            $fullLine = !isset($leftFields[$i]) && isset($rightFields[$i])
                && !is_numeric(trim((string) $allMeasurements[$rightFields[$i]]->value));
        @endphp
        <div class="row measurement-row" style="display:flex;justify-content:space-between;padding:0 10px;">

            {{-- LEFT COLUMN --}}
            @unless ($fullLine)
                <div class="col-6 {{ !isset($leftFields[$i]) ? 'measurement-column-empty' : '' }}" style="width:45%;">
                    @if (isset($leftFields[$i]))
                        @php $item = $allMeasurements[$leftFields[$i]]; @endphp
                        <div
                            style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px;font-weight:600;">
                            <span class="measurement-value measurement-preference-value">{{ $item->value }}</span>
                            <span class="measurement-label"
                                style="flex-shrink:0; white-space:nowrap; font-size:20px;">{{ $item->label }}</span>
                        </div>
                    @endif
                </div>
            @endunless

            @if ($fullLine)
                {{--
                    Whole line, starting from the right edge. The label keeps the width it has in a two-column
                    right column (65% of 45%), so the text starts beside it as in the rows above and uses the
                    rest of the line. Not a col-6: the grid caps that at half the row.
                --}}
                @php $item = $allMeasurements[$rightFields[$i]]; @endphp
                <div class="measurement-full-line">
                    <span class="measurement-value">{{ $item->value }}</span>
                    <span class="measurement-label">{{ $item->label }}</span>
                </div>
            @else
                {{-- RIGHT COLUMN --}}
                <div class="col-6 {{ !isset($rightFields[$i]) ? 'measurement-column-empty' : '' }}"
                    style="width:{{ $colWidth }};">
                    @if (isset($rightFields[$i]))
                        @php $item = $allMeasurements[$rightFields[$i]]; @endphp
                        <div style="display:flex;align-items:flex-start;gap:6px;font-weight:600;padding:2px 4px;">
                            <span class="measurement-value"
                                style="flex:0 1 auto; min-width:0; width:35%; text-align:left; white-space:normal; overflow-wrap:anywhere;">
                                {{ $item->value }}
                            </span>
                            <span class="measurement-label"
                                style="width:65%; text-align:right; white-space:nowrap; font-size:20px;">
                                {{ $item->label }}
                            </span>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    @endfor
@endforeach

<style>
    .measurement-row .measurement-full-line {
        display: flex;
        flex: 0 0 100%;
        align-items: flex-start;
        gap: 6px;
        width: 100%;
        max-width: 100%;
        /* Same side padding as a grid column (15px) plus the 4px the column rows use. */
        padding: 2px 19px;
        font-weight: 600;
    }

    .measurement-row .measurement-full-line .measurement-value {
        flex: 1 1 auto;
        min-width: 0;
        direction: rtl;
        unicode-bidi: plaintext;
        text-align: right;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .measurement-row .measurement-full-line .measurement-label {
        flex: 0 0 29.25% !important;
        max-width: 29.25%;
        font-size: 20px;
        text-align: right;
        white-space: nowrap !important;
    }

    .measurement-section-divider {
        margin: 3px 10px 2px;
        border-top: 1px dashed #777;
    }

    .measurement-section-title {
        margin: 0 10px 2px;
        color: #000;
        font-size: 17px;
        font-weight: 900;
        line-height: 1.5;
        text-align: center;
        text-decoration: underline;
        text-underline-offset: 4px;
    }
</style>
