@php
    // Several plan forms share this page, so only the form that was submitted refills from old input.
    $useOld = old('_form') === $formKey;
    $value = fn (string $field, $default = null) => $useOld ? old($field, $default) : $default;
    $id = fn (string $field) => $formKey.'-'.$field;

    $period = (int) $value('billing_period_days', $plan?->billing_period_days ?? 30);
    $periods = \App\Models\SubscriptionPlan::BILLING_PERIODS;
    if (! array_key_exists($period, $periods)) {
        $periods[$period] = "Every {$period} days";
    }
    $workspace = $value('workspace', $plan?->workspace() ?? 'both');
    $selectedFeatures = $useOld
        ? old('features', [])
        : collect(\App\Models\SubscriptionPlan::FEATURES)->keys()->filter(fn ($key) => $plan ? $plan->{$key} : $key === 'allow_team_management')->all();
    $permissionsMode = $value('permissions_mode', $plan && ! $plan->allowsAllFeaturePermissions() ? 'custom' : 'all');
    $selectedPermissions = $value('allowed_permissions', $plan?->allowed_permissions ?? []);
@endphp
<input type="hidden" name="_form" value="{{ $formKey }}">

<h6 class="plan-form-heading">1. Plan and price</h6>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="{{ $id('name') }}">Plan name</label>
        <input id="{{ $id('name') }}" name="name" class="form-control" maxlength="100" required placeholder="e.g. Business Pro" value="{{ $value('name', $plan?->name) }}">
    </div>
    <div class="form-group col-md-6">
        <label for="{{ $id('price') }}">Price (Rs)</label>
        <input id="{{ $id('price') }}" type="number" name="price" class="form-control" min="0" step="any" required value="{{ $value('price', $plan ? (float) $plan->price : null) }}">
    </div>
    <div class="form-group col-md-6">
        <label for="{{ $id('period') }}">Billed</label>
        <select id="{{ $id('period') }}" name="billing_period_days" class="form-control" required>
            @foreach($periods as $days => $label)
                <option value="{{ $days }}" @selected($period === (int) $days)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="{{ $id('trial') }}">Free trial (days)</label>
        <input id="{{ $id('trial') }}" type="number" name="trial_days" class="form-control" min="0" max="365" required value="{{ $value('trial_days', $plan?->trial_days ?? 14) }}">
        <small class="form-text text-muted">Use 0 for no trial. Plans on the signup page need a trial.</small>
    </div>
    <div class="form-group col-12">
        <label for="{{ $id('summary') }}">One-line summary for shops <span class="text-muted">(optional)</span></label>
        <input id="{{ $id('summary') }}" name="marketing_summary" class="form-control" maxlength="500" placeholder="e.g. Both workspaces · up to 2,500 customers" value="{{ $value('marketing_summary', $plan?->marketing_summary) }}">
    </div>
</div>

<h6 class="plan-form-heading">2. Workspaces</h6>
<div class="plan-choice-group mb-3" role="radiogroup">
    @foreach(\App\Models\SubscriptionPlan::WORKSPACES as $key => $label)
        <label class="plan-choice">
            <input type="radio" name="workspace" value="{{ $key }}" @checked($workspace === $key) required>
            <span>{{ $label }}</span>
        </label>
    @endforeach
</div>

<h6 class="plan-form-heading">3. Limits <small class="text-muted font-weight-normal">— leave blank for unlimited</small></h6>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="{{ $id('customers') }}">Customers</label>
        <input id="{{ $id('customers') }}" type="number" name="max_customers" class="form-control" min="0" placeholder="Unlimited" value="{{ $value('max_customers', $plan?->max_customers) }}">
    </div>
    <div class="form-group col-md-6">
        <label for="{{ $id('employees') }}">Employees</label>
        <input id="{{ $id('employees') }}" type="number" name="max_employees" class="form-control" min="0" placeholder="Unlimited" value="{{ $value('max_employees', $plan?->max_employees) }}">
    </div>
</div>

<h6 class="plan-form-heading">4. Extra features</h6>
<div class="mb-3">
    @foreach(\App\Models\SubscriptionPlan::EXTRA_FEATURES as $key => $label)
        <div class="custom-control custom-switch mb-2">
            <input type="checkbox" class="custom-control-input" id="{{ $id($key) }}" name="features[]" value="{{ $key }}" @checked(in_array($key, $selectedFeatures, true))>
            <label class="custom-control-label" for="{{ $id($key) }}">{{ $label }}</label>
        </div>
    @endforeach
</div>

<h6 class="plan-form-heading">5. Who can choose it</h6>
<div class="mb-3">
    <div class="custom-control custom-switch mb-2">
        <input type="hidden" name="is_public" value="0">
        <input type="checkbox" class="custom-control-input" id="{{ $id('public') }}" name="is_public" value="1" @checked($value('is_public', $plan?->is_public ?? false))>
        <label class="custom-control-label" for="{{ $id('public') }}">Show on the signup page <small class="text-muted">(otherwise only you can assign it)</small></label>
    </div>
    <div class="custom-control custom-switch mb-2">
        <input type="hidden" name="is_recommended" value="0">
        <input type="checkbox" class="custom-control-input" id="{{ $id('recommended') }}" name="is_recommended" value="1" @checked($value('is_recommended', $plan?->is_recommended ?? false))>
        <label class="custom-control-label" for="{{ $id('recommended') }}">Mark as recommended</label>
    </div>
</div>

<details class="plan-advanced mb-3" @if($useOld && (old('permissions_mode') === 'custom' || $errors->hasAny(['code', 'lifecycle_status', 'display_order', 'max_business_roles', 'max_tailors']))) open @endif>
    <summary>Advanced settings</summary>
    <div class="pt-3">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="{{ $id('status') }}">Status</label>
                <select id="{{ $id('status') }}" name="lifecycle_status" class="form-control">
                    @foreach(['active' => 'Active — can be assigned', 'draft' => 'Draft — hidden', 'archived' => 'Archived — no new shops'] as $status => $label)
                        <option value="{{ $status }}" @selected($value('lifecycle_status', $plan?->lifecycle_status ?? 'active') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="{{ $id('code') }}">Code</label>
                <input id="{{ $id('code') }}" name="code" class="form-control" maxlength="60" placeholder="Made from the name" value="{{ $value('code', $plan?->code) }}">
            </div>
            <div class="form-group col-md-4">
                <label for="{{ $id('order') }}">Display order</label>
                <input id="{{ $id('order') }}" type="number" name="display_order" class="form-control" min="0" max="65535" value="{{ $value('display_order', $plan?->display_order ?? 0) }}">
            </div>
            <div class="form-group col-md-4">
                <label for="{{ $id('roles') }}">Custom roles</label>
                <input id="{{ $id('roles') }}" type="number" name="max_business_roles" class="form-control" min="0" placeholder="Unlimited" value="{{ $value('max_business_roles', $plan?->max_business_roles) }}">
            </div>
            <div class="form-group col-md-4">
                <label for="{{ $id('tailors') }}">Tailors</label>
                <input id="{{ $id('tailors') }}" type="number" name="max_tailors" class="form-control" min="0" placeholder="Unlimited" value="{{ $value('max_tailors', $plan?->max_tailors) }}">
            </div>
            <div class="form-group col-md-4 d-flex align-items-end">
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox" class="custom-control-input" id="{{ $id('team') }}" name="features[]" value="allow_team_management" @checked(in_array('allow_team_management', $selectedFeatures, true))>
                    <label class="custom-control-label" for="{{ $id('team') }}">Employee and role management</label>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="{{ $id('description') }}">Internal note</label>
            <textarea id="{{ $id('description') }}" name="description" class="form-control" rows="2" maxlength="2000">{{ $value('description', $plan?->description) }}</textarea>
        </div>

        <label class="d-block mb-1">Employee role permissions</label>
        <div class="custom-control custom-radio">
            <input type="radio" class="custom-control-input js-permissions-mode" id="{{ $id('perm-all') }}" name="permissions_mode" value="all" @checked($permissionsMode !== 'custom')>
            <label class="custom-control-label" for="{{ $id('perm-all') }}">Everything the plan's features include <small class="text-muted">(recommended)</small></label>
        </div>
        <div class="custom-control custom-radio mb-2">
            <input type="radio" class="custom-control-input js-permissions-mode" id="{{ $id('perm-custom') }}" name="permissions_mode" value="custom" @checked($permissionsMode === 'custom')>
            <label class="custom-control-label" for="{{ $id('perm-custom') }}">Let me choose</label>
        </div>
        <div class="row js-permission-list border rounded mx-0 pt-2" @if($permissionsMode !== 'custom') hidden @endif>
            @foreach($permissions as $permission => $label)
                <div class="col-md-6 mb-2"><label class="mb-0 small"><input type="checkbox" name="allowed_permissions[]" value="{{ $permission }}" @checked(in_array($permission, $selectedPermissions, true))> {{ $label }}</label></div>
            @endforeach
        </div>
    </div>
</details>
