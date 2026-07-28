@extends('main')
@section('content')
@php($isSuperAdminSurface = request()->is('administrator') || request()->is('administrator/*'))
@php($useEnglish = $isSuperAdminSurface || (auth()->check() && auth()->user()->hasRole('administrative')))
<main class="main-content">
    <div class="container py-5">
        <div class="card border-0 shadow-sm mx-auto" style="max-width:680px">
            <div class="card-body p-5 text-center" @unless($useEnglish) dir="rtl" @endunless>
                <div class="display-4 font-weight-bold text-warning mb-3">403</div>
                @if($useEnglish)
                    <h1 class="h3">Access not permitted</h1>
                    <p class="text-muted mb-4">Your account does not have permission to open this page or perform this action.</p>
                    <a class="btn btn-primary" href="{{ route('administrator.index') }}">Return to dashboard</a>
                @else
                    <h1 class="h3">رسائی کی اجازت نہیں</h1>
                    <p class="text-muted mb-4">آپ کے اکاؤنٹ کو یہ صفحہ کھولنے یا یہ کام کرنے کی اجازت نہیں دی گئی۔ اگر یہ کام ضروری ہے تو اپنے کاروباری منتظم سے رابطہ کریں۔</p>
                    <a class="btn btn-primary" href="{{ route('admin.home') }}">ڈیش بورڈ پر واپس جائیں</a>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
