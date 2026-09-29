@extends('layouts.app')
@section('body_class', 'auth-page')
@section('html_lang', 'ur')
@section('html_dir', 'rtl')

@push('styles')
    <style>
        :root {
            --navy: #082e58;
            --blue: #1477ea;
            --cyan: #16bfe7;
            --green: #0c9b79;
            --muted: #65758d
        }

        .auth-page {
            min-height: 100vh;
            overflow-x: hidden;
            background: #eef5fb;
            color: #1f2937;
            direction: rtl;
            text-align: right;
            font-family: "Noto Nastaliq Urdu", "Noto Sans Arabic", Tahoma, Arial, sans-serif;
            line-height: 2
        }

        .auth-page .app-navbar {
            display: none
        }

        .auth-page .app-main {
            min-width: 0;
            padding: 0 !important
        }

        .auth-page #app {
            min-width: 0
        }

        .auth-shell {
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(390px, .86fr) minmax(0, 1.14fr);
            grid-template-areas: "entry story";
            direction: ltr
        }

        .auth-story {
            grid-area: story;
            position: relative;
            min-width: 0;
            min-height: 100vh;
            min-height: 100dvh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(2rem, 4vw, 4rem);
            color: #fff;
            direction: rtl;
            text-align: right;
            background: radial-gradient(circle at 21% 22%, rgba(34, 195, 255, .24), transparent 24%), linear-gradient(145deg, #08264d 0%, #07528f 53%, #07376d 100%)
        }

        .auth-story:before,
        .auth-story:after {
            content: "";
            position: absolute;
            pointer-events: none
        }

        .auth-story:before {
            inset: 0;
            background: linear-gradient(110deg, rgba(255, 255, 255, .08), transparent 35%), repeating-linear-gradient(135deg, transparent 0 44px, rgba(255, 255, 255, .022) 45px 46px)
        }

        .auth-story:after {
            width: 760px;
            height: 360px;
            left: -160px;
            bottom: -255px;
            border: 1px solid rgba(59, 206, 255, .25);
            border-radius: 50%;
            box-shadow: 0 0 0 75px rgba(15, 154, 212, .05), 0 0 0 150px rgba(15, 154, 212, .04)
        }

        .auth-brand,
        .auth-copy,
        .auth-flow {
            position: relative;
            z-index: 2
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: .7rem;
            font-family: Arial, sans-serif;
            font-size: 1.75rem;
            font-weight: 900;
            letter-spacing: -.04em;
            direction: ltr
        }

        .auth-brand small {
            display: block;
            margin-top: .25rem;
            font-family: "Noto Nastaliq Urdu", "Noto Sans Arabic", Tahoma, sans-serif;
            font-size: .66rem;
            font-weight: 600;
            letter-spacing: 0;
            color: #cbe5f7;
            direction: rtl;
            line-height: 2
        }

        .brand-name em {
            color: #30baf3;
            font-style: normal
        }

        .brand-mark {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            color: #40c8ff
        }

        .brand-mark svg {
            width: 43px;
            height: 43px;
            filter: drop-shadow(0 8px 14px rgba(0, 0, 0, .2))
        }

        .auth-copy {
            max-width: 680px;
            margin: 2.2rem 0
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .4rem .78rem;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            background: rgba(255, 255, 255, .08);
            color: #d9efff;
            font-size: .76rem;
            font-weight: 700
        }

        .eyebrow i {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #3ddcb9;
            box-shadow: 0 0 0 5px rgba(61, 220, 185, .13)
        }

        .auth-story h1 {
            margin: 1.2rem 0 .8rem;
            font-size: clamp(2.65rem, 5vw, 5rem);
            line-height: 1.7;
            font-weight: 900
        }

        .auth-story h1 span {
            color: #27c1f2
        }

        .auth-copy>p {
            margin: 0;
            max-width: 610px;
            color: #d2e8f8;
            font-size: clamp(.95rem, 1.35vw, 1.12rem);
            line-height: 2.15
        }

        .scene {
            position: absolute;
            z-index: 1;
            left: 4%;
            top: 17%;
            width: 48%;
            height: 47%;
            opacity: .88;
            transform: perspective(900px) rotateY(-5deg)
        }

        .scene-screen {
            position: absolute;
            left: 17%;
            bottom: 3%;
            width: 79%;
            height: 60%;
            padding: 13px;
            border: 8px solid #12283f;
            border-radius: 14px 14px 7px 7px;
            background: #f5f9fd;
            box-shadow: 0 24px 42px rgba(0, 0, 0, .35)
        }

        .scene-screen:before {
            content: "BuyNStitch";
            display: block;
            padding: 7px 10px;
            border-radius: 6px;
            background: #0b3a70;
            color: #fff;
            font: 800 .65rem Arial
        }

        .scene-screen:after {
            content: "";
            display: block;
            height: calc(100% - 35px);
            margin-top: 8px;
            border-radius: 6px;
            background: linear-gradient(90deg, #e8f1fb 27%, transparent 27%), linear-gradient(#fff 48%, #e9f2fb 49% 52%, #fff 53%);
            box-shadow: inset 0 0 0 1px #dce8f4
        }

        .scene-fabric {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 38%;
            height: 18%;
            border-radius: 9px;
            background: repeating-linear-gradient(0deg, #254c75 0 16%, #d6a95d 17% 31%, #6c8e75 32% 47%, #eef0ef 48% 62%);
            box-shadow: 0 16px 30px rgba(0, 0, 0, .28)
        }

        .scene-tape {
            position: absolute;
            left: 36%;
            bottom: -2%;
            width: 32%;
            height: 24px;
            border: 7px solid #f5c82e;
            border-radius: 50%;
            transform: rotate(-8deg)
        }

        .auth-flow {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: .7rem
        }

        .flow-step {
            min-height: 112px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: .8rem .55rem;
            border: 1px solid rgba(112, 203, 255, .25);
            border-radius: 14px;
            background: rgba(7, 51, 99, .55);
            backdrop-filter: blur(10px);
            text-align: center
        }

        .flow-step span {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            margin-bottom: .5rem;
            border-radius: 12px;
            background: linear-gradient(145deg, #24bff0, #1687e9);
            color: #fff;
            font-family: Arial, sans-serif;
            font-size: .9rem;
            font-weight: 900;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .18)
        }

        .flow-step strong,
        .flow-step small {
            display: block
        }

        .flow-step strong {
            font-size: .78rem;
            line-height: 2
        }

        .flow-step small {
            margin-top: .15rem;
            color: #bcd9eb;
            font-size: .61rem;
            line-height: 2
        }

        .story-tagline {
            position: relative;
            z-index: 2;
            margin: .9rem 0 0;
            color: #2bd0fb;
            font-size: 1.05rem;
            font-weight: 800;
            text-align: center
        }

        .auth-entry {
            grid-area: entry;
            position: sticky;
            top: 0;
            align-self: start;
            min-width: 0;
            height: 100vh;
            height: 100dvh;
            overflow-x: hidden;
            overflow-y: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1.5rem, 4vw, 4rem);
            direction: rtl;
            background: radial-gradient(circle at 20% 10%, #e2f1ff 0, transparent 31%), linear-gradient(145deg, #f8fbff, #edf5fc)
        }

        .auth-entry:before,
        .auth-entry:after {
            content: "";
            position: absolute;
            border: 1px solid rgba(20, 119, 234, .08);
            border-radius: 50%;
            pointer-events: none
        }

        .auth-entry:before {
            width: 440px;
            height: 440px;
            left: -230px;
            top: -210px
        }

        .auth-entry:after {
            width: 320px;
            height: 140px;
            right: -220px;
            bottom: 0;
            border-bottom: 0;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0
        }

        .auth-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 510px;
            padding: clamp(1.75rem, 3.5vw, 3.25rem);
            background: rgba(255, 255, 255, .92);
            border: 1px solid #dce9f4;
            border-radius: 24px;
            box-shadow: 0 24px 75px rgba(14, 58, 96, .14);
            backdrop-filter: blur(12px)
        }

        .card-brand {
            justify-content: center;
            margin-bottom: .55rem;
            color: var(--navy)
        }

        .card-brand .brand-mark {
            color: var(--blue)
        }

        .card-brand small {
            color: #64748b
        }

        .mobile-brand {
            display: none
        }

        .auth-card h2 {
            margin: .8rem 0 .3rem;
            color: var(--navy);
            font-size: 2.15rem;
            font-weight: 900;
            line-height: 1.75;
            text-align: center
        }

        .auth-subtitle {
            margin-bottom: 1.6rem;
            color: var(--muted);
            line-height: 2.15;
            text-align: center
        }

        .auth-label {
            display: block;
            min-height: 2em;
            margin-bottom: .45rem;
            color: #263b54;
            font-size: .82rem;
            font-weight: 800;
            line-height: 2
        }

        .auth-control {
            position: relative
        }

        .auth-control>svg {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #57708e;
            pointer-events: none
        }

        .auth-control .form-control {
            min-height: 56px;
            padding: .8rem 3rem .8rem 3rem;
            border: 1px solid #cad9e6;
            border-radius: 13px;
            background: #f8fbff;
            color: #172033;
            box-shadow: none;
            transition: .2s;
            text-align: right;
            line-height: 1.8
        }

        .auth-control .form-control:focus {
            border-color: #2785ea;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(39, 133, 234, .1)
        }

        .password-toggle {
            position: absolute;
            left: .65rem;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border: 0;
            background: transparent;
            color: #526c8e
        }

        .password-toggle svg {
            width: 20px;
            height: 20px
        }

        .auth-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 1rem 0 1.25rem;
            font-size: .82rem
        }

        .auth-row .form-check {
            min-height: 44px;
            display: flex;
            align-items: center;
            gap: .45rem;
            margin: 0
        }

        .auth-row .form-check-input {
            margin: 0;
            width: 1.15rem;
            height: 1.15rem
        }

        .auth-row .form-check-label {
            min-height: 44px;
            display: inline-flex;
            align-items: center
        }

        .auth-link {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            color: var(--blue);
            font-weight: 800;
            text-decoration: none
        }

        .auth-link:hover {
            text-decoration: underline
        }

        .auth-submit {
            width: 100%;
            min-height: 56px;
            border: 0;
            border-radius: 13px;
            background: linear-gradient(135deg, #1268db, #169de1);
            box-shadow: 0 12px 28px rgba(23, 105, 224, .25);
            font-weight: 900;
            transition: .2s
        }

        .auth-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 34px rgba(23, 105, 224, .3)
        }

        .assurance {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            margin-top: 1.2rem;
            color: #718198;
            font-size: .73rem
        }

        .assurance svg {
            width: 15px;
            height: 15px;
            color: var(--green)
        }

        .demo-login-note {
            margin: 0 0 1.15rem;
            padding: .8rem 1rem;
            border: 1px solid #9bdcc8;
            border-radius: 13px;
            background: #effcf7;
            color: #176b55
        }

        .demo-login-note strong,
        .demo-login-note span {
            display: block
        }

        .demo-login-note strong {
            margin-bottom: .15rem;
            color: #075f4a
        }

        .demo-login-note span {
            font-size: .72rem;
            line-height: 1.7
        }

        @media(max-width:1400px) {
            .scene {
                display: none
            }

            .auth-story {
                padding: 2.5rem
            }

            .auth-flow {
                grid-template-columns: repeat(3, 1fr)
            }
        }

        @media(max-width:1100px) {
            .auth-story {
                padding: 2rem
            }
        }

        @media(min-width:861px) and (max-height:900px) {
            .auth-story {
                padding: clamp(1.5rem, 3vw, 2.5rem)
            }

            .auth-story h1 {
                margin: .8rem 0 .45rem;
                font-size: clamp(2.2rem, min(3.8vw, 6.5vh), 3.75rem);
                line-height: 1.6
            }

            .auth-copy {
                margin: 1.25rem 0
            }

            .auth-copy>p {
                line-height: 1.75
            }

            .flow-step {
                min-height: 96px;
                padding: .6rem .45rem
            }

            .auth-entry {
                padding: 1.25rem
            }

            .auth-card {
                padding: 1.5rem 2rem
            }

            .auth-card h2 {
                margin-top: .45rem;
                font-size: 1.85rem
            }

            .auth-subtitle {
                margin-bottom: 1rem
            }

            .auth-row {
                margin: .7rem 0 .85rem
            }

            .assurance {
                margin-top: .8rem
            }
        }

        @media(max-width:860px) {
            .auth-shell {
                grid-template-columns: 1fr;
                grid-template-areas: "entry"
            }

            .auth-story {
                display: none
            }

            .auth-entry {
                position: relative;
                height: auto;
                min-height: 100vh;
                min-height: 100dvh;
                overflow-y: visible;
                padding: 1.25rem
            }

            .mobile-brand {
                display: flex
            }

            .card-brand {
                display: flex
            }

            .auth-card {
                max-width: 540px
            }
        }

        @media(max-width:575.98px) {
            .auth-card {
                padding: 1.4rem;
                border-radius: 20px
            }

            .auth-card h2 {
                font-size: 1.75rem
            }

            .auth-row {
                align-items: flex-start;
                flex-direction: column;
                gap: .55rem
            }

            .card-brand {
                font-size: 1.45rem
            }
        }
    </style>
@endpush

@section('content')
    @php($scissors = '<svg viewBox="0 0 24 24" fill="none"><path d="M8.4 7.9 18.8 3m-10.4 13.1L18.8 21M8.2 12h11.2M8.4 7.9a3.2 3.2 0 1 1-6.4 0 3.2 3.2 0 0 1 6.4 0Zm0 8.2a3.2 3.2 0 1 1-6.4 0 3.2 3.2 0 0 1 6.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>')
    @php($demoPreview = config('demo.enabled') && request()->boolean('demo'))
    <div class="auth-shell">
        <section class="auth-story" aria-label="ٹیلر مینجمنٹ پلیٹ فارم کا تعارف">
            <div class="auth-brand"><span class="brand-name">BuyN<em>Stitch</em><small>مقامی کپڑا اور ٹیلرنگ کاروبار، ایک ہی
                        جگہ</small></span><span class="brand-mark" aria-hidden="true">{!! $scissors !!}</span></div>
            <div class="scene" aria-hidden="true">
                <div class="scene-screen"></div>
                <div class="scene-fabric"></div>
                <div class="scene-tape"></div>
            </div>
            <div class="auth-copy"><span class="eyebrow"><i></i> مکمل کاروباری نظام</span>
                <h1>ہر سلائی،<br>ہر فروخت،<br><span>مکمل اختیار۔</span></h1>
                <p>ٹیلرنگ آرڈرز، کپڑا خریداری، اسٹاک، اکاؤنٹنگ، ادائیگیاں اور منافع ایک ہی آسان نظام سے سنبھالیں۔</p>
            </div>
            <div>
                <div class="auth-flow" aria-label="BuyNStitch کی خصوصیات">
                    @foreach ([['✂', 'ٹیلرنگ آرڈر', 'آرڈر سے ڈیلیوری تک'], ['🛒', 'خریداری', 'سپلائرز کا کنٹرول'], ['▣', 'اسٹاک مینجمنٹ', 'فروخت ہمیشہ تیار'], ['👥', 'گاہکوں کا انتظام', 'گاہکوں کو منظم رکھیں'], ['▥', 'رپورٹس', 'آسانی سے منافع دیکھیں']] as [$icon, $title, $caption])
                        <div class="flow-step"><span>{{ $icon }}</span><strong>{{ $title }}</strong><small>{{ $caption }}</small>
                        </div>
                    @endforeach
                </div>
                <p class="story-tagline">بہتر کاروبار، روشن مستقبل</p>
            </div>
        </section>
        <section class="auth-entry">
            <div class="auth-card">
                <div class="card-brand auth-brand"><span class="brand-name">BuyN<em>Stitch</em><small>مقامی کپڑا اور ٹیلرنگ
                            کاروبار، ایک ہی جگہ</small></span><span class="brand-mark"
                        aria-hidden="true">{!! $scissors !!}</span></div>
                <h2>خوش آمدید</h2>
                <p class="auth-subtitle">اپنا کاروبار سنبھالنے کے لیے لاگ اِن کریں۔</p>
                @if ($demoPreview)
                    <div class="demo-login-note"><strong>ڈیمو اکاؤنٹ تیار ہے</strong><span>ای میل اور پاس ورڈ پہلے سے درج
                            ہیں۔ نمونہ کاروباری ڈیٹا دیکھنے کے لیے صرف لاگ اِن کریں دبائیں۔</span></div>
                @endif
                <form method="POST" action="{{ route('login') }}">@csrf
                    <div class="mb-3"><label for="email" class="auth-label">ای میل یا یوزر نیم</label>
                        <div class="auth-control"><svg viewBox="0 0 24 24" fill="none">
                                <path
                                    d="m3 6 9 6 9-6M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"
                                    stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                            </svg><input id="email" type="text"
                                class="form-control @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email', $demoPreview ? config('demo.email') : '') }}" required
                                autocomplete="username" autofocus placeholder="email@example.com یا username">
                            @error('email')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div><label for="password" class="auth-label">پاس ورڈ</label>
                        <div class="auth-control"><svg viewBox="0 0 24 24" fill="none">
                                <rect x="4" y="10" width="16" height="11" rx="2" stroke="currentColor"
                                    stroke-width="1.7" />
                                <path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3" stroke="currentColor" stroke-width="1.7"
                                    stroke-linecap="round" />
                            </svg><input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror" name="password"
                                value="{{ $demoPreview ? config('demo.password') : '' }}" required
                                autocomplete="current-password" placeholder="اپنا پاس ورڈ درج کریں"><button
                                class="password-toggle" type="button" aria-label="پاس ورڈ دکھائیں"
                                aria-pressed="false"><svg viewBox="0 0 24 24" fill="none">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor"
                                        stroke-width="1.7" />
                                    <circle cx="12" cy="12" r="2.7" stroke="currentColor"
                                        stroke-width="1.7" />
                                </svg></button>
                            @error('password')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="auth-row">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="remember"
                                id="remember" {{ old('remember') ? 'checked' : '' }}><label class="form-check-label"
                                for="remember">مجھے لاگ اِن رکھیں</label></div>
                        @if (Route::has('password.request'))
                            <a class="auth-link" href="{{ route('password.request') }}">پاس ورڈ بھول گئے؟</a>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary auth-submit">لاگ اِن کریں</button>
                </form>
                <div class="assurance"><svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 3 5 6v5c0 4.4 2.7 8.4 7 10 4.3-1.6 7-5.6 7-10V6l-7-3Zm-3 9 2 2 4-4"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg> آپ کے کاروباری ڈیٹا تک محفوظ رسائی</div>
            </div>
        </section>
    </div>
    <script>
        document.querySelector('.password-toggle')?.addEventListener('click', function() {
            const password = document.getElementById('password');
            const showing = password.type === 'text';
            password.type = showing ? 'password' : 'text';
            this.setAttribute('aria-pressed', showing ? 'false' : 'true');
            this.setAttribute('aria-label', showing ? 'پاس ورڈ دکھائیں' : 'پاس ورڈ چھپائیں');
        });
    </script>
@endsection
