@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card">
                <form id="cc-form__addCustomerForm" method="POST" action="{{ url('admin/order/update/' . $data->id) }}"
                    class="add-customer-form mt-4">
                    @csrf
                    @method('put')
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <p class="text-right"><b>ناپ</b></p>
                            <div class="input-group">
                                <input class="form-control border-end-0 border rounded-pill search" type="text"
                                    placeholder="search" id="search" data-url="{{ route('admin.search') }}">
                                <span class="input-group-append">
                                    <button
                                        class="btn btn-outline-secondary bg-white border-start-0 border rounded-pill ms-n3"
                                        type="button">
                                        <i class="fa fa-search" id="SearchData"></i>
                                    </button>
                                </span>
                            </div>

                            <!-- select -->
                            <div class=" mt-3" id="select">
                                <select class='form-control' style="height: 50px" name='sub_id'>
                                    <option value='{{ $sub_customer->id }}'>{{ $sub_customer->name }}
                                        {{ $sub_customer->phone_number1 }}</option>
                                </select>
                            </div>
                            <!-- select -->
                        </div>
                    </div>

                    <h2 class="mb-4 mt-3 text-right">آرڈر میں تبدیلی</h2>

                    <!--{{-- <input type="hidden" name="user_id" value="{{$data['customer']->user_id}}"> --}}-->
                    @csrf
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">کپڑوں کی تعداد</label>
                                <div class="col-sm-9">
                                    <input type="number" value="{{ $data->suitQuantity }}" class="form-control"
                                        name="suitQuantity" required>
                                </div>
                            </div>
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label">نام</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="CustomerName" readonly
                                        value="{{ $customer->name }}">
                                    <input type="hidden" class="form-control" name="customerId"
                                        value="{{ $customer->id }}">
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">ٹوٹل قیمت</label>
                                <div class="col-sm-9">
                                    <input type="number" value="{{ $data->totalPayment }}" class="form-control"
                                        name="totalPayment" id="totalPayment" required>
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">وصول رقم</label>
                                <div class="col-sm-9">
                                    <input type="number" value="{{ $recivedPayment }}" class="form-control"
                                        name="recivedPayment" id="recivedPayment" required>
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">بقیہ</label>
                                <div class="col-sm-9">
                                    @if($remainingBalance !== null)
                                        <input type="number" class="form-control" name="totalBalance" readonly
                                            value="{{ $remainingBalance }}">
                                    @else
                                        <span class="form-control text-muted">بقایا دیکھنے کی اجازت نہیں</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">بیلنس</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="balance"  id="balance">
                                </div>
                            </div>

                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">درزی</label>
                                <div class="col-sm-9">
                                    <select id="tailor-selected" class="form-control" name="tailorId" required
                                        dir="rtl">
                                        <option value="0">درزی کو منتخب کریں</option>
                                        @foreach ($tailors as $tailor)
                                            <option value="{{ $tailor->id }}"
                                                {{ $data->tailorId == $tailor->id ? 'selected' : '' }}>{{ $tailor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">درزی رقم</label>
                                {{-- new change --}}
                                <div class="col-sm-9" id="tailor-rates">
                                    {{-- @if (isset($currentTailorRate))
                                        {{ $currentTailorRate }}-{{ $optionName }}
                                    @endif --}}
                                </div>
                            </div>


                            <!--<div class="form-group form-row">-->
                            <!--    <label class="col-sm-3 col-form-label">ڈیزائن</label>-->
                            <!--    <div class="col-sm-9">-->
                            <!--        <select id="design-selected" class="form-control" name="design" required-->
                            <!--            dir="rtl">-->
                            <!--            <option value="0">ڈیزائن کو منتخب کریں</option>-->
                            <!--            @foreach ($data['design'] as $design)-->
                            <!--                <option value="{{ $data->Name . ' - ' . $design->id }}"-->
                            <!--                    {{ $data->Name == $data->Name ? 'selected' : '' }}>{{ $design->Name }}-->
                            <!--                </option>-->
                            <!--            @endforeach-->
                            <!--        </select>-->
                            <!--    </div>-->
                            <!--</div>-->
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">واپسی کی تاریخ</label>
                                <div class="col-sm-9">
                                    <input type="date" value="{{ $data->returnDate }}" class="form-control"
                                        name="returnDate" required>
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">درزی</label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="tailorId" required dir="rtl">
                                        <option value="0">درزی کو منتخب کریں</option>
                                        @foreach ($tailors as $tailor)
                                            <option value="{{ $tailor->id }}"
                                                {{ $tailor->id == $data->tailorId ? 'selected' : '' }}>{{ $tailor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">درزی رقم</label>
                                <div class="col-sm-9">
                                    <input type="number" value="{{ $data->tailor_price }}" class="form-control"
                                        name="tailor_price">
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <div class="col-md-3">
                                    <label style="font-size:23px; float:right">نوٹ</label>
                                </div>
                                <div class="col-md-9">
                                    <textarea rows="4" cols="" class="form-control" name="remarks" dir="rtl">{{ $data->remarks }}</textarea>
                                </div>
                            </div>
                            <div class="button-group mt-2">
                                <button type="submit" class="btn btn-blue mr-3">محفوظ کریں</button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </section>
@endsection
