@if(!empty($printConfig['show_qr']) && !empty($printConfig['qr_svg']))
    <aside class="tms-print-qr" aria-label="دستاویز کا تصدیقی حوالہ">
        {!! $printConfig['qr_svg'] !!}
        <span class="tms-print-qr-reference">TMS REF: {{ $printConfig['reference'] }}</span>
    </aside>
@endif
