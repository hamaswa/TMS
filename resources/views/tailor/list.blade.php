@extends('main')

@section('content')
    @php
        $tailorLimit = $business?->subscriptionLimit('max_tailors');
        $tailorLimitReached = $tailorLimit !== null && $Tailors->count() >= $tailorLimit;
        $weeklyEarnings = $Tailors->mapWithKeys(function ($tailor) {
            return [$tailor->id => $tailor->orders->sum(
                fn ($order) => (float) $order->tailor_price * max(1, (int) $order->suitQuantity)
            )];
        });
    @endphp

    <style>
        .tailor-directory {
            --td-blue: #1769e0;
            --td-navy: #102a50;
            --td-muted: #68778f;
            --td-line: #e1e9f3;
            direction: rtl;
            padding: 28px 0 52px;
        }
        .tailor-directory .td-shell { width: min(100% - 32px, 1720px); margin-inline: auto; }
        .td-page-head { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
        .td-title { display: flex; align-items: center; gap: 14px; text-align: right; }
        .td-title__icon { display: grid; place-items: center; flex: 0 0 54px; width: 54px; height: 54px; color: var(--td-blue); background: #edf5ff; border: 1px solid #d8e8ff; border-radius: 16px; font-size: 22px; }
        .td-title h1 { margin: 0 0 5px; color: var(--td-navy); font-size: clamp(1.55rem, 2vw, 2rem); font-weight: 800; }
        .td-title p { margin: 0; color: var(--td-muted); font-size: .93rem; }
        .td-head-actions { display: flex; flex-wrap: wrap; gap: 9px; }
        .td-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 9px 15px; border: 1px solid #d7e1ed; border-radius: 10px; color: #344762; background: #fff; font-weight: 700; transition: .2s ease; }
        .td-btn:hover { color: var(--td-blue); border-color: #b7d2f8; text-decoration: none; transform: translateY(-1px); }
        .td-btn.is-primary { color: #fff; border-color: var(--td-blue); background: linear-gradient(135deg, #267bf1, #0d5bd2); box-shadow: 0 8px 18px rgba(23,105,224,.2); }
        .td-btn.is-primary:hover { color: #fff; }
        .td-notice { display: flex; align-items: flex-start; gap: 11px; margin-bottom: 14px; padding: 13px 16px; border: 1px solid transparent; border-radius: 12px; text-align: right; }
        .td-notice i { margin-top: 4px; }
        .td-notice.is-success { color: #146c43; background: #eaf8f1; border-color: #ccebdc; }
        .td-notice.is-warning { color: #805800; background: #fff8e5; border-color: #f4dfaa; }
        .td-notice.is-danger { color: #a32834; background: #fff0f1; border-color: #f2c9cd; }
        .td-panel { overflow: hidden; background: #fff; border: 1px solid var(--td-line); border-radius: 16px; box-shadow: 0 8px 28px rgba(21,47,81,.06); }
        .td-panel__head { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 20px 22px; border-bottom: 1px solid var(--td-line); }
        .td-panel__title h2 { margin: 0 0 4px; color: var(--td-navy); font-size: 1.24rem; font-weight: 800; }
        .td-panel__title p { margin: 0; color: var(--td-muted); font-size: .86rem; }
        .td-search { position: relative; width: min(100%, 350px); }
        .td-search i { position: absolute; top: 50%; right: 15px; color: #8796aa; transform: translateY(-50%); }
        .td-search input { width: 100%; min-height: 43px; padding: 9px 42px 9px 14px; border: 1px solid #d5dfec; border-radius: 10px; color: var(--td-navy); background: #fbfdff; outline: none; }
        .td-search input:focus { border-color: #79abf4; box-shadow: 0 0 0 3px rgba(23,105,224,.11); }
        .td-table-wrap { overflow-x: auto; }
        .td-table { width: 100%; min-width: 1180px; margin: 0; border-collapse: collapse; }
        .td-table thead th { padding: 14px 15px; color: #53647e; background: #f4f7fb; border: 0; border-bottom: 1px solid var(--td-line); font-size: .82rem; font-weight: 800; text-align: right; white-space: nowrap; }
        .td-table tbody td { padding: 16px 15px; color: #213552; border-top: 1px solid #e7edf5; font-size: .93rem; vertical-align: middle; text-align: right; }
        .td-table tbody tr:first-child td { border-top: 0; }
        .td-table tbody tr:hover { background: #fbfdff; }
        .td-person { display: flex; align-items: center; gap: 11px; min-width: 180px; }
        .td-avatar { display: grid; place-items: center; flex: 0 0 42px; width: 42px; height: 42px; color: #1769e0; background: #eaf3ff; border-radius: 12px; font-size: 1rem; font-weight: 800; text-transform: uppercase; }
        .td-person strong { display: block; color: var(--td-navy); font-size: 1rem; }
        .td-person small { display: block; margin-top: 3px; color: #8794a7; }
        .td-phone, .td-money { direction: ltr; unicode-bidi: isolate; white-space: nowrap; }
        .td-money { color: var(--td-navy); font-weight: 800; }
        .td-money.is-success { color: #078653; }
        .td-money.is-warning { color: #d17a0d; }
        .td-count { display: inline-flex; align-items: center; gap: 6px; min-width: 60px; color: #425775; font-weight: 700; }
        .td-row-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; min-width: 320px; }
        .td-action { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 35px; padding: 7px 10px; border: 1px solid #d9e3ef; border-radius: 8px; color: #425774; background: #fff; font-size: .78rem; font-weight: 750; white-space: nowrap; }
        .td-action:hover { color: var(--td-blue); border-color: #b9d3f8; background: #f6faff; text-decoration: none; }
        .td-action.is-primary { color: #fff; border-color: var(--td-blue); background: var(--td-blue); }
        .td-action.is-success { color: #087d50; border-color: #bfe6d3; background: #effbf5; }
        .td-action.is-danger { width: 35px; padding: 7px; color: #dc3545; border-color: #f1c7cc; background: #fff7f8; }
        .td-empty { padding: 55px 20px !important; text-align: center !important; }
        .td-empty i { display: block; margin-bottom: 12px; color: #b3c1d2; font-size: 2rem; }
        .td-empty strong { display: block; color: var(--td-navy); font-size: 1.05rem; }
        .td-empty span { display: block; margin-top: 5px; color: var(--td-muted); }
        .td-limit { margin-bottom: 16px; padding: 12px 15px; color: #586981; background: #f8fafc; border: 1px solid var(--td-line); border-radius: 11px; }
        .td-limit.is-reached { color: #805800; background: #fff8e5; border-color: #f4dfaa; }
        .td-modal { direction: rtl; text-align: right; }
        .td-modal .modal-content { overflow: hidden; border: 0; border-radius: 15px; box-shadow: 0 22px 65px rgba(15,38,70,.2); }
        .td-modal .modal-header { align-items: center; padding: 18px 20px; border-bottom-color: var(--td-line); }
        .td-modal .modal-title { color: var(--td-navy); font-weight: 800; }
        .td-modal .close { margin: -1rem auto -1rem -1rem; }
        .td-modal label { color: #324762; font-weight: 700; }
        .td-modal .form-control { min-height: 44px; border-color: #d5dfec; border-radius: 9px; }
        .td-help { padding: 11px 13px; color: #566982; background: #f4f8fd; border: 1px solid #dce8f5; border-radius: 9px; font-size: .82rem; line-height: 1.7; }
        @media (max-width: 767px) {
            .tailor-directory { padding-top: 18px; }
            .tailor-directory .td-shell { width: min(100% - 20px, 1720px); }
            .td-page-head, .td-panel__head { align-items: stretch; flex-direction: column; }
            .td-head-actions, .td-head-actions .td-btn, .td-search { width: 100%; }
            .td-table-wrap { overflow: visible; }
            .td-table { min-width: 0; }
            .td-table thead { display: none; }
            .td-table, .td-table tbody, .td-table tr, .td-table td { display: block; width: 100%; }
            .td-table tbody { padding: 10px; }
            .td-table tbody tr { margin-bottom: 12px; padding: 12px; border: 1px solid var(--td-line); border-radius: 12px; }
            .td-table tbody td { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 9px 3px; border: 0; text-align: left; }
            .td-table tbody td::before { content: attr(data-label); color: #74839a; font-size: .78rem; font-weight: 700; text-align: right; }
            .td-table tbody td:first-child { display: block; }
            .td-table tbody td:first-child::before { display: none; }
            .td-row-actions { justify-content: flex-start; min-width: 0; }
            .td-empty { display: block !important; }
        }
    </style>

    <section class="main-content tailor-directory">
        <div class="td-shell">
            <div class="td-page-head">
                <div class="td-title">
                    <span class="td-title__icon"><i class="fas fa-user-cog"></i></span>
                    <div><h1>درزی اور کاریگر</h1><p>درزیوں کی اجرت، ایڈوانس، سیکیورٹی، نرخ اور آرڈرز ایک جگہ منظم کریں۔</p></div>
                </div>
                <div class="td-head-actions">
                    <a class="td-btn" href="{{ route('admin.production-workers.index') }}"><i class="fas fa-users-cog"></i> پروڈکشن کاریگر</a>
                    @unless($tailorLimitReached)
                        <a class="td-btn is-primary" href="{{ route('admin.Tailor.create') }}"><i class="fas fa-user-plus"></i> نیا درزی شامل کریں</a>
                    @endunless
                </div>
            </div>

            @foreach(['insert' => 'success', 'update' => 'warning', 'delete' => 'danger'] as $key => $tone)
                @if(Session::has($key))
                    <div class="td-notice is-{{ $tone }}" role="alert"><i class="fas fa-info-circle"></i><div>{{ Session::get($key) }}</div></div>
                @endif
            @endforeach
            @if($errors->any())
                <div class="td-notice is-danger" role="alert"><i class="fas fa-exclamation-circle"></i><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
            @endif

            @if($tailorLimit !== null)
                <div class="td-limit {{ $tailorLimitReached ? 'is-reached' : '' }}">
                    <i class="fas fa-layer-group ml-1"></i> پلان حد: <strong>{{ $Tailors->count() }} / {{ $tailorLimit }}</strong> درزی
                    @if($tailorLimitReached) — مزید درزی کے لیے پلان اپ گریڈ کریں۔ @endif
                </div>
            @endif

            <div class="td-panel">
                <div class="td-panel__head">
                    <div class="td-panel__title"><h2>درزیوں کی فہرست</h2><p>ہر درزی کا موجودہ حساب دیکھیں یا متعلقہ کام پر براہِ راست جائیں۔</p></div>
                    <label class="td-search" for="tailorDirectorySearch"><i class="fas fa-search"></i><input id="tailorDirectorySearch" type="search" autocomplete="off" placeholder="نام یا فون نمبر سے تلاش کریں"></label>
                </div>
                <div class="td-table-wrap">
                    <table class="td-table" id="tailorDirectoryTable">
                        <thead><tr>
                            <th>درزی</th><th>رابطہ نمبر</th><th>سیکیورٹی ڈپازٹ</th><th>قابلِ وصول ایڈوانس</th><th>اس ہفتے کی اجرت</th><th>کل آرڈرز</th><th>حساب اور لین دین</th><th>مزید عمل</th>
                        </tr></thead>
                        <tbody>
                            @forelse($Tailors as $tailor)
                                @php($initial = mb_substr(trim($tailor->name), 0, 1))
                                <tr data-tailor-row>
                                    <td data-label="درزی"><div class="td-person"><span class="td-avatar">{{ $initial }}</span><div><strong>{{ $tailor->name }}</strong><small>{{ $tailor->tailorsalary->count() }} سلائی نرخ محفوظ</small></div></div></td>
                                    <td data-label="رابطہ نمبر"><span class="td-phone">{{ $tailor->phone_number1 ?: '—' }}</span></td>
                                    <td data-label="سیکیورٹی ڈپازٹ"><span class="td-money is-success">Rs. {{ number_format((float) ($tailor->security_deposit ?? 0), 2) }}</span></td>
                                    <td data-label="قابلِ وصول ایڈوانس"><span class="td-money is-warning">Rs. {{ number_format((float) ($tailor->advance ?? 0), 2) }}</span></td>
                                    <td data-label="اس ہفتے کی اجرت"><span class="td-money">Rs. {{ number_format((float) $weeklyEarnings->get($tailor->id, 0), 2) }}</span></td>
                                    <td data-label="کل آرڈرز"><span class="td-count"><i class="fas fa-clipboard-list"></i> {{ number_format($tailor->orders_count) }}</span></td>
                                    <td data-label="حساب اور لین دین"><div class="td-row-actions">
                                        <a class="td-action is-primary" href="{{ route('admin.tailor-report', $tailor->id) }}"><i class="fas fa-file-invoice-dollar"></i> حساب دیکھیں</a>
                                        <button type="button" class="td-action is-success" data-toggle="modal" data-target="#addRecordModal_{{ $tailor->id }}"><i class="fas fa-plus-circle"></i> ایڈوانس دیں</button>
                                        <button type="button" class="td-action" data-toggle="modal" data-target="#securityDepositModal_{{ $tailor->id }}"><i class="fas fa-shield-alt"></i> سیکیورٹی</button>
                                    </div></td>
                                    <td data-label="مزید عمل"><div class="td-row-actions">
                                        <a class="td-action" href="{{ route('admin.tailor-orders', $tailor->id) }}"><i class="fas fa-tshirt"></i> آرڈرز</a>
                                        <a class="td-action" href="{{ route('admin.tailor-rates', $tailor->id) }}"><i class="fas fa-tags"></i> نرخ</a>
                                        <a class="td-action" href="{{ route('admin.Tailor.edit', $tailor->id) }}" aria-label="درزی میں ترمیم کریں"><i class="fas fa-edit"></i> ترمیم</a>
                                        <form action="{{ route('admin.Tailor.destroy', $tailor->id) }}" method="POST" class="d-inline" data-confirm="کیا آپ واقعی یہ درزی حذف کرنا چاہتے ہیں؟">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="td-action is-danger delete-tr" aria-label="درزی حذف کریں"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="td-empty"><i class="fas fa-user-tie"></i><strong>ابھی کوئی درزی شامل نہیں ہے</strong><span>اپنا پہلا درزی شامل کر کے نرخ اور آرڈرز منظم کریں۔</span></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    @foreach($Tailors as $tailor)
        <div class="modal fade td-modal" id="addRecordModal_{{ $tailor->id }}" tabindex="-1" role="dialog" aria-labelledby="addRecordModalLabel_{{ $tailor->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="addRecordModalLabel_{{ $tailor->id }}"><i class="fas fa-hand-holding-usd text-success ml-2"></i>{{ $tailor->name }} کو ایڈوانس دیں</h5><button type="button" class="close" data-dismiss="modal" aria-label="بند کریں">&times;</button></div>
                <form method="post" action="{{ route('admin.tailor.addAdvanceRecord', $tailor->id) }}">@csrf
                    <div class="modal-body">
                        <div class="td-help mb-3">یہ رقم دکان درزی کو دے رہی ہے۔ اسے قابلِ وصول ایڈوانس میں شامل کر کے آئندہ اجرت سے واپس لیا جا سکتا ہے۔</div>
                        <div class="form-group mb-0"><label for="advance_amount_{{ $tailor->id }}">ایڈوانس رقم (روپے)</label><input id="advance_amount_{{ $tailor->id }}" type="number" min="0.01" step="0.01" name="amount" class="form-control" placeholder="مثلاً 500" required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="td-btn" data-dismiss="modal">منسوخ کریں</button><button type="submit" class="td-btn is-primary"><i class="fas fa-save"></i> ایڈوانس محفوظ کریں</button></div>
                </form>
            </div></div>
        </div>

        <div class="modal fade td-modal" id="securityDepositModal_{{ $tailor->id }}" tabindex="-1" role="dialog" aria-labelledby="securityDepositModalLabel_{{ $tailor->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="securityDepositModalLabel_{{ $tailor->id }}"><i class="fas fa-shield-alt text-primary ml-2"></i>{{ $tailor->name }} کی سیکیورٹی ڈپازٹ</h5><button type="button" class="close" data-dismiss="modal" aria-label="بند کریں">&times;</button></div>
                <form method="post" action="{{ route('admin.tailor.securityDeposit', $tailor->id) }}">@csrf
                    <div class="modal-body">
                        <div class="td-help mb-3">سیکیورٹی ڈپازٹ درزی سے وصول کی گئی امانتی رقم ہے۔ درزی کو دیا گیا ایڈوانس الگ حساب میں رہتا ہے۔</div>
                        <div class="form-group"><label for="security_type_{{ $tailor->id }}">لین دین کی قسم</label><select id="security_type_{{ $tailor->id }}" name="transaction_type" class="form-control" required><option value="received">درزی سے مزید رقم وصول کی</option><option value="refunded">درزی کو سیکیورٹی واپس کی</option></select></div>
                        <div class="form-group"><label for="security_amount_{{ $tailor->id }}">رقم (روپے)</label><input id="security_amount_{{ $tailor->id }}" type="number" min="0.01" step="0.01" name="amount" class="form-control" placeholder="مثلاً 1000" required></div>
                        <div class="form-group mb-0"><label for="security_note_{{ $tailor->id }}">نوٹ</label><input id="security_note_{{ $tailor->id }}" type="text" maxlength="500" name="note" class="form-control" placeholder="مثلاً رسید نمبر یا واپسی کی وجہ"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="td-btn" data-dismiss="modal">منسوخ کریں</button><button type="submit" class="td-btn is-primary"><i class="fas fa-save"></i> ریکارڈ محفوظ کریں</button></div>
                </form>
            </div></div>
        </div>
    @endforeach
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var search = document.getElementById('tailorDirectorySearch');
            if (!search) return;
            search.addEventListener('input', function () {
                var query = this.value.toLocaleLowerCase().trim();
                document.querySelectorAll('#tailorDirectoryTable [data-tailor-row]').forEach(function (row) {
                    row.style.display = !query || row.textContent.toLocaleLowerCase().includes(query) ? '' : 'none';
                });
            });
        });
    </script>
@endpush
