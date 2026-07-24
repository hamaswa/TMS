<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>درزی کی ہفتہ وار رپورٹ</title>
    <style>
        @font-face {
            font-family: "Noto Nastaliq Urdu";
            src: url("/assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2") format("woff2");
            font-display: swap;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f5f7; color: #172b3a; font-family: "Noto Nastaliq Urdu", Tahoma, sans-serif; }
        .actions { width: 88mm; display: flex; gap: 8px; padding: 12px 8px 4px; }
        .actions button { border: 0; border-radius: 6px; padding: 8px 12px; background: #1677c8; color: #fff; cursor: pointer; font-family: inherit; }
        .receipt { width: 88mm; min-height: 120mm; margin: 8px; padding: 7mm 5mm; background: #fff; box-shadow: 0 8px 30px rgba(18, 38, 53, .14); }
        .logo { display: block; max-width: 90px; max-height: 70px; margin: 0 auto 8px; object-fit: contain; }
        h1, h2, p { margin: 0; }
        h1 { text-align: center; font-size: 18px; }
        h2 { text-align: center; font-size: 16px; margin: 8px 0 3px; }
        .period { text-align: center; color: #52616b; font-size: 11px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 11px; }
        th, td { border-bottom: 1px solid #dfe5e8; padding: 6px 3px; text-align: right; vertical-align: top; word-break: break-word; }
        th { background: #edf4f8; font-weight: 700; }
        .empty { text-align: center; color: #637381; padding: 20px 4px; }
        .summary { margin-top: 14px; border-top: 2px solid #172b3a; padding-top: 8px; }
        .summary-row { display: flex; justify-content: space-between; gap: 10px; padding: 4px 0; font-size: 12px; }
        .summary-row.total { font-size: 15px; font-weight: 800; border-top: 1px solid #ccd6dc; margin-top: 5px; padding-top: 8px; }
        .footer { text-align: center; margin-top: 18px; border-top: 1px dashed #aebbc3; padding-top: 10px; font-size: 10px; color: #52616b; }
        @media print {
            body { background: #fff; }
            .actions { display: none !important; }
            .receipt { margin: 0; box-shadow: none; }
            @page { size: 88mm auto; margin: 0; }
        }
    </style>
    @include('print.partials.document-styles')
</head>
<body class="tms-worker-print">
@include('print.partials.toolbar')
@php
    $groupedReports = $tailor_report->groupBy(fn ($report) => $report->created_at->format('d-m-Y'));
    $totalSuits = (float) $tailor_report->sum('suitQuantity');
    $totalEarnings = (float) $tailor_report->sum(fn ($report) => $report->tailor_price * $report->suitQuantity);
    $totalAdvance = (float) $tailor_records->where('comment', 'advance')->sum('amount');
    $totalTea = (float) $tailor_records->where('comment', 'chai')->sum('amount');
    $totalSalary = (float) $tailor_records->where('comment', 'salary')->sum('amount');
    $netTotal = $totalSalary + $totalTea - ($transaction ? 0 : $totalAdvance);
@endphp
<div class="actions">
    <button type="button" onclick="window.print()">رپورٹ پرنٹ کریں</button>
    <button type="button" onclick="window.history.back()">واپس جائیں</button>
</div>
<main class="receipt">
    @if($setting->logo_url)
        <img class="logo" src="{{ $setting->logo_url }}" alt="{{ $setting->name }} لوگو">
    @endif
    <h1>{{ $setting->name }}</h1>
    <h2>{{ $tailor->name }} — ہفتہ وار رپورٹ</h2>
    <p class="period">{{ now()->startOfWeek(\Carbon\Carbon::SATURDAY)->format('d-m-Y') }} تا {{ now()->format('d-m-Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>تاریخ</th>
                <th>سوٹ</th>
                <th>اجرت</th>
            </tr>
        </thead>
        <tbody>
        @forelse($groupedReports as $date => $reports)
            <tr>
                <td>{{ $date }}</td>
                <td>{{ number_format((float) $reports->sum('suitQuantity'), 0) }}</td>
                <td>روپے {{ number_format((float) $reports->sum(fn ($report) => $report->tailor_price * $report->suitQuantity), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="empty">اس ہفتے سلائی کا کوئی آرڈر ریکارڈ نہیں ہوا۔</td></tr>
        @endforelse
        </tbody>
    </table>

    <section class="summary">
        <div class="summary-row"><span>کل سوٹ</span><strong>{{ number_format($totalSuits, 0) }}</strong></div>
        <div class="summary-row"><span>کل سلائی اجرت</span><strong>روپے {{ number_format($totalEarnings, 2) }}</strong></div>
        <div class="summary-row"><span>درج شدہ تنخواہ</span><strong>روپے {{ number_format($totalSalary, 2) }}</strong></div>
        @if($totalAdvance > 0)
            <div class="summary-row"><span>ایڈوانس</span><strong>روپے {{ number_format($totalAdvance, 2) }}</strong></div>
        @endif
        @if($totalTea > 0)
            <div class="summary-row"><span>دیگر خرچ</span><strong>روپے {{ number_format($totalTea, 2) }}</strong></div>
        @endif
        <div class="summary-row total"><span>ہفتہ وار حساب</span><strong>روپے {{ number_format($netTotal, 2) }}</strong></div>
    </section>

    <div class="footer">
        <div>{{ $setting->address }}</div>
        <div>{{ $setting->contact_no }}</div>
    </div>
</main>
@include('print.partials.qr')
</body>
</html>
