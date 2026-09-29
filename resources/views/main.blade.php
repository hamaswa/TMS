@include('inc/header')
@include('components.subscription-alert')
@if(Auth::check() && Auth::user()->business?->isDemo())
    <div class="px-3 px-md-4 pt-3" role="status">
        <div class="alert alert-info mb-0 d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem">
            <strong>آپ عوامی ڈیمو اکاؤنٹ استعمال کر رہے ہیں۔</strong>
            <span>نمونہ ڈیٹا روزانہ خودکار طور پر اپنی اصل حالت میں واپس آ جاتا ہے۔</span>
        </div>
    </div>
@endif
@if(session('warning'))
    <div class="px-3 px-md-4 pt-3" role="alert" aria-live="polite">
        <div class="alert alert-warning mb-0">{{ session('warning') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="px-3 px-md-4 pt-3" role="alert" aria-live="polite">
        <div class="alert alert-danger mb-0">{{ session('error') }}</div>
    </div>
@endif
@yield('content')
@include('inc/footer')
