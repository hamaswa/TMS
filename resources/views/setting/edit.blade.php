@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

        <h2 class="mb-4 text-right">ترتیب میں تبدیلی</h2>
        <img src="{{asset('public/images/setting/'.$setting->logo)}}" alt="" class="" width="250" height="150">
        <form id="cc-form__addCustomerForm" action="{{ url('admin/setting/update',$setting->id)}}" class="add-customer-form"
            method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><span class="english">نام:</span> <span class="urdu">نام
                                :</span></label>
                        <input type="" name="title" value="{{$setting->name}}" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>نمبر: </label>
                        <input type="text" name="contact_no" value="{{$setting->contact_no}}" class="form-control"
                            required>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6">
                    <label> تصویر:</label>
                    <input type="file" class="form-control" name="logo">
                    <input type="hidden" class="form-control" name="oldlogo" value="{{$setting->logo}}">
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            نوٹ:
                        </label>
                        <input type="text" name="note" value="{{$setting->note}}" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>پتہ: </label>
                        <textarea rows="4" cols="" class="form-control" name='address'
                            required>{{ $setting->address }}</textarea>
                    </div>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn btn-blue mr-3">تبدیل</button>
                </div>
        </form>
    </div>
</section>

@endsection
