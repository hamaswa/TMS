@extends('main')
@section('content')
@php($isBusinessSurface = request()->is('admin') || request()->is('admin/*'))
@php($useUrdu = $isBusinessSurface || (auth()->check() && auth()->user()->isBusinessMember()))
<main class="main-content">
    <div class="container py-5">
        <div class="card border-0 shadow-sm mx-auto" style="max-width:680px">
            <div class="card-body p-5 text-center" @if($useUrdu) dir="rtl" @endif>
                <div class="display-4 font-weight-bold text-primary mb-3">404</div>
                @if($useUrdu)
                    <h1 class="h3">صفحہ نہیں ملا</h1>
                    <p class="text-muted mb-4">درخواست کردہ صفحہ موجود نہیں یا آپ کے اکاؤنٹ کے لیے دستیاب نہیں ہے۔</p>
                    <a class="btn btn-primary" href="{{ route('admin.home') }}">ڈیش بورڈ پر واپس جائیں</a>
                @else
                    <h1 class="h3">Page not found</h1>
                    <p class="text-muted mb-4">The requested page does not exist or is not available to your account.</p>
                    <a class="btn btn-primary" href="{{ request()->is('administrator') || request()->is('administrator/*') ? route('administrator.index') : url('/') }}">Return to dashboard</a>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
