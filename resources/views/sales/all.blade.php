@extends('main')

@push('styles')
    <style>
        .as-page {
            min-height: calc(100vh - 65px);
            padding: 22px 0 40px;
            background: #f4f7fb;
            color: #0f1f3d
        }

        .as-shell {
            width: min(100% - 32px, 1580px);
            margin: auto
        }

        .as-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px
        }

        .as-head h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 900
        }

        .as-head p {
            margin: 4px 0 0;
            color: #64748b
        }

        .as-head a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border: 1px solid #c9d8f0;
            border-radius: 10px;
            background: #fff;
            color: #2563eb !important;
            font-weight: 800;
            text-decoration: none !important
        }

        .as-card {
            border: 1px solid #e4eaf2;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 4px 18px rgba(15, 31, 61, .05)
        }

        .as-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 10px;
            padding: 16px 18px;
            border-bottom: 1px solid #e4eaf2
        }

        .as-filters label {
            display: block;
            margin: 0 0 4px;
            color: #475569;
            font-size: .78rem;
            font-weight: 800
        }

        .as-filters .form-control {
            min-width: 170px;
            height: 42px;
            border-radius: 10px
        }

        .as-filters .as-search {
            min-width: 240px
        }

        .as-filters button,
        .as-filters .as-clear {
            height: 42px;
            padding: 0 16px;
            border-radius: 10px;
            font-weight: 800
        }

        .as-count {
            margin-inline-start: auto;
            color: #64748b;
            font-weight: 800
        }

        .as-table {
            width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0
        }

        .as-table th {
            padding: 11px 14px;
            border-bottom: 1px solid #e4eaf2;
            background: #f6f8fc;
            color: #475569;
            font-size: .8rem;
            font-weight: 900;
            white-space: nowrap
        }

        .as-table td {
            padding: 13px 14px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle
        }

        .as-table tr:last-child td {
            border-bottom: 0
        }

        .as-muted {
            color: #64748b;
            font-size: .82rem
        }

        .as-num {
            direction: ltr;
            unicode-bidi: isolate;
            font-weight: 800
        }

        .as-money {
            direction: ltr;
            unicode-bidi: isolate;
            font-weight: 900;
            white-space: nowrap
        }

        .as-empty {
            padding: 46px 10px !important;
            color: #94a3b8;
            text-align: center
        }

        .as-pagination {
            padding: 14px 18px;
            border-top: 1px solid #e4eaf2
        }

        .sale-actions-toggle {
            width: 36px;
            height: 36px;
            color: #53647b;
            border: 1px solid #dde5ef;
            border-radius: 10px
        }

        .sale-actions-menu {
            position: fixed;
            z-index: 1060;
            display: none;
            min-width: 220px;
            padding: 6px;
            background: #fff;
            border: 1px solid #dde5ef;
            border-radius: 12px;
            box-shadow: 0 14px 35px rgba(31, 45, 61, .18)
        }

        .sale-actions-menu.is-open {
            display: block
        }

        .sale-action {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 9px 12px;
            color: #243b53 !important;
            background: transparent;
            border: 0;
            border-radius: 8px;
            font-size: .9rem;
            font-weight: 700;
            text-align: right;
            text-decoration: none !important
        }

        .sale-action:hover {
            background: #f1f6ff
        }

        .sale-action i {
            width: 16px;
            color: #2563eb
        }

        .sale-action--return i {
            color: #e11d48
        }

        @media(max-width:767.98px) {
            .as-shell {
                width: min(100% - 22px, 1580px)
            }

            .as-filters .form-control,
            .as-filters .as-search {
                min-width: 0;
                width: 100%
            }

            .as-filters>div {
                flex: 1 1 100%
            }

            .as-count {
                margin: 0
            }
        }
    </style>
@endpush

@section('content')
    @php
        $money = fn($value) => 'Rs. ' . number_format((float) $value, 2);
        $qty = fn($value, $unit) => \App\Models\ClothCategory::isWholeUnit($unit)
            ? number_format((float) $value)
            : number_format((float) $value, 2);
        $unitLabel = fn($unit) => \App\Models\ClothCategory::unitLabelFor($unit);
        $canCustomerOrders = Auth::user()->hasBusinessPermission('tailoring.orders');
        $filtered = collect($filters)->filter(fn($value) => filled($value))->isNotEmpty();
    @endphp
    <section class="as-page" dir="rtl">
        <div class="as-shell">
            <div class="as-head">
                <div>
                    <h1><i class="fas fa-receipt ml-1"></i> تمام فروخت</h1>
                    <p>دکان کی ہر کپڑے کی فروخت، نئی سے پرانی۔ تاریخ خالی چھوڑیں تو تمام ریکارڈ دکھائے جائیں گے۔</p>
                </div>
                @if (Auth::user()->hasBusinessPermission('finance.view'))
                    <a href="{{ route('admin.financial-reports.index', ['view' => 'sales', 'tab' => 'all-sales']) }}"><i
                            class="fas fa-chart-bar"></i> رپورٹس میں دیکھیں</a>
                @endif
            </div>

            <div class="as-card">
                <form class="as-filters" method="GET" action="{{ route('admin.sales.all') }}">
                    <div><label for="as-q">تلاش</label><input id="as-q" name="q"
                            class="form-control as-search" maxlength="100" value="{{ $filters['q'] ?? '' }}"
                            placeholder="گاہک، فون، سیٹ یا رنگ"></div>
                    <div><label for="as-category">کیٹیگری</label>
                        <select id="as-category" name="sales_category" class="form-control">
                            <option value="">تمام کیٹیگریز</option>
                            @foreach ($categoryOptions as $category)
                                <option value="{{ $category->id }}" @selected((int) ($filters['sales_category'] ?? 0) === $category->id)>{{ $category->fullName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="as-start">سے</label><input id="as-start" type="date" name="start_date"
                            class="form-control" value="{{ $filters['start_date'] ?? '' }}"></div>
                    <div><label for="as-end">تک</label><input id="as-end" type="date" name="end_date"
                            class="form-control" value="{{ $filters['end_date'] ?? '' }}"></div>
                    <button class="btn btn-primary"><i class="fas fa-search ml-1"></i> دکھائیں</button>
                    @if ($filtered)
                        <a class="btn btn-light as-clear" href="{{ route('admin.sales.all') }}">صاف کریں</a>
                    @endif
                    <span class="as-count">کل <span class="as-num">{{ number_format($lines->total()) }}</span> فروخت</span>
                </form>

                <div class="table-responsive">
                    <table class="as-table">
                        <thead>
                            <tr>
                                <th>تاریخ</th>
                                <th>سیٹ</th>
                                <th>کیٹیگری</th>
                                <th>مقدار</th>
                                <th>رقم</th>
                                <th>گاہک</th>
                                <th class="text-center">اختیارات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $line)
                                @php
                                    $receiptUrl = $line['counter_order_id']
                                        ? route('admin.counter-orders.print', $line['counter_order_id'])
                                        : ($line['has_customer']
                                            ? route('admin.printStock', [
                                                'id' => $line['id'],
                                                'customerId' => $line['customer_id'],
                                            ])
                                            : null);
                                    // Both pages list this customer's purchases with receipt / return / cancel actions.
                                    $customerUrl = !$line['has_customer']
                                        ? null
                                        : ($canCustomerOrders
                                            ? route('admin.customer.orders', [
                                                'customer' => $line['customer_id'],
                                                'tab' => 'sales',
                                            ])
                                            : route('admin.customers.statement', $line['customer_id']));
                                @endphp
                                <tr>
                                    <td class="as-num">{{ \Carbon\Carbon::parse($line['date'])->format('Y-m-d') }}</td>
                                    <td><strong>{{ $line['name'] }}</strong>
                                        @if ($line['color'] && $line['color'] !== 'عام')
                                            <div class="as-muted">{{ $line['color'] }}</div>
                                        @endif
                                    </td>
                                    <td class="as-muted">{{ $line['category'] }}</td>
                                    <td><span class="as-num">{{ $qty($line['quantity'], $line['unit']) }}</span>
                                        {{ $unitLabel($line['unit']) }}@if ($line['returned'] > 0)
                                            <div class="as-muted" style="color:#e11d48">واپسی: <span
                                                    class="as-num">{{ $qty($line['returned'], $line['unit']) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td><span class="as-money">{{ $money($line['amount']) }}</span></td>
                                    <td>{{ $line['customer'] }}@if ($line['phone'])
                                            <div class="as-muted as-num">{{ $line['phone'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($receiptUrl || $customerUrl || $line['counter_order_id'])
                                            <div class="sale-actions-dropdown">
                                                <button type="button" class="btn btn-light btn-sm sale-actions-toggle"
                                                    aria-haspopup="true" aria-expanded="false"
                                                    aria-label="{{ $line['name'] }} کے اختیارات"><i
                                                        class="fas fa-ellipsis-v"></i></button>
                                                <div class="sale-actions-menu" role="menu" dir="rtl">
                                                    @if ($receiptUrl)
                                                        <a class="sale-action" role="menuitem" href="{{ $receiptUrl }}"
                                                            target="_blank"><i class="fas fa-print"></i> رسید دیکھیں / پرنٹ
                                                            کریں</a>
                                                    @endif
                                                    @if ($line['counter_order_id'])
                                                        <a class="sale-action" role="menuitem"
                                                            href="{{ route('admin.counter-orders.edit', $line['counter_order_id']) }}"><i
                                                                class="fas fa-cash-register"></i> آرڈر کھولیں</a>
                                                    @endif
                                                    @if ($customerUrl)
                                                        <a class="sale-action" role="menuitem"
                                                            href="{{ $customerUrl }}"><i class="fas fa-user"></i> گاہک کی
                                                            تمام خریداری</a>
                                                        <a class="sale-action sale-action--return" role="menuitem"
                                                            href="{{ $customerUrl }}"><i class="fas fa-undo-alt"></i>
                                                            واپسی / منسوخی</a>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="as-empty">
                                            {{ $filtered ? 'ان شرائط پر کوئی فروخت نہیں ملی۔' : 'ابھی کوئی فروخت موجود نہیں۔' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($lines->hasPages())
                        <div class="as-pagination">{{ $lines->links() }}</div>
                    @endif
                </div>
            </div>
        </section>
    @endsection

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // ⋮ menus are fixed-positioned so the table's scroll box doesn't clip them.
                function closeMenus() {
                    document.querySelectorAll('.sale-actions-menu.is-open').forEach(function(menu) {
                        menu.classList.remove('is-open');
                    });
                    document.querySelectorAll('.sale-actions-toggle[aria-expanded="true"]').forEach(function(toggle) {
                        toggle.setAttribute('aria-expanded', 'false');
                    });
                }
                document.addEventListener('click', function(event) {
                    var toggle = event.target.closest('.sale-actions-toggle');
                    if (!toggle) {
                        if (!event.target.closest('.sale-actions-menu') || event.target.closest('.sale-action'))
                            closeMenus();
                        return;
                    }
                    var menu = toggle.parentElement.querySelector('.sale-actions-menu'),
                        wasOpen = menu.classList.contains('is-open');
                    closeMenus();
                    if (wasOpen) return;
                    menu.classList.add('is-open');
                    toggle.setAttribute('aria-expanded', 'true');
                    var rect = toggle.getBoundingClientRect(),
                        width = menu.offsetWidth,
                        height = menu.offsetHeight;
                    var top = rect.bottom + 6,
                        left = rect.right - width;
                    if (top + height > window.innerHeight - 8) top = Math.max(8, rect.top - height - 6);
                    menu.style.top = top + 'px';
                    menu.style.left = Math.min(Math.max(8, left), window.innerWidth - width - 8) + 'px';
                });
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') closeMenus();
                });
                window.addEventListener('scroll', closeMenus, true);
            });
        </script>
    @endpush
