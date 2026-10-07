@extends('main')
@section('content')
<section class="main-content size-chart-page" dir="rtl">
    <div class="container-fluid px-3 px-lg-5 py-4">
        <div class="size-chart-head mb-4">
            <div>
                <a href="{{ route('admin.measurement-templates.index') }}" class="font-weight-bold"><i class="fas fa-arrow-right ml-1"></i> پیمائش ٹیمپلیٹس</a>
                <h1 class="h3 font-weight-bold mt-2 mb-1">{{ $template->name }} — آن لائن سائز چارٹ</h1>
                <p class="text-muted mb-0">Small، Medium، Large یا اپنی مرضی کے سائز محفوظ کریں۔ یہاں صرف جسمانی پیمائش شامل ہوتی ہے، سلائی کی پسند نہیں۔</p>
            </div>
            <button class="btn btn-primary mt-3 mt-lg-0" data-toggle="modal" data-target="#newStandardProfile"><i class="fas fa-plus ml-1"></i> نیا سائز</button>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><strong>براہ کرم معلومات درست کریں:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="card size-chart-card">
            <div class="card-body p-0">
                @forelse($template->standardProfiles->where('is_active', true) as $profile)
                    <div class="size-row">
                        <div><strong>{{ $profile->name }}</strong><small>{{ count($profile->measurement_values ?? []) }} پیمائشیں</small></div>
                        <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#profile-{{ $profile->id }}"><i class="fas fa-pen ml-1"></i> ترمیم</button>
                    </div>
                @empty
                    <div class="empty-size"><i class="fas fa-tags"></i><strong>ابھی کوئی آن لائن سائز محفوظ نہیں</strong><span>پہلا سائز بنانے کے لیے “نیا سائز” دبائیں۔</span></div>
                @endforelse
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="newStandardProfile" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content" dir="rtl">
    <div class="modal-header"><div><h2 class="h5 mb-1">نیا آن لائن سائز</h2><p class="small text-muted mb-0">{{ $template->name }} کے عددی ناپ درج کریں۔</p></div><button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں"><span>&times;</span></button></div>
    <form method="POST" action="{{ route('admin.standard-measurement-profiles.store', $template) }}">@csrf<div class="modal-body">@include('measurement-templates.partials.standard-profile-form', ['profile' => null])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button class="btn btn-primary">سائز محفوظ کریں</button></div></form>
</div></div></div>

@foreach($template->standardProfiles->where('is_active', true) as $profile)
<div class="modal fade" id="profile-{{ $profile->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content" dir="rtl">
    <div class="modal-header"><h2 class="h5 mb-0">{{ $profile->name }} میں ترمیم</h2><button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں"><span>&times;</span></button></div>
    <form method="POST" action="{{ route('admin.standard-measurement-profiles.update', $profile) }}">@csrf @method('PUT')<div class="modal-body">@include('measurement-templates.partials.standard-profile-form', ['profile' => $profile])</div><div class="modal-footer justify-content-between"><button class="btn btn-primary">تبدیلی محفوظ کریں</button></form><form method="POST" action="{{ route('admin.standard-measurement-profiles.destroy', $profile) }}" data-confirm="کیا اس سائز کو غیر فعال کرنا ہے؟">@csrf @method('DELETE')<button class="btn btn-link text-danger">غیر فعال کریں</button></form></div>
</div></div></div>
@endforeach

<style>
.size-chart-page{background:#f4f7fa;min-height:calc(100vh - 70px)}.size-chart-head{display:flex;justify-content:space-between;align-items:center;gap:1rem}.size-chart-card{border:0;border-radius:18px;box-shadow:0 9px 25px rgba(31,45,61,.08);overflow:hidden}.size-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid #edf1f5}.size-row:last-child{border-bottom:0}.size-row strong,.size-row small{display:block}.size-row small{color:#718096;margin-top:.2rem}.empty-size{display:flex;flex-direction:column;align-items:center;text-align:center;padding:4rem;color:#718096}.empty-size i{font-size:2.2rem;color:#8aadd0;margin-bottom:.75rem}.empty-size strong{color:#28445f;margin-bottom:.25rem}.profile-measurement-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.8rem}@media(max-width:767px){.size-chart-head{align-items:flex-start;flex-direction:column}}
</style>
@endsection
