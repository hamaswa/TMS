@extends('main')
@section('content')
<style>
span{
    font-size: 20px;
}
</style>
    <section class="main-content">
        <div class="container">

            <div class="card col-sm-10 mx-auto">
                @include('inc.message')
                <h2 class="mb-4 text-right">ملازمین کا ڈیٹا تبدیل کریں۔</h2>
                <form id="cc-form__addCustomerForm" class="add-customer-form"
                    method="post" action="{{route('admin.worker.update',['id'=>$data->id])}}">
                    @csrf
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">ملازم کا نام</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="name" value="{{$data->Worker_Name}}">
                                    @error('rent')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">ملازم کی تنخواہ</span> </label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="salary" value="{{$data->Worker_salary}}">
                                    @error('bill')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-md-8 mx-auto row">
                        <div class="button-group">
                            <button type="submit" class="btn btn-blue mr-3">محفوظ کریں</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
