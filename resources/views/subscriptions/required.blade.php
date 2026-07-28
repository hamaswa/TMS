@extends('main')

@section('content')
<div class="container py-5" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5 text-center">
                    <div class="display-5 text-warning mb-3"><i class="fa fa-lock"></i></div>
                    <h1 class="h3 mb-3">کاروباری سبسکرپشن فعال نہیں ہے</h1>
                    @if($nextSubscription)
                        <p class="text-muted mb-4">
                            نئی سبسکرپشن {{ $nextSubscription->starts_on->format('d-m-Y') }} سے شروع ہوگی۔
                            اس تاریخ تک کاروباری کام محدود رہے گا۔
                        </p>
                    @elseif($latestSubscription)
                        <p class="text-muted mb-4">
                            آخری سبسکرپشن {{ $latestSubscription->ends_on->format('d-m-Y') }} کو ختم ہوئی۔
                            تجدید کے لیے سپر ایڈمن سے رابطہ کریں۔
                        </p>
                    @else
                        <p class="text-muted mb-4">سبسکرپشن کی تجدید کے لیے کاروبار کے مالک یا سپر ایڈمن سے رابطہ کریں۔</p>
                    @endif

                    @if($user->isBusinessOwner())
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a class="btn btn-primary" href="{{ route('admin.subscription.index') }}">سبسکرپشن کی تفصیل</a>
                            <a class="btn btn-outline-secondary" href="{{ route('admin.notifications.index') }}">اطلاعات دیکھیں</a>
                        </div>
                        <p class="small text-muted mt-4 mb-0">آپ کا کاروباری ڈیٹا محفوظ ہے۔ تجدید کے بعد مکمل رسائی بحال ہو جائے گی۔</p>
                    @else
                        <p class="mb-0">براہ کرم کاروبار کے مالک {{ $business->owner?->name }} سے رابطہ کریں۔</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
