@auth
    @if(Auth::user()->isBusinessOwner())
        @php($business = Auth::user()->business)
        @if($business && ! $business->isDemo() && ! $business->subscriptionIsManaged())
            {{-- Shop is not on any plan yet: keep a banner on every page and pop up (with a chime) once per account. --}}
            @php($deadline = \App\Models\Business::noPlanDeadline())
            @php($daysLeft = $deadline ? (int) now()->startOfDay()->diffInDays($deadline, false) : null)
            {{-- The popup shows once per account; after that the banner below is enough. --}}
            @php($showNoPlanPopup = ! Auth::user()->plan_notice_seen_at && ! request()->routeIs('admin.subscription.index'))
            @if($showNoPlanPopup)
                @php(Auth::user()->forceFill(['plan_notice_seen_at' => now()])->saveQuietly())
            @endif
            <div class="container-fluid px-3 px-md-4 pt-3" data-no-plan-banner>
                <div class="alert alert-warning mb-0 d-flex flex-wrap justify-content-between align-items-center" role="alert" style="gap:.5rem">
                    <div><strong>آپ ہمارے کسی پلان پر نہیں ہیں۔</strong>
                        <span>کام جاری رکھنے کے لیے پلان منتخب کریں، ورنہ آپ کا اکاؤنٹ غیر فعال کر دیا جائے گا۔</span>
                        @if($deadline)<div class="mt-1"><strong>اکاؤنٹ غیر فعال ہونے کی تاریخ:</strong> {{ \App\Support\DisplayDate::long($deadline) }}@if($daysLeft > 0) <span class="badge badge-danger">{{ $daysLeft }} دن باقی</span>@endif</div>@endif</div>
                    <a class="btn btn-sm btn-warning" href="{{ route('admin.subscription.index') }}#plans">پلان دیکھیں</a>
                </div>
            </div>
            @if($showNoPlanPopup)
                <div class="modal fade" id="no-plan-modal" tabindex="-1" role="alertdialog" aria-labelledby="no-plan-modal-title" aria-describedby="no-plan-modal-body" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content border-warning">
                            <div class="modal-body text-center p-4">
                                <div class="mb-3" aria-hidden="true"><i class="fas fa-bell fa-2x text-warning"></i></div>
                                <h5 class="mb-3" id="no-plan-modal-title">آپ ابھی کسی پلان پر نہیں ہیں</h5>
                                <p class="mb-0" id="no-plan-modal-body">آپ ہمارے کسی سبسکرپشن پلان کو استعمال نہیں کر رہے۔ کام جاری رکھنے کے لیے براہ کرم پلان دیکھیں اور ایک پلان منتخب کریں، ورنہ آپ کا اکاؤنٹ غیر فعال کر دیا جائے گا۔</p>
                                @if($deadline)
                                    <div class="alert alert-danger mt-3 mb-0"><strong>اکاؤنٹ غیر فعال ہونے کی تاریخ:</strong> {{ \App\Support\DisplayDate::long($deadline) }}@if($daysLeft > 0) <span class="d-inline-block">({{ $daysLeft }} دن باقی)</span>@endif</div>
                                @endif
                            </div>
                            <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
                                <button type="button" class="btn btn-light" data-dismiss="modal">بعد میں</button>
                                <a class="btn btn-warning" href="{{ route('admin.subscription.index') }}#plans">پلان دیکھیں</a>
                            </div>
                        </div>
                    </div>
                </div>
                @push('scripts')
                <script>
                    (function () {
                        var AudioCtx = window.AudioContext || window.webkitAudioContext;

                        function play(ctx) {
                            [[880, 0], [660, 0.18]].forEach(function (note) {
                                var osc = ctx.createOscillator();
                                var gain = ctx.createGain();
                                var start = ctx.currentTime + note[1];
                                osc.type = 'sine';
                                osc.frequency.value = note[0];
                                gain.gain.setValueAtTime(0.0001, start);
                                gain.gain.exponentialRampToValueAtTime(0.25, start + 0.02);
                                gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.45);
                                osc.connect(gain).connect(ctx.destination);
                                osc.start(start);
                                osc.stop(start + 0.5);
                            });
                            setTimeout(function () { ctx.close(); }, 1200);
                        }

                        // Browsers may block sound until the person interacts with the page; then it plays on the first tap or key.
                        function chime() {
                            if (!AudioCtx) { return; }
                            var ctx = new AudioCtx();
                            var played = false;
                            var playOnce = function () {
                                if (played || ctx.state !== 'running') { return; }
                                played = true;
                                document.removeEventListener('pointerdown', unlock);
                                document.removeEventListener('keydown', unlock);
                                play(ctx);
                            };
                            var unlock = function () { ctx.resume().then(playOnce); };
                            document.addEventListener('pointerdown', unlock);
                            document.addEventListener('keydown', unlock);
                            ctx.resume().then(playOnce);
                            playOnce();
                        }

                        $(function () {
                            $('#no-plan-modal').modal('show');
                            chime();
                        });
                    })();
                </script>
                @endpush
            @endif
        @else
            @php($subscription = $business?->latestSubscription()->first())
            @if($subscription && in_array($subscription->state(), ['expiring','expired'], true))
                <div class="container-fluid px-3 px-md-4 pt-3">
                    <div class="alert alert-{{ $subscription->state()==='expired'?'danger':'warning' }} mb-0 d-flex flex-wrap justify-content-between align-items-center" role="alert">
                        <div><strong>{{ $subscription->state()==='expired'?'آپ کی سبسکرپشن کی میعاد ختم ہو چکی ہے۔':'آپ کی سبسکرپشن جلد ختم ہونے والی ہے۔' }}</strong>
                        <span>{{ $subscription->ends_on->format('d-m-Y') }} @if($subscription->state()==='expiring')· {{ $subscription->daysRemaining() }} دن باقی@endif</span></div>
                        <a class="btn btn-sm btn-outline-dark mt-2 mt-md-0" href="{{ route('admin.subscription.index') }}">تفصیل دیکھیں</a>
                    </div>
                </div>
            @endif
        @endif
    @endif
@endauth
