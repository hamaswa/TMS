@extends('main')

@section('content')
<style>
    .tailor-rates-page{--rate-blue:#1769e0;--rate-navy:#102a50;--rate-muted:#6d7f94;--rate-line:#e0e8f2;direction:rtl;padding:28px 0 50px}
    .tailor-rates-shell{width:min(100% - 32px,1250px);margin-inline:auto}
    .tailor-rates-head{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:22px 24px;margin-bottom:18px;border:1px solid #dce7f5;border-radius:18px;background:linear-gradient(135deg,#f4f8ff,#fff);box-shadow:0 8px 28px rgba(21,47,81,.06)}
    .tailor-rates-title{display:flex;align-items:center;gap:15px}.tailor-rates-avatar{display:grid;place-items:center;flex:0 0 58px;width:58px;height:58px;border-radius:17px;color:#fff;background:linear-gradient(135deg,#2479ee,#0c5bd1);font-size:1.35rem;font-weight:800;box-shadow:0 9px 20px rgba(23,105,224,.2)}
    .tailor-rates-title small{display:block;margin-bottom:3px;color:var(--rate-muted)}.tailor-rates-title h1{margin:0;color:var(--rate-navy);font-size:clamp(1.35rem,2vw,1.8rem);font-weight:800}
    .tailor-rates-actions{display:flex;flex-wrap:wrap;gap:9px}.tailor-rate-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:9px 15px;border:1px solid #d4deeb;border-radius:11px;color:#40566f;background:#fff;font-weight:800;text-decoration:none!important}.tailor-rate-btn:hover{color:var(--rate-blue);border-color:#a9c9f3}.tailor-rate-btn.is-primary{color:#fff;border-color:var(--rate-blue);background:var(--rate-blue);box-shadow:0 7px 16px rgba(23,105,224,.18)}
    .tailor-rates-intro{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:14px}.tailor-rates-intro h2{margin:0 0 4px;color:var(--rate-navy);font-size:1.15rem;font-weight:800}.tailor-rates-intro p{margin:0;color:var(--rate-muted);font-size:.82rem}.tailor-rates-count{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:999px;color:#1769e0;background:#eaf3ff;font-weight:800;white-space:nowrap}
    .tailor-rates-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.tailor-rate-card{position:relative;overflow:hidden;padding:18px;border:1px solid var(--rate-line);border-radius:16px;background:#fff;box-shadow:0 7px 24px rgba(21,47,81,.05);transition:transform .18s ease,border-color .18s ease}.tailor-rate-card:hover{transform:translateY(-2px);border-color:#bed5f3}
    .tailor-rate-card-head{display:flex;align-items:center;gap:13px}.tailor-rate-icon{display:grid;place-items:center;flex:0 0 48px;width:48px;height:48px;border-radius:14px;color:#1769e0;background:#eaf3ff;font-size:19px}.tailor-rate-card:nth-child(3n+2) .tailor-rate-icon{color:#148052;background:#e7f7ef}.tailor-rate-card:nth-child(3n+3) .tailor-rate-icon{color:#8a57d6;background:#f1ebff}.tailor-rate-name{min-width:0}.tailor-rate-name small{display:block;color:var(--rate-muted);font-size:.73rem}.tailor-rate-name h3{overflow:hidden;margin:3px 0 0;color:var(--rate-navy);font-size:1.05rem;font-weight:800;text-overflow:ellipsis;white-space:nowrap}
    .tailor-rate-price{display:flex;align-items:end;justify-content:space-between;gap:12px;padding:16px 2px 14px;margin-top:13px;border-top:1px solid #edf1f6}.tailor-rate-price span{color:var(--rate-muted);font-size:.78rem}.tailor-rate-price strong{direction:ltr;color:#118452;font-size:1.3rem;font-weight:800}
    .tailor-rate-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:12px;border-top:1px solid #edf1f6}.tailor-rate-date{display:flex;align-items:center;gap:7px;color:#8190a3;font-size:.75rem}.tailor-rate-delete{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:35px;padding:6px 10px;border:1px solid #f2c8cd;border-radius:9px;color:#c83b49;background:#fff7f8;font-weight:800}.tailor-rate-delete:hover{color:#fff;background:#cf3f4d}
    .tailor-rates-empty{grid-column:1 / -1;padding:48px 20px;border:1px dashed #cfdbe9;border-radius:16px;color:var(--rate-muted);background:#fbfdff;text-align:center}.tailor-rates-empty i{display:grid;place-items:center;width:58px;height:58px;margin:0 auto 12px;border-radius:50%;color:#1769e0;background:#eaf3ff;font-size:23px}.tailor-rates-empty h3{margin:0 0 5px;color:var(--rate-navy);font-size:1.1rem;font-weight:800}.tailor-rates-empty p{margin:0 0 16px}
    .tailor-rate-modal .modal-content{overflow:hidden;border:0;border-radius:17px;box-shadow:0 22px 65px rgba(15,38,70,.22)}.tailor-rate-modal .modal-header{align-items:center;padding:18px 20px;border-bottom:1px solid var(--rate-line);background:#f7faff}.tailor-rate-modal .modal-title{display:flex;align-items:center;gap:11px;margin:0;color:var(--rate-navy);font-size:1.15rem;font-weight:800}.tailor-rate-modal .modal-title i{display:grid;place-items:center;width:40px;height:40px;border-radius:11px;color:#fff;background:var(--rate-blue)}.tailor-rate-modal .close{margin:-1rem auto -1rem -1rem}.tailor-rate-modal .modal-body{padding:20px;text-align:right}.tailor-rate-modal .rate-form-help{display:flex;gap:9px;padding:11px 13px;margin-bottom:17px;border-radius:10px;color:#52657e;background:#f2f7fd;font-size:.8rem}.tailor-rate-modal label{display:block;color:#344a67;font-weight:800}.tailor-rate-modal .form-control{min-height:48px;border-color:#d3deeb;border-radius:10px;background:#fbfdff}.tailor-rate-modal .form-control:focus{border-color:#75a8ef;box-shadow:0 0 0 3px rgba(23,105,224,.1);background:#fff}.tailor-rate-modal .input-group-text{display:flex;align-items:center;min-width:52px;justify-content:center;border-color:#d3deeb;color:#118452;background:#eef9f4;font-weight:800}.tailor-rate-modal .modal-footer{justify-content:flex-start;padding:14px 20px;border-top:1px solid var(--rate-line)}.tailor-rate-modal .rate-save-btn{display:inline-flex;align-items:center;gap:7px;min-height:42px;padding:8px 17px;border:0;border-radius:10px;color:#fff;background:#15915a;font-weight:800}.tailor-rate-modal .rate-cancel-btn{min-height:42px;padding:8px 15px;border:1px solid #d7e0eb;border-radius:10px;color:#4f627a;background:#fff;font-weight:800}
    @media(max-width:991px){.tailor-rates-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:767px){.tailor-rates-page{padding-top:18px}.tailor-rates-shell{width:min(100% - 20px,1250px)}.tailor-rates-head{align-items:flex-start;flex-direction:column;padding:18px}.tailor-rates-actions{width:100%}.tailor-rate-btn{flex:1}.tailor-rates-intro{align-items:flex-start}.tailor-rates-grid{grid-template-columns:1fr}}
    @media(max-width:420px){.tailor-rates-intro{flex-direction:column}.tailor-rates-count{align-self:flex-start}}
</style>

<section class="main-content tailor-rates-page">
    <div class="tailor-rates-shell">
        @include('inc.message')

        <header class="tailor-rates-head">
            <div class="tailor-rates-title">
                <span class="tailor-rates-avatar">{{ mb_substr($tailor->name,0,1) }}</span>
                <div><small>درزی</small><h1>{{ $tailor->name }} کے سلائی نرخ</h1></div>
            </div>
            <div class="tailor-rates-actions">
                <a class="tailor-rate-btn" href="{{ route('admin.Tailor.index') }}"><i class="fas fa-arrow-right"></i> درزیوں کی فہرست</a>
                <button class="tailor-rate-btn is-primary" type="button" data-toggle="modal" data-target="#addTailorRateModal"><i class="fas fa-plus-circle"></i> نیا سلائی نرخ</button>
            </div>
        </header>

        <div class="tailor-rates-intro">
            <div><h2><i class="fas fa-tags text-primary ml-2"></i>فی سوٹ اجرت</h2><p>ہر سلائی کی قسم کے سامنے درزی کو دی جانے والی رقم دکھائی گئی ہے۔</p></div>
            <span class="tailor-rates-count"><i class="fas fa-list"></i>{{ $tailor_rates->count() }} نرخ</span>
        </div>

        <div class="tailor-rates-grid">
            @forelse($tailor_rates as $rate)
                <article class="tailor-rate-card">
                    <div class="tailor-rate-card-head">
                        <span class="tailor-rate-icon"><i class="fas fa-cut"></i></span>
                        <div class="tailor-rate-name"><small>سلائی کی قسم</small><h3>{{ $rate->options?->Name ?: ($rate->type ?: 'عام سلائی') }}</h3></div>
                    </div>
                    <div class="tailor-rate-price"><span>درزی کی فی سوٹ اجرت</span><strong>Rs. {{ number_format((float)$rate->price,2) }}</strong></div>
                    <div class="tailor-rate-footer">
                        <span class="tailor-rate-date"><i class="far fa-calendar-alt"></i>{{ $rate->created_at?->format('d-m-Y') }}</span>
                        <form action="{{ route('admin.tailor-rates.delete',$rate) }}" method="POST" data-confirm="کیا آپ واقعی یہ سلائی نرخ حذف کرنا چاہتے ہیں؟">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tailor-rate-delete" aria-label="{{ $rate->options?->Name ?: ($rate->type ?: 'سلائی') }} کا نرخ حذف کریں"><i class="far fa-trash-alt"></i> حذف کریں</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="tailor-rates-empty">
                    <i class="fas fa-tags"></i>
                    <h3>ابھی کوئی سلائی نرخ موجود نہیں</h3>
                    <p>کام شروع کرنے کے لیے پہلا فی سوٹ نرخ شامل کریں۔</p>
                    <button class="tailor-rate-btn is-primary" type="button" data-toggle="modal" data-target="#addTailorRateModal"><i class="fas fa-plus-circle"></i> پہلا نرخ شامل کریں</button>
                </div>
            @endforelse
        </div>

        <div class="modal fade tailor-rate-modal" id="addTailorRateModal" tabindex="-1" role="dialog" aria-labelledby="addTailorRateModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ route('admin.tailor-rates.store',$tailor) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title" id="addTailorRateModalTitle"><i class="fas fa-cut"></i> نیا سلائی نرخ</h2>
                            <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            @if($types->isEmpty())
                                <div class="alert alert-warning mb-0"><i class="fas fa-exclamation-circle ml-1"></i> پہلے سلائی کی کم از کم ایک قسم شامل کریں۔ <a class="font-weight-bold" href="{{ url('admin/Options/add/1') }}">سلائی کی قسم شامل کریں</a></div>
                            @else
                                <div class="rate-form-help"><i class="fas fa-info-circle text-primary mt-1"></i><span>سلائی کی قسم منتخب کریں، پھر ایک سوٹ کی وہ اجرت لکھیں جو درزی کو دینی ہے۔</span></div>
                                @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                                <div class="form-group">
                                    <label for="rateOptionsId"><i class="fas fa-tshirt text-primary ml-1"></i> سلائی کی قسم</label>
                                    <select class="form-control" name="options_id" id="rateOptionsId" required>
                                        <option value="">سلائی کی قسم منتخب کریں</option>
                                        @foreach($types as $type)<option value="{{ $type->id }}" @selected((string)old('options_id')===(string)$type->id)>{{ $type->Name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="tailorRatePrice"><i class="fas fa-money-bill-wave text-success ml-1"></i> فی سوٹ اجرت</label>
                                    <div class="input-group" dir="ltr"><div class="input-group-prepend"><span class="input-group-text">Rs.</span></div><input id="tailorRatePrice" type="number" min="0.01" step="0.01" class="form-control" name="price" value="{{ old('price') }}" placeholder="0.00" required></div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            @if($types->isNotEmpty())<button type="submit" class="rate-save-btn"><i class="fas fa-check-circle"></i> نرخ محفوظ کریں</button>@endif
                            <button type="button" class="rate-cancel-btn" data-dismiss="modal">واپس جائیں</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@if($errors->any() || session('openRateModal'))
<script>$(function () { $('#addTailorRateModal').modal('show'); });</script>
@endif
@endsection
