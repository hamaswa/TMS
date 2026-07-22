@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

        <div class="card">
            <div class="row">
            @include('inc/OptionType')
            <div class="col-md-9">
                @if(Session::has('del'))
                <div class="alert alert-danger">{{Session::get('del')}}</div>
                @endif
                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ url('admin/OptionType',$OptionType->id)}}"
                                method="post" class="cc-form__box">
                                @csrf
                                {{ method_field('PUT')}}
                                <input type="hidden" name="OptionTypeId" id="OptionType">
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-row m-0">
                                            <label class="col-sm-4 col-form-label">آپشن کی قسم میں
                                                تبدیلی</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" name="Name"
                                                    value="{{$OptionType->Name}}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-warning mt-md-0 mt-3">تبدیل</button>
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