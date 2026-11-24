@extends('main')
@section('content')
    @include('setting.partials.styles')
    <section class="main-content st-page">
        <div class="st-shell">
            <header class="st-hero">
                <span class="st-hero-icon"><i class="fas fa-store"></i></span>
                <div class="st-hero-text">
                    <h1>نئی دکان ترتیب</h1>
                    <p>صرف دکان کا نام ضروری ہے۔ لوگو، رابطہ، نوٹ اور پتہ بعد میں بھی شامل کیے جا سکتے ہیں۔</p>
                </div>
                <span class="st-hero-art" aria-hidden="true"><i class="fas fa-ruler-combined"></i></span>
            </header>
            @include('inc.message')
            <div class="st-grid">
                @include('setting.partials.form', [
                    'action' => route('admin.insert-setting'),
                    'setting' => null,
                    'submitLabel' => 'محفوظ کریں',
                ])
                @include('setting.partials.side')
            </div>
        </div>
    </section>
@endsection
