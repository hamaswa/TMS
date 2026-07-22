@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

        <div class="card col-sm-10 mx-auto">
            <h2 class="mb-4 text-right"> کپڑے کی کمپنی کو اپ ڈیٹ کریں۔</h2>
        <form id="cc-form__addCustomerForm" action="{{ route('admin.clothbrand.update',$cloth_brand->id)}}" class="add-customer-form"
            method="post" enctype="multipart/form-data">
            @csrf
            @method('put')

            <div class="form-group row">
                <div class="col-md-8 mb-2">
                    <div class="form-row m-0">
                        <label class="col-sm-3 col-form-label f"><span class="english">نام</span> </label>
                        <div class="col-sm-9">
                            <input type="text" value="{{ old('name') ?? $cloth_brand->name }}" class="form-control" name="name" required>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-row m-0">
                        <label class="col-sm-3 col-form-label f"><span class="english">برانڈ کی تصویر</span> </label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" name="file" required>
                        </div>
                    </div>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn btn-blue mr-3">محفوظ کریں</button>
                </div>
            </div>
        </form>
        </div>
    </div>
</section>

@endsection
