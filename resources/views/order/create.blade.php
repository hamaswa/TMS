@extends('main')

@section('content')
<style>
    .order-create-page{--oc-blue:#1769e0;--oc-navy:#102a50;--oc-muted:#697a91;--oc-line:#e1e8f2;direction:rtl;padding:28px 0 52px}
    .order-create-shell{width:min(100% - 32px,1580px);margin-inline:auto}
    .order-create-head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}
    .order-create-title{display:flex;align-items:center;gap:14px}
    .order-create-title__icon{display:grid;place-items:center;width:54px;height:54px;border-radius:15px;color:var(--oc-blue);background:#eaf3ff;border:1px solid #d6e6fb;font-size:22px}
    .order-create-title h1{margin:0 0 5px;color:var(--oc-navy);font-size:clamp(1.6rem,2.2vw,2.1rem);font-weight:800}
    .order-create-title p{margin:0;color:var(--oc-muted)}
    .order-back{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:9px 14px;border:1px solid #d5dfeb;border-radius:10px;color:#40556f;background:#fff;font-weight:700;text-decoration:none!important}
    .order-back:hover{color:var(--oc-blue);border-color:#aecaef}
    .order-alert{display:flex;align-items:flex-start;gap:12px;padding:15px 17px;margin-bottom:16px;border:1px solid;border-radius:13px;text-align:right}
    .order-alert i{margin-top:4px}.order-alert.is-danger{color:#9c2632;background:#fff1f2;border-color:#f0c7cb}.order-alert.is-warning{color:#795800;background:#fff8e6;border-color:#f0dea9}
    .order-customer-strip{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:19px 22px;margin-bottom:18px;border:1px solid #dce7f5;border-radius:16px;background:linear-gradient(135deg,#f5f9ff,#fff);box-shadow:0 6px 22px rgba(24,58,96,.05)}
    .order-customer{display:flex;align-items:center;gap:13px}
    .order-customer-avatar{display:grid;place-items:center;flex:0 0 48px;width:48px;height:48px;border-radius:14px;color:#fff;background:linear-gradient(135deg,#2378ee,#0c5ad1);font-size:18px;font-weight:800}
    .order-customer small,.order-customer-balance small{display:block;margin-bottom:3px;color:var(--oc-muted);font-size:.78rem}.order-customer strong{color:var(--oc-navy);font-size:1.1rem}.order-customer-balance{text-align:left}.order-customer-balance strong{direction:ltr;display:block;color:#d24652;font-size:1.15rem;font-weight:800}
    .order-search-card{display:flex;align-items:center;gap:18px;padding:18px 22px;margin-bottom:18px;border:1px solid var(--oc-line);border-radius:15px;background:#fff;box-shadow:0 6px 22px rgba(24,58,96,.045)}
    .order-search-label{flex:0 0 220px}.order-search-label strong{display:block;color:var(--oc-navy);font-size:1rem}.order-search-label small{color:var(--oc-muted)}
    .order-search-control{position:relative;flex:1}.order-search-control i{position:absolute;top:50%;right:15px;color:#8292a8;transform:translateY(-50%)}
    .order-search-control input{width:100%;min-height:46px;padding:10px 43px 10px 15px;border:1px solid #d4deeb;border-radius:11px;outline:none}.order-search-control input:focus{border-color:#82b4f8;box-shadow:0 0 0 3px rgba(23,105,224,.1)}
    #select{width:100%;margin-top:10px}
    .order-form-grid{display:grid;grid-template-columns:minmax(0,1.12fr) minmax(360px,.88fr);gap:18px;align-items:start}
    .order-form-column{display:grid;gap:18px}
    .order-section{overflow:hidden;border:1px solid var(--oc-line);border-radius:16px;background:#fff;box-shadow:0 8px 28px rgba(21,47,81,.055)}
    .order-section-head{display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid var(--oc-line);background:#fbfdff}
    .order-section-number{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:10px;color:#fff;background:var(--oc-blue);font-weight:800}
    .order-section-head h2{margin:0 0 3px;color:var(--oc-navy);font-size:1.08rem;font-weight:800}.order-section-head p{margin:0;color:var(--oc-muted);font-size:.78rem}
    .order-section-body{padding:20px}
    .order-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px}
    .order-field{min-width:0}.order-field.is-wide{grid-column:1/-1}.order-field label{display:block;margin-bottom:7px;color:#344a65;font-size:.86rem;font-weight:800}.order-required{color:#e33c4d}
    .order-control{position:relative}.order-control>i{position:absolute;z-index:2;top:50%;right:14px;color:#8393a8;transform:translateY(-50%);pointer-events:none}
    .order-create-page .form-control{width:100%;min-height:46px;padding:9px 42px 9px 13px!important;border:1px solid #d5dfeb;border-radius:10px;color:#253a55;background:#fff;box-shadow:none!important}
    .order-create-page textarea.form-control{min-height:112px;padding-top:12px!important;resize:vertical}.order-create-page .form-control:focus{border-color:#79acf3;box-shadow:0 0 0 3px rgba(23,105,224,.09)!important}
    .order-create-page .form-control[readonly]{color:#435874;background:#f4f7fb}.order-create-page .form-text{line-height:1.8}
    .order-money{direction:ltr;text-align:left}.order-money .form-control{padding-left:13px!important}.order-money-prefix{position:absolute;z-index:2;top:50%;left:13px;color:#75869c;font-weight:800;transform:translateY(-50%)}
    .order-money .form-control{padding-left:48px!important}
    .order-info{display:flex;gap:10px;padding:12px 14px;margin-bottom:17px;border:1px solid #cfe1fa;border-radius:11px;color:#315d91;background:#f1f7ff;font-size:.82rem;line-height:1.8}.order-info i{margin-top:5px;color:var(--oc-blue)}
    .order-template{padding:15px;border:1px solid #d7e6fa;border-radius:12px;background:#f6faff}.order-template label{color:#255687}
    .order-balance-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}.order-balance-box{padding:14px;border:1px solid var(--oc-line);border-radius:11px;background:#f8fafc}.order-balance-box small{display:block;margin-bottom:5px;color:var(--oc-muted)}.order-balance-box strong{direction:ltr;display:block;color:var(--oc-navy);font-size:1.12rem}.order-balance-box.is-current strong{color:#d24652}
    .order-create-page #tailor-rates:empty{display:flex;align-items:center;min-height:46px;padding:10px 13px;border:1px dashed #cbd7e5;border-radius:10px;color:#8493a7;background:#fafcff}.order-create-page #tailor-rates:empty:after{content:'پہلے درزی منتخب کریں، پھر اس کی سلائی شرح یہاں دکھائی جائے گی۔';font-size:.78rem}
    .order-create-page #tailor-rates{position:relative;width:100%}
    .order-create-page #tailor-rates .tailor-rate-select{display:block;width:100%;height:56px!important;min-height:56px!important;padding:7px 16px 9px 38px!important;border:1px solid #b9d2f3;border-radius:11px;color:#173b68;background-color:#f5f9ff;font-size:1.02rem;font-weight:800;line-height:2.15!important;overflow:visible;cursor:pointer}
    .order-create-page #tailor-rates .tailor-rate-select:focus{border-color:#4e94ee;box-shadow:0 0 0 3px rgba(23,105,224,.12)!important;background-color:#fff}
    .order-submit-bar{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px 20px;border-top:1px solid var(--oc-line);background:#fbfdff}.order-submit-note{display:flex;align-items:center;gap:8px;color:var(--oc-muted);font-size:.8rem}.order-submit{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-width:190px;min-height:46px;border:0;border-radius:11px;color:#fff;background:linear-gradient(135deg,#1b9d60,#0c7f4b);font-size:1rem;font-weight:800;box-shadow:0 9px 20px rgba(15,143,83,.2)}.order-submit:disabled{cursor:not-allowed;box-shadow:none;opacity:.55}
    .order-create-page .payment-fields-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.order-create-page .payment-fields-grid>.form-group{margin:0}
    @media(max-width:1050px){.order-form-grid{grid-template-columns:1fr}.order-form-column:last-child{grid-row:auto}.order-customer-strip{align-items:flex-start}}
    @media(max-width:767px){.order-create-page{padding-top:18px}.order-create-shell{width:min(100% - 20px,1580px)}.order-create-head,.order-customer-strip,.order-search-card,.order-submit-bar{align-items:stretch;flex-direction:column}.order-search-label{flex-basis:auto}.order-fields,.order-balance-grid,.order-create-page .payment-fields-grid{grid-template-columns:1fr}.order-field.is-wide{grid-column:auto}.order-customer-balance{text-align:right}.order-submit{width:100%}}
</style>

<section class="main-content order-create-page">
    <div class="order-create-shell">
        <header class="order-create-head">
            <div class="order-create-title">
                <span class="order-create-title__icon"><i class="fas fa-cut"></i></span>
                <div><h1>نیا ٹیلرنگ آرڈر</h1><p>آرڈر، حوالگی، ادائیگی اور درزی کی تمام معلومات واضح مراحل میں درج کریں۔</p></div>
            </div>
            <a class="order-back" href="{{ route('admin.Customers.index') }}"><i class="fas fa-arrow-right"></i> گاہکوں کی فہرست</a>
        </header>

        @if($errors->any())
            <div class="order-alert is-danger" role="alert"><i class="fas fa-exclamation-circle"></i><div><strong>آرڈر محفوظ نہیں ہو سکا:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
        @endif
        @if(!$data['hasReadyTailor'])
            <div class="order-alert is-warning" role="alert"><i class="fas fa-user-cog"></i><div><strong>آرڈر بنانے سے پہلے درزی اور اس کی سلائی شرح مکمل کریں۔</strong><div class="mt-1">@if($data['tailors']->isEmpty()) ابھی کوئی درزی موجود نہیں۔ <a class="alert-link" href="{{ route('admin.Tailor.create') }}">نیا درزی شامل کریں</a>@else موجودہ درزی کے لیے کم از کم ایک سلائی شرح شامل کریں۔ <a class="alert-link" href="{{ route('admin.Tailor.index') }}">درزیوں کی فہرست کھولیں</a>@endif</div></div></div>
        @endif

        <div class="order-customer-strip">
            <div class="order-customer"><span class="order-customer-avatar">{{ mb_substr($data['customer']->name,0,1) }}</span><div><small>منتخب گاہک</small><strong>{{ $data['customer']->name }}</strong></div></div>
            <div class="order-customer-balance"><small>گاہک کا پچھلا مشترکہ بقایا</small><strong>@if($data['remainingBalance'] !== null) Rs. {{ number_format((float)$data['remainingBalance'],2) }} @else اجازت درکار ہے @endif</strong></div>
        </div>

        <form id="cc-form__addCustomerForm" action="{{ url('admin/order/insert') }}" method="post">
            @csrf
            <input type="hidden" name="user_id" value="{{ $data['customer']->user_id }}">
            <input type="hidden" name="customerId" value="{{ $data['customer']->id }}">

            <div class="order-search-card">
                <div class="order-search-label"><strong>محفوظ ناپ تلاش کریں</strong><small>اسی گاہک یا متعلقہ فرد کا محفوظ ناپ منتخب کریں۔</small></div>
                <div class="order-search-control"><i class="fas fa-search"></i><input class="search" type="text" placeholder="نام یا ناپ تلاش کریں" id="search" data-url="{{ url('admin/search') }}" aria-label="محفوظ ناپ تلاش کریں" autocomplete="off"><div id="select"></div></div>
            </div>

            <div class="order-form-grid">
                <div class="order-form-column">
                    <section class="order-section">
                        <div class="order-section-head"><span class="order-section-number">1</span><div><h2>آرڈر کی بنیادی معلومات</h2><p>تعداد، شناخت اور استعمال ہونے والی پیمائش منتخب کریں۔</p></div></div>
                        <div class="order-section-body">
                            @if($data['measurementTemplates']->isNotEmpty())
                                <div class="order-template mb-3"><label for="order-measurement-template">لباس کا پیمائش ٹیمپلیٹ</label><select id="order-measurement-template" class="form-control" name="measurement_template_id"><option value="">تمام محفوظ پیمائش</option>@foreach($data['measurementTemplates'] as $template)<option value="{{ $template->id }}" @selected((string)old('measurement_template_id',$data['measurementTemplateId'])===(string)$template->id)>{{ $template->name }}{{ $template->is_default ? ' — ڈیفالٹ' : '' }}</option>@endforeach</select><small class="form-text text-muted">صرف منتخب ٹیمپلیٹ کی پیمائش آرڈر کے ساتھ محفوظ ہوگی؛ گاہک کی اصل پیمائش تبدیل نہیں ہوگی۔</small></div>
                            @endif
                            <div class="order-fields">
                                <div class="order-field"><label for="order_customer_name">گاہک کا نام</label><div class="order-control"><i class="fas fa-user"></i><input id="order_customer_name" type="text" class="form-control" name="CustomerName" readonly value="{{ $data['customer']->name }}"></div></div>
                                <div class="order-field"><label for="order_serial_number">سیریل نمبر</label><div class="order-control" id="suitNumContainer"><i class="fas fa-hashtag"></i><input id="order_serial_number" type="text" class="form-control" name="serail" required value="{{ $data['serialNumber'] }}" readonly></div></div>
                                <div class="order-field is-wide"><label for="suitQuantity">سوٹ کی تعداد <span class="order-required">*</span></label><div class="order-control"><i class="fas fa-tshirt"></i><input type="number" min="1" class="form-control" name="suitQuantity" id="suitQuantity" value="{{ old('suitQuantity',1) }}" required></div></div>
                            </div>
                        </div>
                    </section>

                    <section class="order-section">
                        <div class="order-section-head"><span class="order-section-number">2</span><div><h2>قیمت اور وصولی</h2><p>کل قیمت اور ابھی وصول ہونے والی رقم درج کریں۔</p></div></div>
                        <div class="order-section-body">
                            <div class="order-info"><i class="fas fa-info-circle"></i><div><strong>رقم کی وضاحت:</strong> اس آرڈر کی کل قیمت اور ابھی وصول شدہ رقم درج کریں؛ اس آرڈر کی باقی رقم خود بخود حساب ہوگی۔</div></div>
                            <div class="order-fields">
                                <div class="order-field"><label for="totalPayment">اس آرڈر کی کل قیمت <span class="order-required">*</span></label><div class="order-control order-money"><span class="order-money-prefix">Rs.</span><input type="number" min="0" step="0.01" class="form-control js-no-wheel-number" name="totalPayment" id="totalPayment" value="{{ old('totalPayment') }}" required></div></div>
                                <div class="order-field"><label for="recivedPayment">ابھی وصول شدہ رقم <span class="order-required">*</span></label><div class="order-control order-money"><span class="order-money-prefix">Rs.</span><input type="number" min="0" step="0.01" class="form-control js-no-wheel-number" name="recivedPayment" id="recivedPayment" value="{{ old('recivedPayment',0) }}" required></div></div>
                            </div>
                            <div class="order-balance-grid">
                                <div class="order-balance-box"><small>گاہک کا پچھلا مشترکہ بقایا</small><strong id="customer_previous_balance">@if($data['remainingBalance'] !== null) Rs. {{ number_format((float)$data['remainingBalance'],2) }} @else بقایا دیکھنے کی اجازت نہیں @endif</strong></div>
                                <div class="order-balance-box is-current"><small>اس آرڈر کی باقی رقم</small><strong>Rs. <span id="balanceDisplay">0.00</span></strong><input type="hidden" name="balance" id="balance" value="0.00"></div>
                            </div>
                        </div>
                    </section>

                    <section class="order-section">
                        <div class="order-section-head"><span class="order-section-number">3</span><div><h2>وصول شدہ رقم کی تفصیل</h2><p>ادائیگی کا ذریعہ، حوالہ اور تاریخ محفوظ کریں۔</p></div></div>
                        <div class="order-section-body"><div class="payment-fields-grid">@include('components.payment-method-fields',['prefix'=>'tailoring_order'])<div class="form-group mb-0"><label for="tailoring_order_paid_on">ادائیگی کی تاریخ</label><input id="tailoring_order_paid_on" type="date" name="paid_on" value="{{ old('paid_on',now()->toDateString()) }}" class="form-control" required></div></div></div>
                    </section>
                </div>

                <div class="order-form-column">
                    <section class="order-section">
                        <div class="order-section-head"><span class="order-section-number">4</span><div><h2>حوالگی اور درزی</h2><p>واپسی کی تاریخ، درزی اور اس کی سلائی شرح منتخب کریں۔</p></div></div>
                        <div class="order-section-body"><div class="order-fields">
                            <div class="order-field is-wide"><label for="order_return_date">حوالگی کی تاریخ <span class="order-required">*</span></label><div class="order-control"><i class="fas fa-calendar-check"></i><input id="order_return_date" type="date" class="form-control" name="returnDate" value="{{ old('returnDate') }}" required></div></div>
                            <div class="order-field is-wide"><label for="tailor-selected">درزی منتخب کریں <span class="order-required">*</span></label><div class="order-control"><i class="fas fa-user-tie"></i><select id="tailor-selected" class="form-control" name="tailorId" required><option value="">درزی کو منتخب کریں</option>@foreach($data['tailors'] as $tailor)<option value="{{ $tailor->id }}" @selected((string)old('tailorId')===(string)$tailor->id) @disabled($tailor->tailorsalary->isEmpty())>{{ $tailor->name }}{{ $tailor->tailorsalary->isEmpty() ? ' — شرح شامل نہیں' : '' }}</option>@endforeach</select></div></div>
                            <div class="order-field is-wide"><label>درزی کی فی سوٹ اجرت <span class="order-required">*</span></label><div id="tailor-rates"></div></div>
                        </div></div>
                    </section>

                    <section class="order-section">
                        <div class="order-section-head"><span class="order-section-number">5</span><div><h2>نوٹ اور ہدایات</h2><p>سلائی، ڈیزائن یا حوالگی سے متعلق ضروری بات درج کریں۔</p></div></div>
                        <div class="order-section-body"><div class="order-field"><label for="order_remarks">آرڈر نوٹ</label><textarea id="order_remarks" class="form-control" name="remarks" dir="auto" placeholder="مثلاً کالر، کف، فوری حوالگی یا دوسری خاص ہدایت">{{ old('remarks',$data['customer']->note) }}</textarea></div></div>
                        <div class="order-submit-bar"><div class="order-submit-note"><i class="fas fa-shield-alt"></i> محفوظ کرنے سے پہلے رقم اور حوالگی کی تاریخ دوبارہ دیکھ لیں۔</div><button type="submit" class="order-submit" @disabled(!$data['hasReadyTailor'])><i class="fas fa-check"></i> آرڈر محفوظ کریں</button></div>
                    </section>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded',function(){
    var total=document.getElementById('totalPayment');
    var received=document.getElementById('recivedPayment');
    var balance=document.getElementById('balance');
    var display=document.getElementById('balanceDisplay');
    function updateBalance(){var value=Math.max(0,parseFloat(total?.value||'0')-parseFloat(received?.value||'0')).toFixed(2);if(balance)balance.value=value;if(display)display.textContent=value;}
    total?.addEventListener('input',updateBalance);received?.addEventListener('input',updateBalance);updateBalance();
    var tailor=document.getElementById('tailor-selected');
    if(tailor?.value){tailor.dispatchEvent(new Event('change',{bubbles:true}));}
});
</script>
@endsection
