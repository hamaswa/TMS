@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

        <div class="card">
            <h2 class="mb-4 text-right">نیا گاہک شامل کریں</h2>
        <form id="cc-form__addCustomerForm" action="{{ url('admin/Customers')}}" class="add-customer-form"
            method="post">
            @csrf

            <div class="form-group row">
                <div class="col-md-6">
                    <div class="form-row m-0">
                        <label class="col-sm-3 col-form-label f"><span class="english">نام</span> </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="name" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-row m-0">
                        <label class="col-sm-2 col-form-label f"><span class="english">رابطہ</span> </label>
                        <div class="col-sm-10">
                            <input type="number" class="form-control" name="contact" required>
                        </div>
                    </div>
                </div>
            </div>
            <h5 class="mb-4 text-right">کپڑوں کی سائز</h5>
            <div class="form-group row">

                <div class="col-md-6">
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">لمبائی</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="length" required>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">بازو</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="arms" required>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">تیرا</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="teraa" required>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">سینا چورائی</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="senaChorai" required>
                        </div>
                    </div>

                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">دامن چوڑائی</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="damanchorai" required>
                        </div>
                    </div>

                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">شلوار</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="shalwar" required>
                        </div>
                    </div>

                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">پنچا</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="pancha" required>
                        </div>
                    </div>

                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">شلوار گھیر</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="shalwarGheer" required>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">مونڈا</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="monda" required>
                        </div>
                    </div>
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">چوتا</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="chuta" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    @foreach($data['optionTypes'] as $type)
                    <div class="form-group form-row">
                        <label class="col-sm-3 col-form-label f">{{$type->otn}}</label>
                        <div class="col-sm-9">
                            @php
                            $options = DB::table('options')->where('option_id',$type->option_id)->where('user_id',
                            Auth::user()->id)->get();
                            @endphp
                            <select class="form-control" name="{{$type->slug}}" required>
                                <option value="0">{{$type->otn}} منتخب کریں</option>
                                @foreach($options as $option)
                                <option value="{{$option->id.' - '.$option->Name}}">{{$option->Name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endforeach
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label f">نوٹ</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="note" id="" cols="30" rows="10"></textarea>
                            </div>
                        </div>

                </div>
            </div>
            <div class="form-group row" dir="rtl">
                <div class="col-md-6 ml-auto">
                    <label for="mobile_pin">موبائل لاگ اِن پن</label>
                    <input id="mobile_pin" type="text" inputmode="numeric" pattern="[0-9]{6}"
                        maxlength="6" autocomplete="new-password" class="form-control"
                        name="mobile_pin" value="{{ old('mobile_pin') }}" placeholder="6 ہندسوں کا پن">
                    <small class="form-text text-muted">خالی چھوڑنے پر محفوظ پن خود بن جائے گا اور صرف ایک بار دکھایا جائے گا۔</small>
                    @error('mobile_pin')<div class="text-danger">پن لازماً 6 ہندسوں کا ہونا چاہیے۔</div>@enderror
                </div>
            </div>
            <div class="button-group">
                <button type="submit" class="btn btn-blue mr-3">محفوظ کریں</button>

            </div>
        </form>
        </div>
    </div>
</section>

@endsection
