@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
         <div class="card">
        <div class="row">
            <div class="col-md-12">
                <h5 class="text-right">درزی</h5>
                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ url('admin/Tailor')}}" method="post"
                                class="cc-form__box">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label>نام</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label>پاس ورڈ</label>
                                        <input type="password" class="form-control" name="password" autocomplete="new-password" minlength="6" required>
                                    </div>
                                    <div class="col-sm-6 mt-1">
                                        <label>نمبر</label>
                                        <input type="text" class="form-control" name="contact" required>
                                    </div>
                                    {{-- <div class="col-sm-6 mt-1">
                                        <label>درزی کے مختلف نرخ</label>
                                        <input type="text" data-role="tagsinput" name="tailor_rates" class="form-control" required>
                                    </div> --}}
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
