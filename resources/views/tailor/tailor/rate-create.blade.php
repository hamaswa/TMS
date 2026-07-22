@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
         <div class="card">
        <div class="row">
            <div class="col-md-12">
                @include('inc.message')
                <h5 class="text-right">درزی کی ریٹ</h5>
                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action={{ url('admin/tailors-rates/store/'.$tailor->id) }} method="post"
                                class="cc-form__box">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label>سلائی کی قسم</label>
                                        <select class="form-control" name="options_id" id="options_id">
                                        <option value="">سلائی کی قسم منتخب کریں۔</option> 
                                        @foreach ($types as $type)
                                            <option value="{{ $type->id }}">{{ $type->Name }}</option>
                                        @endforeach   
                                        </select>                                        
                                    </div>
                                    <div class="col-sm-6">
                                        <label>رقم</label>
                                        <input type="number" class="form-control" name="price" required>
                                    </div>
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