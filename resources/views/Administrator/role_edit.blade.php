@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

        <div class="card col-sm-10 mx-auto">
            @include('inc.message')
            <div class="row">
                <div class="col-md-3">
                    <p class="text-right">
                        <a href="{{ route('administrator.roles-permi') }}" class="btn btn-primary"> تمام رول اور اجازتیں۔</a>
                    </p>
                </div>
            </div>
            <h2 class="mb-4 text-right">رول شامل کریں۔</h2>
            <form id="cc-form__addCustomerForm" class="add-customer-form"
                method="post" action="{{route('administrator.role.update',['id'=>$roles->id])}}">
                @csrf
                <div class="form-group row">
                    <div class="col-md-8">
                        <div class="form-row m-0">
                            <label class="col-sm-3 col-form-label f"><span class="english">رول کا نام</span> </label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="name" required value="{{$roles->name}}">
                                @error('name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
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
