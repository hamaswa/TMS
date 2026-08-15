@if(Auth::check() && Auth::user()->isBusinessMember() && !Session::get('tailor'))
<style>
    :root{--tms-sidebar-rail:76px;--tms-sidebar-open:270px}
    .tms-sidebar{position:fixed;z-index:1025;top:0;right:0;bottom:0;width:var(--tms-sidebar-rail);overflow:hidden;background:linear-gradient(180deg,#0c2b54 0%,#0a2345 100%);color:#fff;box-shadow:-8px 0 30px rgba(13,42,79,.16);transition:width .22s ease}
    .tms-sidebar:hover,.tms-sidebar:focus-within{width:var(--tms-sidebar-open)}
    .tms-sidebar-brand{height:72px;display:flex;align-items:center;gap:13px;padding:0 20px;border-bottom:1px solid rgba(255,255,255,.1);text-decoration:none!important;color:#fff!important;white-space:nowrap}
    .tms-sidebar-brand-icon{display:grid;place-items:center;min-width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,#2578ff,#44c7ef);font-size:18px}
    .tms-sidebar-brand-text{font:800 25px/1 Arial,sans-serif;opacity:0;transform:translateX(8px);transition:.18s ease}
    .tms-sidebar:hover .tms-sidebar-brand-text,.tms-sidebar:focus-within .tms-sidebar-brand-text{opacity:1;transform:none}
    .tms-sidebar-scroll{height:calc(100vh - 72px);overflow-y:auto;overflow-x:hidden;padding:14px 10px 28px;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.2) transparent}
    .tms-side-link{position:relative;display:flex;align-items:center;gap:14px;min-height:48px;margin:3px 0;padding:8px 13px;border-radius:10px;color:rgba(255,255,255,.78)!important;text-decoration:none!important;white-space:nowrap;transition:.18s ease}
    .tms-side-link:hover{background:rgba(255,255,255,.09);color:#fff!important}
    .tms-side-link.is-active{background:linear-gradient(135deg,#1769ef,#2f87ff);color:#fff!important;box-shadow:0 8px 20px rgba(0,92,230,.28)}
    .tms-side-icon{display:grid;place-items:center;min-width:30px;height:30px;font-size:17px}
    .tms-side-label{opacity:0;transform:translateX(8px);transition:.18s ease}
    .tms-side-caret{margin-right:auto;font-size:11px;opacity:0;transition:.18s ease}
    .tms-sidebar:hover .tms-side-label,.tms-sidebar:focus-within .tms-side-label,.tms-sidebar:hover .tms-side-caret,.tms-sidebar:focus-within .tms-side-caret{opacity:1;transform:none}
    .tms-side-section{height:1px;margin:12px 8px;background:rgba(255,255,255,.1)}
    .tms-side-submenu{display:none;margin:2px 43px 8px 0;padding-right:10px;border-right:1px solid rgba(124,180,255,.45)}
    .tms-sidebar:hover .tms-side-submenu.is-open,.tms-sidebar:focus-within .tms-side-submenu.is-open{display:block}
    .tms-side-submenu a{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:8px;color:rgba(255,255,255,.68)!important;text-decoration:none!important;white-space:nowrap}
    .tms-side-submenu a i{width:17px;text-align:center;color:#7ec8ff;transition:.18s ease}
    .tms-side-submenu a:hover i,.tms-side-submenu a.is-active i{color:#fff}
    .tms-side-submenu a:hover,.tms-side-submenu a.is-active{background:rgba(49,128,255,.18);color:#fff!important}
    @media (min-width:1200px){
        body{padding-right:var(--tms-sidebar-rail)}
        .tms-nav{margin-right:0}
        .tms-nav #tmsNavigation>.navbar-nav.mr-auto{display:none!important}
    }
    @media (max-width:1199.98px){.tms-sidebar{display:none}}
</style>

<aside class="tms-sidebar" aria-label="مرکزی نیویگیشن">
    <a class="tms-sidebar-brand" href="{{ $activeWorkspace ? route('admin.workspace.current') : route('admin.home') }}">
        <span class="tms-sidebar-brand-icon"><i class="fas fa-cut"></i></span><span class="tms-sidebar-brand-text">TMSX</span>
    </a>
    <nav class="tms-sidebar-scroll">
        <a class="tms-side-link {{ request()->routeIs('admin.home','admin.workspace.current') ? 'is-active' : '' }}" href="{{ $activeWorkspace ? route('admin.workspace.current') : route('admin.home') }}"><span class="tms-side-icon"><i class="fas fa-home"></i></span><span class="tms-side-label">ڈیش بورڈ</span></a>

        @if(Auth::user()->hasModule('clothing') && (! $hasMultipleWorkspaces || $activeWorkspace === 'clothing'))
            @if($canShopPurchases)
                <a class="tms-side-link {{ request()->routeIs('admin.purchases.*') ? 'is-active' : '' }}" href="{{ route('admin.purchases.index') }}"><span class="tms-side-icon"><i class="fas fa-shopping-cart"></i></span><span class="tms-side-label">خریداریاں</span><i class="fas fa-chevron-down tms-side-caret"></i></a>
                <div class="tms-side-submenu {{ request()->routeIs('admin.purchases.*') ? 'is-open' : '' }}">
                    <a class="{{ request()->routeIs('admin.purchases.index','admin.purchases.show') ? 'is-active' : '' }}" href="{{ route('admin.purchases.index') }}"><i class="fas fa-list-ul"></i>خریداری کی فہرست</a>
                    <a class="{{ request()->routeIs('admin.purchases.create') ? 'is-active' : '' }}" href="{{ route('admin.purchases.create') }}"><i class="fas fa-plus-circle"></i>نئی خریداری</a>
                </div>
            @endif
            @if($canShopSuppliers)<a class="tms-side-link {{ request()->routeIs('admin.suppliers.*') ? 'is-active' : '' }}" href="{{ route('admin.suppliers.index') }}"><span class="tms-side-icon"><i class="fas fa-truck"></i></span><span class="tms-side-label">سپلائرز</span></a>@endif
            @if($canShopInventory)
                <a class="tms-side-link {{ request()->routeIs('admin.stock.*') ? 'is-active' : '' }}" href="{{ route('admin.stock.index') }}"><span class="tms-side-icon"><i class="fas fa-box-open"></i></span><span class="tms-side-label">انوینٹری</span></a>

                <a class="tms-side-link {{ request()->routeIs('admin.cloth.*','admin.clothtype.*','admin.clothbrand.*','admin.inventory-ledger.*','admin.inventory-valuation.*') ? 'is-active' : '' }}" href="{{ route('admin.cloth.index') }}"><span class="tms-side-icon"><i class="fas fa-layer-group"></i></span><span class="tms-side-label">کپڑے کی انوینٹری</span><i class="fas fa-chevron-down tms-side-caret"></i></a>
                <div class="tms-side-submenu {{ request()->routeIs('admin.cloth.*','admin.clothtype.*','admin.clothbrand.*','admin.inventory-ledger.*','admin.inventory-valuation.*') ? 'is-open' : '' }}">
                    <a class="{{ request()->routeIs('admin.cloth.index','admin.cloth.show') ? 'is-active' : '' }}" href="{{ route('admin.cloth.index') }}"><i class="fas fa-th-list"></i>کپڑوں کی فہرست</a>
                    <a class="{{ request()->routeIs('admin.cloth.create') ? 'is-active' : '' }}" href="{{ route('admin.cloth.create') }}"><i class="fas fa-plus-circle"></i>نیا کپڑا شامل کریں</a>
                    <a class="{{ request()->routeIs('admin.clothtype.*') ? 'is-active' : '' }}" href="{{ route('admin.clothtype.index') }}"><i class="fas fa-tags"></i>کپڑے کی اقسام</a>
                    <a class="{{ request()->routeIs('admin.clothbrand.*') ? 'is-active' : '' }}" href="{{ route('admin.clothbrand.index') }}"><i class="fas fa-copyright"></i>کپڑے کے برانڈز</a>
                    <a class="{{ request()->routeIs('admin.inventory-ledger.*') ? 'is-active' : '' }}" href="{{ route('admin.inventory-ledger.index') }}"><i class="fas fa-exchange-alt"></i>اسٹاک کھاتہ</a>
                    <a class="{{ request()->routeIs('admin.inventory-valuation.*') ? 'is-active' : '' }}" href="{{ route('admin.inventory-valuation.index') }}"><i class="fas fa-balance-scale"></i>اسٹاک کی مالیت</a>
                </div>
            @endif
            @if($canShopSales)<a class="tms-side-link {{ request()->routeIs('admin.sellCloth','admin.sales.*') ? 'is-active' : '' }}" href="{{ route('admin.sellCloth') }}"><span class="tms-side-icon"><i class="fas fa-dollar-sign"></i></span><span class="tms-side-label">فروخت</span></a>@endif
            @if($canShopSales && Auth::user()->business?->storefront)<a class="tms-side-link {{ request()->routeIs('admin.storefront.orders.*') ? 'is-active' : '' }}" href="{{ route('admin.storefront.orders.index') }}"><span class="tms-side-icon"><i class="fas fa-shopping-bag"></i></span><span class="tms-side-label">آن لائن آرڈرز</span></a>@endif
            @if(Auth::user()->hasBusinessPermission('storefront.manage'))<a class="tms-side-link {{ request()->routeIs('admin.storefront.edit','admin.storefront.update') ? 'is-active' : '' }}" href="{{ route('admin.storefront.edit') }}"><span class="tms-side-icon"><i class="fas fa-globe-asia"></i></span><span class="tms-side-label">آن لائن دکان</span></a>@endif
        @endif

        @if(Auth::user()->hasModule('tailoring') && (! $hasMultipleWorkspaces || $activeWorkspace === 'tailoring'))
            @if($canTailorOrders)<a class="tms-side-link {{ request()->routeIs('admin.order.*') ? 'is-active' : '' }}" href="{{ route('admin.order.total') }}"><span class="tms-side-icon"><i class="fas fa-clipboard-list"></i></span><span class="tms-side-label">ٹیلرنگ آرڈرز</span></a>@endif
            @if($canTailorWorkshop)<a class="tms-side-link {{ request()->routeIs('admin.tailor-jobs.*') ? 'is-active' : '' }}" href="{{ route('admin.tailor-jobs.index') }}"><span class="tms-side-icon"><i class="fas fa-tasks"></i></span><span class="tms-side-label">ورکشاپ</span></a>@endif
            @if($canTailorCustomers)<a class="tms-side-link {{ request()->routeIs('admin.Customers.*') ? 'is-active' : '' }}" href="{{ route('admin.Customers.index') }}"><span class="tms-side-icon"><i class="fas fa-user-friends"></i></span><span class="tms-side-label">گاہک</span></a>@endif

            @if($canTailorTailors)
                <a class="tms-side-link {{ request()->routeIs('admin.Tailor.*','admin.production-workers.*','admin.production-work-types.*','admin.tailor-orders','admin.tailor-report','admin.report-print','admin.tailor-rates*','admin.tailor.*') ? 'is-active' : '' }}" href="{{ route('admin.Tailor.index') }}">
                    <span class="tms-side-icon"><i class="fas fa-user-cog"></i></span>
                    <span class="tms-side-label">درزی اور کاریگر</span>
                    <i class="fas fa-chevron-down tms-side-caret"></i>
                </a>
                <div class="tms-side-submenu {{ request()->routeIs('admin.Tailor.*','admin.production-workers.*','admin.production-work-types.*','admin.tailor-orders','admin.tailor-report','admin.report-print','admin.tailor-rates*','admin.tailor.*') ? 'is-open' : '' }}">
                    <a class="{{ request()->routeIs('admin.Tailor.*','admin.tailor-orders','admin.tailor-report','admin.report-print','admin.tailor-rates*','admin.tailor.*') ? 'is-active' : '' }}" href="{{ route('admin.Tailor.index') }}"><i class="fas fa-user-tie"></i>درزیوں کی فہرست</a>
                    <a class="{{ request()->routeIs('admin.production-workers.*','admin.production-work-types.*') ? 'is-active' : '' }}" href="{{ route('admin.production-workers.index') }}"><i class="fas fa-users"></i>پروڈکشن ورکرز اور اجرت</a>
                </div>
            @endif

            @if($canTailorConfiguration)
                <a class="tms-side-link {{ request()->routeIs('admin.OptionType.*','admin.Options.*','admin.options.*','admin.measurement-templates.*','admin.measurement-fields.*','admin.design.*') ? 'is-active' : '' }}" href="{{ route('admin.OptionType.index') }}">
                    <span class="tms-side-icon"><i class="fas fa-sliders-h"></i></span>
                    <span class="tms-side-label">ٹیلرنگ ترتیب</span>
                    <i class="fas fa-chevron-down tms-side-caret"></i>
                </a>
                <div class="tms-side-submenu {{ request()->routeIs('admin.OptionType.*','admin.Options.*','admin.options.*','admin.measurement-templates.*','admin.measurement-fields.*','admin.design.*') ? 'is-open' : '' }}">
                    <a class="{{ request()->routeIs('admin.OptionType.*','admin.Options.*','admin.options.*') ? 'is-active' : '' }}" href="{{ route('admin.OptionType.index') }}"><i class="fas fa-ruler-combined"></i>پیمائش اور سلائی کے اختیارات</a>
                    <a class="{{ request()->routeIs('admin.measurement-templates.*') ? 'is-active' : '' }}" href="{{ route('admin.measurement-templates.index') }}"><i class="fas fa-clipboard-list"></i>پیمائش ٹیمپلیٹس</a>
                    <a class="{{ request()->routeIs('admin.measurement-fields.*') ? 'is-active' : '' }}" href="{{ route('admin.measurement-fields.index') }}"><i class="fas fa-ruler"></i>اضافی پیمائش خانے</a>
                </div>
            @endif
        @endif

        <div class="tms-side-section"></div>
        @if(Auth::user()->hasBusinessPermission('finance.view'))<a class="tms-side-link {{ request()->routeIs('admin.financial-reports.*') ? 'is-active' : '' }}" href="{{ route('admin.financial-reports.index') }}"><span class="tms-side-icon"><i class="fas fa-chart-bar"></i></span><span class="tms-side-label">رپورٹس</span></a>@endif
        @if(Auth::user()->hasBusinessPermission('team.manage'))<a class="tms-side-link {{ request()->routeIs('admin.team.*') ? 'is-active' : '' }}" href="{{ route('admin.team.index') }}"><span class="tms-side-icon"><i class="fas fa-users-cog"></i></span><span class="tms-side-label">صارفین</span></a>@endif
        @if(Auth::user()->isBusinessOwner())<a class="tms-side-link {{ request()->routeIs('admin.setting.*') ? 'is-active' : '' }}" href="{{ route('admin.setting.index') }}"><span class="tms-side-icon"><i class="fas fa-cog"></i></span><span class="tms-side-label">سیٹنگز</span></a>@endif
        @if($hasMultipleWorkspaces)<a class="tms-side-link" href="{{ route('admin.home') }}"><span class="tms-side-icon"><i class="fas fa-random"></i></span><span class="tms-side-label">ورک اسپیس تبدیل کریں</span></a>@endif
    </nav>
</aside>
@endif
