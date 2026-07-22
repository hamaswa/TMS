@extends('main')
@section('content')
<style>
    .customer-edit-page{background:#f4f7fb;min-height:calc(100vh - 70px)}
    .customer-edit-shell{max-width:1180px;margin:0 auto}
    .customer-edit-hero{background:linear-gradient(135deg,#102a43,#1769aa);border-radius:20px;color:#fff;padding:1.6rem 1.8rem;box-shadow:0 14px 32px rgba(16,42,67,.18)}
    .customer-edit-hero h1,.customer-edit-hero p{color:#fff!important}.customer-edit-hero p{opacity:.82}
    .customer-edit-card{border:0;border-radius:18px;box-shadow:0 10px 28px rgba(31,45,61,.08);overflow:hidden}
    .edit-section{padding:1.5rem;border-bottom:1px solid #e8eef5}.edit-section:last-child{border-bottom:0}
    .edit-section-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.25rem}
    .edit-section-heading h2{font-size:1.18rem;font-weight:800;margin:0 0 .25rem}.edit-section-heading p{color:#718096;margin:0;font-size:.9rem}
    .section-icon{width:42px;height:42px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;background:#eaf4fb;color:#1769aa;flex:0 0 42px}
    .edit-field label{display:block;font-weight:700;color:#334e68;margin-bottom:.45rem}.edit-field .form-control{height:46px;border-color:#d8e2ec;border-radius:10px;background:#fff}
    .edit-field textarea.form-control{height:auto;min-height:120px;resize:vertical}.edit-field .form-control:focus{border-color:#1769aa;box-shadow:0 0 0 .18rem rgba(23,105,170,.12)}
    .measurement-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}
    .measurement-template-picker{background:#f7fbff;border:1px solid #cfe3f3;border-radius:14px;padding:1rem 1.1rem}.measurement-template-picker select{border-radius:10px;border-color:#b9d5e8}
    .preference-empty{border:1px dashed #cbd5e0;border-radius:12px;padding:1rem;color:#718096;background:#fafcff}
    .security-panel{height:100%;background:#fff8e8;border:1px solid #f5dfaa;border-radius:14px;padding:1.1rem}
    .edit-actions{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.25rem 1.5rem;background:#fbfdff}
    @media(max-width:991px){.measurement-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:767px){.measurement-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.customer-edit-hero{border-radius:14px;padding:1.25rem}.edit-section{padding:1.1rem}.edit-actions{position:static;flex-direction:column-reverse}.edit-actions .btn{width:100%}}
    @media(max-width:420px){.measurement-grid{grid-template-columns:1fr}}
</style>

<section class="main-content customer-edit-page" dir="rtl">
    <div class="container-fluid px-3 px-lg-5 py-4">
        <div class="customer-edit-shell">
            <div class="customer-edit-hero mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <span class="badge badge-light text-primary mb-2">گاہک کی معلومات</span>
                        <h1 class="h3 font-weight-bold mb-1">{{ $customer->name }}</h1>
                        <p class="mb-0">رابطہ، پیمائش، سلائی کی پسند اور موبائل پن ایک جگہ تبدیل کریں۔</p>
                    </div>
                    <a href="{{ route('admin.customers.statement', $customer) }}" class="btn btn-light mt-3 mt-md-0">
                        <i class="fas fa-user ml-1"></i> پروفائل / کھاتہ
                    </a>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger customer-edit-card">
                    <strong>براہ کرم درج ذیل معلومات درست کریں:</strong>
                    <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form id="customer-edit-form" action="{{ route('admin.Customers.update', $customer) }}" method="post" class="customer-edit-card bg-white">
                @csrf
                @method('PUT')

                <section class="edit-section">
                    <div class="edit-section-heading">
                        <div><h2>بنیادی معلومات</h2><p>گاہک کی مشترکہ شناخت جو دکان اور ٹیلرنگ دونوں میں استعمال ہوتی ہے۔</p></div>
                        <span class="section-icon"><i class="fas fa-user"></i></span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group edit-field">
                            <label for="customer-name">نام <span class="text-danger">*</span></label>
                            <input id="customer-name" type="text" class="form-control" name="name" value="{{ old('name', $customer->name) }}" required autocomplete="name">
                            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group edit-field">
                            <label for="customer-contact">رابطہ نمبر <span class="text-danger">*</span></label>
                            <input id="customer-contact" type="tel" class="form-control text-left" name="contact" value="{{ old('contact', $customer->phone_number1) }}" required dir="ltr" autocomplete="tel">
                            @error('contact')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                <section class="edit-section">
                    <div class="edit-section-heading">
                        <div><h2>پیمائش</h2><p>لباس منتخب کریں اور صرف اس سے متعلق پیمائش تبدیل کریں۔</p></div>
                        <span class="section-icon"><i class="fas fa-ruler-combined"></i></span>
                    </div>
                    @include('customer.partials.measurement-template-selector', ['selectedTemplateId' => $customer->measurement_template_id])
                    @php
                        $systemMeasurements = [
                            'length' => ['لمبائی', $customer->length],
                            'arms' => ['بازو', $customer->arms],
                            'teraa' => ['تیرا', $customer->teraa],
                            'senaChorai' => ['سینہ چوڑائی', $customer->senaChorai],
                            'damanchorai' => ['دامن چوڑائی', $customer->damanchorai],
                            'shalwar' => ['شلوار', $customer->shalwar],
                            'pancha' => ['پائنچہ', $customer->pancha],
                            'shalwarGheer' => ['شلوار گھیر', $customer->shalwarGheer],
                            'monda' => ['مونڈھا', $customer->shoulder],
                            'chuta' => ['چوٹا', $customer->chuta],
                        ];
                    @endphp
                    <div class="measurement-grid">
                        @foreach($systemMeasurements as $name => [$label, $value])
                            <div class="form-group edit-field mb-0">
                                <label for="measurement-{{ $name }}">{{ $label }}</label>
                                <div class="input-group">
                                    <input id="measurement-{{ $name }}" type="number" step="0.01" min="0" class="form-control" name="{{ $name }}" value="{{ old($name, $value) }}" dir="ltr">
                                    <div class="input-group-append"><span class="input-group-text">انچ</span></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                @if($measurementFields->isNotEmpty())
                    <section class="edit-section">
                        <div class="edit-section-heading mb-3">
                            <div><h2>اضافی پیمائش</h2><p>آپ کے کاروبار کے بنائے ہوئے خصوصی پیمائشی خانے۔</p></div>
                            <span class="section-icon"><i class="fas fa-sliders-h"></i></span>
                        </div>
                        @include('customer.partials.custom-measurements')
                    </section>
                @endif

                <section class="edit-section">
                    <div class="edit-section-heading">
                        <div><h2>سلائی کی پسند</h2><p>یہ مستقل ترجیحات نئے آرڈر میں دوبارہ استعمال کی جا سکتی ہیں۔</p></div>
                        <span class="section-icon"><i class="fas fa-cut"></i></span>
                    </div>
                    @if($optionTypes->isNotEmpty())
                        <div class="row">
                            @foreach($optionTypes as $type)
                                @php
                                    $options = DB::table('options')->where('option_id', $type->option_id)->where('user_id', Auth::user()->businessOwnerId())->get();
                                    $column = $type->type === 'daaman' ? 'Daaman' : $type->type;
                                    $customerValue = trim((string) data_get($customer, $column, ''));
                                @endphp
                                <div class="col-md-6 form-group edit-field">
                                    <label for="preference-{{ $type->slug }}">{{ $type->otn }}</label>
                                    <select id="preference-{{ $type->slug }}" class="form-control" name="{{ $type->slug }}">
                                        <option value="0">{{ $type->otn }} منتخب کریں</option>
                                        @foreach($options as $option)
                                            @php($optionValue = $option->id.' - '.$option->Name)
                                            <option value="{{ $optionValue }}" @selected(old($type->slug, $customerValue) === $optionValue || (!old($type->slug) && trim($option->Name) === $customerValue))>{{ $option->Name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="preference-empty">ابھی سلائی کی کوئی پسند شامل نہیں۔ پیمائش کے اختیارات سے کالر، کف، جیب اور دوسرے انتخاب بنائے جا سکتے ہیں۔</div>
                    @endif
                </section>

                <section class="edit-section">
                    <div class="row">
                        <div class="col-lg-7 form-group edit-field mb-lg-0">
                            <label for="customer-note">خصوصی نوٹ</label>
                            <textarea id="customer-note" class="form-control" name="note" rows="4" maxlength="2000" placeholder="مثلاً فٹنگ، کپڑے یا ڈیلیوری سے متعلق ہدایات">{{ old('note', $customer->note) }}</textarea>
                            <small class="form-text text-muted">یہ نوٹ گاہک کے مشترکہ پروفائل میں دکھائی دے گا۔</small>
                        </div>
                        <div class="col-lg-5">
                            <div class="security-panel edit-field">
                                <label for="mobile-pin"><i class="fas fa-shield-alt ml-1 text-warning"></i> نیا موبائل لاگ اِن پن</label>
                                <input id="mobile-pin" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="new-password" class="form-control text-left" name="mobile_pin" placeholder="6 ہندسوں کا نیا پن" dir="ltr">
                                <small class="form-text text-muted mt-2">خالی چھوڑنے سے موجودہ پن تبدیل نہیں ہوگا۔ نیا پن محفوظ کرنے پر پرانے موبائل سیشن بند ہو جائیں گے۔</small>
                                @error('mobile_pin')<div class="text-danger small mt-1">پن لازماً 6 ہندسوں کا ہونا چاہیے۔</div>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <div class="edit-actions">
                    <a href="{{ route('admin.customers.statement', $customer) }}" class="btn btn-outline-secondary"><i class="fas fa-times ml-1"></i> منسوخ کریں</a>
                    <button type="submit" class="btn btn-success px-5"><i class="fas fa-check ml-1"></i> تبدیلیاں محفوظ کریں</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
