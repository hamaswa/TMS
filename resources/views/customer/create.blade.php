@extends('main')
@section('content')
    <style>
        .customer-create-page {
            background: #f5f7fa;
            min-height: calc(100vh - 70px)
        }

        .customer-form-card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 14px 36px rgba(31, 45, 61, .1);
            overflow: hidden
        }

        .customer-form-head {
            background: linear-gradient(135deg, #102a43, #1769aa);
            color: #fff;
            padding: 1.6rem 2rem
        }

        .customer-form-head h1 {
            color: #fff !important
        }

        .customer-form-head p {
            color: rgba(255, 255, 255, .8) !important
        }

        .customer-progress {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .7rem;
            margin-bottom: 1.5rem
        }

        .customer-progress-item {
            border: 1px solid #dfe7f0;
            border-radius: 14px;
            padding: .8rem;
            background: #fff;
            color: #6c7a89;
            font-weight: 700;
            text-align: center
        }

        .customer-progress-item.active {
            border-color: #1769aa;
            background: #eaf4fb;
            color: #1769aa
        }

        .customer-progress-item.complete {
            border-color: #28a745;
            color: #218838
        }

        .customer-step {
            display: none
        }

        .customer-step.active {
            display: block
        }

        .customer-section {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.25rem;
            background: #fbfdff
        }

        .measurement-field label {
            font-weight: 700
        }

        .step-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #edf2f7
        }

        .customer-details-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(330px, .85fr);
            gap: 1.25rem;
            align-items: start
        }

        .combined-panel {
            height: 100%;
            border: 1px solid #dce6f1;
            border-radius: 18px;
            padding: 1.25rem;
            background: #fbfdff;
            box-shadow: 0 7px 20px rgba(31, 45, 61, .05)
        }

        .combined-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.15rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e8eef5
        }

        .combined-panel-title {
            display: flex;
            align-items: flex-start;
            gap: .75rem
        }

        .combined-panel-icon {
            display: grid;
            place-items: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            color: #1769aa;
            background: #e8f3fc
        }

        .combined-panel h2 {
            font-size: 1.12rem;
            font-weight: 900;
            margin: 0 0 .25rem
        }

        .combined-panel p {
            font-size: .84rem;
            margin: 0;
            color: #718096
        }

        .measurement-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem
        }

        .measurement-grid .form-group {
            margin-bottom: 0
        }

        .measurement-grid label {
            font-weight: 700
        }

        .preference-panel {
            min-width: 0
        }

        .preference-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem
        }

        .preference-grid .form-group {
            min-width: 0;
            margin: 0
        }

        .preference-grid label {
            display: block;
            margin-bottom: .45rem;
            color: #243b53;
            font-weight: 800
        }

        .preference-grid select.form-control {
            width: 100%;
            height: 54px;
            min-height: 54px;
            padding: 0 14px 7px 38px !important;
            border-radius: 10px;
            border-color: #d7e1ed;
            direction: rtl;
            text-align: right;
            font-size: 1rem;
            line-height: 2.2;
            background-position: left .75rem center
        }

        .preference-grid select.form-control:focus {
            border-color: #1769aa;
            box-shadow: 0 0 0 .15rem rgba(23, 105, 170, .12)
        }

        .preference-note {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e8eef5
        }

        .preference-note textarea {
            min-height: 125px;
            resize: vertical
        }

        .duplicate-customer-card {
            border: 1px solid #d9e5f3;
            border-radius: 14px;
            padding: 1rem;
            background: #f8fbff
        }

        .duplicate-customer-card strong {
            display: block;
            color: #102a43;
            font-size: 1.05rem
        }

        .duplicate-customer-card span {
            color: #60758d
        }

        .duplicate-choice {
            display: flex;
            gap: .75rem;
            justify-content: flex-start;
            flex-wrap: wrap
        }

        .duplicate-choice .btn {
            min-height: 44px;
            border-radius: 10px;
            font-weight: 800
        }

        @media(max-width:1199px) {
            .preference-grid {
                grid-template-columns: 1fr
            }
        }

        @media(max-width:991px) {
            .customer-details-grid {
                grid-template-columns: 1fr
            }

            .combined-panel {
                height: auto
            }

            .preference-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
        }

        @media(max-width:767px) {
            .customer-progress {
                grid-template-columns: 1fr
            }

            .customer-form-head {
                padding: 1.3rem
            }

            .customer-form-card .card-body {
                padding: 1rem !important
            }

            .measurement-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
        }

        @media(max-width:420px) {
            .measurement-grid,
            .preference-grid {
                grid-template-columns: 1fr
            }
        }
    </style>
    <section class="main-content customer-create-page" dir="rtl">
        <div class="container-fluid px-3 px-lg-5 py-4">
            <div class="customer-form-card bg-white mx-auto" style="max-width:1420px">
                <div class="customer-form-head">
                    <h1 class="h3 font-weight-bold mb-2">نیا گاہک شامل کریں</h1>
                    <p class="mb-0">بنیادی معلومات کے بعد پیمائش اور سلائی کی پسند ایک ہی جگہ مکمل کریں۔</p>
                </div>
                <div class="card-body p-4 p-lg-5">
                    @if ($errors->any())
                        <div class="alert alert-danger"><strong>براہ کرم درج ذیل معلومات درست کریں:</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="customer-progress" aria-label="گاہک شامل کرنے کے مراحل">
                        <div class="customer-progress-item active" data-progress="1"><span
                                class="badge badge-primary ml-1">1</span> بنیادی معلومات</div>
                        <div class="customer-progress-item" data-progress="2"><span class="badge badge-light ml-1">2</span>
                            پیمائش اور سلائی کی پسند</div>
                    </div>

                    <form id="customer-create-form" action="{{ route('admin.Customers.store') }}" method="post">@csrf
                        <input id="duplicate-action" type="hidden" name="duplicate_action" value="">
                        <section class="customer-step active" data-step="1" aria-labelledby="customer-step-one">
                            <div class="customer-section">
                                <h2 id="customer-step-one" class="h5 font-weight-bold mb-1">گاہک کی بنیادی معلومات</h2>
                                <p class="text-muted mb-4">رابطہ اور موبائل اکاؤنٹ کی معلومات درج کریں۔</p>
                                <div class="row">
                                    <div class="col-md-6 form-group"><label>نام <span
                                                class="text-danger">*</span></label><input type="text"
                                            class="form-control" name="name" value="{{ old('name') }}" required
                                            autocomplete="name"></div>
                                    <div class="col-md-6 form-group"><label for="customer-contact">رابطہ نمبر <span
                                                class="text-danger">*</span></label><input id="customer-contact"
                                            type="tel" inputmode="tel" class="form-control" name="contact"
                                            value="{{ old('contact') }}" required dir="ltr" autocomplete="tel"
                                            placeholder="03001234567 یا +923001234567">
                                        @error('contact')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 form-group"><label for="mobile_pin">موبائل لاگ اِن پن</label><input
                                            id="mobile_pin" type="text" inputmode="numeric" pattern="[0-9]{6}"
                                            maxlength="6" autocomplete="new-password" class="form-control"
                                            name="mobile_pin" value="{{ old('mobile_pin') }}" placeholder="6 ہندسوں کا پن"
                                            dir="ltr"><small class="form-text text-muted">خالی چھوڑنے پر محفوظ پن خود
                                            بنے گا اور صرف ایک بار دکھایا جائے گا۔</small></div>
                                    <div class="col-md-6">
                                        <div class="alert alert-info mb-0"><strong>مشترکہ گاہک اکاؤنٹ</strong><br><small>یہی
                                                گاہک ٹیلرنگ اور دکان دونوں میں استعمال ہوگا؛ بقایا اور ادائیگیاں ایک مشترکہ
                                                کھاتے میں رہیں گی۔</small></div>
                                    </div>
                                </div>
                            </div>
                            <div class="step-actions"><a class="btn btn-outline-secondary"
                                    href="{{ route('admin.Customers.index') }}">منسوخ کریں</a><button type="button"
                                    class="btn btn-primary px-4 next-step">پیمائش اور پسند درج کریں <i
                                        class="fas fa-arrow-left mr-1"></i></button></div>
                        </section>

                        <section class="customer-step" data-step="2" aria-labelledby="customer-step-two">
                            <div class="customer-details-grid">
                                <div class="combined-panel measurement-panel">
                                    <div class="combined-panel-head">
                                        <div class="combined-panel-title"><span class="combined-panel-icon"><i
                                                    class="fas fa-ruler-combined"></i></span>
                                            <div>
                                                <h2 id="customer-step-two">لباس کی پیمائش</h2>
                                                <p>دائیں جانب تمام بنیادی اور خصوصی ناپ درج کریں۔</p>
                                            </div>
                                        </div>
                                        <span class="badge badge-light px-3 py-2">ضروری خانے *</span>
                                    </div>
                                    @include('customer.partials.measurement-template-selector', [
                                        'selectedTemplateId' => $measurementTemplates->firstWhere(
                                            'is_default',
                                            true)?->id,
                                    ])
                                    <div class="measurement-grid">
                                        @foreach (['length' => 'لمبائی', 'arms' => 'بازو', 'teraa' => 'تیرا', 'senaChorai' => 'سینہ چوڑائی', 'damanchorai' => 'دامن چوڑائی', 'shalwar' => 'شلوار', 'pancha' => 'پائنچہ', 'shalwarGheer' => 'شلوار گھیر', 'monda' => 'مونڈھا', 'chuta' => 'چوٹا'] as $name => $label)
                                            <div class="form-group measurement-field"><label
                                                    for="measurement-{{ $name }}">{{ $label }} <span
                                                        class="text-danger">*</span></label><input
                                                    id="measurement-{{ $name }}" type="number" step="0.01"
                                                    min="0" class="form-control js-no-wheel-number" name="{{ $name }}"
                                                    value="{{ old($name) }}" required></div>
                                        @endforeach
                                        @include('customer.partials.custom-measurements', [
                                            'embedded' => true,
                                        ])
                                    </div>
                                </div>

                                <div class="combined-panel preference-panel">
                                    <div class="combined-panel-head">
                                        <div class="combined-panel-title"><span class="combined-panel-icon"><i
                                                    class="fas fa-cut"></i></span>
                                            <div>
                                                <h2>سلائی کی پسند</h2>
                                                <p>بائیں جانب مستقل پسند منتخب کریں؛ آرڈر میں بعد میں تبدیل ہو سکتی ہے۔</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preference-grid">
                                        @foreach ($data['optionTypes'] as $type)
                                            @php(
    $options = DB::table('options')->where('option_id', $type->option_id)->where('user_id', Auth::user()->businessOwnerId())->get()
)
                                            <div class="form-group"><label>{{ $type->otn }}</label><select
                                                    class="form-control" name="{{ $type->slug }}">
                                                    <option value="0">{{ $type->otn }} منتخب کریں</option>
                                                    @foreach ($options as $option)
                                                        <option value="{{ $option->id . ' - ' . $option->Name }}"
                                                            @selected(old($type->slug) === $option->id . ' - ' . $option->Name)>{{ $option->Name }}</option>
                                                    @endforeach
                                                </select></div>
                                        @endforeach
                                    </div>
                                    <div class="preference-note"><label class="font-weight-bold">خصوصی نوٹ</label>
                                        <textarea class="form-control" name="note" rows="5" maxlength="2000"
                                            placeholder="مثلاً فٹنگ، کپڑے یا سلائی سے متعلق ہدایات">{{ old('note') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="step-actions"><button type="button"
                                    class="btn btn-outline-secondary previous-step"><i
                                        class="fas fa-arrow-right ml-1"></i> بنیادی معلومات</button><button type="submit"
                                    class="btn btn-success px-5"><i class="fas fa-check ml-1"></i> گاہک محفوظ
                                    کریں</button></div>
                        </section>
                    </form>
                    @if (session('duplicate_customer'))
                        @php($duplicateCustomer = session('duplicate_customer'))
                        <div class="modal fade" id="duplicateCustomerModal" tabindex="-1" role="dialog"
                            aria-labelledby="duplicateCustomerTitle" aria-hidden="true" data-backdrop="static">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content border-0" style="border-radius:18px;overflow:hidden">
                                    <div class="modal-header align-items-center">
                                        <h4 class="modal-title font-weight-bold" id="duplicateCustomerTitle"><i
                                                class="fas fa-user-check text-primary ml-2"></i> یہ موبائل نمبر پہلے سے
                                            محفوظ ہے</h4>
                                        <button type="button" class="close mr-auto ml-0" data-dismiss="modal"
                                            aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body p-4 text-right">
                                        <p class="mb-3">اس نمبر کے ساتھ موجود گاہک ملا ہے۔ مطلوبہ کارروائی منتخب کریں:
                                        </p>
                                        <div class="duplicate-customer-card mb-4">
                                            <strong>{{ $duplicateCustomer['name'] }}</strong>
                                            <span dir="ltr">{{ $duplicateCustomer['phone'] }}</span>
                                        </div>
                                        <div class="duplicate-choice">
                                            <button type="button" class="btn btn-primary duplicate-customer-choice"
                                                data-action="use_existing"><i class="fas fa-user-check ml-1"></i> موجودہ
                                                گاہک استعمال کریں</button>
                                            <button type="button"
                                                class="btn btn-outline-success duplicate-customer-choice"
                                                data-action="create_profile"><i class="fas fa-copy ml-1"></i> نیا ریکارڈ
                                                شامل کریں</button>
                                        </div>
                                        <small class="d-block text-muted mt-3">نیا ریکارڈ اسی مشترکہ گاہک اکاؤنٹ کے تحت الگ
                                            نام اور ناپ کے طور پر محفوظ ہوگا؛ بقایا اور موبائل لاگ اِن مشترک رہیں
                                            گے۔</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <noscript>
                        <style>
                            .customer-step {
                                display: block !important;
                                margin-bottom: 2rem
                            }

                            .next-step,
                            .previous-step,
                            .customer-progress {
                                display: none !important
                            }
                        </style>
                    </noscript>
                </div>
            </div>
        </div>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            function currentStepIsValid() {
                const controls = Array.from(steps[current].querySelectorAll('input, select, textarea'));
                const invalid = controls.find(control => !control.checkValidity());
                if (invalid) {
                    invalid.reportValidity();
                    invalid.focus();
                    return false;
                }
                return true;
            }

            form.querySelectorAll('.next-step').forEach(button => button.addEventListener('click', () => {
                if (currentStepIsValid()) showStep(current + 1);
            }));
            form.querySelectorAll('.previous-step').forEach(button => button.addEventListener('click', () =>
                showStep(current - 1)));
            progress.forEach((item, index) => item.addEventListener('click', () => {
                if (index <= current) showStep(index);
            }));

            const duplicateModal = document.getElementById('duplicateCustomerModal');
            if (duplicateModal) {
                if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
                    window.jQuery(duplicateModal).modal('show');
                }

                duplicateModal.querySelectorAll('.duplicate-customer-choice').forEach(button => {
                    button.addEventListener('click', function() {
                        document.getElementById('duplicate-action').value = this.dataset.action;
                        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
                            window.jQuery(duplicateModal).modal('hide');
                        }
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                    });
                });
            }
        });
    </script>
@endsection
