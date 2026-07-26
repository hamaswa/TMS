@include('inc/header')
<main class="container py-5" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold mb-1">درزی ڈیش بورڈ</h1>
            <p class="text-muted mb-0">ماہِ رواں کی کمائی، ادائیگی اور جاری کام کا خلاصہ</p>
        </div>
        <a class="btn btn-primary mt-3 mt-md-0" href="{{ route('tailor.jobs.index') }}">میرے جاری کام دیکھیں</a>
    </div>

    <div class="row">
        @foreach ([
            ['ماہِ رواں کے سوٹ', $suits, 'fa-cut', 'success'],
            ['ماہِ رواں کی کمائی', 'روپے '.number_format($earnings, 2), 'fa-coins', 'primary'],
            ['ادا شدہ رقم', 'روپے '.number_format($paid, 2), 'fa-hand-holding-usd', 'info'],
            ['واجب الادا رقم', 'روپے '.number_format($outstanding, 2), 'fa-wallet', $outstanding > 0 ? 'warning' : 'success'],
        ] as [$label, $value, $icon, $color])
            <div class="col-sm-6 col-xl-3 mb-3">
                <section class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small mb-2">{{ $label }}</div>
                                <div class="h4 font-weight-bold text-{{ $color }} mb-0">{{ $value }}</div>
                            </div>
                            <span class="rounded-circle bg-light p-3 text-{{ $color }}"><i class="fas {{ $icon }}"></i></span>
                        </div>
                    </div>
                </section>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mt-2">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <strong class="d-block">جاری کام</strong>
                <span class="text-muted small">وہ آرڈرز جو ابھی گاہک کے حوالے نہیں ہوئے</span>
            </div>
            <span class="badge badge-pill badge-primary px-4 py-3 mt-2 mt-sm-0">{{ $activeJobs }} آرڈرز</span>
        </div>
    </div>
</main>
@include('inc/footer')
