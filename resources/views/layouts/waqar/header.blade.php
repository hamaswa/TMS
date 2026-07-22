<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Online Store</title>
    <!-- Bootstrap CSS -->
    <link href="{{ asset('public/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('public/assets/css/myresponsive.css')}}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">

    <style>
        body {
            background-color: #ddd;
        }

        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background-color: rgba(110, 120, 130, 0.9);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            color: white;
            cursor: pointer;
            display: none;
            z-index: 10;
        }

        header {
            background: linear-gradient(90deg, rgba(157, 239, 231, 0.7), rgba(36, 37, 37, 0.7));
            color: #000;
        }

        footer {
            background: linear-gradient(90deg, rgba(157, 239, 231, 0.7), rgba(36, 37, 37, 0.7));
            color: #000;
        }

        .scroll-to-top i {
            font-size: 20px;
            color: #fff;
        }

        .cart-icon {
            position: relative;
            display: inline-block;
            font-size: 18px;
        }

        .cart-icon .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            font-size: 11px;
            background-color: red;
            color: white;
        }

        a {
            color: #fff;
            transition: .25s ease-in;
        }

        .dropdown-menu {
            background: rgba(0, 0, 0, 0.5);
        }

        .dropdown-item {
            font-size: 18px;
            color: #fff;
        }
        .nav-link,.navbar-brand{
            font-weight: 900;
        }
    </style>
</head>
<body>
    <header class="text-white py-2 header">
        <a href="#" id="scrollToTop" class="scroll-to-top" style="display: none;">
            <i class="fas fa-arrow-up"></i>
        </a>
        @php
            $slug = 'waqar_shop';
        @endphp

        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand text-black fs-3" href="{{ route('user.selling', ['slug' => $slug]) }}">Waqar Fabrics</a>
                <button class="navbar-toggler" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link text-black fs-5" href="{{ route('user.selling', ['slug' => $slug]) }}">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-black fs-5" href="#About">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-black fs-5" href="#Contact">Contact</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ml-auto">
                        @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-black" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ Auth::user()->name }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="{{ route('user.customers.details', ['slug' => $slug]) }}">Account Details</a>
                                <a class="dropdown-item" href="{{ route('user.history', ['slug' => $slug]) }}">Order History</a>
                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                        @endauth
                        <li class="nav-item">
                            @php
                                $cartCount = Auth::user()->carts()->count();
                            @endphp
                            <a class="nav-link text-white cart-icon" href="{{ route('user.cart.show', ['slug' => $slug]) }}">
                                <i class="fa fa-shopping-cart"></i>
                                @if ($cartCount > 0)
                                    <span class="badge badge-pill badge-primary">{{ $cartCount }}</span>
                                @endif
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
