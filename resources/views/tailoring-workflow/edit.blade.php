@extends('main')

@section('content')
<style>
    .workflow-page{min-height:calc(100vh - 70px);padding:34px 20px;background:#f4f7fa;direction:rtl}.workflow-shell{width:min(100%,980px);margin:auto}.workflow-head{margin-bottom:20px}.workflow-head h1{margin:0;color:#11365b;font-size:1.55rem;font-weight:900}.workflow-head p{margin:7px 0 0;color:#718198}.workflow-card{padding:24px;border:1px solid #dfe7f1;border-radius:18px;background:#fff;box-shadow:0 10px 28px rgba(24,55,88,.07)}.workflow-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.workflow-option{position:relative;display:block;height:100%;padding:20px;border:2px solid #e2e9f2;border-radius:15px;background:#fbfcfe;cursor:pointer;transition:.2s}.workflow-option:hover{border-color:#a8c7ef;transform:translateY(-1px)}.workflow-option input{position:absolute;opacity:0}.workflow-option:has(input:checked){border-color:#1769e0;background:#f0f6ff;box-shadow:0 0 0 4px rgba(23,105,224,.08)}.workflow-title{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;color:#163b62;font-size:1.05rem;font-weight:900}.workflow-check{display:grid;place-items:center;width:28px;height:28px;border:2px solid #cdd8e5;border-radius:50%;color:transparent}.workflow-option:has(input:checked) .workflow-check{color:#fff;border-color:#1769e0;background:#1769e0}.workflow-option p{min-height:50px;margin:0 0 14px;color:#718198;line-height:2}.workflow-stages{display:flex;flex-wrap:wrap;gap:7px}.workflow-stages span{padding:5px 9px;border-radius:999px;color:#50667f;background:#edf2f7;font-size:.75rem;font-weight:800}.workflow-option:has(input:checked) .workflow-stages span{color:#1769e0;background:#e1eeff}.workflow-note{margin:18px 0;padding:13px 15px;border-radius:11px;color:#6b7788;background:#f6f8fb}.workflow-actions{display:flex;justify-content:flex-start}.workflow-save{padding:10px 20px;border:0;border-radius:10px;color:#fff;background:#1769e0;font-family:inherit;font-weight:900;cursor:pointer}@media(max-width:700px){.workflow-options{grid-template-columns:1fr}.workflow-page{padding:20px 12px}.workflow-card{padding:16px}}
</style>

<section class="main-content workflow-page">
    <div class="workflow-shell">
        @include('inc.message')
        <header class="workflow-head"><h1><i class="fas fa-project-diagram ml-2 text-primary"></i>سلائی کے کام کی حالتیں</h1><p>اپنی دکان کے طریقۂ کار کے مطابق سادہ یا تفصیلی نظام منتخب کریں۔</p></header>
        <form class="workflow-card" method="POST" action="{{ route('admin.tailoring-workflow.update') }}">
            @csrf @method('PUT')
            <div class="workflow-options">
                <label class="workflow-option">
                    <input type="radio" name="tailoring_status_mode" value="simple" @checked(old('tailoring_status_mode', $business->tailoring_status_mode ?: 'simple') === 'simple')>
                    <span class="workflow-title">سادہ نظام <i class="fas fa-check workflow-check"></i></span>
                    <p>چھوٹی دکانوں کے لیے تیز اور آسان دو حالتوں والا نظام۔ یہ پہلے سے منتخب ہے۔</p>
                    <span class="workflow-stages"><span>کارخانے میں ہے</span><span>تیار ہے</span></span>
                </label>
                <label class="workflow-option">
                    <input type="radio" name="tailoring_status_mode" value="detailed" @checked(old('tailoring_status_mode', $business->tailoring_status_mode) === 'detailed')>
                    <span class="workflow-title">تفصیلی نظام <i class="fas fa-check workflow-check"></i></span>
                    <p>ان دکانوں کے لیے جو ہر آرڈر کو مرحلہ وار ٹریک کرنا چاہتی ہیں۔</p>
                    <span class="workflow-stages"><span>درزی مقرر</span><span>کٹائی</span><span>سلائی</span><span>ٹرائل</span><span>تیار</span><span>حوالہ شدہ</span></span>
                </label>
            </div>
            @error('tailoring_status_mode')<div class="text-danger mt-2">{{ $message }}</div>@enderror
            <div class="workflow-note"><i class="fas fa-info-circle ml-1"></i>نظام تبدیل کرنے سے پرانے آرڈرز یا ان کی تاریخ حذف نہیں ہوگی۔ QR صفحہ بھی منتخب نظام کے مطابق موجودہ حالت دکھائے گا۔</div>
            <div class="workflow-actions"><button class="workflow-save" type="submit"><i class="fas fa-save ml-1"></i>ترتیب محفوظ کریں</button></div>
        </form>
    </div>
</section>
@endsection
