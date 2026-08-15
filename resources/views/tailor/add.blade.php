@extends('main')

@section('content')
<style>
    .tailor-create-page{--tc-blue:#1769e0;--tc-navy:#102a50;--tc-muted:#6d7f94;--tc-line:#e0e8f2;direction:rtl;padding:28px 0 50px}
    .tailor-create-shell{width:min(100% - 32px,1050px);margin-inline:auto}
    .tailor-create-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}.tailor-create-title{display:flex;align-items:center;gap:14px}.tailor-create-title-icon{display:grid;place-items:center;width:56px;height:56px;border-radius:16px;color:#fff;background:linear-gradient(135deg,#2479ee,#0c5bd1);font-size:21px;box-shadow:0 9px 20px rgba(23,105,224,.2)}.tailor-create-title h1{margin:0 0 4px;color:var(--tc-navy);font-size:clamp(1.45rem,2vw,1.9rem);font-weight:800}.tailor-create-title p{margin:0;color:var(--tc-muted);font-size:.85rem}
    .tailor-back-btn{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:8px 14px;border:1px solid #d5dfeb;border-radius:10px;color:#40566f;background:#fff;font-weight:800;text-decoration:none!important}.tailor-back-btn:hover{color:var(--tc-blue);border-color:#a9c9f3}
    .tailor-form-panel{overflow:hidden;border:1px solid var(--tc-line);border-radius:18px;background:#fff;box-shadow:0 9px 30px rgba(21,47,81,.06)}.tailor-form-section{padding:22px 24px}.tailor-form-section+.tailor-form-section{border-top:1px solid var(--tc-line);background:#fbfdff}.tailor-section-head{display:flex;align-items:center;gap:11px;margin-bottom:18px}.tailor-section-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border-radius:12px;color:var(--tc-blue);background:#eaf3ff;font-size:17px}.tailor-form-section.is-optional .tailor-section-icon{color:#148052;background:#e7f7ef}.tailor-section-head h2{margin:0 0 3px;color:var(--tc-navy);font-size:1.08rem;font-weight:800}.tailor-section-head p{margin:0;color:var(--tc-muted);font-size:.76rem}.tailor-optional-badge{display:inline-block;margin-right:6px;padding:3px 8px;border-radius:999px;color:#63758c;background:#edf1f6;font-size:.68rem;font-weight:800}
    .tailor-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px}.tailor-field.is-wide{grid-column:1 / -1}.tailor-field label{display:block;margin-bottom:7px;color:#344a67;font-size:.84rem;font-weight:800}.tailor-field label i{width:19px;color:var(--tc-blue);text-align:center}.tailor-field-wrap{position:relative}.tailor-field-wrap>i{position:absolute;z-index:2;top:50%;right:14px;color:#8190a3;transform:translateY(-50%)}.tailor-field .form-control{width:100%;min-height:49px;padding:8px 42px 8px 13px;border:1px solid #d3deeb;border-radius:10px;color:var(--tc-navy);background:#fbfdff}.tailor-field .form-control:focus{border-color:#75a8ef;box-shadow:0 0 0 3px rgba(23,105,224,.1);background:#fff}.tailor-field small{display:block;margin-top:6px;color:var(--tc-muted);font-size:.72rem;line-height:1.8}.tailor-money-wrap .form-control{direction:ltr;padding-right:13px;padding-left:58px;text-align:left}.tailor-money-wrap span{position:absolute;top:1px;bottom:1px;left:1px;display:flex;align-items:center;padding:0 13px;border-radius:9px 0 0 9px;color:#118452;background:#eef9f4;font-weight:800}
    .tailor-help-card{display:flex;gap:11px;padding:13px 14px;margin-bottom:17px;border:1px solid #d7e8fb;border-radius:11px;color:#53667e;background:#f1f7ff}.tailor-help-card i{margin-top:3px;color:var(--tc-blue);font-size:17px}.tailor-help-card strong{display:block;margin-bottom:2px;color:var(--tc-navy);font-size:.83rem}.tailor-help-card span{font-size:.75rem;line-height:1.8}.tailor-help-card.is-security{border-color:#d4ebdf;background:#f0faf5}.tailor-help-card.is-security i{color:#148052}
    .tailor-optional-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.tailor-option-box{padding:16px;border:1px solid #e0e8f2;border-radius:13px;background:#fff}.tailor-option-box h3{margin:0 0 4px;color:var(--tc-navy);font-size:.92rem;font-weight:800}.tailor-option-box>p{margin:0 0 14px;color:var(--tc-muted);font-size:.72rem;line-height:1.75}.tailor-option-box .tailor-fields{grid-template-columns:1fr;gap:13px}
    .tailor-form-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:17px 24px;border-top:1px solid var(--tc-line);background:#fff}.tailor-form-footer p{margin:0;color:var(--tc-muted);font-size:.75rem}.tailor-save-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:45px;padding:9px 20px;border:0;border-radius:10px;color:#fff;background:#15915a;font-weight:800;box-shadow:0 8px 18px rgba(21,145,90,.18)}.tailor-save-btn:hover{background:#117b4c}.tailor-errors{padding:14px 17px;margin-bottom:16px;border:1px solid #f0c7cc;border-radius:11px;color:#a52c38;background:#fff4f5}.tailor-errors strong{display:block;margin-bottom:5px}.tailor-errors ul{margin:0;padding-right:20px}
    @media(max-width:767px){.tailor-create-page{padding-top:18px}.tailor-create-shell{width:min(100% - 20px,1050px)}.tailor-create-head{align-items:flex-start;flex-direction:column}.tailor-back-btn{width:100%;justify-content:center}.tailor-form-section{padding:18px}.tailor-fields,.tailor-optional-grid{grid-template-columns:1fr}.tailor-field.is-wide{grid-column:auto}.tailor-form-footer{align-items:stretch;flex-direction:column}.tailor-save-btn{width:100%}}
</style>

<section class="main-content tailor-create-page">
    <div class="tailor-create-shell">
        <header class="tailor-create-head">
            <div class="tailor-create-title"><span class="tailor-create-title-icon"><i class="fas fa-user-plus"></i></span><div><h1>نیا درزی شامل کریں</h1><p>بنیادی معلومات درج کریں، باقی چیزیں بعد میں بھی شامل ہو سکتی ہیں۔</p></div></div>
            <a class="tailor-back-btn" href="{{ route('admin.Tailor.index') }}"><i class="fas fa-arrow-right"></i> درزیوں کی فہرست</a>
        </header>

        @if($errors->any())
            <div class="tailor-errors"><strong><i class="fas fa-exclamation-circle ml-1"></i> براہِ کرم یہ معلومات درست کریں:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('admin.Tailor.store') }}" method="POST" class="tailor-form-panel">
            @csrf
            <section class="tailor-form-section">
                <div class="tailor-section-head"><span class="tailor-section-icon"><i class="fas fa-user-tie"></i></span><div><h2>درزی کی بنیادی معلومات</h2><p>یہ تین معلومات ضروری ہیں۔</p></div></div>
                <div class="tailor-fields">
                    <div class="tailor-field">
                        <label for="tailorName"><i class="fas fa-user"></i> درزی کا نام</label>
                        <div class="tailor-field-wrap"><i class="fas fa-user"></i><input id="tailorName" type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="مثلاً محمد وقاص" maxlength="255" required autofocus></div>
                    </div>
                    <div class="tailor-field">
                        <label for="tailorContact"><i class="fas fa-phone-alt"></i> فون نمبر</label>
                        <div class="tailor-field-wrap"><i class="fas fa-phone-alt"></i><input id="tailorContact" type="tel" inputmode="tel" class="form-control" name="contact" value="{{ old('contact') }}" placeholder="مثلاً 03001234567" maxlength="50" dir="ltr" required></div>
                    </div>
                    <div class="tailor-field is-wide">
                        <label for="tailorPassword"><i class="fas fa-key"></i> پورٹل پاس ورڈ</label>
                        <div class="tailor-field-wrap"><i class="fas fa-lock"></i><input id="tailorPassword" type="password" class="form-control" name="password" autocomplete="new-password" minlength="6" required></div>
                        <small><i class="fas fa-info-circle ml-1"></i> درزی فون نمبر، اس پاس ورڈ اور دکان کوڈ سے اپنے پورٹل میں داخل ہوگا۔ کم از کم 6 حروف رکھیں۔</small>
                    </div>
                </div>
            </section>

            <section class="tailor-form-section is-optional">
                <div class="tailor-section-head"><span class="tailor-section-icon"><i class="fas fa-sliders-h"></i></span><div><h2>ابتدائی ترتیب <span class="tailor-optional-badge">اختیاری</span></h2><p>اگر ابھی معلوم نہ ہو تو خالی چھوڑ دیں؛ بعد میں شامل ہو جائے گی۔</p></div></div>
                <div class="tailor-optional-grid">
                    <div class="tailor-option-box">
                        <div class="tailor-help-card is-security"><i class="fas fa-shield-alt"></i><div><strong>ابتدائی سیکیورٹی ڈپازٹ</strong><span>یہ دکان کے پاس امانت ہے۔ یہ درزی کو دیا گیا ایڈوانس نہیں اور اجرت سے منہا نہیں ہوگا۔</span></div></div>
                        <div class="tailor-fields">
                            <div class="tailor-field"><label for="securityDeposit">وصول شدہ رقم</label><div class="tailor-field-wrap tailor-money-wrap"><input id="securityDeposit" type="number" min="0" step="0.01" class="form-control" name="security_deposit" value="{{ old('security_deposit') }}" placeholder="0.00"><span>Rs.</span></div></div>
                            <div class="tailor-field"><label for="securityDepositNote">مختصر نوٹ</label><div class="tailor-field-wrap"><i class="far fa-sticky-note"></i><input id="securityDepositNote" type="text" maxlength="500" class="form-control" name="security_deposit_note" value="{{ old('security_deposit_note') }}" placeholder="مثلاً نقد، رسید نمبر 12"></div></div>
                        </div>
                    </div>
                    <div class="tailor-option-box">
                        <div class="tailor-help-card"><i class="fas fa-cut"></i><div><strong>پہلی فی سوٹ اجرت</strong><span>صرف تب درج کریں جب اس درزی کے ساتھ فوراً آرڈر بنانا ہو۔ مزید نرخ بعد میں شامل کیے جا سکتے ہیں۔</span></div></div>
                        <div class="tailor-fields">
                            <div class="tailor-field"><label for="initialRateLabel">سلائی کی قسم</label><div class="tailor-field-wrap"><i class="fas fa-tshirt"></i><input id="initialRateLabel" type="text" class="form-control" name="initial_rate_label" value="{{ old('initial_rate_label') }}" placeholder="مثلاً معیاری سلائی" maxlength="100"></div></div>
                            <div class="tailor-field"><label for="initialRatePrice">فی سوٹ اجرت</label><div class="tailor-field-wrap tailor-money-wrap"><input id="initialRatePrice" type="number" min="0.01" step="0.01" class="form-control" name="initial_rate_price" value="{{ old('initial_rate_price') }}" placeholder="0.00"><span>Rs.</span></div></div>
                        </div>
                    </div>
                </div>
            </section>

            <footer class="tailor-form-footer"><p><i class="fas fa-lock text-success ml-1"></i> معلومات محفوظ رہیں گی اور بعد میں تبدیل کی جا سکتی ہیں۔</p><button type="submit" class="tailor-save-btn"><i class="fas fa-check-circle"></i> درزی محفوظ کریں</button></footer>
        </form>
    </div>
</section>
@endsection
