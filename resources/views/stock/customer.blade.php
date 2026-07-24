@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
         <div class="card">
        <div class="row">
            <div class="col-md-12">
                <h5 class="text-right">نئی فروخت</h5>
                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ route('admin.addcustomers.sale')}}" method="post"
                                class="cc-form__box">
                                @csrf
                                <div class="row" style="margin-bottom:20px;">
                                    <div class="col-sm-12">
                                        <label style="position:relative;left:90%;font-weight:600;">گاہک کا نام</label>
                                        <input type="text" required class="form-control" name="customer_name">
                                    </div>
                                </div>

                                <div class="row" style="margin-bottom:20px;">
                                    <div class="col-sm-12">
                                        <label for="customer_num" style="font-weight:600;">گاہک کا نمبر</label>
                                        <input id="customer_num" type="tel" inputmode="tel" required class="form-control" name="customer_num" dir="ltr" placeholder="03001234567 یا +923001234567">
                                        @error('customer_num')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>


                                <button type="sumbit" class="btn btn-primary mt-md-0 mt-3">  محفوظ کریں </button>
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
