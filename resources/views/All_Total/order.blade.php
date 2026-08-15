@extends('main')

@section('content')
@php
    $monthNames = [
        'January' => 'جنوری', 'February' => 'فروری', 'March' => 'مارچ',
        'April' => 'اپریل', 'May' => 'مئی', 'June' => 'جون',
        'July' => 'جولائی', 'August' => 'اگست', 'September' => 'ستمبر',
        'October' => 'اکتوبر', 'November' => 'نومبر', 'December' => 'دسمبر',
    ];
    $totals = [
        'orders' => collect($monthly_orders)->sum('orders'),
        'suits' => collect($monthly_orders)->sum('suits'),
        'payment' => collect($monthly_orders)->sum('payment'),
        'neworders' => collect($monthly_orders)->sum('neworders'),
        'inprocessorders' => collect($monthly_orders)->sum('inprocessorders'),
        'completed' => collect($monthly_orders)->sum('completed'),
    ];
@endphp

<style>
    .year-report-page {
        --yr-blue: #1769e0;
        --yr-navy: #11365b;
        --yr-muted: #718198;
        --yr-line: #dfe7f1;
        min-height: calc(100vh - 70px);
        background: #f4f7fa
    }

    .yr-shell {
        width: min(100% - 38px, 1400px);
        margin: 0 auto;
        padding: 28px 0 46px
    }

    .yr-head {
        margin-bottom: 18px
    }

    .yr-head h1 {
        margin: 0;
        color: var(--yr-navy);
        font-size: 1.55rem;
        font-weight: 900
    }

    .yr-head p {
        margin: 5px 0 0;
        color: var(--yr-muted);
        font-size: .78rem
    }

    .yr-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px
    }

    .yr-summary-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border: 1px solid var(--yr-line);
        border-radius: 13px;
        background: #fff;
        box-shadow: 0 4px 14px rgba(24, 52, 82, .04)
    }

    .yr-summary-icon {
        display: grid;
        place-items: center;
        flex: 0 0 44px;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        color: var(--yr-blue);
        background: #eaf3ff
    }

    .yr-summary-card.is-suits .yr-summary-icon { color: #7651c9; background: #f1ebff }
    .yr-summary-card.is-income .yr-summary-icon { color: #a56a00; background: #fff4da }
    .yr-summary-card.is-complete .yr-summary-icon { color: #168553; background: #e8f8ef }

    .yr-summary-card small {
        display: block;
        color: var(--yr-muted);
        font-size: .68rem
    }

    .yr-summary-card strong {
        display: block;
        margin-top: 2px;
        color: var(--yr-navy);
        font-size: 1.18rem;
        font-weight: 900
    }

    .yr-panel {
        overflow: hidden;
        border: 1px solid var(--yr-line);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 5px 16px rgba(25, 55, 88, .04)
    }

    .yr-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 15px 17px;
        border-bottom: 1px solid #e7edf4
    }

    .yr-panel-head h2 {
        margin: 0;
        color: var(--yr-navy);
        font-size: 1rem;
        font-weight: 900
    }

    .yr-panel-head span {
        color: var(--yr-muted);
        font-size: .7rem
    }

    .yr-months {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 13px;
        padding: 15px
    }

    .yr-month {
        overflow: hidden;
        border: 1px solid #e3eaf2;
        border-radius: 12px;
        background: #fbfcfe
    }

    .yr-month.has-work {
        border-color: #bcd5f5;
        background: #fff;
        box-shadow: 0 6px 15px rgba(23, 105, 224, .07)
    }

    .yr-month-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px;
        border-bottom: 1px solid #edf1f6
    }

    .yr-month-head strong {
        color: #66778b;
        font-size: .88rem
    }

    .yr-month.has-work .yr-month-head strong { color: var(--yr-navy) }

    .yr-month-state {
        padding: 4px 8px;
        border-radius: 999px;
        color: #8795a6;
        background: #eef2f6;
        font-size: .62rem;
        font-weight: 800
    }

    .yr-month.has-work .yr-month-state {
        color: var(--yr-blue);
        background: #e9f2ff
    }

    .yr-month-main {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        padding: 12px 8px
    }

    .yr-month-value {
        padding: 0 8px;
        border-left: 1px solid #edf1f6;
        text-align: center
    }

    .yr-month-value:last-child { border-left: 0 }

    .yr-month-value small {
        display: block;
        color: var(--yr-muted);
        font-size: .61rem
    }

    .yr-month-value strong {
        display: block;
        margin-top: 3px;
        color: #52677f;
        font-size: .78rem
    }

    .yr-month.has-work .yr-month-value strong { color: var(--yr-navy) }

    .yr-progress {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
        padding: 0 12px 12px
    }

    .yr-progress span {
        padding: 6px 5px;
        border-radius: 7px;
        color: #68798d;
        background: #f0f4f8;
        font-size: .6rem;
        text-align: center
    }

    .yr-progress span:nth-child(1) { color: #1769e0; background: #eaf3ff }
    .yr-progress span:nth-child(2) { color: #9a6900; background: #fff4da }
    .yr-progress span:nth-child(3) { color: #168553; background: #e8f8ef }

    .yr-empty-note {
        padding: 11px 15px;
        border-top: 1px solid #edf1f6;
        color: var(--yr-muted);
        background: #fbfdff;
        font-size: .68rem
    }

    @media(max-width:992px) {
        .yr-summary { grid-template-columns: repeat(2, minmax(0, 1fr)) }
        .yr-months { grid-template-columns: repeat(2, minmax(0, 1fr)) }
    }

    @media(max-width:650px) {
        .yr-shell { width: min(100% - 20px, 1400px); padding-top: 18px }
        .yr-summary,
        .yr-months { grid-template-columns: 1fr }
        .yr-panel-head { align-items: flex-start; flex-direction: column }
    }
</style>

<section class="main-content year-report-page" dir="rtl">
    <div class="yr-shell">
        @include('inc.message')

        <header class="yr-head">
            <h1><i class="fas fa-chart-bar ml-2 text-primary"></i>سال بھر کے آرڈرز</h1>
            <p>پورے سال کے آرڈرز، سوٹ، سلائی کی رقم اور کام کی حالت ایک نظر میں دیکھیں۔</p>
        </header>

        <div class="yr-summary">
            <div class="yr-summary-card">
                <span class="yr-summary-icon"><i class="fas fa-clipboard-list"></i></span>
                <div><small>کل آرڈرز</small><strong>{{ number_format($totals['orders']) }}</strong></div>
            </div>
            <div class="yr-summary-card is-suits">
                <span class="yr-summary-icon"><i class="fas fa-tshirt"></i></span>
                <div><small>کل سوٹ</small><strong>{{ number_format($totals['suits']) }}</strong></div>
            </div>
            <div class="yr-summary-card is-income">
                <span class="yr-summary-icon"><i class="fas fa-coins"></i></span>
                <div><small>کل سلائی کی رقم</small><strong>Rs. {{ number_format($totals['payment']) }}</strong></div>
            </div>
            <div class="yr-summary-card is-complete">
                <span class="yr-summary-icon"><i class="fas fa-check-circle"></i></span>
                <div><small>مکمل آرڈرز</small><strong>{{ number_format($totals['completed']) }}</strong></div>
            </div>
        </div>

        <section class="yr-panel">
            <div class="yr-panel-head">
                <h2>مہینوں کی تفصیل</h2>
                <span><i class="fas fa-info-circle ml-1"></i>کام والے مہینے نیلے رنگ میں نمایاں ہیں</span>
            </div>
            <div class="yr-months">
                @foreach ($monthly_orders as $monthName => $orders)
                    @php($hasWork = (int) $orders['orders'] > 0)
                    <article class="yr-month {{ $hasWork ? 'has-work' : '' }}">
                        <div class="yr-month-head">
                            <strong>{{ $monthNames[$monthName] ?? $monthName }}</strong>
                            <span class="yr-month-state">{{ $hasWork ? $orders['orders'].' آرڈر' : 'کوئی آرڈر نہیں' }}</span>
                        </div>
                        <div class="yr-month-main">
                            <div class="yr-month-value"><small>آرڈرز</small><strong>{{ number_format($orders['orders']) }}</strong></div>
                            <div class="yr-month-value"><small>سوٹ</small><strong>{{ number_format($orders['suits']) }}</strong></div>
                            <div class="yr-month-value"><small>سلائی کی رقم</small><strong>Rs. {{ number_format($orders['payment']) }}</strong></div>
                        </div>
                        @if ($hasWork)
                            <div class="yr-progress">
                                <span><i class="fas fa-plus-circle ml-1"></i>نئے: {{ $orders['neworders'] }}</span>
                                <span><i class="fas fa-cut ml-1"></i>سلائی جاری: {{ $orders['inprocessorders'] }}</span>
                                <span><i class="fas fa-check ml-1"></i>مکمل: {{ $orders['completed'] }}</span>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="yr-empty-note"><i class="fas fa-lightbulb ml-1 text-warning"></i>جس مہینے میں آرڈر نہ ہو، اسے ہلکے رنگ میں دکھایا گیا ہے تاکہ کام والے مہینے فوراً نظر آئیں۔</div>
        </section>
    </div>
</section>
@endsection
