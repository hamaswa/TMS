@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
         <div class="card">
        <h5 class="mb-4 text-right">درزی</h5>
        <div class="row">


            <div class="col-md-12">
                @if(Session::has('insert'))
                <div class="alert alert-success">{{Session::get('insert')}}</div>
                @endif

                @if(Session::has('del'))
                <div class="alert alert-danger">{{Session::get('del')}}</div>
                @endif

                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ url('admin/Tailor',$tailorData->id)}}"
                                method="post" class="cc-form__box">
                                @csrf
                                {{ method_field('PUT')}}
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label>نام</label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{$tailorData->name}}">
                                    </div>

                                    <div class="col-sm-6">
                                        <label>پاس ورڈ</label>
                                        <input type="password" class="form-control" name="password"
                                            autocomplete="new-password" minlength="6"
                                            placeholder="موجودہ پاس ورڈ برقرار رکھنے کے لیے خالی چھوڑ دیں">
                                    </div>


                                    <div class="col-sm-6">
                                        <label>نمبر</label>
                                        <input type="text" class="form-control" name="contact"
                                            value="{{$tailorData->phone_number1}}">
                                    </div>

                                    {{-- <div class="col-sm-6">
                                        <label>درزی کے پچھلے نرخ</label>
                                        @foreach ($tailorData->tailorsalary as $rate)
                                            <div class="rate">{{ $rate->price }}</div>
                                        @endforeach
                                    </div>

                                    <div class="col-sm-6">
                                        <label>درزی کے مختلف نرخ</label>
                                        <input type="text" value="{{ $tailorData->tailorsalay }}" data-role="tagsinput" name="tailor_rates" class="form-control" required>
                                    
                                    </div> --}}
                                    <div class="col-sm-6 mt-3">
                                        <button class="btn btn-warning mt-md-0">محفوظ کریں</button>
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
