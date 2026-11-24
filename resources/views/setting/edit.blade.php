@extends('main')
@section('content')
    @include('setting.partials.styles')
    <section class="main-content st-page">
        <div class="st-shell">
            <header class="st-hero">
                <span class="st-hero-icon"><i class="fas fa-cog"></i></span>
                <div class="st-hero-text">
                    <h1>دکان کی سیٹنگز میں تبدیلی</h1>
                    <p>دکان کی معلومات اور رسید / پرنٹ کی ترتیب اپ ڈیٹ کریں</p>
                </div>
                <span class="st-hero-art" aria-hidden="true"><i class="fas fa-ruler-combined"></i></span>
            </header>
            @include('inc.message')
            <div class="st-grid">
                @include('setting.partials.form', [
                    'action' => route('admin.update-setting', $setting->id),
                    'setting' => $setting,
                    'submitLabel' => 'تبدیلیاں محفوظ کریں',
                ])
                @include('setting.partials.side')
            </div>
        </div>
    </section>
@endsection
