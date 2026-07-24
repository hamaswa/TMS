@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

        <h2 class="mb-4">نائی ترتیب</h2>
        <form id="cc-form__addCustomerForm" action="{{ url('admin/setting/insert')}}" class="add-customer-form"
            method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>نام</label>
                        <input type="" name="title" value="" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>نمبر</label>
                        <input type="number" name="contact_no" value="" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6">
                    <label> تصویر</label>
                    <input type="file" class="form-control" name="logo" required>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            نوٹ
                        </label>
                        <input type="text" name="note" value="" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label><span class="english">پتہ</span> </label>
                        <textarea rows="4" cols="" class="form-control" name='address' required></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>ڈیفالٹ پرنٹ سائز</label>
                        <select name="print_paper_size" class="form-control" required>
                            @foreach(\App\Models\Setting::printPaperSizes() as $paperValue => $paperLabel)
                                <option value="{{ $paperValue }}" @selected(old('print_paper_size', \App\Models\Setting::PRINT_PAPER_RECEIPT_80) === $paperValue)>
                                    {{ $paperLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check">
                        <input type="hidden" name="print_show_qr" value="0">
                        <input id="print_show_qr" type="checkbox" name="print_show_qr" value="1" class="form-check-input" @checked(old('print_show_qr', true))>
                        <label for="print_show_qr" class="form-check-label">پرنٹ پر QR حوالہ دکھائیں</label>
                    </div>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn btn-blue mr-3">محفوظ</button>
                </div>
        </form>
    </div>
</section>

@endsection
