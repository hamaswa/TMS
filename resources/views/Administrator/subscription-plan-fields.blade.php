@php
    $selectedFeatures = $plan ? collect(\App\Models\SubscriptionPlan::FEATURES)->keys()->filter(fn($key) => $plan->{$key})->all() : [];
    $selectedPermissions = old('allowed_permissions', $plan?->allowed_permissions ?? []);
@endphp
<div class="form-row">
    <div class="form-group col-md-4"><label>Name</label><input name="name" class="form-control" maxlength="100" required value="{{ old('name', $plan?->name) }}"></div>
    <div class="form-group col-md-3"><label>Code</label><input name="code" class="form-control" maxlength="60" required placeholder="starter-monthly" value="{{ old('code', $plan?->code) }}"></div>
    <div class="form-group col-md-3"><label>Price (PKR)</label><input type="number" name="price" class="form-control" min="0" step="0.01" required value="{{ old('price', $plan?->price ?? 0) }}"></div>
    <div class="form-group col-md-2"><label>Period days</label><input type="number" name="billing_period_days" class="form-control" min="1" max="3660" required value="{{ old('billing_period_days', $plan?->billing_period_days ?? 30) }}"></div>
</div>
<div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2" maxlength="2000">{{ old('description', $plan?->description) }}</textarea></div>
<div class="form-row">
    <div class="form-group col-md-4"><label>Maximum active employees</label><input type="number" name="max_employees" class="form-control" min="0" placeholder="Blank = unlimited" value="{{ old('max_employees', $plan?->max_employees) }}"></div>
    <div class="form-group col-md-4"><label>Maximum client-created roles</label><input type="number" name="max_business_roles" class="form-control" min="0" placeholder="Blank = unlimited" value="{{ old('max_business_roles', $plan?->max_business_roles) }}"></div>
    <div class="form-group col-md-4"><label>Maximum tailors</label><input type="number" name="max_tailors" class="form-control" min="0" placeholder="Blank = unlimited" value="{{ old('max_tailors', $plan?->max_tailors) }}"></div>
</div>
<fieldset class="border rounded p-3 mb-3">
    <legend class="h6 w-auto px-2">Included features</legend>
    <div class="row">
        @foreach($features as $key => $label)
            <div class="col-md-4 mb-2"><label class="mb-0"><input type="checkbox" name="features[]" value="{{ $key }}" @checked(in_array($key, old('features', $selectedFeatures), true))> {{ $label }}</label></div>
        @endforeach
    </div>
</fieldset>
<fieldset class="border rounded p-3 mb-3">
    <legend class="h6 w-auto px-2">Permissions available to client-defined roles</legend>
    <p class="small text-muted">A role can only use permissions included here. Feature switches above also take priority.</p>
    <div class="row">
        @foreach($permissions as $permission => $label)
            <div class="col-md-4 mb-2"><label class="mb-0"><input type="checkbox" name="allowed_permissions[]" value="{{ $permission }}" @checked(in_array($permission, $selectedPermissions, true))> {{ $label }}</label></div>
        @endforeach
    </div>
</fieldset>
<div class="form-group form-check">
    <input type="hidden" name="is_active" value="0">
    <input class="form-check-input" id="active-{{ $plan?->id ?? 'new' }}" type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan?->is_active ?? true))>
    <label class="form-check-label" for="active-{{ $plan?->id ?? 'new' }}">Available for new subscriptions</label>
</div>
