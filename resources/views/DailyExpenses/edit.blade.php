@extends('main')
@section('content')
    <style>
        span {
            font-size: 20px;
        }
    </style>
    <section class="main-content">
        <div class="container">

            <div class="card col-sm-10 mx-auto">
                @include('inc.message')
                <h1 class="h4 mb-4 text-right">روزانہ اخراجات تبدیل کریں</h1>
                <form id="cc-form__addCustomerForm" class="add-customer-form" method="post"
                    action="{{ route('admin.dailyexpense.update',['id'=>$data->id]) }}">
                    @csrf
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">اخراجات کا عنوان</span>
                                </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="name[]" value="{{$data->Expense_name}}">
                                    @error('name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">اخراجات کی رقم</span>
                                </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="rupee[]" value="{{$data->Expense_payment}}">
                                    @error('rupee')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="clonedFields"></div>

                    <div class="form-group col-md-8 mx-auto row">
                        <div class="button-group">
                            <button type="submit" class="btn btn-blue mr-3">محفوظ کریں</button>
                        </div>
                        <div class="button-group">
                            <button type="button" class="btn btn-blue mr-3" onclick="cloneFields()">مزید شامل
                                کریں۔</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
<script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
<script>
    function cloneFields() {
        var originalFields = document.querySelectorAll('.form-group');
        var clonedFields = originalFields[0].cloneNode(true); // Cloning the first set of fields
        clearInputValues(clonedFields);
        var clonedFields2 = originalFields[1].cloneNode(true); // Cloning the second set of fields
        clearInputValues(clonedFields2);
        document.getElementById('clonedFields').appendChild(clonedFields);
        document.getElementById('clonedFields').appendChild(clonedFields2);
    }

    function clearInputValues(element) {
        // Select all input elements within the provided element
        var inputs = element.querySelectorAll('input');

        // Loop through each input element and clear its value
        inputs.forEach(function(input) {
            input.value = '';
        });
    }
</script>
