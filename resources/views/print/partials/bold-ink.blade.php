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

    /* Faint divider lines print as gaps; make them solid. */
    body hr,
    body :is(table, th, td, tr):not(.print-toolbar *) {
        border-color: #000 !important;
    }

    /* Logos and QR codes print as drawn, with sharp edges on the QR. */
    body img,
    body svg {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    body svg,
    body img[src*="qr"],
    body img[alt*="QR"] {
        image-rendering: pixelated;
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

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    @endif
</style>
