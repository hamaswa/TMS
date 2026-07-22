@extends('main')
@section('content')
<style>
    .workspace-hero{border-radius:20px;background:linear-gradient(135deg,#102a43,#1769a8);color:#fff!important;padding:2rem;box-shadow:0 18px 45px rgba(16,42,67,.16)}.workspace-hero h2{color:#fff!important}.workspace-hero p{color:rgba(255,255,255,.74)!important}
    .module-panel{border:0;border-radius:18px;box-shadow:0 10px 30px rgba(31,45,61,.08);overflow:hidden}.module-panel .card-header{border:0;padding:1.25rem 1.5rem}.module-icon{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;font-size:1.1rem}.metric-card{height:100%;padding:1rem;border:1px solid #e8edf3;border-radius:14px;background:#fff}.metric-card strong{display:block;font-size:1.45rem;color:#19324d}.quick-action{display:flex;align-items:center;gap:.65rem;padding:.8rem 1rem;border:1px solid #e4eaf1;border-radius:11px;color:#29445f;background:#fff;font-weight:600}.quick-action:hover{text-decoration:none;border-color:#72a8e8;background:#f5f9ff}
</style>
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4">
    <div class="workspace-hero mb-4 d-flex flex-wrap align-items-center justify-content-between"><div><span class="badge badge-light text-primary mb-2">{{ count(Auth::user()->enabledModules()) === 2 ? 'مشترکہ کاروباری نظام' : 'مخصوص کاروباری نظام' }}</span><h2 class="mb-2">خوش آمدید، {{ Auth::user()->name }}</h2><p class="mb-0 text-white-50">اس ڈیش بورڈ میں صرف آپ کے کاروبار کے لیے فعال سہولیات دکھائی گئی ہیں۔</p></div><a href="{{ route('admin.financial-reports.index') }}" class="btn btn-light mt-3 mt-md-0"><i class="fas fa-chart-line ml-1"></i> مالیاتی جائزہ</a></div>

    <div class="row">
        @if($tailoring)
        <div class="col-xl-{{ $clothing ? '6' : '12' }} mb-4"><div class="card module-panel h-100">
            <div class="card-header bg-white d-flex align-items-center"><span class="module-icon bg-primary text-white ml-3"><i class="fas fa-cut"></i></span><div><h4 class="mb-0">ٹیلرنگ سسٹم</h4><small class="text-muted">آرڈرز، ورکشاپ کی پیش رفت، پیمائش اور حوالگی</small></div></div>
            <div class="card-body"><div class="row mb-3">@foreach([['جاری کام',$tailoring['active']],['آج واجب',$tailoring['due_today']],['تیار',$tailoring['ready']],['اس ماہ کے سوٹ',$tailoring['month_suits']]] as [$label,$value])<div class="col-6 mb-3"><div class="metric-card"><small class="text-muted">{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></div>@endforeach</div>
                <div class="row"><div class="col-md-4 mb-2"><a class="quick-action" href="{{ route('admin.tailor-jobs.index') }}"><i class="fas fa-tasks text-primary"></i> کام کی فہرست</a></div><div class="col-md-4 mb-2"><a class="quick-action" href="{{ route('admin.Customers.index') }}"><i class="fas fa-user-friends text-primary"></i> گاہک</a></div><div class="col-md-4 mb-2"><a class="quick-action" href="{{ route('admin.Tailor.index') }}"><i class="fas fa-user-cog text-primary"></i> درزی</a></div></div>
            </div>
        </div></div>
        @endif

        @if($clothing)
        <div class="col-xl-{{ $tailoring ? '6' : '12' }} mb-4"><div class="card module-panel h-100">
            <div class="card-header bg-white d-flex align-items-center"><span class="module-icon bg-info text-white ml-3"><i class="fas fa-boxes"></i></span><div><h4 class="mb-0">کپڑے کی خرید و فروخت</h4><small class="text-muted">اسٹاک، فروخت، سپلائرز اور خریداری</small></div></div>
            <div class="card-body"><div class="row mb-3">@foreach([['اسٹاک میں میٹر',number_format($clothing['meters'],2)],['اسٹاک کی مالیت','روپے '.number_format($clothing['inventory_value'],0)],['کم اسٹاک اشیاء',$clothing['low_stock']],['زیرِ تیاری خریداریاں',$clothing['draft_purchases']],['اس ماہ کی فروخت','روپے '.number_format($clothing['month_sales'],0)]] as [$label,$value])<div class="col-6 mb-3"><div class="metric-card"><small class="text-muted">{{ $label }}</small><strong>{{ $value }}</strong></div></div>@endforeach</div>
                <div class="row"><div class="col-md-4 mb-2"><a class="quick-action" href="{{ route('admin.stock.index') }}"><i class="fas fa-layer-group text-info"></i> اسٹاک</a></div><div class="col-md-4 mb-2"><a class="quick-action" href="{{ route('admin.sellCloth') }}"><i class="fas fa-cash-register text-info"></i> نئی فروخت</a></div><div class="col-md-4 mb-2"><a class="quick-action" href="{{ route('admin.purchases.index') }}"><i class="fas fa-truck-loading text-info"></i> خریداری</a></div></div>
            </div>
        </div></div>
        @endif
    </div>
</div></section>
@endsection
