{{--
    Thermal printers fade thin and grey text (and Nastaliq's fine strokes) into dots.
    Included last in every receipt, slip, label and report print so the printed text is solid black and bold.
    On-screen toolbars and buttons keep their own colours.
    Pass ['printOnly' => true] on pages inside the app layout, so only the paper copy changes, not the screen.
--}}
@php
    $boldInkCss = <<<'CSS'
    body :not(.print-toolbar):not(.print-toolbar *):not(.tms-print-toolbar):not(.tms-print-toolbar *):not(button):not(button *):not(.btn):not(.btn *):not(script):not(style) {
        color: #000 !important;
        font-weight: 800 !important;
        -webkit-text-stroke: .25px #000;
        text-shadow: none !important;
    }

    /* Headings, totals and names stand out a little more. */
    body :is(h1, h2, h3, h4, strong, b, th, .total, .grand-total):not(.print-toolbar *):not(.tms-print-toolbar *) {
        font-weight: 900 !important;
        -webkit-text-stroke: .4px #000;
    }

    /* QR codes (the only SVGs on prints) print at least 34 mm with sharp squares, so a thermal printer
       gives each square enough dots to scan; smaller codes smudged together and didn't scan. */
    body svg[viewBox] {
        flex-shrink: 0 !important;
        min-width: 34mm !important;
        min-height: 34mm !important;
        shape-rendering: crispEdges;
    }
CSS;
@endphp
<style>
    @if ($printOnly ?? false)
        @media print {
            {!! $boldInkCss !!}
        }
    @else
        {!! $boldInkCss !!}
    @endif
</style>
