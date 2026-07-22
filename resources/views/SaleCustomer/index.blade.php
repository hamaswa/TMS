@extends('cust_main')

@section('content')
    <style>
        /* intro section */
        .container-fluid {
            padding: 0;
        }

        .intro {
            position: relative;
            width: 100%;
            height: 100vh;
            background-image: url('/storage/image/bg.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            overflow: hidden;
        }

        .intro::-webkit-scrollbar {
            display: none;
            /* Hide the scrollbar for WebKit browsers (Chrome, Safari, etc.) */
        }

        .intro {
            scrollbar-width: none;
            /* Hide the scrollbar for Firefox */
        }

        .overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            content: '';
            top: 0;
            left: 0;
            background-color: rgba(0, 0, 0, 0.6);
            /* Adjust the opacity as needed */
            z-index: 1;
        }

        .text-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            text-align: center;
            z-index: 2;
        }

        .text-content h1 {
            font-size: 3em;
            margin-bottom: 20px;
        }

        .text-content p {
            font-size: 1.2em;
        }

        /* intro section ends here */

        /* sliding-section */
        .sliding-section {
            width: 100%;
            height: 100vh;
            background-image: url('/storage/image/sliding.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
            /* Enable parallax effect */
        }

        .sliding-overlay {
            position: relative;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            /* Adjust the opacity as needed */
            z-index: 1;
        }


        .card {
            cursor: pointer;
            background-color: #ddd;
            box-shadow: 2px 2px 4px 4px rgba(0, 0, 0, 0.6);
        }

        .card-img-top:hover {
            transform: scale(1.1);
            transition: .35s ease-in-out
        }

        .contact-form {
            width: 50%;
            padding: 10px 20px;
            height: auto;
            position: relative;
            left: calc(50% - 440px);
            background-color: #84817a;
            border-radius: 6px;
            /* Adjust padding as needed */
        }

        .contact-form label {
            font-size: 20px;
        }

        .contact-form input,
        .contact-form textarea {
            border: none;
            background: transparent;
            border-bottom: 2px solid #000;
            color: #fff;
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            outline: none;
            background: transparent;
            /* Remove default focus outline */
        }

        /* Set placeholder color to white when input is focused */
        .contact-form input:focus::placeholder,
        .contact-form textarea:focus::placeholder {
            color: #fff;
        }

        .about-us {
            text-align: center;
            padding: 50px 0;
            height: 100vh;
        }

        .about-us-title {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 30px;
        }

        .about-us-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .about-us-text {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #666;
        }

        /* Add additional styling as needed */
    </style>
    <div class="container-fluid">
        <div class="intro">
            <div class="overlay"></div> <!-- Overlay -->
            <div class="text-content">
                <h1>Welcome to {{ $shop->name }}</h1>
                <p>Discover the Latest Brand Collections Here.</p>
                <a href="#Brands" class="btn btn-primary">Shop Now</a>
            </div>
        </div>


        {{-- sliding section --}}
        <div class="sliding-section">
            <div class="sliding-overlay"></div>
            <div class="sliding-content">

            </div>
        </div>



        <!-- Featured Stocks Section -->
        <div class="text-center mb-4" id="Brands">
            <h2 class="mb-4">Featured Brands</h2>
            <div class="row">
                @foreach ($stocks as $stock)
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <img src="{{ asset('/storage/' . $stock->brand_logo) }}" alt="Product"
                                    class="card-img-top mb-4" style="height: 200px; width: 100%;">
                                <h3 class="card-title">{{ $stock->name }}</h3>
                                <a href="{{ route('user.customer.stock', ['id' => $stock->id]) }}"
                                    class="btn btn-primary">Shop
                                    Now</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- About Us Section -->
        <div class="about-us mb-4" id="About">
            <h2 class="about-us-title">About Us</h2>
            <div class="about-us-content">
                <p class="about-us-text">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris ut nisi eget metus posuere gravida.
                    Donec
                    non turpis vel ante eleifend sollicitudin. Sed non nulla justo. Aenean vitae efficitur nisi, at gravida
                    mi. Vestibulum auctor lacus non nibh ullamcorper, ac hendrerit velit porta. Sed at dapibus arcu. Ut
                    ullamcorper ligula et nisi vehicula, nec lobortis nulla congue. Mauris nec dolor auctor, sodales turpis
                    vel, fermentum turpis. Nulla facilisi. In ultrices ex ut augue pretium, sed vestibulum ipsum volutpat.
                    Sed tristique velit ac pulvinar malesuada. Donec efficitur eget enim eget aliquam. Suspendisse potenti.
                </p>
            </div>
        </div>



        <!-- Contact Us Section -->
        <div class="contact-us mb-4" id="Contact">
            <div class="contact-form">
                <h2>Contact Us</h2>
                <form>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Your Name</label>
                            <input type="text" class="form-control" id="name" placeholder="Enter your name">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number"
                                placeholder="Enter your contact number">

                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Your Email</label>
                        <input type="email" class="form-control" id="email" placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea class="form-control" id="message" rows="5" placeholder="Enter your message"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>

        </div>


    </div>
@endsection
