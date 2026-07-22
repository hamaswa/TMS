@include('layouts.asad.header')
<style>
    /* intro section */
    .container-fluid {
        box-sizing: border-box;
        overflow-x: hidden;
        padding: 0;
    }

    .intro {
        position: relative;
        width: 100%;
        height: 100vh;
        background-image: url('/storage/image/bg_1.jpeg');
        background-repeat: no-repeat;
        background-size: cover;
        overflow: hidden;
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
        font-size: 4em;
        margin-bottom: 20px;
    }

    .text-content p {
        font-size: 1.2em;
    }

    #typed-shop-name {
        color: palevioletred;
    }

    /* intro section ends here */

    /* sliding-section */
    .sliding-section {
        width: 100%;
        height: 100vh;
        background-image: url('/storage/image/sliding1.jpg');
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

    .row {
        padding: 10px 20px;
    }


    .card {
        cursor: pointer;
        background-color: #ddd;
        height: 400px;
        width: 450px;
        /* padd: 10px; */
        margin: 15px auto;
        box-shadow: 2px 2px 4px 4px rgba(0, 0, 0, 0.6);
        transition: transform 0.3s ease-in,box-shadow 0.3s ease-in;
    }

    .card:hover {
        box-shadow: 0px 0px 15px 15px rgba(0, 0, 0, 0.6);
        transform: scale(1.05);
    }

    .card-body img {
        width: 100%;
        height: 100%;
    }

    .card-body img:hover {
        transform: scale(1.1);
        transition: .35s ease-in-out
    }

    .card-body h3 {
        font-size: 40px;
    }

    .contact-us {
        width: 100%;
        height: 100vh;
    }

    .contact-us h2 {
        color: #fff;
    }

    .contact-form {
        width: 50%;
        padding: 15px 20px;
        height: auto;
        position: relative;
        top: 20%;
        left: calc(50% - 440px);
        background-color: #84817a;
        border-radius: 6px;
        /* Adjust padding as needed */
    }

    .contact-form label {
        font-size: 20px;
        color: #fff;
    }

    .contact-form input,
    .contact-form textarea {
        border: none;
        background: transparent;
        border-bottom: 2px solid #000;
        color: #fff;
        font-size: 16px;
    }

    .contact-form input:focus,
    .contact-form textarea:focus {
        outline: none;
        background: color;
        /* Remove default focus outline */
    }

    .contact-form input::placeholder,
    .contact-form textarea::placeholder{
        color: #ddd;
    }
    /* Set placeholder color to white when input is focused */
    .contact-form input:focus::placeholder,
    .contact-form textarea:focus::placeholder {
        color: #000;
    }

    .about-us {
        text-align: center;
        padding: 40px 0;
        height: 100vh;
    }

    .about-us-title {
        font-size: 4rem;
        color: #333;
        margin-bottom: 10px;
        font-family: "Dancing Script", cursive;
        font-optical-sizing: auto;
        font-weight: <weight>;
        font-style: normal;
    }

    .about-us-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .about-us-text {
        font-size: 1.5rem;
        line-height: 1.6;
        color: #111;
        font-family: "Dancing Script", cursive;
        font-optical-sizing: auto;
        font-weight: <weight>;
        font-style: normal;
        word-spacing:3px;
    }
    .about-us h2{
        font-size: 50px;
    }

    #Brands h2,h3 {
        font-family: "Dancing Script", cursive;
        font-optical-sizing: auto;
        font-weight: <weight>;
        font-style: normal;
        font-size: 5em;
    }

    /* Add additional styling as needed */
</style>
<div class="container-fluid">
    <div class="intro">
        <div class="overlay"></div> <!-- Overlay -->
        <div class="text-content">
            <h1>Welcome to <span id="typed-shop-name"></span></h1>
            <p>Explore Premium Wholesale Clothing Collections at Unbeatable Prices.</p>
            <a href="#Brands" class="btn btn-primary">Shop Now</a>
        </div>
    </div>


    <!-- About Us Section -->
    <div class="about-us mb-4" id="About">
        <div class="container">
            <h2 class="mt-4 text-center mb-2">About Us</h2>
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-us-image">
                        <img src="{{ asset('/storage/image/logo.png') }}" alt="{{ $shop->name }}" class="img-fluid"
                            width="400px" height="300px">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-us-content">
                        <h2 class="about-us-title mb-4">{{ $shop->name }}</h2>
                        <p class="about-us-text">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris ut nisi eget metus posuere
                            gravida. Donec non turpis vel ante eleifend sollicitudin. Sed non nulla justo. Aenean vitae
                            efficitur nisi, at gravida mi. Vestibulum auctor lacus non nibh ullamcorper, ac hendrerit
                            velit porta. Sed at dapibus arcu. Ut ullamcorper ligula et nisi vehicula, nec lobortis nulla
                            congue. Mauris nec dolor auctor, sodales turpis vel, fermentum turpis. Nulla facilisi. In
                            ultrices ex ut augue pretium, sed vestibulum ipsum volutpat. Sed tristique velit ac pulvinar
                            malesuada. Donec efficitur eget enim eget aliquam. Suspendisse potenti.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- About section ends --}}

    {{-- sliding section --}}
    <div class="sliding-section">
        <div class="sliding-overlay"></div>
        <div class="sliding-content"></div>
    </div>


    <!-- Featured Stocks Section -->
    <div class="text-center mb-4" id="Brands">
        <h2 class="mb-4 mt-3">Featured Brands</h2>x
        <div class="row">
            @foreach ($stocks as $stock)
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <img src="{{ asset('/storage/' . $stock->brand_logo) }}" alt="Product" class="mb-4"
                                style="height: 200px; width: 200px;">
                            <h3 class="card-title mt-2">{{ $stock->name }}</h3>
                            <a href="{{ route('user.customer.stock', ['slug' => $slug, 'id' => $stock->id]) }}"
                                class="btn btn-lg btn-primary mt-2">Shop
                                Now</a>
                        </div>
                    </div>
                </div>
            @endforeach
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
<!-- Add this within the <head> or at the bottom of your Blade template -->
<script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const shopName = "{{ $shop->name }}";
        new Typed("#typed-shop-name", {
            strings: [shopName],
            typeSpeed: 100,
            backSpeed: 50,
            backDelay: 2000,
            loop: true,
            showCursor: true,
            cursorChar: '|',
        });
    });
</script>

@include('layouts.asad.footer')
