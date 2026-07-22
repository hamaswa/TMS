@extends('main')

@section('content')
    <section class="main-content">
        <div class="container" id="formContainer">
            <div class="card col-sm-10 mx-auto">
                @include('inc.message')
                <h2 class="mb-4 text-right">اسٹاک فروخت کریں۔</h2>
                <form action="{{ route('admin.sellStock') }}" method="post" id="sellStockForm">
                    @csrf
                    {{-- .form-group.row .form-control {
                         /* Adjust the width as needed */
                    } --}}
                    {{-- <input type="hidden" name="id[]" id="id" > --}}
                    <div class="form-group stock-sell col-md-8 mx-auto row" id="stockDataContainer">
                        <!-- This container will hold dynamically added sections -->
                        <div class="stock-data">
                            <div class="form-group row">

                                <label class="col-sm-3 col-form-label f" for="c_name"><span class="english">کسٹمر کا
                                        نام</span></label>
                                <div class="col-sm-9">
                                    <select name="c_name" required class="form-control custom-select" style="width: 100%;"
                                        id="c_name">
                                        <option value="" disabled selected>گاہک منتخب کریں۔</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->name . '|' . $customer->id }}">
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('c_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-sm-3">
                                    <label class="col-form-label f" for="phone"><span class="english">موبائل
                                            نمبر</span></label>
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" class="form-control" style="width: 120%;" name="phone" required
                                        id="nmbr">
                                    @error('phone')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-3">
                                    <label class="col-form-label f" for="brand_name"><span class="english">برانڈ کا
                                            نام</span></label>
                                </div>
                                <div class="col-sm-3">
                                    <select class="form-control" style="width: 120%;" name="brand_name[]" required
                                        id="brand_name">
                                        <option value="" disabled selected>برانڈ منتخب کریں</option>
                                        @foreach ($cloths->unique('cloth_brand_id') as $cloth)
                                            <option value="{{ $cloth->cloth_brand_id }}"
                                                data-cloth-id="{{ $cloth->id }}">
                                                {{ $cloth->brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('brand_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>

                            {{-- new change --}}

                            <div class="form-group row">
                                <div class="col-sm-3">
                                    <label class="col-form-label f" for="cloth_type"><span class="english">کپڑے کی قسم منتخب
                                            کریں۔ </span></label>
                                </div>
                                <div class="col-sm-3">
                                    <select class="form-control" style="width: 120%;" name="cloth_type[]" required
                                        id="cloth_type">
                                    </select>
                                    @error('cloth_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-3">
                                    <label class="col-form-label f" for="color"><span class="english">کپڑے کا رنگ منتخب
                                            کریں</span></label>
                                </div>
                                <div class="col-sm-3">
                                    <select class="form-control" style="width: 120%;" name="color[]" step="2" required
                                        id="color">
                                        <option value="" disabled selected>کپڑے کا رنگ منتخب کریں</option>
                                        @foreach ($cloths as $cloth)
                                            @foreach ($cloth->colors as $color)
                                                <option value="{{ $color->color }}">
                                                    {{ $color->color }}
                                                </option>
                                            @endforeach
                                        @endforeach

                                    </select>
                                    @error('color')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-sm-3">
                                    <label class="col-form-label f" for="per_meter"><span class="english">ریٹ فی
                                            میٹر</span></label>
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" class="form-control" style="width: 120%;" name="per_meter[]"
                                        step="1" required id="meter">
                                    @error('per_meter')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-3">
                                    <label class="col-form-label f" for="clothes_rack"><span class="english">فیبرک
                                            رول</span></label>
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" class="form-control" style="width: 120%;" name="clothes_rack[]"
                                        required step="1">
                                    @error('clothes_rack')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-sm-3">
                                    <label class="col-form-label f" for="length"><span
                                            class="english">گزانہ</span></label>
                                </div>
                                <div class="col-sm-3">
                                    <input type="number" class="form-control" style="width: 120%;" name="length[]"
                                        required id="length" step="0.1">
                                    @error('length')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container">
                        <div class="row mr-5">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between">
                                    <div class="form-group">
                                        <label for="total">کل رقم</label>
                                            <input type="number" name="total" id="total" style="width: 180px;" step="0.1">
                                    </div>

                                    <div class="form-group mx-2">
                                        <label for="payment">رقم موصول</label>
                                        <div class="button-group">
                                            <input type="number" name="payment" id="payment" style="width: 180px;" step="0.1" required>
                                        </div>
                                    </div>

                                    <div class="form-group ml-2">
                                        <label for="remain">دائیگی باقی ہے</label>
                                        <div class="button-group">
                                            <input type="number" name="remain" id="remain" readonly style="width: 180px;" step="0.1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="form-group col-md-8 mx-auto row">
                        <div class="button-group">
                            <button type="button" class="btn btn-secondary mt-3" style="width: 150%;"
                                id="addMoreBtn">Add
                                More</button>
                        </div>
                    </div>

                    <div class="form-group col-md-8 mx-auto row">
                        <div class="button-group">
                            <button type="submit" style="width: 150%;" class="btn btn-primary">فروخت +</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
        // $(document).ready(function () {
        //     $('#addMoreBtn').on('click', function () {
        //         console.log('Button Clicked');
        //         var formCount = $('.stock-data').length + 1;
        //         var newSection = $('.stock-data:first').clone();


        //         newSection.find('select').each(function () {
        //             var oldName = $(this).attr('name');
        //             var newName = oldName.replace(/\[\d+\]/, '[' + formCount + ']');
        //             $(this).attr('name', newName);
        //         });


        //         newSection.find(':input').each(function () {
        //             var oldName = $(this).attr('name');
        //             var newName = oldName.replace(/\[\d+\]/, '[' + formCount + ']');
        //             $(this).attr('name', newName).val('');
        //         });

        //         $('#stockDataContainer').append(newSection);
        //     });
        // });
        $(document).ready(function() {

            var totalProduct = 0;
            var timer;
            var sectionPrices = []; // Array to store price values for each section
            var sectionLengths = []; // Array to store length values for each section
            var sectionProducts = []; // Array to store product values for each section

            $('#addMoreBtn').on('click', function() {
    console.log('Button Clicked');
    var formCount = $('.stock-data').length + 1;
    var newSection = $('.stock-data:first').clone();

    // Keep customer name and phone fields' values
    newSection.find('#c_name').val($('#c_name').val());  // Preserve selected customer
    newSection.find('#nmbr').val($('#nmbr').val());  // Preserve entered phone number

    // Clear other fields in the cloned section
    newSection.find('#brand_name').val(''); // Clear selected brand
    newSection.find('#cloth_type').empty(); // Clear cloth type options
    newSection.find('#color').val(''); // Clear selected color
    newSection.find('#meter').val(''); // Clear meter input
    newSection.find('[name="clothes_rack[]"]').val(''); // Clear rack input
    newSection.find('#length').val(''); // Clear length input

    // Update name attributes for each input/select field to keep unique indices
    newSection.find('input, select').each(function() {
        var oldName = $(this).attr('name');
        if (oldName) {
            var newName = oldName.replace(/\[\d+\]/, '[' + formCount + ']');
            $(this).attr('name', newName);
        }
    });

    // Add Remove button
    newSection.append('<button type="button" class="btn btn-danger btn-sm remove-section">Remove</button>');

    // Append the new section to the container
    $('#stockDataContainer').append(newSection);

    // Reattach the change event listener for the brand select in the cloned section
    newSection.find("#brand_name").on('change', function() {
        var brand_id = $(this).val();

        $.ajax({
            url: '/admin/getType/?id=' + brand_id,
            type: 'GET',
            success: function(response) {
                console.log(response.data);
                var clothTypeSelect = newSection.find("#cloth_type");
                clothTypeSelect.empty(); // Clear previous options
                // Populate select box with cloth types
                $.each(response.data, function(index, clothType) {
                    clothTypeSelect.append('<option value="' +
                        clothType.cloth_type_id + '">' +
                        clothType.type.name + '</option>');
                });
            },
            error: function(xhr, status, error) {
                console.log("Error : " + error);
            }
        });
    });

    // Trigger input event manually for all existing sections
    $('.stock-data').trigger('input');

    // Attach event listener for the Remove button
    newSection.find('.remove-section').on('click', function() {
        newSection.remove(); // Remove the specific section
    });
});



            // Event listener for input fields to update total
            $(document).on('input', '.stock-data [name="length[]"], .stock-data [name="per_meter[]"]', function() {
                clearTimeout(timer); // Clear previous timer
                timer = setTimeout(calculateProduct, 1000); // Set new timer for 1 second delay
            });

            function calculateProduct() {
                sectionPrices = [];
                sectionLengths = [];
                sectionProducts = [];
                totalProduct = 0;

                $('.stock-data').each(function() {
                    var length = parseFloat($(this).find('[name="length[]"]').val());
                    var perMeter = parseFloat($(this).find('[name="per_meter[]"]').val());
                    if (!isNaN(length) && !isNaN(perMeter)) {
                        var product = length * perMeter;
                        sectionPrices.push(perMeter);
                        sectionLengths.push(length);
                        sectionProducts.push(product);
                        totalProduct += product; // Calculate product for the current section
                    }
                });

                // Output for each section
                for (var i = 0; i < sectionPrices.length; i++) {
                    console.log('Section ' + (i + 1) + ':');
                    console.log('Price: ' + sectionPrices[i]);
                    console.log('Length: ' + sectionLengths[i]);
                    console.log('Product: ' + sectionProducts[i]);
                }

                // Output total product
                console.log('Total Product: ' + totalProduct);
                $("#total").val(totalProduct);
            }



            $("#c_name").on('change', function() {
                var name = $(this).val();
                // console.log(name);
                var parts = name.split('|');
                var name = parts[0];
                var id = parts[1];
                // console.log(id);

                $.ajax({
                    url: '/admin/getNmbr/?id=' + id,
                    type: 'GET',
                    success: function(response) {
                        const nmbr = response.data.phone_number1;
                        const id = response.data.id;
                        $("#nmbr").val(nmbr);
                        $("#id").val(id);
                    },
                    error: function(xhr, status, error) {
                        console.log("Error : " + error);
                    }
                });
            });

            $("#payment").on('input', function() {
                var payment = $(this).val();
                // console.log(payment);
                var total = $('#total').val();

                var remain = total - payment;
                $('#remain').val(remain);
            });


            //to automatically get the cloth type for brand
            $("#brand_name").on('change', function() {
                var brand_id = $(this).val();
                // console.log(brand_id);

                $.ajax({
                    url: '/admin/getType/?id=' + brand_id,
                    type: 'GET',
                    success: function(response) {
                        console.log(response.data);
                        $("#cloth_type").empty(); // Clear previous options
                        // Populate select box with cloth types
                        $.each(response.data, function(index, clothType) {
                            $("#cloth_type").append('<option value="' + clothType
                                .cloth_type_id +
                                '">' + clothType.type.name + '</option>');
                        });
                    },
                    error: function(xhr, status, error) {
                        console.log("Error : " + error);
                    }
                });
            });

        });
    </script>
@endsection
