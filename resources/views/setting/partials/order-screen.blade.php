{{-- "Order screen" section of the settings page: how the counter order page offers stock (a dropdown row,
     or a box per item with a cart). Saved by OrderScreenSettingController. --}}
<style>
    .screen-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.screen-option{position:relative;display:block;height:100%;padding:20px;border:2px solid #e2e9f2;border-radius:15px;background:#fbfcfe;cursor:pointer;transition:.2s}.screen-option:hover{border-color:#a8c7ef}.screen-option input{position:absolute;opacity:0}.screen-option:has(input:checked){border-color:#1769e0;background:#f0f6ff;box-shadow:0 0 0 4px rgba(23,105,224,.08)}.screen-title{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;color:#163b62;font-size:1.05rem;font-weight:900}.screen-check{display:grid;place-items:center;width:28px;height:28px;border:2px solid #cdd8e5;border-radius:50%;color:transparent}.screen-option:has(input:checked) .screen-check{color:#fff;border-color:#1769e0;background:#1769e0}.screen-option p{margin:0 0 14px;color:#718198;line-height:2}
    .screen-preview{display:grid;gap:6px;padding:12px;border:1px solid #e2e9f2;border-radius:11px;background:#fff}.screen-preview .bar{height:12px;border-radius:6px;background:#e6edf5}.screen-preview .bar.is-blue{width:45%;background:#cfe0fa}.screen-preview.is-grid{grid-template-columns:repeat(3,1fr) 34%}.screen-preview.is-grid .box{height:38px;border-radius:8px;background:#e6edf5}.screen-preview.is-grid .cart{grid-column:4;grid-row:1/3;border-radius:8px;background:#cfe0fa}
    .screen-note{margin:18px 0;padding:13px 15px;border-radius:11px;color:#6b7788;background:#f6f8fb}.screen-save{padding:10px 20px;border:0;border-radius:10px;color:#fff;background:#1769e0;font-family:inherit;font-weight:900;cursor:pointer}@media(max-width:700px){.screen-options{grid-template-columns:1fr}}
</style>
@php($layout = old('counter_order_layout', $business?->counter_order_layout ?: \App\Models\Business::ORDER_LAYOUT_LIST))
<section class="st-card is-full" id="order-screen">
    <div class="st-card-head">
        <h2 class="st-card-title"><i class="fas fa-th-large"></i> آرڈر اسکرین</h2>
        <span class="text-muted small">نئے آرڈر میں آئٹم شامل کرنے کا طریقہ منتخب کریں۔</span>
    </div>
    <form method="POST" action="{{ route('admin.order-screen.update') }}">
        @csrf @method('PUT')
        <div class="screen-options">
            <label class="screen-option">
                <input type="radio" name="counter_order_layout" value="list" @checked($layout === 'list')>
                <span class="screen-title">فہرست <i class="fas fa-check screen-check"></i></span>
                <p>ایک قطار میں آئٹم منتخب کریں، پھر میٹر / تعداد اور قیمت لکھیں۔ یہ پہلے سے منتخب ہے۔</p>
                <span class="screen-preview"><span class="bar is-blue"></span><span class="bar"></span><span class="bar"></span></span>
            </label>
            <label class="screen-option">
                <input type="radio" name="counter_order_layout" value="grid" @checked($layout === 'grid')>
                <span class="screen-title">آئٹم باکس <i class="fas fa-check screen-check"></i></span>
                <p>ہر سیٹ / آئٹم ایک باکس میں دکھائیں۔ دبانے پر وہ ساتھ والی ٹوکری میں تعداد، قیمت اور کل بل کے ساتھ آ جائے گا۔</p>
                <span class="screen-preview is-grid"><span class="box"></span><span class="box"></span><span class="box"></span><span class="cart"></span><span class="box"></span><span class="box"></span><span class="box"></span></span>
            </label>
        </div>
        @error('counter_order_layout')<div class="text-danger mt-2">{{ $message }}</div>@enderror
        <div class="screen-note"><i class="fas fa-info-circle ml-1"></i>یہ ترتیب پوری دکان کے لیے ہے۔ پرانے آرڈرز پر کوئی اثر نہیں پڑے گا۔</div>
        <button class="screen-save" type="submit"><i class="fas fa-save ml-1"></i>ترتیب محفوظ کریں</button>
    </form>
</section>
