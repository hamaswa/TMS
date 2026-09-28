@extends('main')

@push('styles')
<style>
    .sales-session-card { border-radius: 18px; overflow: hidden; }
    .sales-session-table th, .sales-session-table td { vertical-align: middle; }
    .sales-session-table .session-action { white-space: nowrap; }
    @media (max-width: 767.98px) {
        .sales-session-table thead { display: none; }
        .sales-session-table, .sales-session-table tbody, .sales-session-table tr, .sales-session-table td { display: block; width: 100%; }
        .sales-session-table tbody { padding: .75rem; background: #f5f8fb; }
        .sales-session-table tr { margin-bottom: .85rem; padding: .6rem .85rem; border: 1px solid #dfe7ef; border-radius: 14px; background: #fff; box-shadow: 0 4px 14px rgba(18, 59, 93, .06); }
        .sales-session-table tr:last-child { margin-bottom: 0; }
        .sales-session-table td { display: grid; grid-template-columns: minmax(92px, 36%) 1fr; gap: .75rem; align-items: center; padding: .55rem .15rem; border-top: 1px solid #edf1f5; text-align: right; }
        .sales-session-table td:first-child { border-top: 0; }
        .sales-session-table td::before { content: attr(data-label); color: #6b7b88; font-size: .78rem; font-weight: 700; }
        .sales-session-table .session-action { display: block; padding-top: .8rem; }
        .sales-session-table .session-action::before { content: none; }
        .sales-session-table .session-action .btn { display: block; width: 100%; }
        .sales-session-table .empty-session-row { display: block; box-shadow: none; }
        .sales-session-table .empty-session-row td { display: block; text-align: center; border: 0; }
        .sales-session-table .empty-session-row td::before { content: none; }
    }
</style>
@endpush

@section('content')
<section class="main-content" dir="rtl">
    <div class="container-fluid px-3 px-md-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">سیلز اِن باکس</h2>
                <p class="text-muted mb-0">سیلز ایجنٹ کی جاری، مدد طلب اور حالیہ مکمل فروخت</p>
            </div>
            <span class="badge badge-warning p-2" id="attention-count">{{ $sessions->where('status', 'needs_attention')->count() }} توجہ طلب</span>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
        @php
            $statusLabels = [
                'active' => 'جاری',
                'needs_attention' => 'توجہ طلب',
                'claimed' => 'زیرِ کار',
                'completed' => 'مکمل',
                'cancelled' => 'منسوخ',
            ];
        @endphp
        <div class="card border-0 shadow-sm sales-session-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 sales-session-table">
                    <thead class="thead-light"><tr><th>ایجنٹ</th><th>حالت</th><th>گاہک</th><th>آئٹمز</th><th>آخری تبدیلی</th><th></th></tr></thead>
                    <tbody id="sales-session-list">
                    @forelse($sessions as $session)
                        <tr class="{{ $session->status === 'needs_attention' ? 'table-warning' : '' }}">
                            <td data-label="ایجنٹ">{{ $session->agent?->name ?: '—' }}</td>
                            <td data-label="حالت"><span class="badge badge-{{ $session->status === 'completed' ? 'success' : ($session->status === 'needs_attention' ? 'warning' : ($session->status === 'claimed' ? 'info' : 'primary')) }}">{{ $statusLabels[$session->status] ?? $session->status }}</span></td>
                            <td data-label="گاہک">{{ data_get($session->customer_data, 'name') ?: ($session->customer_mode === 'walk-in' ? 'واک اِن' : '—') }}</td>
                            <td data-label="آئٹمز">{{ count($session->items ?? []) }}</td>
                            <td data-label="آخری تبدیلی">{{ $session->updated_at?->format('d-m-Y h:i A') }}</td>
                            <td class="session-action"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.sales-sessions.show', $session) }}">{{ $session->status === 'completed' && $session->receipt ? 'رسید دیکھیں' : 'کھولیں' }}</a></td>
                        </tr>
                    @empty
                        <tr class="empty-session-row"><td colspan="6" class="text-center text-muted py-5">ابھی کوئی سیلز سیشن نہیں ہے۔</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $sessions->links() }}</div>
    </div>
</section>
@endsection

@push('scripts')
<script>
window.addEventListener('sales-session-feed', function (event) {
        const feed = event.detail;
        document.getElementById('attention-count').textContent = feed.attentionCount + ' توجہ طلب';
        const body = document.getElementById('sales-session-list');
        body.innerHTML = '';
        if (!feed.data.length) {
            const row = body.insertRow(); row.className = 'empty-session-row'; const cell = row.insertCell(); cell.colSpan = 6;
            cell.className = 'text-center text-muted py-5'; cell.textContent = 'ابھی کوئی سیلز سیشن نہیں ہے۔';
            return;
        }
        const showUrl = @json(route('admin.sales-sessions.show', '__UUID__'));
        const labels = ['ایجنٹ', 'حالت', 'گاہک', 'آئٹمز', 'آخری تبدیلی'];
        const statusLabels = { active: 'جاری', needs_attention: 'توجہ طلب', claimed: 'زیرِ کار', completed: 'مکمل', cancelled: 'منسوخ' };
        const formatDateTime = function (value) {
            if (!value) return '—';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '—';
            return new Intl.DateTimeFormat('en-GB', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true,
            }).format(date).replace(',', '');
        };
        feed.data.forEach(function (session) {
            const row = body.insertRow();
            if (session.status === 'needs_attention') row.className = 'table-warning';
            const values = [
                session.agent?.name || '—',
                statusLabels[session.status] || session.status,
                session.customer?.name || (session.customerMode === 'walk-in' ? 'واک اِن' : '—'),
                String(session.items?.length || 0),
                formatDateTime(session.updatedAt),
            ];
            values.forEach(function (value, index) {
                const cell = row.insertCell(); cell.textContent = value; cell.dataset.label = labels[index];
            });
            const action = row.insertCell(); const link = document.createElement('a');
            action.className = 'session-action';
            link.className = 'btn btn-sm btn-outline-primary'; link.textContent = session.status === 'completed' && session.receipt ? 'رسید دیکھیں' : 'کھولیں';
            link.href = showUrl.replace('__UUID__', encodeURIComponent(session.uuid)); action.appendChild(link);
        });
});
</script>
@endpush
