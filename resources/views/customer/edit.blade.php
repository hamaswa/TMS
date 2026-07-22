@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

        <div class="card">
            <h2 class="mb-4 text-right">گاہک</h2>
        <form id="cc-form__addCustomerForm" action="{{ url('admin/Customers',$customer->id)}}" class="add-customer-form"
            method="post">
            @csrf
            {{ method_field('PUT')}}
            <div class="form-group row">
                <div class="col-md-6">
                    <div class="form-row m-0">
                        <label class="col-sm-2 col-form-label"><span class="english">رابطہ:</span> </label>
                        <div class="col-sm-10">
                            <input type="number" class="form-control" name="contact"
                                value="{{$customer->phone_number1}}" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-row m-0">
                        <label class="col-sm-3 col-form-label"><span class="english">نام:</span> </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="name" value="{{$customer->name}}" required>
                        </div>
                    </div>
                </div>

            </div>
            <h5 class=" mb-4 text-right">کپڑوں کی سائز</h5>
            <div class="form-group row">
                <div class="col-md-6">
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">لمبائی:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="length" value="{{$customer->length}}"
                                required>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">بازو:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="arms" value="{{$customer->arms}}" required>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">تیرا:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="teraa" value="{{$customer->teraa}}" required>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">سینا چورائی:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="senaChorai" value="{{$customer->senaChorai}}"
                                required>
                        </div>
                    </div>

                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">دامن چوڑائی:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="damanchorai"
                                value="{{$customer->damanchorai}}" required>
                        </div>
                    </div>

                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">شلوار:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="shalwar" value="{{$customer->shalwar}}"
                                required>
                        </div>
                    </div>

                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">پنچا:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="pancha" value="{{$customer->pancha}}"
                                required>
                        </div>
                    </div>

                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">شلوار گھیر:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="shalwarGheer"
                                value="{{$customer->shalwarGheer}}" required>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">مونڈا:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" required name="monda"
                                value="{{$customer->shoulder}}">
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">چوتا:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" required name="chuta"
                                value="{{$customer->Chuta}}">
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    @foreach($optionTypes as $type)
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label">{{$type->otn}}</label>
                        <div class="col-sm-9">
                            @php
                            $options = DB::table('options')->where('option_id',$type->option_id)->where('user_id',
                            Auth::user()->id)->get();
                            @endphp
                            <select class="form-control" name="{{ $type->slug }}" required>
    <option value="0">{{ $type->otn }} منتخب کریں</option>

    @php
        // FIX: Handle lowercase (daaman) and actual DB column (Daaman)
        $customerValue =
            trim($customer->{$type->type} ?? $customer->{ucfirst($type->type)} ?? '');
    @endphp

    @foreach($options as $option)
        <option
            value="{{ $option->id . ' - ' . $option->Name }}"
            {{ trim($option->Name) == $customerValue ? 'selected' : '' }}>
            {{ $option->Name }}
        </option>
    @endforeach
</select>


                        </div>
                    </div>
                    @endforeach
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">نوٹ</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" name="note" cols="30" rows="10">{{$customer->note}}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group row" dir="rtl">
                <div class="col-md-6 ml-auto">
                    <label for="mobile_pin">نیا موبائل لاگ اِن پن</label>
                    <input id="mobile_pin" type="text" inputmode="numeric" pattern="[0-9]{6}"
                        maxlength="6" autocomplete="new-password" class="form-control"
                        name="mobile_pin" placeholder="ری سیٹ کرنے کے لیے 6 ہندسے درج کریں">
                    <small class="form-text text-muted">خالی چھوڑنے سے موجودہ پن تبدیل نہیں ہوگا۔ نیا پن محفوظ کرنے پر پرانے موبائل سیشن بند ہو جائیں گے۔</small>
                    @error('mobile_pin')<div class="text-danger">پن لازماً 6 ہندسوں کا ہونا چاہیے۔</div>@enderror
                </div>
            </div>
            <div class="button-group">
                <!-- <a href="#" class="btn btn-blue mr-3">Save</a> -->
                <button type="submit" class="btn btn-warning mr-3">محفوظ کریں</button>
            </div>
        </form>
        </div>
    </div>
</section>

@endsection
