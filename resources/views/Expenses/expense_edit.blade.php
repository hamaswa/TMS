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
                <h1 class="h4 mb-4 text-right">اخراجات تبدیل کریں</h1>
                <form id="cc-form__addCustomerForm" class="add-customer-form"
                    method="post" action="{{route('admin.expense.update',['id'=>$data->id])}}">
                    @csrf
                    <input type="hidden" value="{{$data->id}}">
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">ماہانہ کرایہ</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="rent" value="{{$data->Monthly_Rent}}">
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
                                <label class="col-sm-3 col-form-label f"><span class="english">ماہانہ بل</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="bill" value="{{$data->Monthly_Bill}}">
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

