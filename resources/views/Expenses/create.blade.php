@extends('main')
@section('content')
<style>
span{
    font-size: 20px;
}
</style>
    <section class="main-content">
        <div class="container">

            <div class="card col-sm-10 mx-auto">
                @include('inc.message')
                <h1 class="h4 mb-4 text-right">اخراجات شامل کریں</h1>
                <form id="cc-form__addCustomerForm" class="add-customer-form"
                    method="post" action="{{route('admin.expense.insert')}}">
                    @csrf
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">ماہانہ کرایہ</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="rent">
                                    @error('rent')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">ماہانہ بل</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="bill" >
                                    @error('bill')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">ملازمین کی تعداد</span></label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="num_workers" name="num_workers" >
                                    @error('num_workers')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="workers-container"></div>


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
<script src="{{asset('public/assets/js/jquery-3.5.1.min.js')}}"></script>
<script>
    $(document).ready(function() {
    // Handle input event for the number of workers input
    $('#num_workers').on('input', function() {
        try {
            var numWorkers = $(this).val();
            var workersContainer = $('#workers-container');

            // Clear previous input fields
            workersContainer.empty();

            // Create input fields for each worker
            for (var i = 1; i <= numWorkers; i++) {
                var workerNameInput = '<input type="text" class="form-control" name="worker_name_' + i + '" placeholder="Worker ' + i + ' Name" >';
                var workerSalaryInput = '<input type="text" class="form-control" name="worker_salary_' + i + '" placeholder="Worker ' + i + ' Salary" >';

                // Append input fields to the container
                workersContainer.append('<div class="form-group row">' +
                    '<div class="col-md-8">' +
                    '<div class="form-row m-0">' +
                    '<label class="col-sm-3 col-form-label f"><span class="english">ملازم کا نام</span></label>' +
                    '<div class="col-sm-9">' +
                    workerNameInput +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="form-group row">' +
                    '<div class="col-md-8">' +
                    '<div class="form-row m-0">' +
                    '<label class="col-sm-3 col-form-label f"><span class="english">ملازم تنخواہ</span></label>' +
                    '<div class="col-sm-9">' +
                    workerSalaryInput +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>');
            }
        } catch (error) {
            // Log errors to the console
            console.error('Error:', error);
        }
    });
});

</script>

