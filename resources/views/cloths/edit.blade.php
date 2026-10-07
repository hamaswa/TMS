@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">

            <div class="card col-sm-10 mx-auto">
                <h2 class="mb-4 text-right">کپڑا اپ ڈیٹ کریں۔</h2>
                <form id="cc-form__addCustomerForm" action="{{ route('admin.cloth.update', $data['cloth']->id) }}"
                    class="add-customer-form" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')

                    <div class="form-group row">
                        <div class="col-md-8"><div class="form-row m-0">
                            <label class="col-sm-3 col-form-label f">سیٹ کا نام</label>
                            <div class="col-sm-9"><input type="text" value="{{ old('name', $data['cloth']->name) }}" class="form-control" name="name" maxlength="100" required><small class="text-muted">مثلاً Dilbar، Waqar یا Premium Gold</small></div>
                        </div></div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">کپڑے کی قسم</span> </label>
                                <div class="col-sm-9">
                                    <select name="cloth_type_id" class="form-control" required>
                                        <option value="">کپڑے کی قسم منتخب کریں۔</option>
                                        @foreach ($cloth_types as $cloth_type)
                                            <option {{ $cloth_type->id == $data['cloth']->cloth_type_id ? 'selected' : '' }}
                                                value="{{ $cloth_type->id }}">{{ $cloth_type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <label class="font-weight-bold d-block">فروخت کی دستیابی</label>
                            <div class="border rounded p-3 mb-2">
                                <label class="d-block mb-2"><input type="radio" name="online_availability" value="pos_only" @checked(old('online_availability', $onlineAvailability) === 'pos_only') @disabled(! $canConfigureOnline)> صرف POS / دکان کی فروخت</label>
                                <label class="d-block mb-0 text-success"><input type="radio" name="online_availability" value="online_order" @checked(old('online_availability', $onlineAvailability) === 'online_order') @disabled(! $canConfigureOnline)> POS اور عوامی دکان پر آن لائن آرڈر</label>
                                @unless($canConfigureOnline)<input type="hidden" name="online_availability" value="{{ $onlineAvailability }}">@endunless
                            </div>
                            @if(!$canConfigureOnline)<small class="text-muted">آن لائن دکان بنائیں، کپڑے کا شعبہ فعال کریں، یا مجاز صارف سے یہ ترتیب تبدیل کرائیں۔</small>@endif
                            @error('online_availability')<div class="text-danger">{{ $message }}</div>@enderror
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
                                            <option {{ $cloth_brand->id == $data['cloth']->cloth_brand_id ? 'selected' : '' }}
                                                value="{{ $cloth_brand->id }}">{{ $cloth_brand->name }}</option>
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
                                    @if($data['cloth']->usesDisplayOnlyColors())
                                        <input type="hidden" name="colors" value="{{ $data['specificColor']->color }}">
                                        <input type="text" class="form-control" id="colors" name="display_colors" value="{{ old('display_colors', collect($data['cloth']->selectableColorNames())->join('، ')) }}" required>
                                        <small class="text-muted">یہ رنگ گاہک منتخب کر سکے گا، مگر مقدار مجموعی اسٹاک سے کم ہوگی۔</small>
                                    @elseif($data['cloth']->tracksColors())
                                        <input type="text" class="form-control" id="colors" name="colors" value="{{ $data['specificColor']->color }}" required readonly>
                                        <small class="text-muted">اس رنگ کا اسٹاک الگ ٹریک ہو رہا ہے۔</small>
                                    @else
                                        <input type="hidden" id="colors" name="colors" value="{{ $data['specificColor']->color }}">
                                        <input type="text" class="form-control" value="رنگ لاگو نہیں" disabled>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">لمبائی (میٹر) میں</span>
                                </label>
                                <div class="col-sm-9">
                                    <input type="text" value="{{ old('length') ?? $data['specificColor']->length }}" class="form-control"
                                        name="length" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">قیمت خرید</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" value="{{ old('price') ?? $data['cloth']->price }}" class="form-control"
                                        name="price" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- sale price for customers side --}}
                    <div class="form-group row">
                        <div class="col-md-8"><div class="form-row m-0">
                            <label class="col-sm-3 col-form-label f">فروخت کے ریٹ کی بنیاد</label>
                            <div class="col-sm-9"><select id="sale_price_basis" name="sale_price_basis" class="form-control" required><option value="per_meter" @selected(old('sale_price_basis', $data['cloth']->sale_price_basis ?? 'per_meter') === 'per_meter')>فی میٹر</option><option value="per_suit" @selected(old('sale_price_basis', $data['cloth']->sale_price_basis) === 'per_suit')>فی سوٹ</option></select></div>
                        </div></div>
                    </div>
                    <div id="suitSalePriceField" class="form-group row">
                        <div class="col-md-8"><div class="form-row m-0">
                            <label class="col-sm-3 col-form-label f">فی سوٹ قیمت فروخت</label>
                            <div class="col-sm-9"><input id="suit_sale_price" type="number" min="0" step="0.01" value="{{ old('suit_sale_price', $data['cloth']->suit_sale_price) }}" class="form-control" name="suit_sale_price"></div>
                        </div></div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-8"><div class="form-row m-0">
                            <label class="col-sm-3 col-form-label f">ڈیفالٹ سوٹ لمبائی (میٹر)</label>
                            <div class="col-sm-9"><input id="default_sale_length" type="number" min="0.01" step="0.01" value="{{ old('default_sale_length', $data['cloth']->default_sale_length) }}" class="form-control" name="default_sale_length"><small class="text-muted">QR اسکین پر خود بھرے گی؛ فروخت کے وقت تبدیل کی جا سکتی ہے۔</small></div>
                        </div></div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">فی میٹر قیمت فروخت</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" value="{{ old('sale_price') ?? $data['cloth']->sale_price }}"
                                        class="form-control" name="sale_price" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="image-uploads">

                    </div>
                    <button type="button" id="add-more-images" class="btn btn-secondary">تصاویر شامل کریں</button>

                    <div id="video-uploads">
                    </div>

                    <button type="button" id="add-more-videos" class="btn btn-secondary mt-2">ویڈیوز شامل کریں</button>


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
        const salePriceBasis = document.getElementById('sale_price_basis');
        const defaultSaleLength = document.getElementById('default_sale_length');
        const suitSalePriceField = document.getElementById('suitSalePriceField');
        const suitSalePrice = document.getElementById('suit_sale_price');
        function syncSalePricing() {
            const perSuit = salePriceBasis.value === 'per_suit';
            defaultSaleLength.required = perSuit;
            suitSalePrice.required = perSuit;
            suitSalePriceField.hidden = !perSuit;
        }
        salePriceBasis.addEventListener('change', syncSalePricing);
        syncSalePricing();

        // Add More Images
        document.getElementById('add-more-images').addEventListener('click', function() {
            var colors = document.getElementById('colors').value.split(',');
            var colorOptions = colors.map(function(color) {
                return `<option value="${color.trim()}">${color.trim()}</option>`;
            }).join('');

            var newImageUpload = document.createElement('div');
            newImageUpload.className = "form-group row image-upload-block";
            newImageUpload.style = "border: 1px solid #ccc; padding: 10px; margin-top: 5px;";

            newImageUpload.innerHTML = `
        <div class="col-md-8">
            <div class="form-row m-0">
                <label class="col-sm-3 col-form-label f"><span class="english">کپڑے کی تصویر</span></label>
                <div class="col-sm-9">
                    <input type="file" class="form-control" name="images[]" style="margin-bottom: 5px;">
                    <select name="image_colors[]" class="form-control" style="margin-bottom: 5px;">
                        <option value="">رنگ منتخب کریں</option>
                        ${colorOptions}
                    </select>
                    <button type="button" class="btn btn-danger btn-sm remove-image-upload">ہٹائیں</button>
                </div>
            </div>
        </div>
    `;

            document.getElementById('image-uploads').appendChild(newImageUpload);

            newImageUpload.querySelector('.remove-image-upload').addEventListener('click', function() {
                newImageUpload.remove();
            });
        });



        // Add More Videos
        document.getElementById('add-more-videos').addEventListener('click', function() {
            var colors = document.getElementById('colors').value.split(',');
            var colorOptions = colors.map(function(color) {
                return `<option value="${color.trim()}">${color.trim()}</option>`;
            }).join('');

            var newVideoUpload = document.createElement('div');
            newVideoUpload.className = "form-group row video-upload-block";
            newVideoUpload.style = "border: 1px solid #ccc; padding: 10px; margin-top: 5px;";

            newVideoUpload.innerHTML = `
        <div class="col-md-8">
            <div class="form-row m-0">
                <label class="col-sm-3 col-form-label f"><span class="english">کپڑے کی ویڈیو</span></label>
                <div class="col-sm-9">
                    <input type="file" class="form-control" name="videos[]" style="margin-bottom: 5px;">
                    <select name="video_colors[]" class="form-control" style="margin-bottom: 5px;">
                        <option value="">رنگ منتخب کریں</option>
                        ${colorOptions}
                    </select>
                    <button type="button" class="btn btn-danger btn-sm remove-video-upload">ہٹائیں</button>
                </div>
            </div>
        </div>
    `;

            document.getElementById('video-uploads').appendChild(newVideoUpload);

            newVideoUpload.querySelector('.remove-video-upload').addEventListener('click', function() {
                newVideoUpload.remove();
            });
        });

        // Add More Lengths
        document.getElementById('add-more-length').addEventListener('click', function() {
            var colors = document.getElementById('colors').value.split(',');
            var colorOptions = colors.map(function(color) {
                return `<option value="${color.trim()}">${color.trim()}</option>`;
            }).join('');

            var newLengthUpload = document.createElement('div');
            newLengthUpload.className = "form-group row length-upload-block";
            newLengthUpload.style = "border: 1px solid #ccc; padding: 10px; margin-top: 5px;";

            newLengthUpload.innerHTML = `
        <div class="col-md-8">
            <div class="form-row m-0">
                <label class="col-sm-3 col-form-label f"><span class="english">لمبائی (میٹر) میں</span> </label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" name="length[]" required style="margin-bottom: 5px;">
                    <select name="image_colors[]" class="form-control" required style="margin-bottom: 5px;">
                        <option value="">رنگ منتخب کریں</option>
                        ${colorOptions}
                    </select>
                    <button type="button" class="btn btn-danger btn-sm remove-length-upload">ہٹائیں</button>
                </div>
            </div>
        </div>
    `;

            document.getElementById('length-uploads').appendChild(newLengthUpload);

            newLengthUpload.querySelector('.remove-length-upload').addEventListener('click', function() {
                newLengthUpload.remove();
            });
        });
    </script>
@endsection
