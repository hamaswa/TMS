@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

        <div class="card col-sm-10 mx-auto">
            <h2 class="mb-4 text-right">   کپڑے کی قسم کو اپ ڈیٹ کریں   </h2>
        <form id="cc-form__addCustomerForm" action="{{ route('admin.clothtype.update',$cloth_type->id)}}" class="add-customer-form"
            method="post">
            @csrf
            @method('put')

            <div class="form-group row">
                <div class="col-md-8">
                    <div class="form-row m-0">
                        <label class="col-sm-3 col-form-label f"><span class="english">نام</span> </label>
                        <div class="col-sm-9">
                            <input type="text" value="{{ old('name') ?? $cloth_type->name }}" class="form-control" name="name" required>
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