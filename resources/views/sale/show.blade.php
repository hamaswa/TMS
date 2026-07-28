@extends('main')

@push('styles')
<style>
@media (max-width: 767.98px) {
    .sale-detail-table, .sale-detail-table tbody, .sale-detail-table tr, .sale-detail-table td { display:block; width:100%; }
    .sale-detail-table thead { display:none; }
    .sale-detail-table tr { border:1px solid #e3e8ee; border-radius:.65rem; margin-bottom:1rem; padding:.4rem .75rem; }
    .sale-detail-table td { display:flex; justify-content:space-between; gap:1rem; border-top:1px solid #eef1f4; padding:.7rem 0; }
    .sale-detail-table td:first-child { border-top:0; }
    .sale-detail-table td::before { content:attr(data-label); flex:0 0 38%; color:#6c757d; font-weight:700; }
}
</style>
@endpush

@section('content')
@php
    $currentTransaction = $transaction->firstWhere('Order_type', 'Sale');
    $cancellationTransaction = $transaction->firstWhere('Order_type', 'Sale Cancellation');
    $saleTotal = $sale->detail->sum(fn ($detail) => (float) $detail->price * (int) $detail->quantity);
@endphp
<section class="main-content">
    <div class="container py-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-4" style="gap:.75rem">
                    <div>
                        <h1 class="h4 mb-1">{{ $sale->customer?->name ?? $sale->customer_name }} کی فروخت کی تفصیلات</h1>
                        <p class="text-muted mb-0">فروخت #{{ $sale->id }} · {{ $sale->created_at?->format('d-m-Y') }}</p>
                    </div>
                    <div class="d-flex flex-wrap" style="gap:.5rem">
                        <a href="{{ route('admin.sale.index') }}" class="btn btn-light">فروخت ریکارڈ</a>
                        @if($sale->status !== 'cancelled')<a href="{{ route('admin.sale.edit', $sale) }}" class="btn btn-outline-primary">تبدیل کریں</a>@endif
                        <a href="{{ route('admin.sale-print', $sale) }}" class="btn btn-primary">رسید پرنٹ کریں</a>
                    </div>
                </div>

                @if($sale->status === 'cancelled')
                    <div class="alert alert-danger">
                        <strong>یہ فروخت منسوخ ہے۔</strong>
                        {{ $sale->cancelled_at?->format('d-m-Y H:i') }}
                        @if($sale->cancellation_reason)<br>وجہ: {{ $sale->cancellation_reason }}@endif
                        <br>اصل رسید محفوظ ہے اور گاہک کے کھاتے میں منسوخی کی واپسی الگ درج کی گئی ہے۔
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table sale-detail-table">
                        <thead>
                            <tr>
                                <th scope="col">پروڈکٹ کا نام</th>
                                <th scope="col">تعداد</th>
                                <th scope="col">فی عدد قیمت</th>
                                <th scope="col">کل قیمت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->detail as $detail)
                                <tr>
                                    <td data-label="پروڈکٹ کا نام">{{ $detail->product_name }}</td>
                                    <td data-label="تعداد">{{ $detail->quantity }}</td>
                                    <td data-label="فی عدد قیمت">Rs {{ number_format((float) $detail->price, 2) }}</td>
                                    <td data-label="کل قیمت">Rs {{ number_format((float) $detail->price * (int) $detail->quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4 mb-3"><div class="border rounded p-3 h-100"><span class="text-muted">فروخت کی کل رقم</span><strong class="d-block h5 mb-0">Rs {{ number_format($saleTotal, 2) }}</strong></div></div>
                    <div class="col-md-4 mb-3"><div class="border rounded p-3 h-100"><span class="text-muted">موصول شدہ رقم</span><strong class="d-block h5 mb-0">Rs {{ number_format((float) ($currentTransaction?->recivedPayment ?? 0), 2) }}</strong></div></div>
                    <div class="col-md-4 mb-3"><div class="border rounded p-3 h-100"><span class="text-muted">اس فروخت کا بقایا</span><strong class="d-block h5 mb-0">Rs {{ number_format((float) ($currentTransaction?->remainingBalance ?? 0), 2) }}</strong></div></div>
                </div>

                @if($currentTransaction)
                    <div class="border rounded p-3">
                        <strong>ادائیگی:</strong>
                        {{ \App\Support\PaymentMethods::LABELS[$currentTransaction->payment_method ?? 'cash'] ?? $currentTransaction->payment_method }}
                        @if($currentTransaction->payment_reference)
                            · حوالہ: <span dir="ltr">{{ $currentTransaction->payment_reference }}</span>
                        @endif
                        @if($currentTransaction->paid_on)
                            · تاریخ: {{ $currentTransaction->paid_on->format('d-m-Y') }}
                        @endif
                    </div>
                @endif

                @if($sale->status !== 'cancelled')
                    <div class="card border-danger mt-4">
                        <div class="card-header text-danger">فروخت منسوخ کریں</div>
                        <div class="card-body">
                            <p class="text-muted">منسوخی سے رسید حذف نہیں ہو گی۔ اس فروخت کا بقایا اور وصول شدہ رقم کھاتے میں الگ واپسی کے ذریعے صفر کی جائے گی۔</p>
                            <form action="{{ route('admin.sale.destroy', $sale) }}" method="POST" data-confirm="کیا آپ یہ فروخت منسوخ کر کے گاہک کا کھاتہ واپس کرنا چاہتے ہیں؟">@csrf @method('DELETE')
                                <div class="form-group"><label for="cancellation_reason">منسوخی کی وجہ</label><textarea id="cancellation_reason" name="cancellation_reason" maxlength="1000" minlength="5" class="form-control" required>{{ old('cancellation_reason') }}</textarea></div>
                                @if((float)($currentTransaction?->recivedPayment ?? 0) > 0)
                                    <div class="alert alert-warning">وصول شدہ روپے {{ number_format((float)$currentTransaction->recivedPayment,2) }} واپس کرنے کی تفصیل درج کریں۔</div>
                                    <div class="form-row">
                                        <x-payment-method-fields prefix="sale_refund" method-name="refund_method" reference-name="refund_reference" method-group-class="form-group col-md-6" reference-group-class="form-group col-md-6" />
                                    </div>
                                @endif
                                <button class="btn btn-danger">فروخت منسوخ اور کھاتہ واپس کریں</button>
                            </form>
                        </div>
                    </div>
                @elseif($cancellationTransaction)
                    <div class="border rounded p-3 mt-3">
                        <strong>رقم واپسی:</strong> {{ \App\Support\PaymentMethods::label($cancellationTransaction->payment_method) }}
                        @if($cancellationTransaction->payment_reference) · حوالہ: <span dir="ltr">{{ $cancellationTransaction->payment_reference }}</span>@endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
