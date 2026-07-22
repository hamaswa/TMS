@extends('main')
@section('content')
<section class="main-content" dir="rtl">
    <div class="container-fluid px-3 px-md-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div class="text-right">
                <h1 class="h3 mb-1">{{ $customer->name }} کا مشترکہ کھاتہ</h1>
                <div class="text-muted">{{ $customer->phone_number1 }} · ٹیلرنگ اور دکان کا مکمل ریکارڈ</div>
            </div>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">واپس جائیں</a>
        </div>

        @php
            $tailorBalance = (float) ($balances['Tailor'] ?? 0);
            $saleBalance = (float) ($balances['Sale'] ?? 0);
            $totalBalance = (float) $balances->sum();
        @endphp
        <div class="row mb-4">
            <div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body text-right"><div class="text-muted">کل بقایا</div><div class="h3 mb-0">Rs {{ number_format($totalBalance, 2) }}</div></div></div></div>
            <div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body text-right"><div class="text-muted">ٹیلرنگ بقایا</div><div class="h3 mb-0">Rs {{ number_format($tailorBalance, 2) }}</div></div></div></div>
            <div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body text-right"><div class="text-muted">دکان بقایا</div><div class="h3 mb-0">Rs {{ number_format($saleBalance, 2) }}</div></div></div></div>
        </div>

        <div class="card mb-4">
            <div class="card-header text-right"><strong>تمام لین دین</strong></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-right">
                    <thead><tr><th>تاریخ</th><th>شعبہ</th><th>حوالہ</th><th>وصول شدہ</th><th>بقایا تبدیلی</th><th>تفصیل</th></tr></thead>
                    <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at?->format('d-m-Y') }}</td>
                            <td>{{ $transaction->Order_type === 'Tailor' ? 'ٹیلرنگ' : ($transaction->Order_type === 'Sale' ? 'دکان' : 'دیگر') }}</td>
                            <td>{{ $transaction->orderId ? 'آرڈر #'.$transaction->orderId : ($transaction->sale_id ? 'فروخت #'.$transaction->sale_id : 'ادائیگی') }}</td>
                            <td>Rs {{ number_format((float) $transaction->recivedPayment, 2) }}</td>
                            <td>Rs {{ number_format((float) $transaction->remainingBalance, 2) }}</td>
                            <td>{{ $transaction->comment ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">ابھی کوئی لین دین موجود نہیں۔</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())<div class="card-footer">{{ $transactions->links() }}</div>@endif
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4"><div class="card h-100"><div class="card-header text-right"><strong>حالیہ ٹیلرنگ آرڈرز</strong></div><div class="table-responsive"><table class="table mb-0 text-right"><thead><tr><th>آرڈر</th><th>تاریخ</th><th>رقم</th><th>حالت</th></tr></thead><tbody>@forelse($orders as $order)<tr><td>#{{ $order->id }}</td><td>{{ $order->created_at?->format('d-m-Y') }}</td><td>Rs {{ number_format((float) $order->totalPayment, 2) }}</td><td>{{ $order->status }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">کوئی ٹیلرنگ آرڈر نہیں۔</td></tr>@endforelse</tbody></table></div></div></div>
            <div class="col-lg-6 mb-4"><div class="card h-100"><div class="card-header text-right"><strong>حالیہ دکان فروخت</strong></div><div class="table-responsive"><table class="table mb-0 text-right"><thead><tr><th>فروخت</th><th>تاریخ</th><th>اشیاء</th></tr></thead><tbody>@forelse($sales as $sale)<tr><td>#{{ $sale->id }}</td><td>{{ $sale->created_at?->format('d-m-Y') }}</td><td>{{ $sale->detail()->count() }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">کوئی دکان فروخت نہیں۔</td></tr>@endforelse</tbody></table></div></div></div>
        </div>
    </div>
</section>
@endsection
