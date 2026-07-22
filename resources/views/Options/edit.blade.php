@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

       <div class="card">
            <div class="row">

            @include('inc/OptionType')
            <div class="col-md-9">
                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ URL('admin/Options',$Option['id'])}}"
                                method="POST" class="cc-form__box">
                                <input type="hidden" name="OptionTypeId" value="{{$Option->option_id}}">
                                @csrf
                                {{ method_field('PUT')}}
                                <div class="row">
                                    <div class="col-md-2">
                                        <button class="btn btn-blue mt-md-0 mt-3">تبدیل</button>
                                    </div>
                                    <div class="col-md-10">
                                        <div class="form-row m-0">
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" name="Name"
                                                    value="{{$Option->Name}}">
                                            </div>
                                            <label class="col-sm-4 col-form-label"><span class="english"></span> آپشن
                                                میں تبدیلی<span class="urdu"></span></label>
                                        </div>
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