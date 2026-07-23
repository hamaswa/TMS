@extends('main')

@section('content')
<section class="main-content public-catalog-admin">
    <style>
        .public-catalog-admin{background:#f3f7f8;min-height:calc(100vh - 70px)}
        .catalog-hero{background:linear-gradient(135deg,#123c31,#167356);border-radius:20px;color:#fff;padding:1.5rem 1.7rem}
        .catalog-hero h1{color:#fff!important}.catalog-card{border:0;border-radius:18px;box-shadow:0 9px 28px rgba(31,45,61,.08);overflow:hidden}
        .catalog-photo{height:100%;min-height:230px;background:#e6efeb;display:grid;place-items:center;color:#6b8178;overflow:hidden}
        .catalog-photo img{width:100%;height:100%;object-fit:cover}.stock-pill{display:inline-block;border-radius:999px;padding:.3rem .7rem;font-weight:700}
        .stock-in{background:#e0f5e9;color:#17653d}.stock-out{background:#f8e5e5;color:#9a3131}
        @media(max-width:767px){.catalog-photo{min-height:190px}.catalog-hero{border-radius:14px}}
    </style>
    <div class="container-fluid px-3 px-md-4 py-4" dir="rtl">
        <div class="catalog-hero mb-4 d-flex flex-wrap justify-content-between align-items-center">
            <div><div class="small mb-1">آن لائن دکان</div><h1 class="h3 mb-1">کپڑوں کی عوامی فہرست</h1><p class="mb-0 text-white-50">موجودہ اسٹاک میں سے وہ کپڑے منتخب کریں جو گاہکوں کو دکھانے ہیں۔</p></div>
            <a href="{{ route('admin.storefront.edit') }}" class="btn btn-light mt-3 mt-md-0">دکان کی ترتیب</a>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="alert alert-info">یہ صفحہ اسٹاک میں تبدیلی نہیں کرتا۔ دستیاب مقدار ہمیشہ اصل انوینٹری سے خودکار طور پر دکھائی جاتی ہے۔</div>

        @forelse($cloths as $cloth)
            @php($listing=$listings->get($cloth->id))
            @php($image=$cloth->images->first(fn($item)=>$item->image_url))
            <div class="card catalog-card mb-4">
                <div class="row no-gutters">
                    <div class="col-md-3"><div class="catalog-photo">@if($image)<img src="{{ $image->image_url }}" alt="">@else<i class="fas fa-tshirt fa-3x"></i>@endif</div></div>
                    <div class="col-md-9">
                        <form method="POST" action="{{ route('admin.storefront.clothing.update',$cloth) }}" class="card-body">
                            @csrf @method('PUT')
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                                <div><h2 class="h5 mb-1">{{ $cloth->brand->name ?? 'بغیر برانڈ' }} — {{ $cloth->type->name ?? 'کپڑا' }}</h2><div class="text-muted">{{ $cloth->colors->pluck('color')->filter()->implode('، ') ?: 'رنگ درج نہیں' }}</div></div>
                                <div class="text-left"><span class="stock-pill {{ (float)$cloth->available_length>0?'stock-in':'stock-out' }}">{{ (float)$cloth->available_length>0 ? number_format($cloth->available_length,2).' میٹر دستیاب' : 'اسٹاک ختم' }}</span><div class="mt-2 font-weight-bold">Rs {{ number_format((float)($cloth->sale_price ?: $cloth->price),2) }} فی میٹر</div></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-7"><label>عوامی نام <span class="text-muted">(اختیاری)</span></label><input name="public_name" maxlength="180" class="form-control" value="{{ old('public_name',$listing?->public_name) }}" placeholder="{{ $cloth->brand->name ?? '' }} {{ $cloth->type->name ?? '' }}"></div>
                                <div class="form-group col-md-5"><label>ترتیب</label><input type="number" min="0" max="9999" name="sort_order" class="form-control" value="{{ old('sort_order',$listing?->sort_order ?? 0) }}"></div>
                            </div>
                            <div class="form-group"><label>عوامی تفصیل</label><textarea name="description" maxlength="2000" rows="3" class="form-control" placeholder="کپڑے کی ساخت، موسم، استعمال یا دوسری خصوصیات لکھیں۔">{{ old('description',$listing?->description) }}</textarea></div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <div><label class="ml-3"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured',$listing?->is_featured))> نمایاں کپڑا</label><label><input type="checkbox" name="is_published" value="1" @checked(old('is_published',$listing?->is_published))> عوام کو دکھائیں</label></div>
                                <button class="btn btn-primary px-4"><i class="fas fa-save ml-1"></i> محفوظ کریں</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="card catalog-card"><div class="card-body text-center py-5"><i class="fas fa-box-open fa-3x text-muted mb-3"></i><h2 class="h5">ابھی کوئی کپڑا موجود نہیں</h2><p class="text-muted">عوامی فہرست بنانے سے پہلے کپڑے اور اسٹاک شامل کریں۔</p><a href="{{ route('admin.cloth.create') }}" class="btn btn-primary">کپڑا شامل کریں</a></div></div>
        @endforelse
        @if($cloths->hasPages())<div class="mt-3">{{ $cloths->links() }}</div>@endif
    </div>
</section>
@endsection
