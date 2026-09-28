@extends('main')

@push('styles')
<style>
    .set-label-page{padding:28px;background:#f5f7fa;min-height:100vh}.set-label-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:22px}.set-label-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(245px,1fr));gap:16px}.set-label{background:#fff;border:1px solid #ccd6e2;border-radius:14px;padding:18px;text-align:center;break-inside:avoid}.set-label svg{display:block;width:180px;height:180px;max-width:100%;margin:0 auto 10px}.set-label h2{font-size:1.05rem;font-weight:800;margin:0;color:#123b5d}.set-label p{margin:5px 0;color:#536672}.set-label-code{direction:ltr;font:700 .72rem/1.4 monospace;word-break:break-all;color:#147a5a}.set-label-colors{font-size:.78rem}.print-actions{display:flex;gap:9px}
    @media print{header,.sidebar,.set-label-head,.footer{display:none!important}.main-content,.set-label-page{margin:0!important;padding:0!important;background:#fff}.set-label-grid{grid-template-columns:repeat(3,1fr);gap:8mm}.set-label{border:1px solid #000;border-radius:0;padding:5mm}.set-label svg{width:42mm;height:42mm}}
</style>
@endpush

@section('content')
<section class="main-content set-label-page" dir="rtl">
    <div class="set-label-head">
        <div><h1 class="h3 mb-1">کپڑے کے سیٹ QR لیبل</h1><p class="text-muted mb-0">ہر مکمل سیٹ کے لیے ایک QR — تمام رنگ اسی سیٹ میں شامل ہیں۔</p></div>
        <div class="print-actions"><a class="btn btn-outline-secondary" href="{{ route('admin.cloth.index') }}">واپس</a><button class="btn btn-primary" type="button" onclick="window.print()"><i class="fas fa-print ml-1"></i> لیبل پرنٹ کریں</button></div>
    </div>
    <div class="set-label-grid">
        @forelse($cloths as $cloth)
            <article class="set-label" id="cloth-set-{{ $cloth->id }}">
                {!! $cloth->qr_svg !!}
                <h2>{{ $cloth->brand?->name ?? 'Brand' }} — {{ $cloth->type?->name ?? 'Cloth' }}</h2>
                @if($cloth->tracksColors())<p class="set-label-colors">رنگ: {{ $cloth->colors->pluck('color')->join('، ') }}</p>@else<p class="set-label-colors">رنگ کے بغیر مجموعی سیٹ</p>@endif
                <div class="set-label-code">{{ $cloth->set_code }}</div>
            </article>
        @empty
            <div class="alert alert-info">پہلے انوینٹری میں کپڑا شامل کریں۔</div>
        @endforelse
    </div>
</section>
@endsection
