@extends('layouts.app')

@section('content')
<div class="container" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h1 class="h3 mb-2">پاس ورڈ دوبارہ بنائیں</h1>
                    <p class="text-muted mb-0">اپنا رجسٹرڈ ای میل درج کریں۔ اگر دکان کے لیے ای میل سروس فعال ہے تو پاس ورڈ تبدیل کرنے کا لنک آپ کو بھیج دیا جائے گا۔</p>
                </div>

                <div class="card-body px-4 pb-4">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">ای میل ایڈریس</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    پاس ورڈ تبدیل کرنے کا لنک بھیجیں
                                </button>
                                <a href="{{ route('login') }}" class="btn btn-link">لاگ اِن پر واپس جائیں</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
