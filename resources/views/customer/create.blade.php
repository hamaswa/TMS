@extends('main')
@section('content')
    @include('customer.partials.measurement-form-layout')


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
            min-height: 2em;
            margin-bottom: .55rem;
            font-weight: 700;
            line-height: 2
        }

        .preference-panel {
            min-width: 0
        }

        .customer-details-grid.template-one-column {
            grid-template-columns: minmax(0, 1fr)
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
            min-height: 2em;
            margin-bottom: .55rem;
            color: #243b53;
            font-weight: 800;
            line-height: 2
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
            <div class="customer-form-card customer-tailoring-shell bg-white mx-auto">
                <div class="customer-form-head">
                    <h1 class="h3 font-weight-bold mb-2">
                        {{ $parentCustomer ? 'خاندان کا نیا ناپ شامل کریں' : 'نیا گاہک شامل کریں' }}</h1>
                    <p class="mb-0">
                        {{ $parentCustomer ? $parentCustomer->name . ' کے مشترکہ اکاؤنٹ میں الگ نام اور پیمائش محفوظ کریں۔' : 'بنیادی معلومات کے بعد پیمائش اور سلائی کی پسند ایک ہی جگہ مکمل کریں۔' }}
                    </p>
                     {{-- @include('customer.partials.action-menu', [
                        'customer' => $parentCustomer,
                        'selectedCustomer' => $parentCustomer,
                    ]) --}}
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
                        @if ($parentCustomer)
                            <input type="hidden" name="parent_customer_id" value="{{ $parentCustomer->id }}">
                        @endif
                        <section class="customer-step active" data-step="1" aria-labelledby="customer-step-one">
                            <div class="customer-section">
                                <h2 id="customer-step-one" class="h5 font-weight-bold mb-1">گاہک کی بنیادی معلومات</h2>
                                <p class="text-muted mb-4">رابطہ اور موبائل اکاؤنٹ کی معلومات درج کریں۔</p>
                                <div class="row">
                                    <div class="col-md-6 form-group"><label>نام <span
                                                class="text-danger">*</span></label><input type="text"
                                            class="form-control" name="name" value="{{ old('name') }}"
                                            autocomplete="name"></div>
                                    <div class="col-md-6 form-group"><label for="customer-contact">رابطہ نمبر <span
                                                class="text-danger">*</span></label><input id="customer-contact"
                                            type="tel" inputmode="tel" class="form-control" name="contact"
                                            value="{{ old('contact', $parentCustomer?->phone_number1) }}" dir="ltr"
                                            autocomplete="tel" @if ($parentCustomer) readonly @endif
                                            placeholder="03001234567 یا +923001234567">
                                        @error('contact')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    @unless ($parentCustomer)
                                        <div class="col-md-6 form-group"><label for="mobile_pin">موبائل لاگ اِن پن</label><input
                                                id="mobile_pin" type="text" inputmode="numeric" pattern="[0-9]{6}"
                                                maxlength="6" autocomplete="new-password" class="form-control"
                                                name="mobile_pin" value="{{ old('mobile_pin') }}" placeholder="6 ہندسوں کا پن"
                                                dir="ltr"><small class="form-text text-muted">خالی چھوڑنے پر محفوظ پن خود
                                                بنے گا اور صرف ایک بار دکھایا جائے گا۔</small></div>
                                    @endunless
                                    <div class="col-md-6">
                                        <div class="alert alert-info mb-0">
                                            <strong>{{ $parentCustomer ? 'خاندانی پیمائش پروفائل' : 'گاہک اکاؤنٹ' }}</strong><br><small>
                                                @if ($parentCustomer)
                                                    رابطہ، موبائل لاگ اِن، بقایا اور ادائیگیاں {{ $parentCustomer->name }}
                                                    کے مشترکہ اکاؤنٹ میں رہیں گی؛ صرف نام، پیمائش اور سلائی کی پسند الگ
                                                    محفوظ ہوگی۔
                                                @else
                                                    اس گاہک کی پیمائش، آرڈرز، بقایا اور ادائیگیاں اسی کھاتے میں رہیں گی۔
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="step-actions">
                                <a class="btn btn-outline-secondary"
                                    href="{{ $parentCustomer
                                        ? route('admin.customers.statement', ['id' => $parentCustomer->id, 'tab' => 'measurements'])
                                        : route('admin.Customers.index') }}">
                                    منسوخ کریں
                                </a>

                                <div class="d-flex flex-wrap justify-content-end" style="gap: .5rem;">
                                    <button type="submit" name="save_basic_customer" value="1" formnovalidate
                                        class="btn btn-outline-success">
                                        <i class="fas fa-user-check ml-1"></i>
                                        صرف گاہک محفوظ کریں
                                    </button>

                                    <button type="button" class="btn btn-primary px-4 next-step">
                                        پیمائش اور پسند درج کریں
                                        <i class="fas fa-arrow-left mr-1"></i>
                                    </button>
                                </div>
                            </div>
                        </section>

                        <section class="customer-step" data-step="2" aria-labelledby="customer-step-two">
                            <div class="customer-details-grid customer-tailoring-layout">
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
                                    <div class="measurement-grid" data-template-column="right">
                                        @foreach (['length' => 'لمبائی', 'arms' => 'بازو', 'teraa' => 'تیرا', 'senaChorai' => 'سینہ چوڑائی', 'damanchorai' => 'دامن چوڑائی', 'shalwar' => 'شلوار', 'pancha' => 'پائنچہ', 'shalwarGheer' => 'شلوار گھیر', 'monda' => 'مونڈھا', 'chuta' => 'چوٹا'] as $name => $label)
                                            @include('customer.partials.system-measurement-field', [
                                                'fieldKey' => $name === 'monda' ? 'shoulder' : $name,
                                                'name' => $name,
                                                'label' => $label,
                                                'unit' => 'inch',
                                                'choices' => collect(),
                                                'optionTypeId' => null,
                                                'optionTemplateId' => null,
                                                'inputId' => 'measurement-'.$name,
                                                'inputName' => $name,
                                                'value' => null,
                                                'required' => false,
                                            ])
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
                                    <div class="preference-grid" data-template-column="left">
                                        @foreach ($data['optionTypes'] as $type)
                                            @php
                                                $templateSystemField =
                                                    $type->type === 'daaman' ? 'Daaman' : $type->type;
                                                $selectedValue = trim((string) old($type->slug));
                                                $choices = $optionChoices->get($type->option_id, collect());
                                                $hasSelected = $choices->contains(
                                                    fn($c) => trim((string) $c->Name) === $selectedValue,
                                                );
                                            @endphp
                                            <div class="form-group"
                                                data-template-field="system.{{ $templateSystemField }}"
                                                data-default-column="left">
                                                <label for="preference-{{ $type->slug }}">{{ $type->otn }}</label>
    
                                                <select id="preference-{{ $type->slug }}" class="form-control" name="{{ $type->slug }}"
                                                    @if(Auth::user()->hasBusinessPermission('tailoring.configuration')) data-inline-option-type="{{ $type->option_id }}" data-inline-option-label="{{ $type->otn }}" @endif
                                                    data-template-system-field="{{ $templateSystemField }}">
                                                    <option value="">{{ $type->otn }} منتخب کریں</option>

                                                    @if ($selectedValue !== '' && !$hasSelected)
                                                        <option value="{{ $selectedValue }}" selected>
                                                            {{ $selectedValue }}</option>
                                                    @endif

                                                    @foreach ($choices as $choice)
                                                        @php $choiceName = trim((string) $choice->Name); @endphp
                                                        <option value="{{ $choiceName }}" data-template="{{ $choice->measurement_template_id }}" @selected($choiceName === $selectedValue)>
                                                            {{ $choiceName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
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
                                        class="fas fa-arrow-right ml-1"></i> بنیادی معلومات</button>
                                <button type="submit" class="btn btn-success px-5">
                                    <i class="fas fa-ruler-combined ml-1"></i>
                                    پیمائش محفوظ کریں
                                </button>
                            </div>
                        </section>
                    </form>
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

        });
    </script>
    @if(Auth::user()->hasBusinessPermission('tailoring.configuration'))
        @include('customer.partials.quick-option-modal')
        @include('customer.partials.searchable-option-select')
    @endif
@endsection
