<!DOCTYPE html>
<html lang="ur" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>آرڈر کی صورتحال — {{ $setting?->name ?: 'TMS' }}</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">
    <style>
        @font-face {
            font-family: 'Noto Nastaliq Urdu';
            src: url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');
            font-weight: 100 900;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --blue: #1769e0;
            --navy: #102a50;
            --muted: #68788f;
            --line: #e0e8f2;
            --green: #12905b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--navy);
            background: #f2f6fb;
            font-family: 'Noto Nastaliq Urdu', Tahoma, sans-serif;
        }

        .tracking-shell {
            width: min(100% - 24px, 720px);
            margin: 28px auto;
        }

        .tracking-card {
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: 0 18px 55px rgba(16, 42, 80, .1);
        }

        .tracking-head {
            padding: 26px 24px 22px;
            text-align: center;
            background: linear-gradient(145deg, #fff, #f5f9ff);
            border-bottom: 1px solid var(--line);
        }

        .tracking-logo {
            display: block;
            width: 78px;
            height: 78px;
            margin: 0 auto 10px;
            object-fit: contain;
        }

        .tracking-shop {
            margin: 0;
            font: 900 1.55rem/1.7 Arial, sans-serif;
        }

        .tracking-title {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: .93rem;
        }

        .tracking-body {
            padding: 22px;
        }

        .status-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 17px 18px;
            margin-bottom: 18px;
            color: #fff;
            background: linear-gradient(135deg, #2378ee, #0d5bd0);
            border-radius: 15px;
        }

        .status-hero small {
            display: block;
            margin-bottom: 4px;
            color: #dceaff;
        }

        .status-hero strong {
            font-size: 1.35rem;
            font-weight: 900;
        }

        .status-icon {
            display: grid;
            place-items: center;
            flex: 0 0 50px;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, .17);
            border-radius: 50%;
            font: 900 1.35rem/1 Arial, sans-serif;
        }

        .tracking-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }

        .tracking-field {
            min-height: 92px;
            padding: 14px 15px;
            background: #f8fafd;
            border: 1px solid #e7edf5;
            border-radius: 13px;
        }

        .tracking-field span {
            display: block;
            margin-bottom: 5px;
            color: var(--muted);
            font-size: .8rem;
        }

        .tracking-field strong {
            display: block;
            color: var(--navy);
            font: 800 1.03rem/1.5 Arial, sans-serif;
            overflow-wrap: anywhere;
        }

        .tracking-field.is-payment {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .tracking-field.is-payment.is-paid {
            background: #eaf8f0;
            border-color: #b9e2ca;
        }

        .tracking-field.is-payment.is-paid strong {
            color: var(--green);
        }

        .tracking-field.is-payment.is-unpaid {
            background: #fff0f1;
            border-color: #f0c5ca;
        }

        .tracking-field.is-payment.is-unpaid strong {
            color: #c02b39;
        }

        .tracking-field.is-payment strong {
            font-family: 'Noto Nastaliq Urdu', Tahoma, sans-serif;
            font-size: 1rem;
            font-weight: 900;
        }

        .tracking-field.is-previous-balance {
            grid-column: 1 / -1;
            min-height: auto;
            background: #fff8e8;
            border-color: #f0d99f;
        }

        .tracking-field.is-previous-balance strong {
            color: #9a6500;
            font-size: 1.08rem;
        }

        .tracking-note {
            padding: 14px 16px;
            margin-top: 19px;
            color: #64748a;
            background: #f7f9fc;
            border-radius: 12px;
            font-size: .8rem;
            line-height: 2;
            text-align: center;
        }

        .tracking-footer {
            padding: 17px 22px;
            color: var(--muted);
            border-top: 1px solid var(--line);
            text-align: center;
            font-size: .78rem;
        }

        .tracking-footer p {
            margin: 2px 0;
        }

        @media (max-width: 520px) {
            .tracking-shell {
                margin: 12px auto;
            }

            .tracking-card {
                border-radius: 16px;
            }

            .tracking-head,
            .tracking-body {
                padding-right: 16px;
                padding-left: 16px;
            }

            .tracking-grid {
                grid-template-columns: 1fr;
            }

            .tracking-field {
                min-height: 78px;
            }
        }
    </style>
</head>

<body>
    <main class="tracking-shell">
        <article class="tracking-card">
            <header class="tracking-head">
                @if ($setting?->logo_url)
                    <img class="tracking-logo" src="{{ $setting->logo_url }}" alt="{{ $setting->name }}">
                @endif
                <h1 class="tracking-shop">{{ $setting?->name ?: 'TMS Tailoring' }}</h1>
                <p class="tracking-title">آپ کے سلائی آرڈر کی تازہ صورتحال</p>
            </header>

            <div class="tracking-body">
                <section class="status-hero" aria-label="موجودہ آرڈر صورتحال">
                    <div><small>موجودہ مرحلہ</small><strong>{{ $currentStatusLabel }}</strong></div>
                    <span class="status-icon">✓</span>
                </section>

                <section class="tracking-grid" aria-label="آرڈر کی معلومات">
                    <div class="tracking-field"><span>آرڈر نمبر</span><strong>#{{ $order->id }}</strong></div>
                    <div class="tracking-field"><span>گاہک کا
                            نام</span><strong>{{ $order->customers?->name ?: '—' }}</strong></div>
                    <div class="tracking-field"><span>سوٹ کی
                            تعداد</span><strong>{{ $order->suitQuantity ?: '—' }}</strong></div>
                    <div class="tracking-field"><span>واپسی کی
                            تاریخ</span><strong>{{ $order->returnDate ?: '—' }}</strong></div>
                    <div class="tracking-field"><span>آرڈر کی رقم</span><strong>Rs.
                            {{ number_format((float) $order->totalPayment, 2) }}</strong></div>
                    <div class="tracking-field is-payment {{ $paymentReceived ? 'is-paid' : 'is-unpaid' }}">
                        <span>ادائیگی کی صورتحال</span>
                        <strong>{{ $paymentReceived ? '✓ ادائیگی موصول ہو گئی ہے' : '✕ ادائیگی موصول نہیں ہوئی' }}</strong>
                    </div>
                    @if($previousBalance > 0)
                        <div class="tracking-field is-previous-balance">
                            <span>پچھلا بقایا باقی ہے</span>
                            <strong>Rs. {{ number_format($previousBalance, 2) }}</strong>
                        </div>
                    @endif
                </section>

                <p class="tracking-note">یہ نجی لنک آپ کی رسید کے QR کوڈ سے کھولا گیا ہے۔ تازہ صورتحال دیکھنے کے لیے اسی
                    صفحے کو دوبارہ کھولیں۔</p>
            </div>

            <footer class="tracking-footer">
                @if ($setting?->address)
                    <p>{{ strip_tags($setting->address) }}</p>
                @endif
                @if ($setting?->contact_no)
                    <p dir="ltr">{{ $setting->contact_no }}</p>
                @endif
            </footer>
        </article>
    </main>
</body>

</html>
