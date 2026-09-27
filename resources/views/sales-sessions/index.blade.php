@extends('main')

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
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light"><tr><th>ایجنٹ</th><th>حالت</th><th>گاہک</th><th>آئٹمز</th><th>آخری تبدیلی</th><th></th></tr></thead>
                    <tbody id="sales-session-list">
                    @forelse($sessions as $session)
                        <tr class="{{ $session->status === 'needs_attention' ? 'table-warning' : '' }}">
                            <td>{{ $session->agent?->name }}</td>
                            <td><span class="badge badge-{{ $session->status === 'completed' ? 'success' : ($session->status === 'needs_attention' ? 'warning' : ($session->status === 'claimed' ? 'info' : 'primary')) }}">{{ str_replace('_', ' ', $session->status) }}</span></td>
                            <td>{{ data_get($session->customer_data, 'name', $session->customer_mode === 'walk-in' ? 'Walk-in' : '—') }}</td>
                            <td>{{ count($session->items ?? []) }}</td>
                            <td>{{ $session->updated_at?->diffForHumans() }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.sales-sessions.show', $session) }}">کھولیں</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">ابھی کوئی سیلز سیشن نہیں ہے۔</td></tr>
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
            const row = body.insertRow(); const cell = row.insertCell(); cell.colSpan = 6;
            cell.className = 'text-center text-muted py-5'; cell.textContent = 'ابھی کوئی سیلز سیشن نہیں ہے۔';
            return;
        }
        const showUrl = @json(route('admin.sales-sessions.show', '__UUID__'));
        feed.data.forEach(function (session) {
            const row = body.insertRow();
            if (session.status === 'needs_attention') row.className = 'table-warning';
            const values = [
                session.agent?.name || '—',
                session.status.replaceAll('_', ' '),
                session.customer?.name || (session.customerMode === 'walk-in' ? 'Walk-in' : '—'),
                String(session.items?.length || 0),
                session.updatedAt ? new Date(session.updatedAt).toLocaleTimeString() : '—',
            ];
            values.forEach(function (value) { row.insertCell().textContent = value; });
            const action = row.insertCell(); const link = document.createElement('a');
            link.className = 'btn btn-sm btn-outline-primary'; link.textContent = 'کھولیں';
            link.href = showUrl.replace('__UUID__', encodeURIComponent(session.uuid)); action.appendChild(link);
        });
});
</script>
@endpush
