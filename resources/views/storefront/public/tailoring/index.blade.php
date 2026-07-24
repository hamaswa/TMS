<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ٹیلرنگ خدمات — {{ $storefront->display_name }}</title>
    <style>
        @font-face{font-family:Nastaliq;src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        *{box-sizing:border-box}body{margin:0;background:#f5f6f8;color:#23364d;font-family:Nastaliq,Tahoma,sans-serif}.shell{width:min(1120px,calc(100% - 28px));margin:auto}.nav{background:#fff;border-bottom:1px solid #e0e5eb}.nav .shell{min-height:70px;display:flex;align-items:center;justify-content:space-between}.nav a{color:#324f7c;text-decoration:none;border:1px solid #c5d0df;border-radius:10px;padding:7px 13px}
        .hero{background:linear-gradient(135deg,#253d63,#5572a2);color:#fff;padding:58px 0}.hero h1{font-size:clamp(2.1rem,5vw,3.8rem);margin:0}.hero p{color:#e2e9f3}.section{padding:45px 0}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.card{background:#fff;border:1px solid #e0e5eb;border-radius:20px;padding:22px;box-shadow:0 10px 28px rgba(36,57,88,.07)}.card h2{margin:7px 0}.label{color:#486997;font-weight:800}.price{font-size:1.15rem;font-weight:900;margin:12px 0}.meta{display:flex;gap:7px;flex-wrap:wrap}.pill{background:#edf1f6;border-radius:999px;padding:4px 10px}.link{display:block;text-align:center;background:#314f7e;color:#fff;text-decoration:none;border-radius:10px;padding:9px;margin-top:15px}
        .inquiry-wrap{background:#e9eef5}.inquiry-grid{display:grid;grid-template-columns:.8fr 1.25fr;gap:30px}.form{background:#fff;border-radius:22px;padding:25px;box-shadow:0 12px 30px rgba(36,57,88,.08)}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.group label{display:block;font-weight:800;margin-bottom:5px}.control{width:100%;border:1px solid #c9d2df;border-radius:10px;padding:10px 12px;font:inherit;background:#fff}.wide{grid-column:1/-1}.btn{border:0;border-radius:11px;background:#314f7e;color:#fff;padding:11px 22px;font:inherit;cursor:pointer}.success{background:#dff3e8;color:#185c38;border-radius:12px;padding:13px;margin-bottom:18px}.errors{background:#f8e2e2;color:#8c2f2f;border-radius:12px;padding:13px;margin-bottom:18px}.hp{position:fixed;top:-10000px;width:1px;height:1px;overflow:hidden}
        @media(max-width:850px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.inquiry-grid{grid-template-columns:1fr}}@media(max-width:600px){.grid,.form-grid{grid-template-columns:1fr}.wide{grid-column:auto}.hero{padding:38px 0}.nav .shell{min-height:62px}}
    </style>
</head>
<body>
<nav class="nav"><div class="shell"><strong>{{ $storefront->display_name }}</strong><a href="{{ route('storefront.show',$storefront) }}">دکان کا تعارف</a></div></nav>
<header class="hero"><div class="shell"><h1>ٹیلرنگ خدمات</h1><p>خدمات، ابتدائی قیمت اور متوقع تیاری کا وقت دیکھیں۔ حتمی قیمت کپڑے، ڈیزائن اور پیمائش کی تصدیق کے بعد طے ہوگی۔</p></div></header>
<main>
<section class="section"><div class="shell"><div class="grid">
    @forelse($services as $service)<article class="card">@if($service->is_featured)<div class="label">منتخب خدمت</div>@endif<h2>{{ $service->name }}</h2><p>{{ \Illuminate\Support\Str::limit($service->description,150) ?: 'تفصیل کے لیے خدمت کھولیں یا دکان سے رابطہ کریں۔' }}</p><div class="meta">@if($service->price_from!==null)<span class="pill">Rs {{ number_format($service->price_from,2) }} سے · {{ $service->price_unit }}</span>@endif @if($service->estimated_days)<span class="pill">{{ $service->estimated_days }} دن تقریباً</span>@endif</div><a class="link" href="{{ route('storefront.tailoring.show',[$storefront,$service]) }}">مکمل تفصیل</a></article>
    @empty<div class="card" style="grid-column:1/-1;text-align:center"><h2>خدمات جلد شامل ہوں گی</h2><p>مزید معلومات کے لیے دکان سے رابطہ کریں۔</p></div>@endforelse
</div></div></section>
@if($storefront->inquiries_enabled)
<section class="section inquiry-wrap" id="inquiry"><div class="shell inquiry-grid"><div><h2>سلائی کے بارے میں درخواست بھیجیں</h2><p>اپنی بنیادی معلومات اور مطلوبہ خدمت بتائیں۔ یہ صرف ابتدائی درخواست ہے؛ آرڈر اور قیمت دکان کی تصدیق کے بعد بنے گی۔</p>@if($storefront->public_phone)<p><strong>فون:</strong> <span dir="ltr">{{ $storefront->public_phone }}</span></p>@endif</div><div class="form">
    @if(session('inquiry_success'))<div class="success">{{ session('inquiry_success') }}</div>@endif
    @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('storefront.inquiries.store',$storefront) }}">@csrf
        <div class="hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-grid">
            <div class="group"><label for="customer_name">نام</label><input id="customer_name" name="customer_name" required maxlength="150" class="control" value="{{ old('customer_name') }}"></div>
            <div class="group"><label for="phone">فون نمبر</label><input id="phone" name="phone" required minlength="7" maxlength="50" dir="ltr" class="control" value="{{ old('phone') }}"></div>
            <div class="group"><label for="service">مطلوبہ خدمت</label><select id="service" name="tailoring_service_id" class="control"><option value="">عمومی معلومات</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected((string)old('tailoring_service_id',request('service'))===(string)$service->id)>{{ $service->name }}</option>@endforeach</select></div>
            <div class="group"><label for="preferred_date">پسندیدہ تاریخ</label><input id="preferred_date" type="date" min="{{ now()->toDateString() }}" name="preferred_date" class="control" value="{{ old('preferred_date') }}"></div>
            <div class="group"><label for="email">ای میل <span>(اختیاری)</span></label><input id="email" type="email" name="email" maxlength="150" dir="ltr" class="control" value="{{ old('email') }}"></div>
            <div class="group"><label for="city">شہر <span>(اختیاری)</span></label><input id="city" name="city" maxlength="100" class="control" value="{{ old('city') }}"></div>
            <div class="group wide">
                <label for="tailoring_payment_method">متوقع ادائیگی کا طریقہ</label>
                <select id="tailoring_payment_method" name="payment_method" class="control">
                    @foreach(\App\Models\StorefrontInquiry::paymentMethods() as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_method','unpaid')===$value)>{{ $label }}</option>
                    @endforeach
                </select>
                <small>یہ صرف ترجیح ہے۔ حتمی قیمت اور ادائیگی دکان کی تصدیق کے بعد ہوگی۔</small>
            </div>
            <div id="tailoring-easypaisa-fields" class="group wide">
                <div class="form-grid">
                    <div class="group"><label for="tailoring_payment_phone">ایزی پیسہ بھیجنے والا نمبر</label><input id="tailoring_payment_phone" name="payment_sender_phone" dir="ltr" maxlength="50" class="control" value="{{ old('payment_sender_phone') }}"></div>
                    <div class="group"><label for="tailoring_payment_reference">ٹرانزیکشن آئی ڈی / حوالہ</label><input id="tailoring_payment_reference" name="payment_reference" dir="ltr" maxlength="100" class="control" value="{{ old('payment_reference') }}"></div>
                </div>
            </div>
            <div class="group wide"><label for="message">تفصیل یا سوال</label><textarea id="message" name="message" maxlength="3000" rows="4" class="control">{{ old('message') }}</textarea></div>
            <div class="wide"><button class="btn">درخواست بھیجیں</button></div>
        </div>
    </form>
</div></div></section>
@endif
</main>
<script>
    (() => {
        const method = document.getElementById('tailoring_payment_method');
        const fields = document.getElementById('tailoring-easypaisa-fields');
        const phone = document.getElementById('tailoring_payment_phone');
        const reference = document.getElementById('tailoring_payment_reference');
        const refresh = () => {
            const visible = method?.value === 'easypaisa';
            if (fields) fields.hidden = !visible;
            if (phone) phone.required = visible;
            if (reference) reference.required = visible;
        };
        method?.addEventListener('change', refresh);
        refresh();
    })();
</script>
</body>
</html>
