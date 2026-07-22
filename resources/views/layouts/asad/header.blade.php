<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Online Store</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">


    <style>
        .header,
        footer {
            background: linear-gradient(90deg, rgba(82, 34, 34, 0.9), rgba(0, 0, 0, 0.8));
            /* padding: 10px 0; */
        }

        .header a {
            font-size: 20px;
            transition: color 0.3s ease;
        }

        .header a:hover {
            color: #fff;
            text-decoration: none;
        }

        .dropdown-menu {
            background-color: #343a40;
        }

        .dropdown-item {
            color: #f8f9fa;
        }

        .dropdown-item:hover {
            background-color: rgba(114, 108, 108, 0.9);
        }

        .cart-icon i {
            font-size: 1.2rem;
            position: relative;
            color: #f8f9fa;
            left: 95px;
            bottom: 17px;

        }

        .badge-primary {
            background-color: #007bff;
            position: relative;
            left: 85px;
            bottom: 37px;
            font-size: 11px;
        }

        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            padding: 10px 15px;
            cursor: pointer;
            display: none;
            z-index: 1000;
        }

        .scroll-to-top:hover {
            background-color: #0056b3;
        }

        .custom-nav {
            position: relative;
            right: 10%;
        }

        .custom-dropdown {
            position: absolute;
            right: 10%;
        }

        .dropdown {
            position: relative;
            top: 10px;
        }
    </style>
</head>

<body>
    <header class="header text-white py-3">
        <a href="#" id="scrollToTop" class="scroll-to-top" style="display: none;">
            <i class="fas fa-arrow-up"></i>
        </a>
        @php
            $slug = 'asad_shop';
        @endphp

        <div class="container">
            <div class="row align-items-center">
                <div class="custom-nav">
                    <nav>
                        <a href="{{ route('user.selling', ['slug' => $slug]) }}"
                            class="text-white mr-3 text-decoration-none">Home</a>
                        <a href="#About" class="text-white mr-3 text-decoration-none">About Us</a>
                        <a href="#Contact" class="text-white mr-3 text-decoration-none">Contact</a>
                    </nav>
                </div>
                <div class="custom-dropdown">
                    <div class="dropdown">
                        <a class="text-white mr-5 dropdown-toggle" href="#" role="button" id="userDropdown"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            @auth
                                {{ Auth::user()->name }}
                            @endauth
                        </a>
                        <div class="dropdown-menu" aria-labelledby="userDropdown">
                            <!-- Account details -->
                            <a class="dropdown-item"
                                href="{{ route('user.customers.details', ['slug' => $slug]) }}">Account Details</a>
                            <a class="dropdown-item" href="{{ route('user.history', ['slug' => $slug]) }}">Order
                                History</a>
                            <!-- Divider -->
                            {{-- <div class="dropdown-divider"></div> --}}
                            <!-- Logout link -->
                            <a class="dropdown-item" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </div>
                    <!-- Display cart icon with count -->
                    @php
                        $cartCount = Auth::user()->carts()->count(); // Get count of cart items
                    @endphp

                    <!-- Display cart icon with count -->
                    <span class="cart-icon ml-3">
                        <a href="{{ route('user.cart.show', ['slug' => $slug]) }}" title="Cart Items"><i
                                class="fa fa-shopping-cart"></i></a>
                        @if ($cartCount > 0)
                            <span class="badge badge-pill badge-primary">{{ $cartCount }}</span>
                        @else
                            <span class="badge badge-pill badge-primary"></span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </header>
