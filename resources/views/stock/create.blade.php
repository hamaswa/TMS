@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">

            <div class="card col-sm-10 mx-auto">
                <h2 class="mb-4 text-right">کپڑا شامل کریں۔</h2>
                <form id="cc-form__addCustomerForm" action="{{ route('admin.cloth.store') }}" class="add-customer-form" method="post" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">کپڑے کی قسم</span> </label>
                                <div class="col-sm-9">
                                    <select name="cloth_type_id" class="form-control" required>
                                        <option value="">کپڑے کی قسم منتخب کریں۔</option>
                                        @foreach ($cloth_types as $cloth_type)
                                        <option value="{{ $cloth_type->id }}">{{ $cloth_type->name }}</option>
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
                                        <option value="{{ $cloth_brand->id }}">{{ $cloth_brand->name }}</option>
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
                                    <input type="text" class="form-control" name="color" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">لمبائی (میٹر) میں</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="length" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">ریٹ فی میٹر</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="price" required>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- new input box for cloth image --}}
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english"> کپڑے کی تصویر</span> </label>
                                <div class="col-sm-9">
                                    <input type="file" class="form-control" name="image" required>
                                    @error('image')
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

    <script>
        $(document).ready(function() {
            $("#c_name").on('change', function() {
                var selectedOption = $(this).val();
                console.log(selectedOption);
                $.ajax({
                    url : '/admin/getId/?name=' + selectedOption,
                    type : 'GET',
                    success:function(response){
                        const nmbr = response.data.id;
                        $("#cloth_id").val(nmbr);
                    },
                    error: function(xhr,status,error){
                        console.log("Error : " + error);
                    }
                });
            });
        });
    </script>
@endsection
