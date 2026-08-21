@extends('main')
@section('content')
<style>
    .customer-create-page{background:#f5f7fa;min-height:calc(100vh - 70px)}
    .customer-form-card{border:0;border-radius:20px;box-shadow:0 14px 36px rgba(31,45,61,.1);overflow:hidden}
    .customer-form-head{background:linear-gradient(135deg,#102a43,#1769aa);color:#fff;padding:1.6rem 2rem}
    .customer-form-head h1{color:#fff!important}.customer-form-head p{color:rgba(255,255,255,.8)!important}
    .customer-progress{display:grid;grid-template-columns:repeat(3,1fr);gap:.7rem;margin-bottom:1.5rem}
    .customer-progress-item{border:1px solid #dfe7f0;border-radius:14px;padding:.8rem;background:#fff;color:#6c7a89;font-weight:700;text-align:center}
    .customer-progress-item.active{border-color:#1769aa;background:#eaf4fb;color:#1769aa}.customer-progress-item.complete{border-color:#28a745;color:#218838}
    .customer-step{display:none}.customer-step.active{display:block}.customer-section{border:1px solid #e2e8f0;border-radius:16px;padding:1.25rem;background:#fbfdff}
    .measurement-field label{font-weight:700}.step-actions{display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #edf2f7}
    .measurement-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.measurement-grid .form-group{margin-bottom:0}.measurement-grid label{font-weight:700}
    @media(max-width:767px){.customer-progress{grid-template-columns:1fr}.customer-form-head{padding:1.3rem}.customer-form-card .card-body{padding:1rem!important}.measurement-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:420px){.measurement-grid{grid-template-columns:1fr}}
</style>
<section class="main-content customer-create-page" dir="rtl"><div class="container-fluid px-3 px-lg-5 py-4"><div class="customer-form-card bg-white mx-auto" style="max-width:1100px">
    <div class="customer-form-head"><h1 class="h3 font-weight-bold mb-2">نیا گاہک شامل کریں</h1><p class="mb-0">بنیادی معلومات، پیمائش اور سلائی کی پسند الگ مراحل میں درج کریں۔</p></div>
    <div class="card-body p-4 p-lg-5">
        @if($errors->any())<div class="alert alert-danger"><strong>براہ کرم درج ذیل معلومات درست کریں:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="customer-progress" aria-label="گاہک شامل کرنے کے مراحل">
            <div class="customer-progress-item active" data-progress="1"><span class="badge badge-primary ml-1">1</span> بنیادی معلومات</div>
            <div class="customer-progress-item" data-progress="2"><span class="badge badge-light ml-1">2</span> پیمائش</div>
            <div class="customer-progress-item" data-progress="3"><span class="badge badge-light ml-1">3</span> سلائی کی پسند</div>
        </div>

        <form id="customer-create-form" action="{{ route('admin.Customers.store') }}" method="post">@csrf
            <section class="customer-step active" data-step="1" aria-labelledby="customer-step-one">
                <div class="customer-section"><h2 id="customer-step-one" class="h5 font-weight-bold mb-1">گاہک کی بنیادی معلومات</h2><p class="text-muted mb-4">رابطہ اور موبائل اکاؤنٹ کی معلومات درج کریں۔</p><div class="row">
                    <div class="col-md-6 form-group"><label>نام <span class="text-danger">*</span></label><input type="text" class="form-control" name="name" value="{{ old('name') }}" required autocomplete="name"></div>
                    <div class="col-md-6 form-group"><label for="customer-contact">رابطہ نمبر <span class="text-danger">*</span></label><input id="customer-contact" type="tel" inputmode="tel" class="form-control" name="contact" value="{{ old('contact') }}" required dir="ltr" autocomplete="tel" placeholder="03001234567 یا +923001234567">@error('contact')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6 form-group"><label for="mobile_pin">موبائل لاگ اِن پن</label><input id="mobile_pin" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="new-password" class="form-control" name="mobile_pin" value="{{ old('mobile_pin') }}" placeholder="6 ہندسوں کا پن" dir="ltr"><small class="form-text text-muted">خالی چھوڑنے پر محفوظ پن خود بنے گا اور صرف ایک بار دکھایا جائے گا۔</small></div>
                    <div class="col-md-6"><div class="alert alert-info mb-0"><strong>مشترکہ گاہک اکاؤنٹ</strong><br><small>یہی گاہک ٹیلرنگ اور دکان دونوں میں استعمال ہوگا؛ بقایا اور ادائیگیاں ایک مشترکہ کھاتے میں رہیں گی۔</small></div></div>
                </div></div>
                <div class="step-actions"><a class="btn btn-outline-secondary" href="{{ route('admin.Customers.index') }}">منسوخ کریں</a><button type="button" class="btn btn-primary px-4 next-step">پیمائش درج کریں <i class="fas fa-arrow-left mr-1"></i></button></div>
            </section>

            <section class="customer-step" data-step="2" aria-labelledby="customer-step-two">
                <div class="customer-section">@include('customer.partials.measurement-template-selector', ['selectedTemplateId' => $measurementTemplates->firstWhere('is_default', true)?->id])<div class="d-flex flex-wrap justify-content-between align-items-center mb-3"><div><h2 id="customer-step-two" class="h5 font-weight-bold mb-1">لباس کی پیمائش</h2><p class="text-muted mb-0">بنیادی اور کاروبار کے خصوصی خانے ایک ہی جگہ درج کریں۔</p></div><span class="badge badge-light px-3 py-2">ضروری خانے *</span></div><div class="measurement-grid">
                    @foreach(['length'=>'لمبائی','arms'=>'بازو','teraa'=>'تیرا','senaChorai'=>'سینہ چوڑائی','damanchorai'=>'دامن چوڑائی','shalwar'=>'شلوار','pancha'=>'پائنچہ','shalwarGheer'=>'شلوار گھیر','monda'=>'مونڈھا','chuta'=>'چوٹا'] as $name => $label)
                        <div class="form-group measurement-field"><label for="measurement-{{ $name }}">{{ $label }} <span class="text-danger">*</span></label><input id="measurement-{{ $name }}" type="number" step="0.01" min="0" class="form-control" name="{{ $name }}" value="{{ old($name) }}" required></div>
                    @endforeach
                    @include('customer.partials.custom-measurements', ['embedded' => true])
                </div>
                </div>
                <div class="step-actions"><button type="button" class="btn btn-outline-secondary previous-step"><i class="fas fa-arrow-right ml-1"></i> بنیادی معلومات</button><button type="button" class="btn btn-primary px-4 next-step">سلائی کی پسند <i class="fas fa-arrow-left mr-1"></i></button></div>
            </section>

            <section class="customer-step" data-step="3" aria-labelledby="customer-step-three">
                <div class="customer-section"><h2 id="customer-step-three" class="h5 font-weight-bold mb-1">سلائی کی پسند اور نوٹ</h2><p class="text-muted mb-4">گاہک کی مستقل پسند منتخب کریں؛ آرڈر بناتے وقت اسے تبدیل کیا جا سکتا ہے۔</p><div class="row">
                    @foreach($data['optionTypes'] as $type)
                        @php($options = DB::table('options')->where('option_id', $type->option_id)->where('user_id', Auth::user()->businessOwnerId())->get())
                        <div class="col-md-6 form-group"><label>{{ $type->otn }}</label><select class="form-control" style="padding: 0px" name="{{ $type->slug }}"><option value="0">{{ $type->otn }} منتخب کریں</option>@foreach($options as $option)<option value="{{ $option->id.' - '.$option->Name }}" @selected(old($type->slug) === $option->id.' - '.$option->Name)>{{ $option->Name }}</option>@endforeach</select></div>
                    @endforeach
                    <div class="col-12 form-group"><label>خصوصی نوٹ</label><textarea class="form-control" name="note" rows="5" maxlength="2000" placeholder="مثلاً فٹنگ، کپڑے یا سلائی سے متعلق ہدایات">{{ old('note') }}</textarea></div>
                </div></div>
                <div class="step-actions"><button type="button" class="btn btn-outline-secondary previous-step"><i class="fas fa-arrow-right ml-1"></i> پیمائش</button><button type="submit" class="btn btn-success px-5"><i class="fas fa-check ml-1"></i> گاہک محفوظ کریں</button></div>
            </section>
        </form>
        <noscript><style>.customer-step{display:block!important;margin-bottom:2rem}.next-step,.previous-step,.customer-progress{display:none!important}</style></noscript>
    </div>
</div></div></section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('customer-create-form');
    const steps = Array.from(form.querySelectorAll('.customer-step'));
    const progress = Array.from(document.querySelectorAll('[data-progress]'));
    let current = 0;

    function showStep(index) {
        current = Math.max(0, Math.min(index, steps.length - 1));
        steps.forEach((step, i) => step.classList.toggle('active', i === current));
        progress.forEach((item, i) => {
            item.classList.toggle('active', i === current);
            item.classList.toggle('complete', i < current);
        });
        window.scrollTo({top: 0, behavior: 'smooth'});
    }

    function currentStepIsValid() {
        const controls = Array.from(steps[current].querySelectorAll('input, select, textarea'));
        const invalid = controls.find(control => !control.checkValidity());
        if (invalid) { invalid.reportValidity(); invalid.focus(); return false; }
        return true;
    }

    form.querySelectorAll('.next-step').forEach(button => button.addEventListener('click', () => {
        if (currentStepIsValid()) showStep(current + 1);
    }));
    form.querySelectorAll('.previous-step').forEach(button => button.addEventListener('click', () => showStep(current - 1)));
    progress.forEach((item, index) => item.addEventListener('click', () => { if (index <= current) showStep(index); }));
});
</script>
@endsection
