@include('layouts.waqar.header')

<style>
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        max-height: 500px;
        /* Adjust height for responsiveness */
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .card-body {
        transition: box-shadow 0.3s ease-in;
        cursor: pointer;
    }

    .card-body:hover {
        box-shadow: 0px 0px 10px 10px rgba(0, 0, 0, 0.5);
    }

    .product-image {
        overflow: hidden;
        border-radius: 10px 10px 0 0;
        transition: box-shadow 0.3s ease-in;
        width: 100%;
        height: 100%;
    }

    .product-image img {
        transition: transform 0.3s ease;
        width: 100%;
        height: 100%;
        cursor: pointer;
        position: relative;
    }

    .product-image:hover img {
        transform: scale(1.1);
    }

    .btn-primary,
    .btn-success {
        font-size: 1.1rem;
        font-weight: 500;
    }

    .input-group-prepend,
    .input-group-append {
        background-color: #f8f9fa;
        border-radius: 0.25rem;
    }

    .quantity {
        text-align: center;
    }

    footer {
        position: relative;
        top: 72px;
    }

    .img-div {
        width: 100px;
        height: 70px;
        cursor: pointer;
        position: relative;
    }

    .img-div img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .img-thumbnails {
        margin: 10px;
    }

    .thumbnail-img {
        width: 50px;
        height: auto;
        cursor: pointer;
        transition: 0.15s ease-in;
    }

    .thumbnail-img:hover {
        opacity: 0.7;
    }

    .color-options {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .color-box {
        width: 30px;
        height: 30px;
        border: 1px solid #000;
        cursor: pointer;
        border-radius: 50%;
    }

    .color-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: transparent;
        pointer-events: none;
    }

    #colorOverlay {
        opacity: 0;
    }

    .video-div {
        position: relative;
        width: 100px;
        height: 70px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .video-div video {
        width: 100%;
        height: 100%;
    }

    .play-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 20px;
    }

    .overlay-content {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .thumbnail-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0);
        /* Initial transparent */
        opacity: 0;
        /* Initial opacity */
        transition: opacity 0.3s ease;
        /* Smooth transition */
    }


    .close-icon {
        position: absolute;
        top: 5%;
        right: 5%;
        color: white;
        cursor: pointer;
        font-size: 2rem;
    }

    .video-container {
        position: relative;
        width: 80%;
        max-width: 900px;
        max-height: 80%;
    }

    .video-container video {
        width: 100%;
        height: 100%;
    }

    .swiper-container {
        position: relative;
        overflow: hidden;
    }

    .related {
        width: 100%;
    }

    .related img {
        margin-bottom: 10px;
        width: 100%;
        height: auto;
        transition: transform 0.2s ease-in-out;
    }

    .swiper-slide {
        width: 100%;
        /* Make slides responsive */
        max-width: 400px;
        margin-right: 15px;
    }

    .swiper-button-next,
    .swiper-button-prev {
        background-color: #fff;
        border-radius: 4px;
        color: #999;
        width: 50px;
        height: 50px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 9;
    }

    .swiper-button-next {
        right: 10px;
    }

    .swiper-button-prev {
        left: 10px;
    }

    .swiper-pagination {
        position: static;
        margin-top: 20px;
        text-align: center;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .w-100.d-flex {
            flex-direction: column;
            align-items: center;
        }

        .img-thumbnails {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 20px;
        }

        .img-div {
            margin: 2px;
        }

        .product-card {
            margin-bottom: 20px;
        }

        .product-image {
            height: auto;
        }

        .col-md-8,
        .col-md-4 {
            width: 100%;
            max-width: none;
        }

        .video-container {
            width: 90%;
            max-width: 100%;
        }

        .swiper-slide {
            width: 100%;
            max-width: none;
            margin: 0;
        }

        .swiper-button-next,
        .swiper-button-prev {
            background-color: #fff;
            border-radius: 4px;
            color: #999;
            width: 50px;
            height: 50px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 9;
        }
    }

    @media (min-width: 768px) {
        .w-100.d-flex {
            flex-direction: column;
            align-items: center;
        }

        .img-thumbnails {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 20px;
        }

        .img-div {
            margin: 2px;
        }

        .product-card {
            margin-bottom: 20px;
        }

        .col-md-8,
        .col-md-4 {
            width: 100%;
            max-width: none;
        }

        .video-container {
            position: relative;
            width: 80%;
            max-width: 700px;
            max-height: 80%;
        }

        .video-container video {
            width: 100%;
            height: 100%;
        }

        .swiper-slide {
            width: 100%;
            max-width: 380px;
            margin: 0;
        }

        .swiper-button-next,
        .swiper-button-prev {
            background-color: #fff;
            border-radius: 4px;
            color: #999;
            width: 50px;
            height: 50px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 9;
        }

        .product-card {
            margin-right: 0px;
        }

        .product-card .card-title {
            font-size: 16px;
        }
    }

    @media (min-width: 992px) {
        .w-100.d-flex {
            flex-direction: row;
            align-items: center;
        }

        .img-thumbnails {
            display: flex;
            flex-wrap: wrap;
            flex-direction: column;
            margin-bottom: 60px;
        }

        .img-div {
            margin: 2px;
        }

        .product-card {
            margin-bottom: 20px;
        }

        .product-image {
            width: 100%;
            height: 100%;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            /* object-fit: cover; */
        }

        .col-md-8,
        .col-md-4 {
            width: 100%;
            max-width: none;
        }

        .video-container {
            position: relative;
            width: 80%;
            max-width: 900px;
            max-height: 80%;
        }

        .video-container video {
            width: 100%;
            height: 100%;
        }

        .swiper-slide {
            width: 100%;
            max-width: 380px;
            margin: 0;
        }

        .swiper-button-next,
        .swiper-button-prev {
            background-color: #fff;
            border-radius: 4px;
            color: #999;
            width: 50px;
            height: 50px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 9;
        }

        .product-card {
            margin-right: 0px;
            height: 100%;
            max-height: 500px;
        }

        .product-card .card-title {
            font-size: 20px;
            margin-top: 20px;
        }
    }
</style>
<div class="w-100 d-flex justify-content-around py-5">
    <div class="overlay-content">
        <i class="fa fa-times close-icon"></i>
        <div class="video-container"></div> <!-- Container for the video -->
    </div>

    <!-- Thumbnails Section -->
<div class="img-thumbnails me-md-4">
    @foreach ($stocks->images as $image)
        <div class="img-div mb-2">
            <img src="{{ asset('public/storage/' . $image->images) }}" alt="Cloth Image" class="thumbnail-img"
            data-main="{{ asset('public/storage/' . $image->images) }}">
        </div>
    @endforeach

    @foreach ($stocks->videos as $video)
        <div class="video-div mb-2">
            <video class="video-thumbnail" src="{{ asset('public/storage/' . $video->video) }}" alt="" title="watch"></video>
            <i class="fa fa-play-circle play-icon"></i>
        </div>
    @endforeach
</div>



    <!-- Product Details Section -->
    <div class="d-flex flex-column flex-md-row">
        <div class="col-md-8 mb-4">
            <div class="card product-card">
                <div class="product-image">
                    @foreach ($stocks->images as $image)
                    <img id="mainImage" src="{{ asset('public/storage/' . $stocks->images->first()->images) }}" alt="Cloth Image">
                    @endforeach
                    {{-- <div id="colorOverlay" class="color-overlay"></div> --}}
                </div>
            </div>
            @php
                $cloth = \App\Models\Cloth::where('cloth_brand_id', $brand_id)
                    ->where('cloth_type_id', $type_id)
                    ->first(); // Assuming you retrieve a single cloth record

                $availableColors = json_decode($cloth->color);
            @endphp

            {{-- <div class="color-options mt-2 d-flex justify-content-center">
                <p><strong>Available Colors</strong></p>
                @foreach ($availableColors as $color)
                    <span class="color-box align-center" data-color="{{ $color }}"
                        style="background-color: {{ $color }}"></span>
                @endforeach
            </div> --}}
        </div>

        <div class="col-md-4">
            <div class="card product-card">
                <div class="card-body">
                    <h5 class="card-title"><strong>Brand Name:</strong> {{ $stocks->brand->name }}</h5>
                    <h5 class="card-title"><strong>Cloth Type:</strong> {{ $stocks->type->name }}</h5>
                    @php
                        $colour = $color;
                    @endphp
                    <h5 class="card-title"><strong>Color:</strong> {{ $color }}</h5>
                    <h5 class="card-title mb-4"><strong>Price:</strong> Rs {{ $stocks->sale_price }} per meter</h5>
                    <form method="POST" class="mb-4">
                        @csrf
                        <div class="d-flex align-items-center my-3">
                            <p class="mb-0">Choose Length:</p>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <button class="btn btn-outline-secondary decrement" type="button">-</button>
                                </div>
                                <input type="number" class="form-control quantity" value="1" name="length"
                                    step="0.1" min="1" id="lengthInput">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary increment" type="button">+</button>
                                </div>
                            </div>
                        </div>
                        <h5 id="subtotal" class="mb-4 mt-4">SubTotal Rs: </h5>
                        <button type="button" class="btn btn-primary mb-2 cart w-100">Add to Cart</button>
                        <button type="button" class="btn btn-success buy w-100">Buy it Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-5 upper-div">
    <h3 class="text-center mb-4">Related Stock</h3>
    <div class="swiper-container">
        <div class="swiper-wrapper">
            @foreach ($relatedstocks as $stock)
                @foreach ($stock->colors as $color)
                    <div class="swiper-slide">
                        <div class="card related">
                            <div class="card-body">
                                @php
                                    $image = $stock->images->firstWhere('image_color', $color->color);
                                @endphp
                                <img src="{{ asset('public/storage/' . $image->images) }}" alt="Cloth Image">
                                <p class="card-title"><strong>Cloth Type:</strong> {{ $stock->type->name }}</p>
                                {{-- <p class="card-title"><strong>Color:</strong> {{ $stock->color }}</p> --}}
                                <a href="{{ route('user.customer.stock.show', [
                                        'slug' => $slug,
                                        'brand_id' => $stock->cloth_brand_id,
                                        'type_id' => $stock->cloth_type_id,
                                        'color' => $color->color
                                    ]) }}" class="btn btn-primary">Check Details</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
        <!-- Add Pagination -->
        <div class="swiper-pagination"></div>
        <!-- Add Navigation -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('public/assets/js/jquery-3.5.1.min.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const pricePerMeter = {{ $stocks->sale_price }};
        const lengthInput = document.getElementById('lengthInput');
        const subtotalElement = document.getElementById('subtotal');
        const decrementButton = document.querySelector('.decrement');
        const incrementButton = document.querySelector('.increment');

        function updateSubtotal() {
            const length = parseFloat(lengthInput.value);
            const subtotal = length * pricePerMeter;
            subtotalElement.textContent = `SubTotal: Rs ${subtotal.toFixed(2)}`;
        }

        lengthInput.addEventListener('input', updateSubtotal);

        decrementButton.addEventListener('click', function() {
            let currentLength = parseFloat(lengthInput.value);
            if (currentLength > 1) {
                lengthInput.value = (currentLength - 0.5).toFixed(1);
                updateSubtotal();
            }
        });

        incrementButton.addEventListener('click', function() {
            lengthInput.value = (parseFloat(lengthInput.value) + 0.5).toFixed(1);
            updateSubtotal();
        });

        // Initial subtotal calculation
        updateSubtotal();
    });


    $('.cart').on('click', function(e) {
        e.preventDefault();
        const stockId = "{{ $stocks->id }}";
        const length = $('.quantity').val();
        const price = "{{ $stocks->sale_price }}";
        const color = "{{$colour}}";
        console.log(color);

        var csrfToken = "{{ csrf_token() }}";

        $.ajax({
            url: "{{ route('user.stock.cart', ['slug' => $slug]) }}",
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            data: {
                Stock: stockId,
                length: length,
                price: price,
                color: color,
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Cart',
                    text: response,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    window.location.reload();
                });
            },
            error: function(xhr, status, error) {
                var errorMessage = xhr.responseJSON.message;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "An error occurred: " + errorMessage,
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        });
    });

    $('.buy').on('click', function(e) {
        e.preventDefault();
        var stockId = "{{ $stocks->id }}";
        var length = $('.quantity').val();
        var price = "{{ $stocks->sale_price }}";
        const color = "{{$colour}}";

        var csrfToken = "{{ csrf_token() }}";

        $.ajax({
            url: "{{ route('user.stock.order', ['slug' => $slug]) }}",
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            data: {
                Stock: stockId,
                length: length,
                price: price,
                color: color,
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Order Placed',
                    text: response,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    window.location.href =
                        "{{ route('user.thank_you', ['slug' => $slug]) }}";
                });
            },
            error: function(xhr, status, error) {
                var errorMessage = xhr.responseJSON.message;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "An error occurred: " + errorMessage,
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        // Get all thumbnail images
        const thumbnails = document.querySelectorAll('.thumbnail-img');

        // Get the main product image element
        const mainImage = document.getElementById('mainImage');

        // Loop through each thumbnail and add a click event listener
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                // Set the main image src to the clicked thumbnail's data-main attribute value
                mainImage.src = this.getAttribute('data-main');
            });
        });
    });

    // document.addEventListener("DOMContentLoaded", function() {
    //     const colorBoxes = document.querySelectorAll('.color-box');
    //     const colorOverlay = document.getElementById('colorOverlay');
    //     const thumbnailOverlays = document.querySelectorAll('.thumbnail-overlay');

    //     colorBoxes.forEach(box => {
    //         box.addEventListener('click', function() {
    //             const color = this.getAttribute('data-color');
    //             // Get computed style of opacity
    //             const overlayOpacity = window.getComputedStyle(colorOverlay).getPropertyValue(
    //                 'opacity');
    //             // Check if color is black (#000000)
    //         if (color === '#000000') {
    //             // Apply solid black color without opacity
    //             colorOverlay.style.backgroundColor = color;
    //             colorOverlay.style.opacity = '0.1'; // Full opacity
    //             thumbnailOverlays.forEach(overlay => {
    //                 overlay.style.backgroundColor = color;
    //                 overlay.style.opacity = '0.1'; // Full opacity
    //             });
    //         } else {
    //             // Apply color overlay with opacity for non-black colors
    //             colorOverlay.style.backgroundColor = color;
    //             colorOverlay.style.opacity = '0.4'; // Example opacity
    //             thumbnailOverlays.forEach(overlay => {
    //                 overlay.style.backgroundColor = color;
    //                 overlay.style.opacity = '0.4'; // Example opacity
    //             });
    //         }

    //         });
    //     });
    // });

    $(document).ready(function() {
        $('.video-div').on('click', function() {
            // Clone the video element
            var videoElement = $(this).find('video').clone().prop('controls', true);
            // Clear any existing content in the overlay video container
            $('.video-container').empty().append(videoElement);

            //show overlay
            $('.overlay-content').css('display', 'flex');
        });

        $('.close-icon').on('click', function() {
            $(this).parent('.overlay-content').css('display', 'none');
            $('.video-container').empty(); // Clear the video container
        });
    });

    var swiper = new Swiper('.swiper-container', {
        speed: 800, // Transition speed in milliseconds
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        autoplay: {
            delay: 2000, // Time in milliseconds between each slide
            disableOnInteraction: false, // Continue autoplay after user interactions
        },
        breakpoints: {
            // When window width is <= 576px
            576: {
                slidesPerView: 1, // Show 1 slide at a time on small screens
                spaceBetween: 10,
            },
            // When window width is <= 768px
            768: {
                slidesPerView: 2, // Show 2 slides at a time on medium screens
                spaceBetween: 20,
            },
            // When window width is <= 992px
            992: {
                slidesPerView: 3, // Show 3 slides at a time on large screens
                spaceBetween: 25,
            },
            // When window width is <= 1200px
            1200: {
                slidesPerView: 4, // Show 4 slides at a time on extra-large screens
                spaceBetween: 30,
            }
        }
    });
</script>

@include('layouts.waqar.footer')
