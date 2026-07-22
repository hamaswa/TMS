@extends('main')
@section('content')
<section class="main-content">
    <div class="container">

        <div class="card col-sm-10 mx-auto">
            @include('inc.message')
            <div class="row">
                <div class="col-md-3">
                </div>
            </div>
            <h2 class="mb-4 text-right">پیغام بھیجیں</h2>
            <form id="cc-form__addCustomerForm" class="add-customer-form" method="post" action="{{ route('administrator.send') }}">
                @csrf
                <input type="hidden" value="{{ $id }}" name="id" />

                <div class="form-group row">
                    <label class="col-sm-3 col-form-label"><span class="english">عنوان</span></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="title" required>
                        @error('title')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-form-label"><span>پیغام</span></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="body" required>
                        @error('body')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group col-md-8 mx-auto row">
                    <div class="button-group">
                        <button type="submit" class="btn btn-blue mr-3">پیغام بھیجیں</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

@endsection
