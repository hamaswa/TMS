@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
         <div class="card">
        <div class="row">
            <div class="col-md-12">
                <h1 class="h4 text-right">فروخت تبدیل کریں</h1>
                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ route('admin.sale.update', $sales->id)}}" method="post"
                                class="cc-form__box">
                                @csrf
                                {{ method_field('PUT')}}
                                <div class="row" style="margin-bottom:20px;">
                                    <div class="col-sm-12">
                                        <label style="position:relative;left:90%;font-weight:600;">گاہک کا نام</label>
                                        <select required class="form-control" name="customer_id">
                                            <option value="">گاہک منتخب کریں</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" @selected(old('customer_id', $sales->customer_id) == $customer->id)>
                                                    {{ $customer->name }} — {{ $customer->phone_number1 }}
                                                </option>
                                            @endforeach
                                        </select>
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
                                            <input type="number" class="form-control total_price" required name="price[]">

                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-4">
                                        <label style="position:relative;left:70%;font-weight:600;" >ادائیگی موصول ہوئی۔</label>
                                        <input type="number" id="received_price" class="form-control" required name="received_payment" oninput="receivedPayment()">
                                    </div>
                                    <div class="col-sm-4">
                                        <label style="position:relative;left:80%;font-weight:600;">بقیہ رقم</label>
                                        <input type="number" id="remaining_price" class="form-control" required name="remaining_balance" readonly>
                                    </div>
                                    <div class="col-sm-4">
                                        <label style="position:relative;left:50%;font-weight:600;">پچھلی ادائیگی واجب الادا </label>
                                        <input type="number" class="form-control" value="{{ $latestBalance}}" required readonly>
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
// function receivedPayment() {
//     var total_prices = $(".total_price");
//     var received_price = $("#received_price").val();
//     var total_price = 0;

//     total_prices.each(function () {
//         total_price += parseFloat($(this).val()) || 0;
//     });

//     var remaining_price = total_price - received_price;
//     $("#remaining_price").val(remaining_price);
// }
function receivedPayment() {
    var total_prices = $(".total_price");
    var received_price = $("#received_price").val();
    var total_price = 0;

    total_prices.each(function () {
        total_price += parseFloat($(this).val()) || 0;
    });

    var remaining_price = total_price - received_price;
    $("#remaining_price").val(remaining_price);
}



</script>


@endsection
