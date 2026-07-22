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
                            <form id="cc-form__optionsForm" action="{{ url('admin/sale')}}" method="post"
                                class="cc-form__box">
                                @csrf
                                <div class="row" style="margin-bottom:20px;">
                                    <div class="col-sm-12">
                                        <label style="position:relative;left:90%;font-weight:600;">گاہک کا نام</label>
                                        <select required class="form-control" name="customer_id">
                                            <option value="">گاہک منتخب کریں</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                                    {{ $customer->name }} — {{ $customer->phone_number1 }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted text-right">ٹیلرنگ اور دکان دونوں کے لیے یہی مشترکہ گاہک کھاتہ استعمال ہوگا۔</small>
                                    </div>
                                </div>
                                <div class="addmore" style="margin-bottom:20px;">
                                    <div class="row record">
                                        <div class="col-sm-4">
                                            <label style="position:relative;left:70%;font-weight:600;">پروڈکٹ کا نام</label>
                                            <input type="text" class="form-control" required name="name[]">
                                        </div>
                                        <div class="col-sm-4">
                                            <label style="position:relative;left:50%;font-weight:600;"> مصنوعات کی تعداد</label>
                                            <input type="number" class="form-control" required name="quantity[]">
                                        </div>
                                        <div class="col-sm-4 mt-1">
                                            <label style="position:relative;left:70%;font-weight:600;">کل قیمت</label>
                                            <input type="number" class="form-control" required name="price[]">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <label style="position:relative;left:70%;font-weight:600;">ادائیگی موصول ہوئی۔</label>
                                        <input type="number" class="form-control" required name="received_payment" id="received_payment">
                                    </div>
                                    <div class="col-sm-6">
                                        <label style="position:relative;left:80%;font-weight:600;">بقیہ رقم</label>
                                        <input type="number" class="form-control" required name="remaining_balance">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-12 d-flex justify-content-between  mt-4">
                                        <button type="button"  class="btn btn-primary remove-div mt-md-0 mt-3">منسوخ  کریں </button>
                                        <button type="button"  class="btn btn-primary add_new mt-md-0 mt-3">مزید شامل کریں </button>
                                        <button type="sumbit" class="btn btn-primary mt-md-0 mt-3">  محفوظ کریں </button>
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


<script>
    $(document).ready(function(){
        $("#received_payment").on('input',function(){
            var payment = $(this).val();
            console.log(payment);
        })
    });
</script>



@endsection
