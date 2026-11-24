@extends('main')

@section('content')
    @php($activeSetting = $settings->firstWhere('status', 1) ?: $settings->first())
    @include('setting.partials.styles')
    <section class="main-content st-page">
        <div class="st-shell">
            <header class="st-hero">
                <span class="st-hero-icon"><i class="fas fa-cog"></i></span>
                <div class="st-hero-text">
                    <h1>کاروباری سیٹنگز</h1>
                    <p>دکان کی شناخت، رسید اور پرنٹ میں استعمال ہونے والی بنیادی معلومات</p>
                </div>
                @if ($activeSetting)
                    <a href="{{ route('admin.edit-setting', $activeSetting->id) }}" class="st-hero-action"><i class="fas fa-pen"></i> معلومات تبدیل کریں</a>
                @else
                    <a href="{{ route('admin.add-setting') }}" class="st-hero-action"><i class="fas fa-plus"></i> دکان کی معلومات شامل کریں</a>
                @endif
            </header>
            @foreach (['success' => 'success', 'update' => 'success', 'delete' => 'warning', 'error' => 'danger'] as $key => $class)
                @if (session($key))<div class="alert alert-{{ $class }}">{{ session($key) }}</div>@endif
            @endforeach

            <div class="st-grid">
                <div>
                    @if ($business?->shop_code)
                        <div class="st-code">
                            <div><strong>درزی پورٹل کا دکان کوڈ</strong><div class="small mt-1">یہ کوڈ درزی کو اس کے فون نمبر اور پاس ورڈ کے ساتھ دیں۔</div></div>
                            <code dir="ltr">{{ $business->shop_code }}</code>
                        </div>
                    @endif

                    @if ($activeSetting)
                        <section class="st-card">
                            <div class="st-card-head">
                                <h2 class="st-card-title"><i class="far fa-id-card"></i> موجودہ کاروباری پروفائل</h2>
                                <span class="st-badge"><i class="fas fa-check-circle"></i> فعال</span>
                            </div>
                            <div class="st-profile">
                                <div class="st-logo">
                                    @if ($activeSetting->logo_url)
                                        <img src="{{ $activeSetting->logo_url }}" alt="{{ $activeSetting->name }} لوگو">
                                    @else
                                        <div><i class="fas fa-store fa-2x"></i><div class="small mt-2">لوگو شامل نہیں</div></div>
                                    @endif
                                </div>
                                <div class="st-tiles">
                                    <div class="st-tile"><i class="far fa-user"></i><div><small>کاروبار کا نام</small><strong>{{ $activeSetting->name }}</strong></div></div>
                                    <div class="st-tile"><i class="fas fa-phone-alt"></i><div><small>رابطہ نمبر</small><strong dir="ltr">{{ $activeSetting->contact_no ?: 'درج نہیں' }}</strong></div></div>
                                    <div class="st-tile is-wide"><i class="fas fa-map-marker-alt"></i><div><small>پتہ</small><strong>{{ $activeSetting->address ?: 'درج نہیں' }}</strong></div></div>
                                    <div class="st-tile is-wide"><i class="far fa-file-alt"></i><div><small>رسید / کاروباری نوٹ</small><strong>{{ $activeSetting->note ?: 'درج نہیں' }}</strong></div></div>
                                    <div class="st-tile"><i class="fas fa-ruler"></i><div><small>پرنٹ سائز</small><strong>{{ \App\Models\Setting::printPaperSizes()[$activeSetting->print_paper_size] ?? $activeSetting->print_paper_size }}</strong></div></div>
                                    <div class="st-tile"><i class="fas fa-qrcode"></i><div><small>QR حوالہ</small><strong>{{ $activeSetting->print_show_qr ? 'رسید پر دکھائیں' : 'نہ دکھائیں' }}</strong></div></div>
                                </div>
                            </div>
                        </section>
                    @else
                        <section class="st-card">
                            <div class="st-empty"><i class="fas fa-store"></i><strong>دکان کی معلومات ابھی شامل نہیں ہوئیں۔</strong><div class="mt-2">رسید اور آرڈر پرنٹ کے لیے کاروباری پروفائل بنائیں۔</div></div>
                        </section>
                    @endif

                    @if ($settings->count() > 1)
                        <section class="st-card">
                            <div class="st-card-head">
                                <h2 class="st-card-title"><i class="fas fa-archive"></i> غیر فعال سابقہ پروفائلز</h2>
                                <span class="text-muted small">صرف ضرورت کے وقت استعمال کریں</span>
                            </div>
                            <details>
                                <summary class="font-weight-bold" style="cursor:pointer">{{ $settings->count() - 1 }} سابقہ پروفائل دکھائیں</summary>
                                <div class="mt-3">
                                    @foreach ($settings->where('id', '!=', $activeSetting?->id) as $setting)
                                        <div class="st-archive">
                                            <div><strong>{{ $setting->name }}</strong><div class="small text-muted">{{ $setting->contact_no ?: 'رابطہ درج نہیں' }}</div></div>
                                            <div class="st-archive-actions">
                                                <form action="{{ route('admin.active-setting', $setting->id) }}" method="POST" data-confirm="کیا یہ پروفائل فعال کرنا ہے؟">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">فعال کریں</button></form>
                                                <a href="{{ route('admin.edit-setting', $setting->id) }}" class="btn btn-sm btn-light">ترمیم</a>
                                                <form action="{{ route('admin.delete-setting', $setting->id) }}" method="POST" data-confirm="کیا یہ سابقہ پروفائل حذف کرنا ہے؟">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">حذف</button></form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </section>
                    @endif
                </div>
                @include('setting.partials.side')
            </div>
        </div>
    </section>
@endsection
