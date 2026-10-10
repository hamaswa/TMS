@php
    $isDelivered = $order->status === 'delivered';
    $isReady = $order->status === 'ready';
    $assignments = $order->workAssignments->whereNull('legacy_key');
@endphp

<div class="modal-header">
    <div>
        <h3 class="modal-title">آرڈر #{{ $order->id }} — پروڈکشن تفصیل</h3>
        <small class="text-muted">{{ $order->customers?->name }} ·
            {{ $order->measurementTemplate?->name ?: ($order->suitNum ?: 'سلائی') }}</small>
    </div>
    <button type="button" class="close ml-0" data-dismiss="modal"><span>&times;</span></button>
</div>

<div class="modal-body">
    <div class="wo-modal-summary">
        <span @class([
            'js-modal-order-status',
            'is-ready' => $isReady,
            'is-delivered' => $isDelivered,
        ])><i
                class="fas {{ $isDelivered ? 'fa-handshake' : ($isReady ? 'fa-check-circle' : 'fa-tools') }}"></i><b>{{ $statusLabels[$order->status] ?? $order->status }}</b></span>
        <span><i class="fas fa-user-cog"></i><b
                class="js-modal-tailor-name">{{ $order->tailor?->name ?: 'درزی مقرر نہیں' }}</b></span>
        <span><i
                class="far fa-calendar-alt"></i>{{ $order->returnDate ? \Illuminate\Support\Carbon::parse($order->returnDate)->format('d-m-Y') : 'واپسی کی تاریخ موجود نہیں' }}</span>
    </div>
    @if (filled($order->remarks))
        <section class="wo-modal-section">
            <h4><i class="fas fa-sticky-note ml-1 text-primary"></i>پروڈکشن نوٹ</h4>
            <div class="wo-note">{{ $order->remarks }}</div>
        </section>
    @endif
    <section class="wo-modal-section">
        <h4><i class="fas fa-users-cog ml-1 text-primary"></i>کاریگر اور تفویض شدہ کام</h4>
        @forelse($assignments as $assignment)
            <div class="wo-assignment">
                <div><strong>{{ $assignment->worker?->name ?: 'کاریگر' }} —
                        {{ $assignment->workType?->name ?: 'کام' }}</strong><small>{{ number_format((float) $assignment->quantity, 2) }}
                        مقدار ·
                        {{ $workerStatusLabels[$assignment->status] ?? $assignment->status }}
                        @if ($assignment->notes)
                            · {{ $assignment->notes }}
                        @endif
                    </small>
                </div>
                @if (in_array($assignment->status, ['assigned', 'in_progress'], true))
                    <form method="POST" action="{{ route('admin.orders.workforce.status', [$order, $assignment]) }}"
                        class="wo-inline-form">@csrf @method('PATCH')<select name="status" class="wo-inline-select">
                            @if ($assignment->status === 'assigned')
                                <option value="in_progress">کام شروع</option>
                            @endif
                            <option value="completed">
                                مکمل</option>
                            <option value="cancelled">منسوخ</option>
                        </select><button class="wo-inline-save"><i class="fas fa-check"></i></button></form>
                @endif
            </div>
        @empty<div class="text-muted small">ابھی کسی دوسرے کاریگر کو کام نہیں دیا گیا۔</div>
        @endforelse

        <div class="wo-work-locked js-work-lock" @if (!$isReady && !$isDelivered) hidden @endif>
            <i
                class="fas fa-lock"></i><span>{{ $isDelivered ? 'آرڈر حوالہ کیا جا چکا ہے؛ نیا پروڈکشن کام شامل نہیں کیا جا سکتا۔' : 'آرڈر تیار ہے؛ اب نیا پروڈکشن کام شامل نہیں کیا جا سکتا۔' }}</span>
        </div>
        @if (!$isDelivered && $workers->isNotEmpty())
            <form method="POST" action="{{ route('admin.orders.workforce.store', $order) }}"
                class="wo-worker-form js-worker-form" @if ($isReady) hidden @endif>@csrf
                <select name="production_worker_id" class="form-control js-production-worker" required>
                    <option value="">کاریگر منتخب کریں</option>
                    @foreach ($workers as $worker)
                        <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                    @endforeach
                </select>
                <select name="work_type_id" class="form-control js-production-work" required disabled>
                    <option value="">پہلے کاریگر منتخب کریں</option>
                    @foreach ($workers as $worker)
                        @foreach ($worker->skills as $skill)
                            @php($plan = $worker->compensationPlans->firstWhere('work_type_id', $skill->id))@if ($plan && in_array($plan->method, ['per_piece', 'hybrid']) && (float) $plan->rate > 0)
                                <option value="{{ $skill->id }}" data-worker="{{ $worker->id }}" hidden disabled>
                                    {{ $skill->name }}</option>
                            @endif
                        @endforeach
                    @endforeach
                </select>
                <input type="number" name="quantity" min="0.001" step="0.001"
                    value="{{ max(1, (int) $order->suitQuantity) }}" class="form-control" aria-label="مقدار" required>
                <button class="btn btn-primary" type="submit"><i class="fas fa-plus ml-1"></i>کام دیں</button>
                <input name="notes" class="form-control notes" maxlength="1000" placeholder="کام کی ہدایت (اختیاری)">
            </form>
        @elseif(!$isReady && !$isDelivered)
            <a class="btn btn-outline-primary mt-2"
                href="{{ route('admin.team.index', ['open' => 'create', 'type' => 'production']) }}"><i
                    class="fas fa-user-plus ml-1"></i>کاریگر شامل کریں</a>
        @endif
    </section>
    <section class="wo-modal-section">
        <h4><i class="fas fa-history ml-1 text-primary"></i>پروڈکشن اور تقرری کی تاریخ</h4>
        <ul class="wo-history">
            @forelse($order->statusHistory as $history)
                <li><strong>{{ $statusLabels[$history->from_status] ?? $history->from_status }} →
                        {{ $statusLabels[$history->to_status] ?? $history->to_status }}</strong>
                    @if ($history->note)
                        <div>{{ $history->note }}</div>
                    @endif
                    <time>
                        {{ optional($history->created_at)->format('d-m-Y h:i A') }}</time>
            </li>@empty<li>ابھی کوئی تبدیلی درج نہیں ہوئی۔</li>
            @endforelse
        </ul>
    </section>
</div>

<div class="modal-footer">
    @if ($canManageOrders)
        <a class="btn btn-outline-primary" href="{{ route('admin.order-print', $order) }}" target="_blank"
            rel="noopener">
            <i class="fas fa-print ml-1"></i>رسید</a>
        <a class="btn btn-primary" href="{{ route('admin.order.edit', $order) }}">
            <i class="fas fa-pen ml-1"></i>آرڈر میں ترمیم</a>
    @endif
    <button type="button" class="btn btn-light" data-dismiss="modal">بند کریں</button>
</div>
