@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
         <div class="card">
        <div class="row">
            <div class="col-md-12">
                <h1 class="h4 text-right">نیا درزی شامل کریں</h1>
                @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ url('admin/Tailor')}}" method="post"
                                class="cc-form__box">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label for="tailor_name">نام</label>
                                        <input id="tailor_name" type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="مثلاً محمد وقاص" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="tailor_password">پورٹل پاس ورڈ</label>
                                        <input id="tailor_password" type="password" class="form-control" name="password" autocomplete="new-password" minlength="6" required>
                                        <small class="form-text text-muted">درزی اس پاس ورڈ، فون نمبر اور دکان کوڈ سے پورٹل میں داخل ہوگا۔</small>
                                    </div>
                                    <div class="col-sm-6 mt-1">
                                        <label for="tailor_contact">فون نمبر</label>
                                        <input id="tailor_contact" type="tel" inputmode="tel" class="form-control" name="contact" value="{{ old('contact') }}" placeholder="مثلاً 03001234567" required>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <div class="alert alert-info text-right mb-2" dir="rtl">
                                            <strong>آرڈر فوراً بنانے کے لیے ابھی ایک بنیادی سلائی اجرت بھی درج کریں۔</strong>
                                            <div class="small mt-1">یہ گاہک سے وصولی نہیں، درزی کی فی سوٹ اجرت ہے۔ اسے بعد میں تبدیل یا مزید نرخ شامل کیے جا سکتے ہیں۔</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 mt-1">
                                        <label for="initial_rate_label">سلائی کی قسم</label>
                                        <input id="initial_rate_label" type="text" class="form-control" name="initial_rate_label" value="{{ old('initial_rate_label', 'معیاری سلائی') }}" required>
                                    </div>
                                    <div class="col-sm-6 mt-1">
                                        <label for="initial_rate_price">فی سوٹ اجرت</label>
                                        <input id="initial_rate_price" type="number" min="0.01" step="0.01" class="form-control" name="initial_rate_price" value="{{ old('initial_rate_price') }}" placeholder="مثلاً 500" required>
                                    </div>
                                    {{-- <div class="col-sm-6 mt-1">
                                        <label>درزی کے مختلف نرخ</label>
                                        <input type="text" data-role="tagsinput" name="tailor_rates" class="form-control" required>
                                    </div> --}}
                                    <div class="col-sm-6 mt-3">
                                        <button class="btn btn-primary mt-md-0 mt-3">محفوظ کریں</button>
                                    </div>

                                </div>
                            </form>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
    </div>
</section>


@endsection
