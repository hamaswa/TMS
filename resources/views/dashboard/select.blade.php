@extends('main')
@section('content')
<style>
    .workspace-select{min-height:calc(100vh - 70px);display:flex;align-items:center;background:radial-gradient(circle at 85% 10%,#e1f2ff 0,transparent 30%),#f5f7fa}.workspace-intro{max-width:760px;margin:0 auto 2rem;text-align:center}.workspace-intro h1{color:#102a43;font-weight:800}.workspace-choice{display:block;height:100%;padding:2rem;border:1px solid #dfe7f0;border-radius:22px;background:#fff;color:#243b53;box-shadow:0 14px 35px rgba(31,45,61,.09);transition:.2s}.workspace-choice:hover{transform:translateY(-4px);text-decoration:none;color:#102a43;box-shadow:0 20px 45px rgba(31,45,61,.14)}.workspace-choice .choice-icon{width:62px;height:62px;display:grid;place-items:center;margin-bottom:1.25rem;border-radius:18px;color:#fff;font-size:1.45rem}.workspace-choice h3{font-weight:800}.workspace-choice ul{padding-right:1.15rem;color:#60758a;line-height:2}.choice-tailor .choice-icon{background:linear-gradient(135deg,#1769e0,#20a3c4)}.choice-shop .choice-icon{background:linear-gradient(135deg,#0f9b76,#55b96b)}
</style>
<section class="main-content workspace-select"><div class="container py-5">
    <div class="workspace-intro"><span class="badge badge-primary mb-3">مشترکہ کاروباری اکاؤنٹ</span><h1>آپ آج کون سا شعبہ سنبھالنا چاہتے ہیں؟</h1><p class="text-muted mb-0">ہر شعبے کا اپنا آسان ڈیش بورڈ اور مخصوص مینو ہے۔ آپ کسی بھی وقت اوپر موجود ورک اسپیس بٹن سے شعبہ تبدیل کر سکتے ہیں۔</p></div>
    <div class="row justify-content-center">
        <div class="col-lg-5 mb-3"><a class="workspace-choice choice-tailor" href="{{ route('admin.workspace.switch', 'tailoring') }}"><span class="choice-icon"><i class="fas fa-cut"></i></span><h3>ٹیلرنگ ورک اسپیس</h3><p>گاہک، پیمائش، آرڈرز اور ورکشاپ کی مکمل پیش رفت۔</p><ul><li>گاہک اور پیمائش</li><li>درزی اور کام کی تقسیم</li><li>کٹائی، سلائی اور حوالگی</li></ul><strong class="text-primary">ٹیلرنگ ڈیش بورڈ کھولیں <i class="fas fa-arrow-left mr-1"></i></strong></a></div>
        <div class="col-lg-5 mb-3"><a class="workspace-choice choice-shop" href="{{ route('admin.workspace.switch', 'clothing') }}"><span class="choice-icon"><i class="fas fa-store"></i></span><h3>دکان اور فروخت ورک اسپیس</h3><p>فروخت، اسٹاک، سپلائرز اور خریداری ایک جگہ۔</p><ul><li>کاؤنٹر فروخت</li><li>اسٹاک اور کپڑے کی فہرست</li><li>سپلائرز اور خریداریاں</li></ul><strong class="text-success">دکان ڈیش بورڈ کھولیں <i class="fas fa-arrow-left mr-1"></i></strong></a></div>
    </div>
</div></section>
@endsection
