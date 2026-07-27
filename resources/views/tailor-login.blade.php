<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>درزی لاگ اِن — TMS</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        :root{--navy:#102a43;--blue:#1769e0;--cyan:#20b8cd}
        *{box-sizing:border-box}body{min-height:100vh;margin:0;background:radial-gradient(circle at 80% 10%,#dff5fb 0,transparent 32%),#f4f7fb;color:#243b53;direction:rtl;text-align:right;font-family:"Noto Nastaliq Urdu","Noto Sans Arabic",Tahoma,Arial,sans-serif}
        .login-shell{min-height:100vh;display:grid;grid-template-columns:minmax(340px,.9fr) minmax(0,1.1fr)}
        .login-panel{display:flex;align-items:center;justify-content:center;padding:2rem}.login-card{width:100%;max-width:460px;padding:2.5rem;background:#fff;border:1px solid #e3eaf2;border-radius:24px;box-shadow:0 24px 70px rgba(31,61,93,.14)}
        .brand{display:flex;align-items:center;gap:.75rem;margin-bottom:2rem;color:var(--navy);font-weight:800}.brand-mark{width:46px;height:46px;display:grid;place-items:center;border-radius:14px;background:linear-gradient(135deg,var(--cyan),#eaffff);color:#0b4778;font-size:1.25rem}.brand small{display:block;color:#67809a;font-size:.7rem}
        h1{margin:0 0 .6rem;color:var(--navy);font-size:2rem;font-weight:800}.subtitle{margin-bottom:1.8rem;color:#6b7c91;line-height:1.8}.form-control{min-height:52px;border-radius:12px;border-color:#d9e2ec;text-align:right}.form-control:focus{border-color:#3b82f6;box-shadow:0 0 0 4px rgba(59,130,246,.1)}label{font-weight:700}.btn-login{min-height:52px;border:0;border-radius:12px;background:linear-gradient(135deg,var(--blue),#1597bd);font-weight:800;box-shadow:0 12px 28px rgba(23,105,224,.23)}
        .story{position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:center;padding:clamp(3rem,7vw,7rem);color:#fff;background:linear-gradient(145deg,#0b2440,#124c86 55%,#167ca8)}.story:after{content:"";position:absolute;width:500px;height:500px;left:-220px;bottom:-220px;border-radius:50%;background:rgba(255,255,255,.07)}.story>*{position:relative;z-index:1}.story h2{font-size:clamp(2.6rem,5vw,4.8rem);line-height:1.18;font-weight:800}.story p{max-width:600px;color:#d5e9f4;font-size:1.05rem;line-height:2}.feature{display:flex;align-items:center;gap:.8rem;margin-top:1.1rem;color:#d9f4f5}.feature span{width:30px;height:30px;display:grid;place-items:center;border-radius:9px;background:rgba(66,224,230,.18);color:#80f4ef;font-weight:800}
        @media(max-width:900px){.login-shell{grid-template-columns:1fr}.story{display:none}.login-panel{min-height:100vh;padding:1.25rem}.login-card{padding:1.6rem}}
    </style>
</head>
<body>
<main class="login-shell">
    <section class="login-panel">
        <div class="login-card">
            <div class="brand"><span class="brand-mark">✂</span><span>TMS<small>درزی کا محفوظ پورٹل</small></span></div>
            <h1>درزی لاگ اِن</h1>
            <p class="subtitle">اپنے تفویض شدہ کام، مراحل اور اجرت دیکھنے کے لیے لاگ اِن کریں۔</p>

            @if (session('failed'))
                <div class="alert alert-danger">{{ session('failed') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pr-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form action="{{ url('tailor-login') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="shop_code">دکان کا کوڈ</label>
                    <input id="shop_code" type="text" class="form-control text-uppercase" name="shop_code" value="{{ old('shop_code') }}" maxlength="30" autocomplete="organization" placeholder="مثلاً TMS-000001" dir="ltr" required autofocus>
                    <small class="form-text text-muted">یہ کوڈ دکان کے مالک سے حاصل کریں۔</small>
                </div>
                <div class="form-group">
                    <label for="contact">فون نمبر</label>
                    <input id="contact" type="tel" inputmode="tel" class="form-control" name="contact" value="{{ old('contact') }}" maxlength="50" autocomplete="tel" placeholder="مثلاً 03001234567" required>
                </div>
                <div class="form-group">
                    <label for="password">پاس ورڈ</label>
                    <input id="password" type="password" class="form-control" name="password" autocomplete="current-password" placeholder="اپنا پاس ورڈ درج کریں" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-login">لاگ اِن کریں</button>
            </form>
        </div>
    </section>
    <section class="story" aria-label="درزی پورٹل کی سہولیات">
        <h2>آپ کا کام۔<br>آپ کی کمائی۔<br>صاف حساب۔</h2>
        <p>صرف اپنے تفویض شدہ آرڈرز دیکھیں، سلائی کے مراحل اپ ڈیٹ کریں اور اپنی کمائی و ادائیگی کا ریکارڈ جانیں۔</p>
        <div class="feature"><span>1</span> صرف آپ کو تفویض شدہ کام</div>
        <div class="feature"><span>2</span> مرحلہ وار پیش رفت</div>
        <div class="feature"><span>3</span> اجرت اور ادائیگی کا واضح ریکارڈ</div>
    </section>
</main>
</body>
</html>
