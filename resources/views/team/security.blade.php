@extends('main')
@section('content')
<section class="main-content team-page" dir="rtl"><div class="container-fluid py-4 px-lg-5">
    @include('team.partials.workspace')

    <div class="card team-card mx-auto" style="max-width:900px"><div class="card-body p-4 p-lg-5"><div class="row align-items-center"><div class="col-lg-7"><span class="team-action-icon"><i class="fas fa-key"></i></span><h2 class="h4 font-weight-bold">ملازمین کے پاس ورڈ کی میعاد</h2><p class="text-muted">مدت ختم ہونے پر ملازم کو کام جاری رکھنے سے پہلے نیا مضبوط پاس ورڈ بنانا ہوگا۔ پالیسی تبدیل کرنے سے موجودہ ملازمین کو مکمل نئی مدت ملے گی۔</p><ul class="text-muted pr-3"><li>عارضی پاس ورڈ پہلے لاگ اِن پر لازماً تبدیل ہوتا ہے۔</li><li>پاس ورڈ میں بڑا حرف، چھوٹا حرف، عدد اور علامت ضروری ہیں۔</li><li>پاس ورڈ یا خفیہ فارم معلومات سرگرمی لاگ میں محفوظ نہیں ہوتیں۔</li></ul></div><div class="col-lg-5"><div class="permission-box p-4"><form method="POST" action="{{ route('admin.team.password-policy.update') }}">@csrf @method('PUT')<div class="form-group"><label class="font-weight-bold">پاس ورڈ کتنے دن بعد تبدیل ہو؟</label><select class="form-control" name="password_expiry_days"><option value="0" @selected(! $business->password_expiry_days)>میعاد مقرر نہ کریں</option>@foreach([30,60,90,180,365] as $days)<option value="{{ $days }}" @selected((int) $business->password_expiry_days === $days)>{{ $days }} دن</option>@endforeach</select></div><button class="btn btn-primary btn-block">پالیسی محفوظ کریں</button></form></div></div></div></div></div>
</div></section>
@endsection
