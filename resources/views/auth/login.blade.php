@extends('layouts.app')
@section('body_class', 'auth-page')
@section('html_lang', 'ur')
@section('html_dir', 'rtl')

@push('styles')
<style>
    :root{--navy:#102a43;--blue:#1769e0;--cyan:#20b8cd;--muted:#64748b}
    .auth-page{min-height:100vh;background:#f4f7fb;color:#1f2937;direction:rtl;text-align:right;font-family:"Noto Nastaliq Urdu","Noto Sans Arabic",Tahoma,Arial,sans-serif}.auth-page .app-navbar{display:none}.auth-page .app-main{padding:0!important}
    .auth-shell{min-height:100vh;display:grid;grid-template-columns:minmax(0,1.08fr) minmax(420px,.92fr)}
    .auth-story{position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between;padding:clamp(2.5rem,6vw,5.5rem);color:#fff;background:linear-gradient(145deg,#0b2440 0%,#124c86 55%,#167ca8 100%)}
    .auth-story:before,.auth-story:after{content:"";position:absolute;border-radius:50%;pointer-events:none}.auth-story:before{width:540px;height:540px;right:-230px;top:-230px;background:rgba(44,211,225,.14);border:1px solid rgba(255,255,255,.14)}.auth-story:after{width:360px;height:360px;left:-170px;bottom:-170px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12)}
    .auth-brand,.auth-copy,.auth-flow{position:relative;z-index:1}.auth-brand{display:flex;align-items:center;gap:.85rem;font-weight:800;letter-spacing:.02em}.auth-brand small{display:block;font-size:.7rem;font-weight:600;letter-spacing:.13em;text-transform:uppercase;color:#b8e9f4}
    .brand-mark{width:46px;height:46px;border-radius:14px;display:grid;place-items:center;background:linear-gradient(135deg,#3de0e7,#fff);color:#0b4778;box-shadow:0 12px 30px rgba(0,0,0,.18)}.brand-mark svg{width:26px;height:26px}
    .auth-copy{max-width:630px;margin:4rem 0 3rem}.eyebrow{display:inline-flex;align-items:center;gap:.55rem;padding:.42rem .75rem;border:1px solid rgba(255,255,255,.2);border-radius:999px;background:rgba(255,255,255,.08);font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.eyebrow i{width:7px;height:7px;border-radius:50%;background:#51e3bd;box-shadow:0 0 0 5px rgba(81,227,189,.12)}
    .auth-story h1{margin:1.4rem 0 1rem;font-size:clamp(2.5rem,5vw,4.75rem);line-height:1.04;letter-spacing:-.045em;font-weight:800}.auth-copy>p{margin:0;max-width:560px;color:#d3e8f4;font-size:clamp(1rem,1.5vw,1.16rem);line-height:1.75}
    .auth-flow{display:grid;grid-template-columns:repeat(4,1fr);gap:.65rem;max-width:650px}.flow-step{padding:1rem .8rem;border:1px solid rgba(255,255,255,.15);border-radius:15px;background:rgba(255,255,255,.08);backdrop-filter:blur(8px)}.flow-step span{display:block;width:27px;height:27px;margin-bottom:.65rem;border-radius:9px;background:rgba(63,221,226,.18);color:#7df0ec;text-align:center;line-height:27px;font-size:.7rem;font-weight:800}.flow-step strong,.flow-step small{display:block}.flow-step strong{font-size:.82rem}.flow-step small{color:#b9d8e8;font-size:.68rem}
    .auth-entry{display:flex;align-items:center;justify-content:center;padding:clamp(1.5rem,5vw,5rem);background:radial-gradient(circle at 80% 10%,#e6f4ff 0,transparent 30%),#f7f9fc}.auth-card{width:100%;max-width:470px;padding:clamp(1.75rem,4vw,3rem);background:rgba(255,255,255,.95);border:1px solid #e5eaf1;border-radius:26px;box-shadow:0 24px 70px rgba(31,61,93,.13)}
    .mobile-brand{display:none;margin-bottom:2rem;color:var(--navy)}.market-home{min-height:44px;display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1rem;color:var(--blue);font-size:.82rem;font-weight:800;text-decoration:none}.market-home:hover{text-decoration:underline}.auth-card h2{margin:0 0 .55rem;color:var(--navy);font-size:2rem;font-weight:800;letter-spacing:-.035em}.auth-subtitle{margin-bottom:2rem;color:var(--muted);line-height:1.6}.auth-label{margin-bottom:.5rem;color:#334155;font-size:.82rem;font-weight:700}
    .auth-control{position:relative}.auth-control svg{position:absolute;right:1rem;top:27px;transform:translateY(-50%);width:19px;height:19px;color:#8a9aae;pointer-events:none}.auth-control .form-control{min-height:54px;padding:.8rem 3rem .8rem 1rem;border:1px solid #d9e1eb;border-radius:13px;background:#fbfcfe;color:#172033;box-shadow:none;transition:.2s;text-align:right}.auth-control .form-control:focus{border-color:#3b82f6;background:#fff;box-shadow:0 0 0 4px rgba(59,130,246,.11)}
    .auth-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin:1.25rem 0 1.5rem;font-size:.86rem}.auth-row .form-check{min-height:44px;display:flex;align-items:center;gap:.45rem;margin:0}.auth-row .form-check-input{margin:0;width:1.25rem;height:1.25rem}.auth-row .form-check-label{min-height:44px;display:inline-flex;align-items:center}.auth-link{min-height:44px;display:inline-flex;align-items:center;color:var(--blue);font-weight:700;text-decoration:none}.auth-link:hover{text-decoration:underline}
    .auth-submit{width:100%;min-height:54px;border:0;border-radius:13px;background:linear-gradient(135deg,var(--blue),#1597bd);box-shadow:0 12px 28px rgba(23,105,224,.25);font-weight:800;transition:.2s}.auth-submit:hover{transform:translateY(-1px);box-shadow:0 16px 34px rgba(23,105,224,.3)}.assurance{display:flex;align-items:center;justify-content:center;gap:.45rem;margin-top:1.35rem;color:#7a899c;font-size:.75rem}.assurance svg{width:15px;height:15px;color:#15977e}
    @media(max-width:991.98px){.auth-shell{grid-template-columns:1fr}.auth-story{display:none}.auth-entry{min-height:100vh;padding:1.25rem}.mobile-brand{display:flex}.auth-card{max-width:520px}}@media(max-width:575.98px){.auth-card{padding:1.5rem;border-radius:20px}.auth-card h2{font-size:1.7rem}.auth-row{align-items:flex-start;flex-direction:column;gap:.75rem}}
</style>
@endpush

@section('content')
@php($scissors = '<svg viewBox="0 0 24 24" fill="none"><path d="M8.4 7.9 18.8 3m-10.4 13.1L18.8 21M8.2 12h11.2M8.4 7.9a3.2 3.2 0 1 1-6.4 0 3.2 3.2 0 0 1 6.4 0Zm0 8.2a3.2 3.2 0 1 1-6.4 0 3.2 3.2 0 0 1 6.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>')
<div class="auth-shell">
    <section class="auth-story" aria-label="ٹیلر مینجمنٹ پلیٹ فارم کا تعارف">
        <div class="auth-brand"><span class="brand-mark" aria-hidden="true">{!! $scissors !!}</span><span>TMS<small>ٹیلر مینجمنٹ سسٹم</small></span></div>
        <div class="auth-copy"><span class="eyebrow"><i></i> مکمل کاروباری نظام</span><h1>ہر سلائی۔<br>ہر فروخت۔<br>مکمل اختیار۔</h1><p>ٹیلرنگ آرڈرز، گاہکوں کی پیمائش، خریداری، اسٹاک، ادائیگیاں اور منافع ایک ہی آسان نظام سے سنبھالیں۔</p></div>
        <div class="auth-flow" aria-label="آرڈر کا طریقۂ کار">@foreach([['01','پیمائش','گاہک کا ناپ'],['02','کٹائی','تیاری'],['03','سلائی','ورکشاپ'],['04','حوالگی','وصولی']] as [$number,$title,$caption])<div class="flow-step"><span>{{ $number }}</span><strong>{{ $title }}</strong><small>{{ $caption }}</small></div>@endforeach</div>
    </section>
    <section class="auth-entry">
        <div class="auth-card">
            <div class="mobile-brand auth-brand"><span class="brand-mark" aria-hidden="true">{!! $scissors !!}</span><span>TMS<small>ٹیلر مینجمنٹ سسٹم</small></span></div>
            <a class="market-home" href="{{ route('storefront.index') }}">← ٹی ایم ایس بازار دیکھیں</a>
            <h2>خوش آمدید</h2><p class="auth-subtitle">اپنا کاروبار سنبھالنے کے لیے لاگ اِن کریں۔</p>
            <form method="POST" action="{{ route('login') }}">@csrf
                <div class="mb-3"><label for="email" class="auth-label">ای میل یا یوزر نیم</label><div class="auth-control"><svg viewBox="0 0 24 24" fill="none"><path d="m3 6 9 6 9-6M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><input id="email" type="text" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus placeholder="email@example.com یا username">@error('email')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror</div></div>
                <div><label for="password" class="auth-label">پاس ورڈ</label><div class="auth-control"><svg viewBox="0 0 24 24" fill="none"><rect x="4" y="10" width="16" height="11" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="اپنا پاس ورڈ درج کریں">@error('password')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror</div></div>
                <div class="auth-row"><div class="form-check"><input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember')?'checked':'' }}><label class="form-check-label" for="remember">مجھے لاگ اِن رکھیں</label></div>@if(Route::has('password.request'))<a class="auth-link" href="{{ route('password.request') }}">پاس ورڈ بھول گئے؟</a>@endif</div>
                <button type="submit" class="btn btn-primary auth-submit">لاگ اِن کریں</button>
            </form>
            <div class="assurance"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3 5 6v5c0 4.4 2.7 8.4 7 10 4.3-1.6 7-5.6 7-10V6l-7-3Zm-3 9 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg> آپ کے کاروباری ڈیٹا تک محفوظ رسائی</div>
        </div>
    </section>
</div>
@endsection
