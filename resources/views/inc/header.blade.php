@php($isSuperAdminSurface = request()->is('administrator') || request()->is('administrator/*'))
@php($isSuperAdmin = $isSuperAdminSurface || (Auth::check() && Auth::user()->hasRole('administrative')))
@php($enabledWorkspaces = Auth::check() && Auth::user()->isBusinessMember() ? Auth::user()->enabledModules() : [])
@php($hasMultipleWorkspaces = count($enabledWorkspaces) > 1)
@php($activeWorkspace = session('active_workspace'))
@php($canShopSales = Auth::check() && Auth::user()->hasBusinessPermission('clothing.sales'))
@php($canShopInventory = Auth::check() && Auth::user()->hasBusinessPermission('clothing.inventory'))
@php($canShopPurchases = Auth::check() && Auth::user()->hasBusinessPermission('clothing.purchases'))
@php($canShopSuppliers = Auth::check() && Auth::user()->hasBusinessPermission('clothing.suppliers'))
@php($canTailorCustomers = Auth::check() && Auth::user()->hasBusinessPermission('tailoring.customers'))
@php($canTailorWorkshop = Auth::check() && Auth::user()->hasBusinessPermission('tailoring.workshop'))
@php($canTailorOrders = Auth::check() && Auth::user()->hasBusinessPermission('tailoring.orders'))
@php($canTailorTailors = Auth::check() && Auth::user()->hasBusinessPermission('tailoring.tailors'))
@php($canTailorConfiguration = Auth::check() && Auth::user()->hasBusinessPermission('tailoring.configuration'))
@php($canCustomerBalances = Auth::check() && Auth::user()->hasBusinessPermission('customers.balances'))
@php($unreadNotificationCount = Auth::check() && Auth::user()->isBusinessOwner() ? Auth::user()->unreadNotifications()->count() : 0)
<!doctype html>
<html lang="{{ $isSuperAdmin ? 'en' : 'ur' }}" dir="{{ $isSuperAdmin ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isSuperAdmin ? 'TMS Super Admin' : 'ٹیلر مینجمنٹ سسٹم' }}</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/responsive.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        body{direction:{{ $isSuperAdmin ? 'ltr' : 'rtl' }};text-align:{{ $isSuperAdmin ? 'left' : 'right' }};background:#f5f7fa;color:#243b53;font-family:{{ $isSuperAdmin ? 'Arial,sans-serif' : '"Noto Nastaliq Urdu","Noto Sans Arabic",Tahoma,Arial,sans-serif' }}}.tms-nav{background:linear-gradient(90deg,#102a43,#174f78);box-shadow:0 5px 18px rgba(15,42,67,.18)}.tms-nav .navbar-brand{font-weight:800;letter-spacing:.04em}.tms-nav .nav-link{color:rgba(255,255,255,.82)!important;font-weight:600;padding:.8rem .72rem!important}.tms-nav .nav-link:hover{color:#fff!important}.tms-nav .dropdown-menu{direction:{{ $isSuperAdmin ? 'ltr' : 'rtl' }};text-align:{{ $isSuperAdmin ? 'left' : 'right' }};border:0;border-radius:12px;box-shadow:0 14px 35px rgba(31,45,61,.18);padding:.5rem}.tms-nav .dropdown-item{border-radius:8px;padding:.55rem .8rem}.module-pill{font-size:.65rem;border:1px solid rgba(255,255,255,.25);border-radius:999px;padding:.25rem .5rem;color:#bfe8f3}.main-content{direction:{{ $isSuperAdmin ? 'ltr' : 'rtl' }};text-align:{{ $isSuperAdmin ? 'left' : 'right' }};min-height:calc(100vh - 70px)}body{top:0!important}
        .flatpickr-calendar{direction:{{ $isSuperAdmin ? 'ltr' : 'rtl' }};border:1px solid #dfe7f1;border-radius:12px;box-shadow:0 14px 35px rgba(31,45,61,.18);font-family:{{ $isSuperAdmin ? 'Arial,sans-serif' : '"Noto Nastaliq Urdu","Noto Sans Arabic",Tahoma,Arial,sans-serif' }}}
        .flatpickr-calendar.arrowTop:before,.flatpickr-calendar.arrowTop:after{border-bottom-color:#fff}.flatpickr-months{padding:6px 4px 2px}.flatpickr-months .flatpickr-month{height:42px;color:#14213d}.flatpickr-current-month{padding-top:5px;font-size:1rem}.flatpickr-current-month .flatpickr-monthDropdown-months,.flatpickr-current-month input.cur-year{font-weight:700}.flatpickr-weekday{color:#718096!important;font-weight:700!important}.flatpickr-day{border-radius:8px}.flatpickr-day.today{border-color:#1769ef;color:#1769ef}.flatpickr-day.selected,.flatpickr-day.startRange,.flatpickr-day.endRange,.flatpickr-day.selected:hover{border-color:#1769ef;background:#1769ef;color:#fff}.flatpickr-day:hover{border-color:#e7efff;background:#e7efff}.flatpickr-input[readonly]:not([type=hidden]){cursor:pointer;background-color:#fff}.flatpickr-input.form-control:not([type=hidden]){padding-left:40px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2356677f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:left 12px center}
    </style>
    <script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    @stack('styles')
</head>
<body>
<header class="tms-nav sticky-top"><div class="container-fluid px-3 px-lg-4"><nav class="navbar navbar-expand-xl navbar-dark p-0">
    @if(Session::get('tailor'))
        <a class="navbar-brand" href="{{ url('tailor/tailor-dashboard') }}"><i class="fas fa-cut mr-2"></i>TMS</a>
    @else
        <a class="navbar-brand" href="{{ $isSuperAdmin ? route('administrator.index') : route('admin.home') }}"><i class="fas fa-cut mr-2"></i>TMS</a>
    @endif
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#tmsNavigation" aria-controls="tmsNavigation" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="tmsNavigation"><ul class="navbar-nav mr-auto align-items-xl-center">
        @if(Session::get('tailor'))
            <li class="nav-item"><a class="nav-link" href="{{ route('tailor.jobs.index') }}">میرے کام</a></li><li class="nav-item"><a class="nav-link" href="{{ url('tailor/logout') }}">لاگ آؤٹ</a></li>
        @elseif(Auth::check() && Auth::user()->isBusinessMember())
            <li class="nav-item"><a class="nav-link" href="{{ $activeWorkspace ? route('admin.workspace.current') : route('admin.home') }}">ڈیش بورڈ</a></li>
            @if(Auth::user()->hasModule('tailoring') && (! $hasMultipleWorkspaces || $activeWorkspace === 'tailoring'))
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="tailoringMenu" data-toggle="dropdown">ٹیلرنگ <span class="module-pill ml-1">فعال</span></a><div class="dropdown-menu" aria-labelledby="tailoringMenu">
                    @if($canTailorOrders)<a class="dropdown-item" href="{{ route('admin.order.total') }}"><i class="fas fa-clipboard-list fa-fw ml-2 text-primary"></i>ٹیلرنگ آرڈرز</a>@endif
                    @if($canTailorWorkshop)<a class="dropdown-item" href="{{ route('admin.tailor-jobs.index') }}"><i class="fas fa-tasks fa-fw ml-2 text-primary"></i>ورکشاپ</a>@endif
                    @if($canTailorCustomers)<a class="dropdown-item" href="{{ route('admin.Customers.index') }}"><i class="fas fa-user-friends fa-fw ml-2 text-primary"></i>گاہک اور پیمائش</a>@endif
                    @if($canTailorTailors)<div class="dropdown-divider"></div><a class="dropdown-item" href="{{ route('admin.Tailor.index') }}"><i class="fas fa-user-tie fa-fw ml-2 text-primary"></i>درزیوں کی فہرست</a>@endif
                    @if($canTailorTailors)<a class="dropdown-item" href="{{ route('admin.production-workers.index') }}"><i class="fas fa-users fa-fw ml-2 text-primary"></i>پروڈکشن ورکرز اور اجرت</a>@endif
                    @if($canTailorConfiguration)<div class="dropdown-divider"></div><a class="dropdown-item" href="{{ route('admin.OptionType.index') }}"><i class="fas fa-sliders-h fa-fw ml-2 text-primary"></i>پیمائش اور سلائی کے اختیارات</a>@endif
                    @if($canTailorConfiguration)<a class="dropdown-item" href="{{ route('admin.measurement-templates.index') }}"><i class="fas fa-clipboard-list fa-fw ml-2 text-primary"></i>پیمائش ٹیمپلیٹس</a><a class="dropdown-item" href="{{ route('admin.measurement-fields.index') }}"><i class="fas fa-ruler fa-fw ml-2 text-primary"></i>اضافی پیمائش خانے</a><a class="dropdown-item" href="{{ route('admin.design.index') }}"><i class="fas fa-palette fa-fw ml-2 text-primary"></i>سلائی ڈیزائن</a>@endif
                </div></li>
            @endif
            @if(Auth::user()->hasModule('clothing') && (! $hasMultipleWorkspaces || $activeWorkspace === 'clothing'))
                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="clothingMenu" data-toggle="dropdown">کپڑے کی خرید و فروخت <span class="module-pill ml-1">فعال</span></a><div class="dropdown-menu" aria-labelledby="clothingMenu">
                    @if($canShopInventory)
                    <a class="dropdown-item" href="{{ route('admin.stock.index') }}"><i class="fas fa-layer-group fa-fw ml-2 text-info"></i>اسٹاک</a>
                    <a class="dropdown-item" href="{{ route('admin.cloth.index') }}"><i class="fas fa-swatchbook fa-fw ml-2 text-info"></i>کپڑے کی فہرست</a>
                    <a class="dropdown-item" href="{{ route('admin.clothtype.index') }}"><i class="fas fa-tags fa-fw ml-2 text-info"></i>کپڑے کی اقسام</a>
                    <a class="dropdown-item" href="{{ route('admin.clothbrand.index') }}"><i class="fas fa-copyright fa-fw ml-2 text-info"></i>کپڑے کے برانڈز</a>
                    <a class="dropdown-item" href="{{ route('admin.inventory-ledger.index') }}"><i class="fas fa-exchange-alt fa-fw ml-2 text-info"></i>اسٹاک کھاتہ</a>
                    <a class="dropdown-item" href="{{ route('admin.inventory-valuation.index') }}"><i class="fas fa-balance-scale fa-fw ml-2 text-info"></i>اسٹاک کی مالیت</a>
                    @endif
                    @if($canShopSales)<a class="dropdown-item" href="{{ route('admin.sellCloth') }}"><i class="fas fa-cash-register fa-fw ml-2 text-info"></i>نئی فروخت</a>@endif
                    @if($canShopSales && Auth::user()->business?->storefront)<a class="dropdown-item" href="{{ route('admin.storefront.orders.index') }}"><i class="fas fa-shopping-bag fa-fw ml-2 text-info"></i>آن لائن آرڈرز</a>@endif
                    @if($canShopPurchases)<a class="dropdown-item" href="{{ route('admin.purchases.index') }}"><i class="fas fa-truck-loading fa-fw ml-2 text-info"></i>خریداری</a>@endif
                    @if($canShopSuppliers)<a class="dropdown-item" href="{{ route('admin.suppliers.index') }}"><i class="fas fa-building fa-fw ml-2 text-info"></i>سپلائرز</a>@endif
                </div></li>
            @endif
            @if($hasMultipleWorkspaces)
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.home') }}"><i class="fas fa-random ml-1"></i>ورک اسپیس تبدیل کریں</a></li>
            @endif
            <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="businessMenu" data-toggle="dropdown">کاروبار</a><div class="dropdown-menu" aria-labelledby="businessMenu">
                @if(Auth::user()->hasBusinessPermission('finance.view'))<a class="dropdown-item" href="{{ route('admin.financial-reports.index') }}"><i class="fas fa-chart-line fa-fw ml-2 text-success"></i>مالیاتی ڈیش بورڈ</a>@endif
                @if($canCustomerBalances)<a class="dropdown-item" href="{{ route('admin.customer-accounts.index') }}"><i class="fas fa-address-book fa-fw ml-2 text-success"></i>گاہکوں کے کھاتے</a>@endif
                @if(Auth::user()->hasBusinessPermission('expenses.manage'))<a class="dropdown-item" href="{{ route('admin.dailyexpense.index') }}"><i class="fas fa-receipt fa-fw ml-2 text-warning"></i>روزانہ اخراجات</a>
                <a class="dropdown-item" href="{{ route('admin.expense.index') }}"><i class="fas fa-calendar-alt fa-fw ml-2 text-warning"></i>ماہانہ اخراجات</a>@endif
                @if(Auth::user()->hasBusinessPermission('team.manage'))<div class="dropdown-divider"></div><a class="dropdown-item" href="{{ route('admin.team.index') }}"><i class="fas fa-users-cog fa-fw ml-2 text-primary"></i>ملازمین اور اجازتیں</a>@endif
                @if(Auth::user()->hasBusinessPermission('activity.view'))<a class="dropdown-item" href="{{ route('admin.activity.index') }}"><i class="fas fa-history fa-fw ml-2 text-primary"></i>ملازمین کی سرگرمی</a>@endif
                @if(Auth::user()->hasBusinessPermission('storefront.manage'))<div class="dropdown-divider"></div><a class="dropdown-item" href="{{ route('admin.storefront.edit') }}"><i class="fas fa-globe-asia fa-fw ml-2 text-info"></i>آن لائن دکان</a>@endif
            </div></li>
        @elseif(Auth::check() && Auth::user()->hasRole('administrative'))
            <li class="nav-item"><a class="nav-link" href="{{ route('administrator.index') }}">Clients</a></li><li class="nav-item"><a class="nav-link" href="{{ route('administrator.subscriptions.index') }}">Subscriptions</a></li><li class="nav-item"><a class="nav-link" href="{{ route('administrator.subscription-plans.index') }}">Plans</a></li><li class="nav-item"><a class="nav-link" href="{{ route('administrator.marketplace.index') }}">Marketplace</a></li><li class="nav-item"><a class="nav-link" href="{{ route('administrator.create') }}">Create client</a></li>
        @endif
    </ul>
    @auth<ul class="navbar-nav ml-auto">@if(Auth::user()->isBusinessOwner())<li class="nav-item"><a class="nav-link" href="{{ route('admin.notifications.index') }}" aria-label="اطلاعات"><i class="fas fa-bell"></i>@if($unreadNotificationCount)<span class="badge badge-danger">{{ min($unreadNotificationCount,99) }}</span>@endif</a></li>@endif<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="accountMenu" data-toggle="dropdown"><i class="fas fa-user-circle mr-1"></i>{{ Auth::user()->name }}</a><div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountMenu">
        @if($isSuperAdmin)<a class="dropdown-item" href="{{ route('employee.password.edit') }}">Change password</a><div class="dropdown-divider"></div>@elseif(Auth::user()->isBusinessOwner())<a class="dropdown-item" href="{{ route('admin.subscription.index') }}">سبسکرپشن اور ادائیگی</a><a class="dropdown-item" href="{{ route('admin.setting.index') }}">دکان کی ترتیبات</a><a class="dropdown-item" href="{{ route('admin.users') }}">اکاؤنٹ کی تفصیل</a><div class="dropdown-divider"></div>@else<a class="dropdown-item" href="{{ route('employee.password.edit') }}">پاس ورڈ تبدیل کریں</a><div class="dropdown-divider"></div>@endif
        <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">{{ $isSuperAdmin ? 'Logout' : 'لاگ آؤٹ' }}</a><form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div></li></ul>@endauth
    </div>
</nav></div></header>
@include('inc.sidebar')
