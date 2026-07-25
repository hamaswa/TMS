<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Business;
use App\Models\Storefront;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\ServerNotifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Notifications\AdminNotification;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use App\Models\BusinessStatusHistory;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class AdministratorController extends Controller
{
    public function showData(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Business::STATUSES)],
            'module' => ['nullable', Rule::in(['tailoring', 'clothing', 'both'])],
        ]);
        $users = User::role('shop_owner')->with(['roles', 'ownedBusiness'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->whereHas('ownedBusiness', fn ($business) => $business->where('status', $status)))
            ->when($filters['module'] ?? null, function ($query, $module) {
                $query->whereHas('ownedBusiness', function ($business) use ($module) {
                    if ($module === 'both') {
                        $business->where('tailoring_enabled', true)->where('clothing_enabled', true);
                    } else {
                        $business->where($module.'_enabled', true);
                    }
                });
            })
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('Administrator.index', compact('users'));
    }
    public function index()
    {
        $allRoles = Role::where('name', 'shop_owner')->get();
        return view('Administrator.create', compact('allRoles'));
    }
    public function show()
    {
        $users = User::with('roles.permissions')->get();
        return view('Administrator.roles', compact('users'));
    }

    public function insert(Request $req)
    {
        $validated = $this->validateAccount($req);
        $modules = $validated['modules'] ?? [];
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tailoring_access' => $validated['role'] === 'shop_owner' && in_array('tailoring', $modules, true),
            'clothing_access' => $validated['role'] === 'shop_owner' && in_array('clothing', $modules, true),
        ]);
        $user->syncRoles([$validated['role']]);

        if ($validated['role'] === 'shop_owner') {
            $business = Business::create([
                'name' => $user->name,
                'owner_user_id' => $user->id,
                'tailoring_enabled' => $user->tailoring_access,
                'clothing_enabled' => $user->clothing_access,
                'status' => Business::STATUS_PENDING,
                'status_changed_at' => now(),
                'status_changed_by_user_id' => Auth::id(),
            ]);
            $business->statusHistory()->create([
                'from_status' => null,
                'to_status' => Business::STATUS_PENDING,
                'changed_by_user_id' => Auth::id(),
                'reason' => 'Client account created and awaiting approval.',
                'created_at' => now(),
            ]);
            $user->forceFill(['business_id' => $business->id, 'is_business_owner' => true])->save();
        }

        return redirect()->route('administrator.clients.show', $user)->with('success', 'Client account created and is awaiting approval.');
    }

    public function edit($id)
    {
        $user = $this->client($id);
        $allRoles = Role::where('name', 'shop_owner')->get();
        return view('Administrator.edit', compact('user', 'allRoles'));
    }

    public function update(Request $req, $id)
{
    $user = $this->client($id);
    $validated = $this->validateAccount($req, $user);
    $modules = $validated['modules'] ?? [];
    $user->fill([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'tailoring_access' => $validated['role'] === 'shop_owner' && in_array('tailoring', $modules, true),
        'clothing_access' => $validated['role'] === 'shop_owner' && in_array('clothing', $modules, true),
    ]);
    if (! empty($validated['password'])) {
        $user->password = Hash::make($validated['password']);
    }
    $user->save();
    $user->syncRoles([$validated['role']]);

    if ($validated['role'] === 'shop_owner') {
        $business = $user->ownedBusiness()->firstOrCreate(
            ['owner_user_id' => $user->id],
            ['name' => $user->name]
        );
        $business->update([
            'name' => $user->name,
            'tailoring_enabled' => $user->tailoring_access,
            'clothing_enabled' => $user->clothing_access,
        ]);
        $user->forceFill(['business_id' => $business->id, 'is_business_owner' => true])->save();
    }

    return redirect()->route('administrator.index')->with('success', 'Client access updated.');
}

    private function validateAccount(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['shop_owner'])],
            'modules' => [Rule::requiredIf(fn () => $request->input('role') === 'shop_owner'), 'array', 'min:1'],
            'modules.*' => [Rule::in(['tailoring', 'clothing'])],
        ]);
    }

    public function delete($id)
    {
        $this->client($id);

        return redirect()->route('administrator.index')->with('warning', 'Permanent deletion is disabled to protect client data. Suspend the account instead.');
    }

    public function clientDetails($id)
    {
        $user = $this->client($id)->load([
            'ownedBusiness.statusHistory.changedBy',
            'ownedBusiness.storefront.moderatedBy',
            'ownedBusiness.storefront.moderationHistory.changedBy',
        ]);
        $business = $user->ownedBusiness;
        abort_unless($business, 404);
        $ownerId = $user->id;
        $metrics = [
            'employees' => User::where('business_id', $business->id)->where('id', '!=', $ownerId)->count(),
            'customers' => DB::table('customers')->where('user_id', $ownerId)->whereNull('deleted_at')->count(),
            'tailors' => DB::table('tailors')->where('user_id', $ownerId)->count(),
            'orders' => DB::table('orders')->where('userId', $ownerId)->count(),
            'production_workers' => DB::table('production_workers')->where('user_id', $ownerId)->count(),
            'sales' => DB::table('sales')->where('user_id', $ownerId)->count(),
            'purchases' => DB::table('purchases')->where('user_id', $ownerId)->count(),
            'storefront_listings' => $business->storefront?->clothingListings()->where('is_published', true)->count() ?? 0,
            'storefront_services' => $business->storefront?->tailoringServices()->where('is_published', true)->count() ?? 0,
            'storefront_inquiries' => $business->storefront?->inquiries()->count() ?? 0,
            'storefront_orders' => $business->storefront?->orders()->count() ?? 0,
        ];

        return view('Administrator.show', compact('user', 'business', 'metrics'));
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $this->client($id);
        $business = $user->ownedBusiness()->firstOrFail();
        $validated = $request->validate([
            'status' => ['required', Rule::in(Business::STATUSES)],
            'reason' => [Rule::requiredIf(fn () => in_array($request->input('status'), [Business::STATUS_SUSPENDED, Business::STATUS_REJECTED], true)), 'nullable', 'string', 'max:1000'],
        ]);
        $target = $validated['status'];
        $allowed = [
            Business::STATUS_PENDING => [Business::STATUS_ACTIVE, Business::STATUS_REJECTED],
            Business::STATUS_ACTIVE => [Business::STATUS_SUSPENDED],
            Business::STATUS_SUSPENDED => [Business::STATUS_ACTIVE],
            Business::STATUS_REJECTED => [Business::STATUS_ACTIVE],
        ];
        abort_unless(in_array($target, $allowed[$business->status] ?? [], true), 422, 'This status change is not allowed.');

        DB::transaction(function () use ($business, $user, $target, $validated) {
            $from = $business->status;
            $business->forceFill([
                'status' => $target,
                'approved_at' => $target === Business::STATUS_ACTIVE ? ($business->approved_at ?? now()) : $business->approved_at,
                'approved_by_user_id' => $target === Business::STATUS_ACTIVE ? ($business->approved_by_user_id ?? Auth::id()) : $business->approved_by_user_id,
                'status_changed_at' => now(),
                'status_changed_by_user_id' => Auth::id(),
                'status_reason' => $validated['reason'] ?? null,
            ])->save();
            BusinessStatusHistory::create([
                'business_id' => $business->id,
                'from_status' => $from,
                'to_status' => $target,
                'changed_by_user_id' => Auth::id(),
                'reason' => $validated['reason'] ?? null,
                'created_at' => now(),
            ]);

            if ($target === Business::STATUS_ACTIVE) {
                Setting::ensureDefaultFor($user);
            }
        });

        return redirect()->route('administrator.clients.show', $user)->with('success', 'Client status updated to '.ucfirst($target).'.');
    }

    public function marketplace(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'publication' => ['nullable', Rule::in(['published', 'draft'])],
            'moderation' => ['nullable', Rule::in(Storefront::MODERATION_STATUSES)],
            'business_status' => ['nullable', Rule::in(Business::STATUSES)],
            'module' => ['nullable', Rule::in(['clothing', 'tailoring', 'both'])],
        ]);
        $storefronts = Storefront::query()
            ->with(['business.owner:id,name,email'])
            ->withCount([
                'clothingListings as published_clothing_count' => fn ($query) => $query->where('is_published', true),
                'tailoringServices as published_services_count' => fn ($query) => $query->where('is_published', true),
                'inquiries',
                'orders',
                'orders as pending_orders_count' => fn ($query) => $query->where('status', StorefrontOrder::STATUS_PENDING),
            ])
            ->withSum([
                'orders as order_revenue' => fn ($query) => $query->where('status', '!=', StorefrontOrder::STATUS_CANCELLED),
            ], 'subtotal')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($nested) use ($search) {
                $nested->where('display_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('business', fn ($business) => $business
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('owner', fn ($owner) => $owner
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")));
            }))
            ->when($filters['publication'] ?? null, fn ($query, $publication) => $query
                ->where('is_published', $publication === 'published'))
            ->when($filters['moderation'] ?? null, fn ($query, $moderation) => $query
                ->where('moderation_status', $moderation))
            ->when($filters['business_status'] ?? null, fn ($query, $status) => $query
                ->whereHas('business', fn ($business) => $business->where('status', $status)))
            ->when($filters['module'] ?? null, function ($query, $module) {
                if ($module === 'both') {
                    $query->where('show_clothing', true)->where('show_tailoring', true);
                } else {
                    $query->where('show_'.$module, true);
                }
            })
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();
        $metrics = [
            'configured' => Storefront::count(),
            'public' => Storefront::publiclyVisible()->count(),
            'paused' => Storefront::where('moderation_status', Storefront::MODERATION_PAUSED)->count(),
            'pending_orders' => StorefrontOrder::where('status', StorefrontOrder::STATUS_PENDING)->count(),
            'order_value' => (float) StorefrontOrder::where('status', '!=', StorefrontOrder::STATUS_CANCELLED)->sum('subtotal'),
        ];

        return view('Administrator.marketplace', compact('storefronts', 'metrics'));
    }

    public function updateMarketplaceModeration(Request $request, Storefront $storefront)
    {
        $validated = $request->validate([
            'moderation_status' => ['required', Rule::in(Storefront::MODERATION_STATUSES)],
            'reason' => [
                Rule::requiredIf($request->input('moderation_status') === Storefront::MODERATION_PAUSED),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);
        DB::transaction(function () use ($storefront, $validated) {
            $from = $storefront->moderation_status;
            $storefront->forceFill([
                'moderation_status' => $validated['moderation_status'],
                'moderation_reason' => $validated['moderation_status'] === Storefront::MODERATION_PAUSED
                    ? $validated['reason']
                    : null,
                'moderated_by_user_id' => Auth::id(),
                'moderated_at' => now(),
            ])->save();
            $storefront->moderationHistory()->create([
                'from_status' => $from,
                'to_status' => $validated['moderation_status'],
                'reason' => $validated['reason'] ?? null,
                'changed_by_user_id' => Auth::id(),
            ]);
        });

        return redirect()->route('administrator.marketplace.index')
            ->with('success', $validated['moderation_status'] === Storefront::MODERATION_PAUSED
                ? 'The storefront is hidden from public access. No client data was deleted.'
                : 'The storefront is allowed to appear publicly when the client publishes it and the account is active.');
    }

    private function client(int|string $id): User
    {
        return User::role('shop_owner')->findOrFail($id);
    }

    public function editRole(Request $req, $id)
    {
        $user = User::find($id);

        $allRoles = Role::all();
        $allPermissions = Permission::all();

        if ($user) {
            $roles = $user->getRoleNames();
            $permissions = $user->getPermissionsViaRoles();
        } else {
            abort(404);
        }

        return view('Administrator.edit_roles', compact('user', 'roles', 'permissions', 'allRoles', 'allPermissions'));
    }

    public function updateRole(Request $req, $id)
    {
        $user = User::find($id);

        if ($user) {
            // Update user's roles
            $user->syncRoles([$req->input('userRoles', [])]);

            // Update user's permissions
            $user->syncPermissions($req->input('userPermissions', []));

            // Direct permissions belong to this account only. Shared role permissions
            // must not be changed while editing one client.
        }

        return redirect()->route('administrator.roles');
    }

    public function newRole()
    {
        return view('Administrator.role_per');
    }

    public function createRole(Request $request)
    {
        $role = $request->input('name');
        $permission = $request->input('perm');

        //create role
        Role::create([
            'name' => $role
        ]);

        //create permission

        Permission::create([
            'name' => $permission
        ]);

        return redirect()->route('administrator.index')->with('success', 'رول اور اجازت کامیابی کے ساتھ بنائی گئی۔');
    }

    public function showRolePermi()
    {
        $roles = Role::all();
        $perm = Permission::all();

        return view('Administrator.show_rol_per', compact('roles', 'perm'));
    }

    public function editRoles($id)
    {
        $roles = Role::find($id);
        return view('Administrator.role_edit', compact('roles'));
    }

    public function updateRoles(Request $request, $id)
    {
        $roles = Role::find($id);
        $roles->update([
            'name' => $request->input('name'),
        ]);
        return back()->with('update', 'role updated');
    }

    public function editPermissions($id)
    {
        $permis = Permission::find($id);
        return view('Administrator.perm_edit', compact('permis'));
    }

    public function updatePermissions(Request $request, $id)
    {
        $permis = Permission::find($id);
        $permis->update([
            'name' => $request->input('perm')
        ]);
        return back()->with('update', 'permission updated');
    }

    public function send(Request $request, $id)
    {
        $id = $request->id;
        return view('Administrator.notification', compact('id'));
    }

    public function store(Request $request)
    {
        try {
            $obj = new ServerNotifications();
            $obj->user_id = $request->input('id');
            $obj->message = $request->input('message');
            $obj->save();

            $notification = new AdminNotification($obj);

            $user = User::where('id',$request->input('id'))->first();

            Notification::send($user,$notification);
            return response()->json('Message Sent');
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function SSEupdates()
    {
        $notifications = ServerNotifications::where('user_id', auth()->user()->id)->where('is_send', 0)->first();

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        if ($notifications) {
            $eventData = [
                'message' => $notifications->message,
            ];

            echo "data" . json_encode($eventData) . "\n\n";
            $notifications->is_send = 1;
            $notifications->update();
        } else {
            echo "\n\n";
        }
        ob_flush();
        flush();
    }
}
