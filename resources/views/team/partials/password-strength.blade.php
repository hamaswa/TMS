@once
<style>
    .password-strength{margin-top:.55rem;padding:.7rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc}.password-strength-bar{height:6px;overflow:hidden;border-radius:999px;background:#e2e8f0}.password-strength-fill{width:0;height:100%;border-radius:inherit;background:#dc3545;transition:width .2s,background .2s}.password-strength-rules{display:flex;flex-wrap:wrap;gap:.25rem .75rem;margin:.55rem 0 0;padding:0;list-style:none;font-size:.78rem;color:#718096}.password-strength-rules li::before{content:'○';margin-left:.25rem}.password-strength-rules li.valid{color:#198754}.password-strength-rules li.valid::before{content:'✓'}.password-strength-label{display:block;margin-top:.35rem;font-size:.78rem;font-weight:700;color:#718096}
</style>
@endonce
<div class="password-strength" id="{{ $inputId }}-strength" aria-live="polite">
    <div class="password-strength-bar"><div class="password-strength-fill"></div></div>
    <span class="password-strength-label">مضبوط پاس ورڈ بنائیں</span>
    <ul class="password-strength-rules">
        <li data-rule="length">کم از کم 8 حروف</li><li data-rule="lower">چھوٹا حرف</li><li data-rule="upper">بڑا حرف</li><li data-rule="number">عدد</li><li data-rule="symbol">علامت</li>
    </ul>
</div>
<script>
(() => {
    const input = document.getElementById(@json($inputId));
    const panel = document.getElementById(@json($inputId . '-strength'));
    if (!input || !panel || panel.dataset.ready) return;
    panel.dataset.ready = '1';
    const checks = {
        length: value => value.length >= 8,
        lower: value => /[a-z]/.test(value),
        upper: value => /[A-Z]/.test(value),
        number: value => /[0-9]/.test(value),
        symbol: value => /[^A-Za-z0-9]/.test(value),
    };
    const update = () => {
        const value = input.value;
        let score = 0;
        Object.entries(checks).forEach(([rule, passes]) => {
            const valid = passes(value);
            panel.querySelector(`[data-rule="${rule}"]`)?.classList.toggle('valid', valid);
            if (valid) score++;
        });
        const fill = panel.querySelector('.password-strength-fill');
        const label = panel.querySelector('.password-strength-label');
        fill.style.width = `${score * 20}%`;
        fill.style.background = score === 5 ? '#198754' : (score >= 3 ? '#f0ad4e' : '#dc3545');
        label.textContent = score === 5 ? 'مضبوط پاس ورڈ' : (score >= 3 ? 'درمیانہ — تمام شرائط مکمل کریں' : 'کمزور پاس ورڈ');
    };
    input.addEventListener('input', update);
    update();
})();
</script>
