{{-- "Your order" panel beside the item cards: picked items with count, price and the total bill. Driven by item-boxes.blade.php. --}}
<style>
    .co-cart{display:flex;flex-direction:column;border-radius:18px;box-shadow:0 12px 30px rgba(15,37,71,.08)}
    .co-cart-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 18px 14px;border-bottom:1px solid #eef2f7}
    .co-cart-head h2{display:flex;align-items:center;gap:14px;margin:0;color:#0f172a;font-size:1.2rem;font-weight:900}
    .co-cart-icon{position:relative;display:grid;place-items:center;width:46px;height:46px;border-radius:13px;background:#eef4ff;color:#1769e0;font-size:1.25rem}
    .co-cart-count{position:absolute;top:-6px;inset-inline-end:-6px;display:grid;place-items:center;min-width:20px;height:20px;padding:0 5px;border:2px solid #fff;border-radius:999px;background:#ef4444;color:#fff;font-size:.68rem;font-weight:900}
    .co-cart-clear{display:inline-flex;align-items:center;gap:6px;border:0;background:none;color:#dc2626;font-size:.88rem;font-weight:800}
    .co-cart-lines{max-height:calc(100vh - 330px);min-height:90px;overflow-y:auto}
    .co-cart-line{display:flex;gap:12px;padding:14px 18px;border-bottom:1px solid #eef2f7}
    .co-cart-thumb{position:relative;display:grid;flex:0 0 72px;place-items:center;height:88px;overflow:hidden;border-radius:12px;background:#e8f0fd;color:#1769e0;font-size:1.6rem;font-weight:900}.co-cart-thumb img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
    .co-cart-line-main{flex:1;min-width:0}
    .co-cart-line-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}.co-cart-line-head strong{overflow:hidden;color:#0f172a;font-size:.95rem;text-overflow:ellipsis;white-space:nowrap}
    .co-cart-line-main>small{display:block;margin-top:2px;overflow:hidden;color:#64748b;font-size:.78rem;text-overflow:ellipsis;white-space:nowrap}
    .co-cart-remove{padding:0 2px;border:0;background:none;color:#94a3b8;font-size:.95rem}.co-cart-remove:hover{color:#dc2626}
    .co-cart-line-body{display:flex;align-items:center;gap:8px;margin-top:10px}
    .co-cart-stepper{display:flex;align-items:center;overflow:hidden;border:1px solid #dbe4f0;border-radius:10px;background:#fff}
    .co-cart-stepper button{width:32px;height:36px;border:0;background:#f5f8fc;color:#0f172a;font-size:1rem;font-weight:900}
    .co-cart-stepper input{width:38px;height:36px;border:0;text-align:center;font-weight:800;-moz-appearance:textfield}
    .co-cart-stepper input::-webkit-inner-spin-button{display:none}
    .co-cart-field{flex:1;margin:0;color:#64748b;font-size:.72rem;font-weight:700}.co-cart-field .form-control{height:36px;padding:4px 10px;border-color:#dbe4f0;border-radius:10px;font-weight:700}
    .co-cart-stepper.is-metres input{width:58px}
    .co-cart-hint{margin-top:6px;color:#64748b;font-size:.76rem;font-weight:700}
    .co-cart-line-total{margin-top:8px;color:#0f172a;font-size:1.05rem;font-weight:900;text-align:end;direction:ltr}
    [dir=rtl] .co-cart-line-total{text-align:left}
    .co-cart-empty{padding:34px 18px;color:#64748b;text-align:center;font-size:.88rem}.co-cart-empty i{display:block;margin-bottom:10px;font-size:30px;color:#c4d0de}
    .co-cart-errors{margin:12px 18px 0;padding:10px 12px;border-radius:10px;background:#fff1f2;color:#be123c;font-size:.82rem;font-weight:700}
    .co-cart-foot{padding:16px 18px 18px;background:#f5f8ff}
    .co-cart-bill{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;color:#0f172a;font-size:1.15rem;font-weight:900}.co-cart-bill strong{color:#1769e0;font-size:1.35rem}
    .co-cart-submit{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;min-height:50px;border:0;border-radius:13px;background:#1769e0;color:#fff;font-size:1rem;font-weight:900;box-shadow:0 10px 22px rgba(23,105,224,.25)}
    .co-cart-submit:disabled{background:#9db8e6;box-shadow:none}
</style>
<section class="co-panel co-cart" id="co-cart" data-order="{{ $counterOrder->id }}"
    data-action="{{ route('admin.counter-orders.cloth-items.bulk', $counterOrder) }}" data-token="{{ csrf_token() }}">
    <div class="co-cart-head">
        <h2><span class="co-cart-icon"><i class="fas fa-shopping-cart"></i><span class="co-cart-count" id="co-cart-count" hidden>0</span></span>آپ کا آرڈر</h2>
        <button type="button" class="co-cart-clear" id="co-cart-clear" hidden><i class="fas fa-trash-alt"></i> سب ہٹائیں</button>
    </div>
    <div class="co-cart-lines" id="co-cart-lines"></div>
    <div class="co-cart-errors" id="co-cart-errors" role="alert" hidden></div>
    <div class="co-cart-foot">
        <div class="co-cart-bill"><span>کل بل</span><strong id="co-cart-total" dir="ltr">Rs. 0</strong></div>
        <button type="button" class="co-cart-submit" id="co-cart-submit" disabled><i class="fas fa-cart-plus"></i>
            آرڈر میں شامل کریں</button>
    </div>
</section>
