@include('layouts.waqar.header')
<style>

    .thank-you-message {
        text-align: center;
        margin-bottom: 30px;
    }

    .thank-you-message h2 {
        font-size: 2.5rem;
        color: #28a745;
        margin-bottom: 20px;
    }

    .order-details {
        background-color: #ffffff;
        border: 1px solid #dee2e6;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .order-details p {
        font-size: 1.2rem;
        margin-bottom: 10px; /* Adjusted for more spacing */
        line-height: 1.3; /* Improved readability with increased line height */
    }

    .order-details p strong {
        font-weight: bold;
    }

    .note-message {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        margin-top: 20px;
        margin-bottom: 30px;
    }

    .note-message h4 {
        font-size: 1.5rem;
        margin-bottom: 10px;
    }

    .note-message p {
        font-size: 1rem;
        margin-bottom: 20px;
    }

    .note-message button {
        margin-bottom: 35px;
    }

    .btn-success a {
        color: #fff;
        text-decoration: none;
    }

    /* Responsive adjustments */
    @media(max-width: 576px) {
        .thank-you-message h2 {
            font-size: 1.5rem;
            margin-top: 10px;
        }

        .order-details {
            padding: 20px;
        }

        .order-details p {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .note-message {
            padding: 15px;
        }

        .note-message h4 {
            font-size: 1.2rem;
        }

        .note-message p {
            font-size: 0.9rem;
        }
    }

    @media(min-width: 768px) {
        .thank-you-message h2 {
            font-size: 2rem;
            margin-top: 15px;
        }

        .note-message h4 {
            font-size: 1.5rem;
        }

        .note-message p {
            font-size: 1rem;
        }
    }

    @media(min-width: 992px) {
        .thank-you-message h2 {
            font-size: 2.5rem;
            margin-top: 20px;
        }

        .note-message h4 {
            font-size: 1.8rem;
        }

        .note-message p {
            font-size: 1.2rem;
            font-weight: 500;
        }
    }
</style>
<div class="container">
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
                    <p><strong>Customer :&nbsp;&nbsp;&nbsp;&nbsp; {{ $latestOrder->user->name }}</strong></p>

                    <p><strong>Brand Name:&nbsp;&nbsp;&nbsp;&nbsp; {{ $latestOrder->cloth->brand->name }}</strong></p>

                    <p><strong>Cloth Type: &nbsp;&nbsp;&nbsp;&nbsp;{{ $latestOrder->cloth->type->name }}</strong></p>

                    <p><strong>Length: &nbsp;&nbsp;&nbsp;&nbsp;{{ $latestOrder->length }} meters</strong></p>

                    <p><strong>Price per Meter: &nbsp;&nbsp;&nbsp;&nbsp;Rs {{ $latestOrder->price }}</strong></p>

                    <p><strong>Total Price: &nbsp;&nbsp;&nbsp;&nbsp;Rs {{ $latestOrder->price * $latestOrder->length }}</strong></p>
                </div>
            @else
                <p>No orders found.</p>
            @endif
        </div>
        <div class="col-md-8">
            <div class="note-message">
                <h4>Your Order ID: {{ $latestOrder->id }}</h4>
                <p>Take a screenshot of this page or write down your order ID. When you visit our shop, show the screenshot or provide your order ID.</p>
                <button type="button" class="btn btn-success">
                    <a href="{{ route('user.selling', ['slug' => $slug]) }}">Back to Home</a>
                </button>
            </div>
        </div>
    </div>
</div>
@include('layouts.waqar.footer')
