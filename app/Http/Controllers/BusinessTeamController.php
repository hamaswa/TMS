<?php

namespace App\Http\Controllers;

use App\Models\BusinessRole;
use App\Models\Business;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class BusinessTeamController extends Controller
{
    public function index(Request $request)
    {
        return view('team.index', ['business' => $this->businessFor($request)]);
    }

    public function employees(Request $request)
    {
        return view('team.employees', ['business' => $this->businessFor($request)]);
    }

    public function roles(Request $request)
    {
        $business = $this->businessFor($request);
        $permissions = $this->availablePermissions($business);

        return view('team.roles', [
            'business' => $business,
            'permissions' => $permissions,
            'permissionGroups' => $this->permissionGroups($permissions),
            'rolePresets' => $this->rolePresets($permissions),
        ]);
    }

    public function security(Request $request)
    {
        return view('team.security', ['business' => $this->businessFor($request)]);
    }

    private function businessFor(Request $request)
    {
        return $request->user()->business()->with([
            'roles' => fn ($query) => $query->withCount('users')->orderBy('name'),
            'members' => fn ($query) => $query->where('is_business_owner', false)->with(['businessRole', 'business'])->orderBy('name'),
        ])->firstOrFail();
    }

    public function storeRole(Request $request, SubscriptionEntitlementService $entitlements)
    {
        $business = $request->user()->business;
        $validated = $this->validateRole($request, $business->id);
        DB::transaction(function () use ($business, $validated, $entitlements) {
            $locked = Business::query()->lockForUpdate()->findOrFail($business->id);
            $entitlements->assertCanAddRole($locked);
            $locked->roles()->create($validated);
        });

        return back()->with('success', 'نیا رول کامیابی سے بنا دیا گیا ہے۔');
    }

    public function editRole(Request $request, int $role)
    {
        $role = $this->ownedRole($request, $role);
        $business = $this->businessFor($request);
        $permissions = $this->availablePermissions($business);

        return view('team.role-edit', [
            'role' => $role,
            'business' => $business,
            'permissions' => $permissions,
            'permissionGroups' => $this->permissionGroups($permissions),
            'rolePresets' => $this->rolePresets($permissions),
        ]);
    }

    public function updateRole(Request $request, int $role)
    {
        $role = $this->ownedRole($request, $role);
        $role->update($this->validateRole($request, $role->business_id, $role->id));

        return redirect()->route('admin.team.roles.index')->with('success', 'رول کی اجازتیں تبدیل کر دی گئی ہیں۔');
    }

    public function destroyRole(Request $request, int $role)
    {
        $role = $this->ownedRole($request, $role);
        abort_if($role->users()->exists(), 422, 'استعمال ہونے والا رول حذف نہیں کیا جا سکتا۔ پہلے ملازمین کا رول تبدیل کریں۔');
        $role->delete();

        return back()->with('success', 'رول حذف کر دیا گیا ہے۔');
    }

    public function storeEmployee(Request $request, SubscriptionEntitlementService $entitlements)
    {
        $business = $request->user()->business;
        $validated = $this->validateEmployee($request, $business->id);
        $role = $business->roles()->findOrFail($validated['business_role_id']);
        $actorId = $request->user()->id;

        DB::transaction(function () use ($validated, $business, $role, $actorId, $entitlements) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($business->id);
            $entitlements->assertCanAddEmployee($lockedBusiness);
            $employee = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'password' => Hash::make($validated['password']),
                'business_id' => $business->id,
                'business_role_id' => $role->id,
                'job_title' => $validated['job_title'] ?? null,
                'employee_active' => true,
                'must_change_password' => true,
                'password_reset_at' => now(),
                'password_reset_by_user_id' => $actorId,
                'is_business_owner' => false,
                'tailoring_access' => false,
                'clothing_access' => false,
            ]);
            $employee->assignRole(Role::firstOrCreate(['name' => 'business_employee', 'guard_name' => 'web']));
        });

        return back()->with('success', 'ملازم کا اکاؤنٹ بنا دیا گیا ہے۔');
    }

    public function editEmployee(Request $request, int $employee)
    {
        return view('team.employee-edit', [
            'employee' => $this->ownedEmployee($request, $employee),
            'roles' => $request->user()->business->roles()->orderBy('name')->get(),
        ]);
    }

    public function updateEmployee(Request $request, int $employee, SubscriptionEntitlementService $entitlements)
    {
        $employee = $this->ownedEmployee($request, $employee);
        $validated = $this->validateEmployee($request, $employee->business_id, $employee);
        $this->ownedRole($request, (int) $validated['business_role_id']);

        DB::transaction(function () use ($employee, $validated, $request, $entitlements) {
            $lockedBusiness = Business::query()->lockForUpdate()->findOrFail($employee->business_id);
            $activating = ! $employee->employee_active && $request->boolean('employee_active');
            if ($activating) {
                $entitlements->assertCanAddEmployee($lockedBusiness, $employee->id);
            }

            $employee->fill([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'business_role_id' => $validated['business_role_id'],
                'job_title' => $validated['job_title'] ?? null,
                'employee_active' => $request->boolean('employee_active'),
            ]);
            $employee->save();
        });

        return redirect()->route('admin.team.employees.index')->with('success', 'ملازم کی معلومات تبدیل کر دی گئی ہیں۔');
    }

    public function resetPassword(Request $request, int $employee)
    {
        $employee = $this->ownedEmployee($request, $employee);
        $validated = $request->validate([
            'temporary_password' => ['required', 'string', new StrongPassword, 'confirmed'],
        ]);

        $employee->forceFill([
            'password' => Hash::make($validated['temporary_password']),
            'must_change_password' => true,
            'password_reset_at' => now(),
            'password_reset_by_user_id' => $request->user()->id,
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('success', 'عارضی پاس ورڈ محفوظ ہو گیا ہے۔ ملازم اگلے لاگ اِن پر نیا پاس ورڈ بنائے گا۔');
    }

    public function updatePasswordPolicy(Request $request)
    {
        $validated = $request->validate([
            'password_expiry_days' => ['nullable', 'integer', Rule::in([0, 30, 60, 90, 180, 365])],
        ]);
        $business = $request->user()->business;
        $days = (int) ($validated['password_expiry_days'] ?? 0);
        $newValue = $days > 0 ? $days : null;

        if ($business->password_expiry_days !== $newValue) {
            $business->forceFill([
                'password_expiry_days' => $newValue,
                'password_policy_updated_at' => $newValue ? now() : null,
            ])->save();
        }

        return back()->with('success', $newValue
            ? "ملازمین کے پاس ورڈ ہر {$newValue} دن بعد تبدیل ہوں گے۔"
            : 'پاس ورڈ کی میعاد ختم ہونے کی پابندی بند کر دی گئی ہے۔');
    }

    private function validateRole(Request $request, int $businessId, ?int $roleId = null): array
    {
        $allowed = array_keys($this->availablePermissions($request->user()->business));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('business_roles')->where('business_id', $businessId)->ignore($roleId)],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [Rule::in($allowed)],
        ]);

        if (array_intersect($validated['permissions'], [
            BusinessRole::CLOTHING_SALES,
            BusinessRole::CLOTHING_INVENTORY,
            BusinessRole::CLOTHING_PURCHASES,
            BusinessRole::CLOTHING_SUPPLIERS,
        ])) {
            $validated['permissions'][] = BusinessRole::CLOTHING_ACCESS;
            $validated['permissions'] = array_values(array_unique($validated['permissions']));
        }

        if (array_intersect($validated['permissions'], [
            BusinessRole::TAILORING_CUSTOMERS,
            BusinessRole::TAILORING_ORDERS,
            BusinessRole::TAILORING_WORKSHOP,
            BusinessRole::TAILORING_TAILORS,
            BusinessRole::TAILORING_CONFIGURATION,
        ])) {
            $validated['permissions'][] = BusinessRole::TAILORING_ACCESS;
            $validated['permissions'] = array_values(array_unique($validated['permissions']));
        }

        return $validated;
    }

    private function validateEmployee(Request $request, int $businessId, ?User $employee = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($employee?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'password' => [$employee ? 'prohibited' : 'required', 'string', new StrongPassword],
            'job_title' => ['nullable', 'string', 'max:100'],
            'business_role_id' => ['required', Rule::exists('business_roles', 'id')->where('business_id', $businessId)],
            'employee_active' => ['nullable', 'boolean'],
        ]);
    }

    private function availablePermissions($business): array
    {
        return array_filter(BusinessRole::PERMISSIONS, function ($label, $permission) use ($business) {
            if (! $business->subscriptionAllowsPermission($permission)) {
                return false;
            }

            if (str_starts_with($permission, 'tailoring.')) {
                return $business->tailoring_enabled
                    && $business->subscriptionAllowsFeature('allow_tailoring');
            }

            if (str_starts_with($permission, 'clothing.')) {
                return $business->clothing_enabled
                    && $business->subscriptionAllowsFeature('allow_clothing');
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function permissionGroups(array $permissions): array
    {
        $allowed = array_keys($permissions);

        return collect(BusinessRole::PERMISSION_GROUPS)
            ->map(function (array $group) use ($allowed, $permissions) {
                $group['permissions'] = collect($group['permissions'])
                    ->filter(fn (string $permission) => in_array($permission, $allowed, true))
                    ->mapWithKeys(fn (string $permission) => [$permission => $permissions[$permission]])
                    ->all();

                return $group;
            })
            ->filter(fn (array $group) => $group['permissions'] !== [])
            ->all();
    }

    private function rolePresets(array $permissions): array
    {
        $allowed = array_keys($permissions);
        $tailoringEnabled = in_array(BusinessRole::TAILORING_ACCESS, $allowed, true);
        $clothingEnabled = in_array(BusinessRole::CLOTHING_ACCESS, $allowed, true);

        return collect(BusinessRole::ROLE_PRESETS)
            ->filter(function (array $preset) use ($tailoringEnabled, $clothingEnabled) {
                if ($preset['permissions'] === ['*']) {
                    return true;
                }

                $requiresTailoring = collect($preset['permissions'])
                    ->contains(fn (string $permission) => str_starts_with($permission, 'tailoring.'));
                $requiresClothing = collect($preset['permissions'])
                    ->contains(fn (string $permission) => str_starts_with($permission, 'clothing.'));

                return (! $requiresTailoring || $tailoringEnabled)
                    && (! $requiresClothing || $clothingEnabled);
            })
            ->map(function (array $preset) use ($allowed) {
                $preset['permissions'] = $preset['permissions'] === ['*']
                    ? $allowed
                    : array_values(array_intersect($preset['permissions'], $allowed));

                return $preset;
            })
            ->filter(fn (array $preset) => $preset['permissions'] !== [])
            ->all();
    }

    private function ownedRole(Request $request, int $id): BusinessRole
    {
        return $request->user()->business->roles()->findOrFail($id);
    }

    private function ownedEmployee(Request $request, int $id): User
    {
        return $request->user()->business->members()
            ->where('is_business_owner', false)
            ->findOrFail($id);
    }
}
