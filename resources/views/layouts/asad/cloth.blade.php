@include('layouts.asad.header')
<style>
    .main {
        width: 100%;
        height: 100vh;
        background-color: #ddd;
    }

    .inner {
        position: relative;
        width: 80%;
        height: 80%;
        background: #fff;
        top: 6%;
        left: 10%;
        border-radius: 4px;
    }

    .img-div {
        position: absolute;
        top: 8%;
        left: 10%;
        width: 450px;
        height: 500px;
        border-radius: 5px;
        box-shadow: 0px 0px 15px 5px rgba(20, 0, 0, 0.75);
        cursor: pointer;
    }

    .img-div img {
        width: 100%;
        height: 100%;
        border-radius: 5px;
    }

    .properties {
        position: absolute;
        left: 50%;
        top: 20%;
        font-size: 24px;
        font-weight: 500;
    }

    .properties .brand-name {
        position: relative;
        /* background: #fff; */
        padding: 5px;
        right: 50px;
    }

    .properties .brand-name p {
        font-size: 24px;
    }

    .properties .brand-name li {
        position: relative;
        bottom: 10px;
        left: 50px;
        padding: 0px;
    }
    .properties .details{
        position: relative;
        /* background: #fff; */
        padding: 2px;
        right: 50px;
    }

    .properties .details li{
        position: relative;
        bottom: 10px;
        left: 50px;
        padding: 0px;
    }

    .length-selector {
        display: flex;
        align-items: center;
    }

    .length-selector p {
        margin-right: 10px;
        /* Adjust margin as needed */
    }

    .counter {
        display: flex;
        align-items: center;
    }

    button {
        padding: 5px 10px;
        font-size: 1rem;
    }

    input {
        width: 70px;
        text-align: center;
        margin: 2px;
    }

    .buy {
        padding: 5px 10px;
        background-color: #00a8ff;
        color: #fff;
        border-radius: 4px;
        border: none;
    }

    .buy:hover {
        background-color: #0097e6;
        transition: .35s;
    }

    .cart {
        padding: 5px 10px;
        background-color: #38ada9;
        color: #fff;
        border-radius: 4px;
        border: none;
    }

    .cart:hover {
        background-color: #079992;
        transition: .35s;
    }
</style>
<div class="main">
    <div class="inner">
        <div class="img-div">
            <img src="{{ asset('/storage/' . $stocks->image) }}" alt="" title="{{ $stocks->type->name }}">
        </div>

        <div class="properties">
            <ul>
                <div class="brand-name">
                    <p>Brand Name</p>
                    <li>{{ $stocks->brand->name }}</li>
                </div>
                <div class="details">
                    <p>Details</p>
                    <li>{{ $stocks->type->name }}</li>
                    <li>{{ $stocks->color }}</li>
                    <li>Rs{{ $stocks->price }} per meter</li>
                </div>
            </ul>
            <form method="POST">
                @csrf
                <div class="length-selector">
                    <p>Choose Length:</p>
                    <div class="counter">
                        <button class="decrement" type="button">-</button>
                        <input type="number" class="quantity" value="1" name="length" step="0.1">
                        <button class="increment" type="button">+</button>
                    </div>
                </div>
                <button type="button" class="cart">Add to Cart</button>
                <button type="button" class="buy">Buy it Now</button>
            </form>
        </div>
    </div>

</div>

<script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
<script>
    const increment = document.querySelector('.increment');
    const decrement = document.querySelector('.decrement');
    const qty = document.querySelector('.quantity');

    increment.addEventListener('click', function() {
        // console.log('clicked');
        let currentValue = parseInt(qty.value);
        // console.log(currentValue);
        qty.value = currentValue + 1;
    });

    decrement.addEventListener('click', function() {
        // console.log('clicked');
        let currentValue = parseInt(qty.value);
        // console.log(currentValue);
        if (currentValue > 1) {
            qty.value = currentValue - 1;
        }

    });

    // Add to cart
    $('.cart').on('click', function(e) {
        e.preventDefault();
        var stockId = "{{ $stocks->id }}";
        var length = $('.quantity').val();
        var price = "{{ $stocks->price }}";

        // Get CSRF token value
        var csrfToken = "{{ csrf_token() }}";

        $.ajax({
            url: "{{ route('user.stock.cart', ['slug' => $slug]) }}",
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': csrfToken // Include CSRF token in headers
            },
            data: {
                Stock: stockId,
                length: length,
                price: price,
            },

            success: function(response) {
                alert(response);
                window.location.reload();
            },
            error: function(xhr, status, error) {
                var errorMessage = xhr.responseJSON
                    .message; // Extract error message from JSON response
                alert("An error occurred: " + errorMessage);
            }
        })
    });

    // Buy Directly

    $('.buy').on('click', function(e) {
        e.preventDefault();
        var stockId = "{{ $stocks->id }}";
        var length = $('.quantity').val();
        var price = "{{ $stocks->price }}";

        // Get CSRF token value
        var csrfToken = "{{ csrf_token() }}";

        $.ajax({
            url: "{{ route('user.stock.order', ['slug' => $slug]) }}",
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': csrfToken // Include CSRF token in headers
            },
            data: {
                Stock: stockId,
                length: length,
                price: price,
            },

            success: function(response) {
                alert(response);
                // Redirect to the thank you page
                window.location.href = "{{ route('user.thank_you', ['slug' => $slug]) }}";
            },
            error: function(xhr, status, error) {
                var errorMessage = xhr.responseJSON
                    .message; // Extract error message from JSON response
                alert("An error occurred: " + errorMessage);
            }
        })
    });
</script>
@include('layouts.asad.footer')
