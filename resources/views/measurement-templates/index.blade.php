@extends('main')
@section('content')
<section class="main-content template-page" dir="rtl">
    <div class="container-fluid px-3 px-lg-5 py-4">
        <div class="template-hero mb-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
                <div>
                    <span class="badge badge-light text-primary mb-2">پیمائش کی ترتیب</span>
                    <h1 class="h3 font-weight-bold mb-1">لباس کے پیمائش ٹیمپلیٹس</h1>
                    <p class="mb-0">ہر لباس کا الگ، صاف فارم بنائیں اور اسی کے متعلقہ ناپ اور سلائی کی پسند دکھائیں۔</p>
                </div>
                <div class="template-hero-actions mt-3 mt-lg-0">
                    <a href="{{ route('admin.measurement-templates.create') }}" class="btn btn-light text-primary"><i class="fas fa-plus ml-1"></i> نیا ٹیمپلیٹ</a>
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div><h2 class="h4 font-weight-bold mb-1">آپ کے ٹیمپلیٹس</h2><p class="text-muted mb-0">ترمیم کے لیے صرف مطلوبہ لباس کھولیں؛ باقی صفحہ مختصر رہے گا۔</p></div>
            <span class="badge badge-secondary px-3 py-2">{{ $templates->where('is_active', true)->count() }} فعال</span>
        </div>

        <div class="row">
            @forelse($templates as $template)
                @php
                    $system = collect($template->system_fields ?? []);
                    $measurementCount = $system->filter(fn($key) => data_get(\App\Services\MeasurementService::SYSTEM_FIELDS, $key.'.unit') !== '')->count();
                    $preferenceCount = $system->filter(fn($key) => data_get(\App\Services\MeasurementService::SYSTEM_FIELDS, $key.'.unit') === '')->count();
                    $customCount = count($template->custom_field_ids ?? []);
                @endphp
                <div class="col-xl-4 col-md-6 mb-4">
                    <article class="card template-card h-100 {{ $template->is_active ? '' : 'template-inactive' }}">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div><h3 class="h5 font-weight-bold mb-2">{{ $template->name }}</h3>@if($template->is_builtin)<span class="badge badge-primary"><i class="fas fa-lock ml-1"></i> بنیادی ڈیفالٹ</span>@elseif($template->is_default)<span class="badge badge-primary">ڈیفالٹ</span>@endif @unless($template->is_active)<span class="badge badge-secondary">غیر فعال</span>@endunless</div>
                                <span class="template-count">{{ $measurementCount + $preferenceCount + $customCount }} خانے</span>
                            </div>
                            <p class="text-muted flex-grow-1">{{ $template->description ?: 'کوئی وضاحت درج نہیں۔' }}</p>
                            <div class="template-summary mb-3">
                                <span><i class="fas fa-ruler-combined"></i><strong>{{ $measurementCount }}</strong> جسمانی ناپ</span>
                                <span><i class="fas fa-cut"></i><strong>{{ $preferenceCount }}</strong> سلائی پسند</span>
                                <span><i class="fas fa-plus-square"></i><strong>{{ $customCount }}</strong> اضافی خانے</span>
                                <span><i class="fas fa-tags"></i><strong>{{ $template->standardProfiles->where('is_active', true)->count() }}</strong> معیاری سائز</span>
                            </div>
                            @if($template->is_active)
                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="{{ route('admin.measurement-templates.edit', $template) }}" class="btn btn-primary"><i class="fas fa-sliders-h ml-1"></i> ٹیمپلیٹ کھولیں</a>
                                    <div><a href="{{ route('admin.standard-measurement-profiles.index', $template) }}" class="btn btn-sm btn-link"><i class="fas fa-tags ml-1"></i> آن لائن سائز</a>@unless($template->is_builtin)<form class="d-inline" method="POST" action="{{ route('admin.measurement-templates.destroy', $template) }}" data-confirm="کیا اس ٹیمپلیٹ کو غیر فعال کرنا ہے؟">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">غیر فعال کریں</button></form>@endunless</div>
                                </div>
                            @endif
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12"><div class="card template-card"><div class="card-body empty-state"><i class="fas fa-clipboard-list"></i><strong>ابھی کوئی ٹیمپلیٹ نہیں بنایا گیا</strong><span>شلوار قمیض، واسکٹ یا کسی دوسرے لباس کا فارم بنائیں۔</span><a href="{{ route('admin.measurement-templates.create') }}" class="btn btn-primary mt-3">پہلا ٹیمپلیٹ بنائیں</a></div></div></div>
            @endforelse
        </div>
    </div>
</section>
@include('measurement-templates.partials.styles')
@endsection
