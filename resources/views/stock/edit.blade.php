@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">

            <div class="card col-sm-10 mx-auto">
                <h2 class="mb-4 text-right">کپڑا اپ ڈیٹ کریں۔</h2>
                <form id="cc-form__addCustomerForm" action="{{ route('admin.cloth.update',$cloth->id) }}" class="add-customer-form"
                    method="post">
                    @csrf
                    @method('put')

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">کپڑے کی قسم</span> </label>
                                <div class="col-sm-9">
                                    <select name="cloth_type_id" class="form-control" required>
                                        <option value="">کپڑے کی قسم منتخب کریں۔</option>
                                        @foreach ($cloth_types as $cloth_type)
                                        <option {{ $cloth_type->id==$cloth->cloth_type_id?'selected':'' }} value="{{ $cloth_type->id }}">{{ $cloth_type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">کپڑے کی کمپنی</span> </label>
                                <div class="col-sm-9">
                                    <select name="cloth_brand_id" class="form-control" required>
                                        <option value="">کپڑے کی کمپنی منتخب کریں۔</option>
                                        @foreach ($cloth_brands as $cloth_brand)
                                        <option {{ $cloth_brand->id==$cloth->cloth_brand_id?'selected':'' }} value="{{ $cloth_brand->id }}">{{ $cloth_brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">رنگ</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" value="{{ old('color') ?? $cloth->color }}" class="form-control" name="color" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">لمبائی (میٹر) میں</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" value="{{ old('length') ?? $cloth->length }}" class="form-control" name="length" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">کل قیمت</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" value="{{ old('price') ?? $cloth->price }}" class="form-control" name="price" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-8 mx-auto row">
                        <div class="button-group">
                            <button type="submit" class="btn btn-blue mr-3">محفوظ کریں</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
