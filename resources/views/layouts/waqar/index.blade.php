@include('layouts.waqar.header')

{{-- links for font family --}}
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">

<style>
    /* intro section */
    .container-fluid {
        padding: 0;
        overflow-x: hidden;
        /* Prevent horizontal scroll */
        box-sizing: border-box;
    }

    .intro {
        position: relative;
        width: 100%;
        height: 100vh;
        background-image: url('{{asset('public//storage/image/bg.jpg')}}');
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


    /* sliding-section starts here*/
    .sliding-section {
        width: 100%;
        height: 100vh;
        background-image: url('{{asset('public/storage/image/sliding2.jpg')}}');
        background-repeat: no-repeat;
        background-size: cover;
        background-attachment: fixed;
        /* Enable parallax effect */
        margin-bottom: 4px;
        object-fit: cover;
    }

    .sliding-overlay {
        position: relative;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.3);
        /* Adjust the opacity as needed */
        z-index: 1;
    }

    /* sliding section ends here */

    /* sliding-section starts here*/
    .sliding-section2 {
        width: 100%;
        height: 100vh;
        background-image: url('{{asset('public/storage/image/sliding1.jpg')}}');
        background-repeat: no-repeat;
        background-size: cover;
        background-attachment: fixed;
        /* Enable parallax effect */
        object-fit: cover;
    }

    .sliding-overlay2 {
        position: relative;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        /* Adjust the opacity as needed */
        z-index: 1;
    }

    /* sliding section ends here */



    /* brand cards section starts here */
    .fancy-font {
        font-family: 'Great Vibes', cursive;
        font-size: 60px;
    }

    .card {
        cursor: pointer;
        background-color: #ddd;
        box-shadow: 2px 2px 4px 4px rgba(0, 0, 0, 0.6);
    }

    .card-img-top:hover {
        transform: scale(1.1);
        transition: .35s ease-in-out;
    }

    .card img {
        width: 200px;
        height: 200px;
        object-fit: cover;
    }

    .card-title:hover {
        transform: scale(1.1);
        transition: .35s ease-in-out;
    }

    .btn-group a:hover {
        transform: scale(1.2);
        transition: .35s ease-in-out;
    }

    /* brand cards section ends here */


    /* Contact us Section starts here */
    .contact-form {
        width: 80%;
        max-width: 600px;/* Ensures the form doesn't exceed a certain width */
        margin: 0 auto;/* Centers the form horizontally */
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .contact-form label {
        font-size: 20px;
    }

    .contact-form input,
    .contact-form textarea {
        border: none;
        background: transparent;
        border-bottom: 2px solid #000;
        color: #000;
    }

    .contact-form input:focus,
    .contact-form textarea:focus {
        outline: none;
        background: transparent;
    }

    /* Set placeholder color to white when input is focused */
    .contact-form input:focus::placeholder,
    .contact-form textarea:focus::placeholder {
        color: #000;
    }

    /* Contact us section ends here */


    /* about us section starts */
    .about-us {
        text-align: center;
        padding: 50px 0;
        height: 100vh;
    }
    .about-us-image img{
        width: 400px;
        height: 300px;
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

    /* About us section ends here */
</style>


{{-- start here --}}
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
    {{-- sliding section ends --}}

    {{-- sliding section --}}
    <div class="sliding-section2">
        <div class="sliding-overlay2"></div>
        <div class="sliding-content2">

        </div>
    </div>
    {{-- sliding section ends --}}


    <!-- Featured brands Section -->
    <div class="text-center mt-1 px-4 brands" id="Brands">
        <h1 class="mb-4 fancy-font">Featured Brands</h1>
        <div class="row">
            @foreach ($stocks as $stock)
                <div class="col-md-4">
                    <div class="card mb-4 shadow-lg">
                        <a href="{{ route('user.customer.stock', ['slug' => $slug, 'id' => $stock->id]) }}"
                            style="text-decoration: none;color:#000;">
                            <img src="{{ asset('public/storage/' . $stock->brand_logo) }}" alt="{{ $stock->name }}"
                                class="card-img-top">
                            <div class="card-body">
                                <h3 class="card-title">{{ $stock->name }}</h3>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="btn-group">
                                        <a href="{{ route('user.customer.stock', ['slug' => $slug, 'id' => $stock->id]) }}"
                                            class="btn btn-sm btn-outline-secondary">Shop Now</a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    {{-- brands section ends --}}

    <!-- About Us Section -->
    <div class="about-us mb-4" id="About">
        <div class="container">
            <h2 class="mt-3 text-center mb-5 fancy-font">About Us</h2>
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-us-image">
                        <img src="{{ asset('public/storage/image/logo.png') }}" alt="{{ $shop->name }}" class="img-fluid">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-us-content">
                        <h2 class="about-us-title mb-4 fancy-font">{{ $shop->name }}</h2>
                        <p class="about-us-text fancy-font">
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

    <!-- Contact us section -->
    <div class="contact-us mb-4" id="Contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="contact-form p-5 rounded shadow">
                        <h2 class="text-center mb-4">Contact Us</h2>
                        <form>
                            <div class="form-group">
                                <input type="text" class="form-control form-control-lg" id="name"
                                    placeholder="Your Name">
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control form-control-lg" id="contact_number"
                                    placeholder="Contact Number">
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control form-control-lg" id="email"
                                    placeholder="Your Email">
                            </div>
                            <div class="form-group">
                                <textarea class="form-control form-control-lg" id="message" rows="5" placeholder="Message"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg btn-block">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@include('layouts.waqar.footer')
