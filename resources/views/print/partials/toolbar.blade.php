<nav class="tms-print-toolbar" aria-label="پرنٹ سائز">
    <button type="button" class="tms-print-now" onclick="window.print()">ابھی پرنٹ کریں</button>
    @foreach($printConfig['paper_options'] as $paperValue => $paperLabel)
        <a
            href="{{ request()->fullUrlWithQuery(['paper' => $paperValue]) }}"
            class="{{ $printConfig['paper'] === $paperValue ? 'is-active' : '' }}"
            @if($printConfig['paper'] === $paperValue) aria-current="page" @endif
        >{{ $paperLabel }}</a>
    @endforeach
    <button type="button" onclick="window.history.back()">واپس جائیں</button>
</nav>
