<!DOCTYPE html>
<html lang="en" dir="rtl">

<head>
    <title>Tailor</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="keywords" content="" />
    <meta name="author" content="" />

    <link href="https://example.com/" rel="canonical" /> <!-- preferred URL - change for you site -->
    <link rel="icon" href="{{ asset('public/assets/images/web-app-manifest-192x192.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('public/assets/images/favicon.ico') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">

    <!--fonts-->
    <link
        href="https://fonts.googleapis.com/css?family=Alegreya+SC:400,700|Permanent+Marker|Abril+Fatface|Poppins:300,400,500,600,700"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- css -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css"
        integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
    <link href="{{ asset('public/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!--<link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">-->
    <link rel="stylesheet" href="{{ asset('public/assets/owlcarousel/assets/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/css/jquery.dataTables.min.css') }}">
    <link href="{{ asset('public/assets/css/main.css') }}" rel="stylesheet">
    <link href="{{ asset('public/assets/css/responsive.css') }}" rel="stylesheet">
    <!--<link rel="stylesheet" href="{{ asset('assets/owlcarousel/assets/owl.carousel.min.css') }}">-->
    <!--<link rel="stylesheet" href="{{ asset('assets/css/jquery.dataTables.min.css') }}">-->
    <!--<link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">-->
    <!--<link href="{{ asset('assets/css/responsive.css') }}" rel="stylesheet">-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link href="{{ asset('public/assets/css/style.css') }}" rel="stylesheet">
    <!--<link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">-->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css"
        crossorigin="anonymous" />
    

    <style type="text/css">
        body {
            font-family: 'Noto Nastaliq Urdu', serif;
        }

        .bootstrap-tagsinput {
            width: 100%;
        }

        .label-info {
            background-color: #17a2b8;

        }

        .label {
            display: inline-block;
            padding: .25em .4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: .25rem;
            transition: color .15s ease-in-out, background-color .15s ease-in-out,
                border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .notification-icon {
            position: relative;
            display: inline-block;
            color: #17a2b8;
            font-size: 24px;
            cursor: pointer;
        }

        .notification-icon .count {
            position: relative;
            top: -12px;
        }

        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 10px;
            /* Adjust for alignment */
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 250px;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            font-size: 18px;
        }



        .notification-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notification-dropdown li {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .notification-dropdown li a {
            text-decoration: none;
            color: #333;
        }

        .notification-dropdown li a:hover {
            background-color: #f5f5f5;
        }

        .notification-icon:hover .notification-dropdown {
            display: block;
        }
    </style>
    {{-- jquery and bootstarp js files --}}
    <script src="{{ asset('public/assets/js/jquery-3.5.1.min.js') }}"></script>
    <script src="{{ asset('public/assets/js/bootstrap.min.js') }}"></script>
    <!--<script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>-->
    <!--<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>-->
</head>

<body>
    {{-- @vite('resources/js/app.js') --}}
    <header class="bg-dark">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
                @if (Session::get('tailor'))
                    <a class="navbar-brand" href="{{ url('tailor/tailor-dashboard') }}">Dashboard</a>
                @else
                    @role('shop_owner')
                        <a class="navbar-brand" href="{{ url('/') }}">Dashboard</a>
                    @endrole()
                @endif
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                    data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="text-white mr-5">
                    @auth
                        {{ Auth::user()->name }}
                    @endauth
                </div>
                {{-- @auth
                    <div class="text-white mr-5">
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ml-auto">
                                <li class="nav-item dropdown">
                                    <div class="dropdown-menu" aria-labelledby="user-dropdown">
                                        @if (Auth::user()->hasRole('administrative'))
                                            <a class="dropdown-item" href="{{ route('administrator.index') }}">صارفین</a>
                                            <a class="dropdown-item" href="{{ route('administrator.roles') }}">رول چیک کریں۔</a>
                                        @endif

                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            لاگ آوٹ
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endauth --}}


                {{-- end administrative role --}}
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <!-- <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarOptions" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Options
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarOptions">
                                <a class="dropdown-item" href="#">Add Sewing Type</a>
                                <a class="dropdown-item" href="#">Add Shirt Button Type</a>
                                <a class="dropdown-item" href="#">Add Neck Type</a>
                                <a class="dropdown-item" href="#">Add Sleeve Opening Type</a>
                                <a class="dropdown-item" href="#">Add Pocket Type</a>
                                <a class="dropdown-item" href="#">Add Button Type</a>
                                <a class="dropdown-item" href="#">Plate Type</a>
                            </div>
                        </li> -->
                        @if (Session::get('tailor'))
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navBarCustomers" role="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    درزی </a>
                                <div class="dropdown-menu" aria-labelledby="navBarCustomers">
                                    <a class="dropdown-item" href="{{ url('tailor/tailor-order-list') }}">Orders</a>
                                    <a class="dropdown-item" href="{{ url('tailor/logout') }}">Logout</a>
                                </div>

                            </li>
                        @else
                            @role('shop_owner')
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navBarCustomers"
                                        role="button" data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        گاہک
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navBarCustomers">
                                        <a class="dropdown-item" href="{{ url('admin/Customers/create') }}">نیا گاہک شامل
                                            کریں</a>
                                        <a class="dropdown-item" href="{{ url('admin/Customers') }}">تمام گاہک</a>
                                        <a class="dropdown-item" href="{{ url('admin/Tailor') }}">تمام درزی</a>
                                        <a class="dropdown-item" href="{{ url('admin/OptionType') }}">آپشن کی قسم</a>

                                        <!--<a class="dropdown-item" href="{{ url('admin/design') }}">ڈیزائن کی قسم</a>-->
                                        {{-- <a class="dropdown-item" href="{{ url('admin/sale') }}">فروخت</a> --}}
                                        <a class="dropdown-item" href="{{ url('admin/setting') }}">ترتیب </a>
                                        <a class="dropdown-item" href="{{ route('admin.users') }}">اکاؤنٹ کی تفصیلات</a>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            لاگ آوٹ
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                            class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            @endrole

                            @hasanyrole(['stock_seller', 'shop_owner'])
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navBarCustomers"
                                        role="button" data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        کپڑے
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navBarCustomers">
                                        <a class="dropdown-item" href="{{ url('admin/cloth') }}">کپڑوں کی فہرست</a>
                                        <a class="dropdown-item" href="{{ url('admin/clothtype') }}"> کپڑے کی اقسام کی
                                            فہرست </a>
                                        <a class="dropdown-item" href="{{ url('admin/clothbrand') }}"> کپڑے کی کمپنی کی
                                            فہرست </a>
                                    </div>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navBarCustomers"
                                        role="button" data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        کپڑے کا ذخیرہ
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navBarCustomers">
                                        <a class="dropdown-item" href="{{ url('admin/stock') }}"> کپڑے کے اسٹاک کی
                                            فہرست</a>

                                        {{-- to show to only seller --}}
                                        @role('stock_seller')
                                            <a class="dropdown-item" href="{{ url('admin/setting') }}">ترتیب </a>
                                            <a class="dropdown-item" href="{{ route('admin.users') }}">اکاؤنٹ کی تفصیلات</a>
                                            <a class="dropdown-item" href="{{ route('logout') }}"
                                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                لاگ آوٹ
                                            </a>

                                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                                class="d-none">
                                                @csrf
                                            </form>
                                        @endrole
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navBarCustomers"
                                        role="button" data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        اخراجات
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navBarCustomers">
                                        <a class="dropdown-item" href="{{ route('admin.dailyexpense.index') }}">
                                            روزمرہ کے اخراجات</a>
                                        <a class="dropdown-item" href="{{ route('admin.expense.index') }}">
                                            ماہانہ اخراجات</a>
                                    </div>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navBarCustomers"
                                        role="button" data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        فروخت
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navBarCustomers">
                                        <a class="dropdown-item" href="{{ route('admin.sales.total') }}">
                                            کل فروخت</a>
                                        <a class="dropdown-item" href="{{ route('admin.earning.total') }}"> کل کمائی </a>

                                        @role('shop_owner')
                                            <a class="dropdown-item" href="{{ route('admin.order.total') }}"> کل آرڈر </a>
                                        @endrole
                                    </div>
                                </li>
                                {{-- for notifications --}}
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navBarCustomers"
                                        role="button" data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        نوٹیفکیشن
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navBarCustomers">
                                        <a class="dropdown-item" href="{{ route('admin.notify') }}">
                                            ایڈمن نوٹیفکیشن</a>
                                        <a class="dropdown-item" href="{{ route('admin.user') }}">
                                            گاہک نوٹیفکیشن</a>
                                    </div>
                                </li>
                    </div>
                    </li>
                    @php
                        // Get count of unread notifications
                        $notiCount = Auth::user()->unreadNotifications->count();
                        // Get unread notifications
                        $unreadNotifications = Auth::user()->unreadNotifications;
                    @endphp

                    <span class="notification-icon" id="notificationIcon" data-count="{{ $notiCount }}">
                        <i class="fa fa-bell"><span class="count">{{ $notiCount }}</span></i>
                        <div class="notification-dropdown" id="notificationDropdown">
                            <ul>
                                @forelse ($unreadNotifications as $notification)
                                    <li>
                                        <a href="#">
                                            {{ $notification->data['message'] ?? 'A new order has been placed.' }}
                                        </a>
                                    </li>
                                @empty
                                    <li>No new notifications</li>
                                @endforelse
                            </ul>

                            <!-- Show Notifications link -->
                            @if ($notiCount >= 0)
                                <div class="show-notifications-link">
                                    <a href="{{ route('admin.notifications.index') }}">Show All Notifications</a>
                                </div>
                            @endif
                        </div>
                    </span>
                @endhasanyrole

                @if (Auth::user()->hasRole('administrative'))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navBarCustomers" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            صارفین
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navBarCustomers">

                            <a class="dropdown-item" href="{{ route('administrator.role.new') }}">نیا رول اور
                                اجازت</a>
                            <a class="dropdown-item" href="{{ route('administrator.index') }}">صارفین</a>
                            <a class="dropdown-item" href="{{ route('administrator.roles') }}">رول چیک
                                کریں۔</a>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                لاگ آوٹ
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endif
                @endif
                </ul>
        </div>
        </nav>
        </div>
    </header>
