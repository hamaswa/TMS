@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="card p-4">
            <div class="mb-4 text-right" dir="rtl">
                <h1 class="h3 mb-2">نئی دکان ترتیب</h1>
                <p class="text-muted mb-0">صرف دکان کا نام ضروری ہے۔ لوگو، رابطہ، نوٹ اور پتہ بعد میں بھی شامل کیے جا سکتے ہیں۔</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger text-right" dir="rtl" role="alert">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form id="cc-form__addSettingForm" action="{{ route('admin.insert-setting') }}"
                method="post" enctype="multipart/form-data">
                @csrf
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="setting-title">دکان کا نام <span class="text-danger">*</span></label>
                        <input id="setting-title" type="text" name="title" value="{{ old('title') }}"
                            class="form-control" maxlength="255" required>
                    </div>
                    <div class="col-md-6">
                        <label for="setting-contact">رابطہ نمبر <span class="text-muted">(اختیاری)</span></label>
                        <input id="setting-contact" type="tel" name="contact_no" value="{{ old('contact_no') }}"
                            class="form-control" maxlength="50" dir="ltr">
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="setting-logo">لوگو <span class="text-muted">(اختیاری)</span></label>
                        <input id="setting-logo" type="file" class="form-control" name="logo" accept="image/*">
                        <small class="form-text text-muted">زیادہ سے زیادہ 2 MB۔ لوگو کے بغیر بھی رسید پرنٹ ہو گی۔</small>
                    </div>
                    <div class="col-md-6">
                        <label for="setting-note">رسید کا نوٹ <span class="text-muted">(اختیاری)</span></label>
                        <input id="setting-note" type="text" name="note" value="{{ old('note') }}"
                            class="form-control" maxlength="1000">
                    </div>
                </div>

                <div class="form-group">
                    <label for="setting-address">پتہ <span class="text-muted">(اختیاری)</span></label>
                    <textarea id="setting-address" rows="3" class="form-control" name="address"
                        maxlength="2000">{{ old('address') }}</textarea>
                </div>

                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="setting-paper-size">ڈیفالٹ پرنٹ سائز</label>
                        <select id="setting-paper-size" name="print_paper_size" class="form-control" required>
                            @foreach(\App\Models\Setting::printPaperSizes() as $paperValue => $paperLabel)
                                <option value="{{ $paperValue }}" @selected(old('print_paper_size', \App\Models\Setting::PRINT_PAPER_RECEIPT_80) === $paperValue)>
                                    {{ $paperLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input type="hidden" name="print_show_qr" value="0">
                            <input id="print_show_qr" type="checkbox" name="print_show_qr" value="1"
                                class="form-check-input" @checked(old('print_show_qr', true))>
                            <label for="print_show_qr" class="form-check-label">پرنٹ پر QR حوالہ دکھائیں</label>
                        </div>
                    </div>
                </div>

                <div class="button-group text-right" dir="rtl">
                    <button type="submit" class="btn btn-blue">محفوظ کریں</button>
                    <a href="{{ route('admin.setting.index') }}" class="btn btn-light mr-2">واپس</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
