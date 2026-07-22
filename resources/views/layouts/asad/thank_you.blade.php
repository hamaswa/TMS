@include('layouts.asad.header')
    <style>
        .container {
            /* width: 100%;
            height: 100vh; */
        }

        .order-details {
            background-color: #0007;
            border: 1px solid #dee2e6;
            padding: 20px 15px;
            border-radius: 5px;
            font-size: 20px;
        }

        .order-details p {
            margin-bottom: 10px;
        }

        .order-details p strong {
            font-weight: bold;
        }

        .thank-you-message {
            text-align: center;
            margin-bottom: 20px;
        }
        .note-message{
            width: 60%;
            padding: 5px 10px;
            margin-top: 10px;
            word-spacing: 2px;
        }
        .note-message h4{
            padding: 4px;
        }
        a{
            color: #fff;
            text-decoration: none;
        }
        .note-message button{
            margin-bottom: 37px;
        }
    </style>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="thank-you-message">
                <h2>Thank You For Shopping</h2>
            </div>
            @php
                $latestOrder = Auth::user()->onlineorder()->latest()->first(); // Retrieve the latest order
            @endphp

            @if ($latestOrder)
            <div class="order-details">
                <p><strong>User:</strong> {{ $latestOrder->user->name }}</p>
                <p><strong>Brand Name:</strong> {{ $latestOrder->cloth->brand->name }}</p>
                <p><strong>Cloth Type:</strong> {{ $latestOrder->cloth->type->name }}</p>
                <p><strong>Length:</strong> {{ $latestOrder->length }}</p>
                <p><strong>Price per Meter:</strong> {{ $latestOrder->price }}</p>
                <p><strong>Total Price:</strong> {{ $latestOrder->price * $latestOrder->length}}</p>
                <!-- Add other details as needed -->
            </div>
            @else
            <p>No orders found.</p>
            @endif
        </div>
        <div class="note-message">
            <h4>Your Order id is : {{$latestOrder->id}}</h4>
            <h5>Take a Screenshot of this page or write down your order id so when you came to visit our shop just show the screenshot or show order id.</h5>
            <button type="button" class="btn btn-success"><a href="{{route('user.selling',['slug'=>$slug])}}">Back to Home</a></button>
        </div>
    </div>
</div>
@include('layouts.asad.footer')
