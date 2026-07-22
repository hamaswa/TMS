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
                <h2 class="mb-4 text-right">اخراجات شامل کریں۔</h2>
                <form id="cc-form__addCustomerForm" class="add-customer-form" method="post"
                    action="{{ route('admin.dailyexpense.insert') }}">
                    @csrf
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">اخراجات کا عنوان</span>
                                </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="name[]">
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
                                    <input type="text" class="form-control" name="rupee[]">
                                    @error('rupee')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="clonedFieldsContainer"></div>

                    <div class="form-group col-md-8 mx-auto row">
                        <div class="button-group">
                            <button type="submit" class="btn btn-blue mr-3">محفوظ کریں</button>
                        </div>
                        <div class="button-group">
                            <button id="cloneButton" type="button" class="btn btn-blue mr-3">مزید شامل کریں</button>
                        </div>
                        <div class="button-group">
                            <button id="cancelButton" type="button" class="btn btn-blue mr-3">منسوخ کریں</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
<script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to clone fields
        function cloneFields() {
            var originalFields = document.querySelectorAll('.form-group');
            var clonedFields = originalFields[0].cloneNode(true); // Cloning the first set of fields
            clearInputValues(clonedFields);
            var clonedFields2 = originalFields[1].cloneNode(true); // Cloning the second set of fields
            clearInputValues(clonedFields2);
            document.getElementById('clonedFieldsContainer').appendChild(clonedFields);
            document.getElementById('clonedFieldsContainer').appendChild(clonedFields2);
        }

        // Function to remove the last cloned fields
        function removeLastClonedFields() {
            var container = document.getElementById('clonedFieldsContainer');
            var clonedFields = container.querySelectorAll('.form-group');
            var numClonedFields = clonedFields.length;

            // Check if there are cloned fields to remove
            if (numClonedFields >= 2) {
                // Remove the last set of cloned fields
                container.removeChild(clonedFields[numClonedFields - 1]);
                container.removeChild(clonedFields[numClonedFields - 2]);
            } else if (numClonedFields === 1) {
                // If there's only one set of cloned fields, remove it
                container.removeChild(clonedFields[0]);
            } else {
                console.log("No cloned fields to remove.");
            }
        }

        // Function to clear input values in the cloned fields
        function clearInputValues(element) {
            var inputs = element.querySelectorAll('input');
            inputs.forEach(function(input) {
                input.value = '';
            });
        }

        // Add event listener to the clone button
        var cloneButton = document.getElementById('cloneButton');
        if (cloneButton) {
            cloneButton.addEventListener('click', cloneFields);
        } else {
            console.error("Clone button not found in the DOM.");
        }

        // Add event listener to the cancel button
        var cancelButton = document.getElementById('cancelButton');
        if (cancelButton) {
            cancelButton.addEventListener('click', removeLastClonedFields);
        } else {
            console.error("Cancel button not found in the DOM.");
        }
    });
</script>
