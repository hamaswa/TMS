@php
    $selectorId = $selectorId ?? 'permission-selector';
    $selectedPermissions = array_values($selectedPermissions ?? []);
@endphp

<div id="{{ $selectorId }}" class="permission-selector" data-role-name-target="{{ $roleNameTarget ?? '' }}">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
            <div>
                <h3 class="h6 font-weight-bold mb-1">تیار رول منتخب کریں</h3>
                <p class="text-muted small mb-0">قریب ترین ذمہ داری منتخب کریں، پھر ضرورت کے مطابق اجازتیں بدلیں۔</p>
            </div>
            <button type="button" class="btn btn-sm btn-link text-danger permission-clear">سب صاف کریں</button>
        </div>
        <div class="role-presets">
            @foreach($rolePresets as $key => $preset)
                <button type="button" class="role-preset" data-name="{{ $preset['label'] }}" data-permissions='@json($preset['permissions'])'>
                    <strong>{{ $preset['label'] }}</strong>
                    <small>{{ $preset['description'] }}</small>
                </button>
            @endforeach
        </div>
    </div>

    <div class="permission-summary mb-4" aria-live="polite">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>منتخب رسائی کا خلاصہ</strong>
            <span class="badge badge-primary permission-count">0 اجازتیں</span>
        </div>
        <div class="permission-summary-items text-muted small">ابھی کوئی اجازت منتخب نہیں کی گئی۔</div>
    </div>

    <div class="row">
        @foreach($permissionGroups as $groupKey => $group)
            <div class="col-lg-6 mb-3">
                <section class="permission-group h-100">
                    <div class="permission-group-head">
                        <div class="d-flex align-items-center">
                            <span class="permission-group-icon"><i class="fas {{ $group['icon'] }}"></i></span>
                            <div><h3 class="h6 font-weight-bold mb-0">{{ $group['label'] }}</h3><small class="text-muted">{{ $group['description'] }}</small></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary permission-group-toggle" data-permissions='@json(array_keys($group['permissions']))'>سب منتخب</button>
                    </div>
                    <div class="permission-options">
                        @foreach($group['permissions'] as $key => $label)
                            <label class="permission-option">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}" data-label="{{ $label }}" @checked(in_array($key, $selectedPermissions, true))>
                                <span><strong>{{ $label }}</strong>@if(in_array($key, [\App\Models\BusinessRole::TAILORING_ACCESS, \App\Models\BusinessRole::CLOTHING_ACCESS], true))<small>اس حصے کی بنیادی رسائی</small>@endif</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            </div>
        @endforeach
    </div>
</div>

<style>
    .role-presets{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:.65rem}
    .role-preset{border:1px solid #d9e4ee;border-radius:12px;background:#fff;padding:.75rem;text-align:right;color:#243b53;transition:.15s ease}
    .role-preset:hover,.role-preset.active{border-color:#1769aa;background:#eef7ff;color:#0f548c;box-shadow:0 5px 14px rgba(23,105,170,.12)}
    .role-preset strong,.role-preset small{display:block}.role-preset small{color:#6c7f90;margin-top:.2rem}
    .permission-summary{border:1px solid #b9dcf5;background:#eef8ff;border-radius:14px;padding:1rem}
    .permission-summary-items .badge{white-space:normal;line-height:1.5;margin:0 0 .3rem .3rem;padding:.35rem .55rem}
    .permission-group{border:1px solid #dfe7f0;border-radius:15px;background:#fff;overflow:hidden}
    .permission-group-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:1rem;background:#f7fafc;border-bottom:1px solid #e6edf3}
    .permission-group-icon{width:38px;height:38px;border-radius:11px;background:#e5f2fb;color:#1769aa;display:flex;align-items:center;justify-content:center;margin-left:.65rem;flex:0 0 auto}
    .permission-options{padding:.45rem 1rem}.permission-option{display:flex;align-items:flex-start;gap:.65rem;padding:.7rem 0;margin:0;border-bottom:1px solid #edf2f7;cursor:pointer}
    .permission-option:last-child{border-bottom:0}.permission-option input{margin-top:.25rem;flex:0 0 auto}.permission-option span,.permission-option small{display:block}.permission-option small{color:#718096;margin-top:.15rem}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById(@json($selectorId));
    if (!root) return;
    var checks = Array.from(root.querySelectorAll('input[name="permissions[]"]'));
    var summary = root.querySelector('.permission-summary-items');
    var count = root.querySelector('.permission-count');

    function selectedValues() {
        return checks.filter(function (check) { return check.checked; }).map(function (check) { return check.value; });
    }

    function enforceModuleAccess() {
        ['tailoring', 'clothing'].forEach(function (module) {
            var hasDetail = checks.some(function (check) { return check.checked && check.value.indexOf(module + '.') === 0 && check.value !== module + '.access'; });
            var rootCheck = checks.find(function (check) { return check.value === module + '.access'; });
            if (hasDetail && rootCheck) rootCheck.checked = true;
        });
    }

    function renderSummary() {
        enforceModuleAccess();
        var selected = checks.filter(function (check) { return check.checked; });
        count.textContent = selected.length + ' اجازتیں';
        summary.innerHTML = selected.length
            ? selected.map(function (check) { return '<span class="badge badge-light">' + check.dataset.label + '</span>'; }).join('')
            : 'ابھی کوئی اجازت منتخب نہیں کی گئی۔';
        root.querySelectorAll('.role-preset').forEach(function (button) {
            var preset = JSON.parse(button.dataset.permissions);
            button.classList.toggle('active', preset.length === selected.length && preset.every(function (permission) { return selectedValues().indexOf(permission) !== -1; }));
        });
    }

    checks.forEach(function (check) { check.addEventListener('change', renderSummary); });
    root.querySelectorAll('.role-preset').forEach(function (button) {
        button.addEventListener('click', function () {
            var permissions = JSON.parse(button.dataset.permissions);
            checks.forEach(function (check) { check.checked = permissions.indexOf(check.value) !== -1; });
            var nameTarget = document.querySelector(root.dataset.roleNameTarget);
            if (nameTarget && !nameTarget.value.trim()) nameTarget.value = button.dataset.name;
            renderSummary();
        });
    });
    root.querySelectorAll('.permission-group-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var permissions = JSON.parse(button.dataset.permissions);
            var groupChecks = checks.filter(function (check) { return permissions.indexOf(check.value) !== -1; });
            var selectAll = groupChecks.some(function (check) { return !check.checked; });
            groupChecks.forEach(function (check) { check.checked = selectAll; });
            renderSummary();
        });
    });
    root.querySelector('.permission-clear').addEventListener('click', function () {
        checks.forEach(function (check) { check.checked = false; });
        renderSummary();
    });
    renderSummary();
});
</script>
