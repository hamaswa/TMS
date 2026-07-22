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
                <div class="button-group">
                    <button type="submit" class="btn btn-blue mr-3">محفوظ</button>
                </div>
        </form>
    </div>
</section>

@endsection
