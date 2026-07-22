<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Business;
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

class AdministratorController extends Controller
{
    public function showData()
    {
        $users = User::with('roles')->orderBy('name')->get();
        return view('Administrator.index', compact('users'));
    }
    public function index()
    {
        $allRoles = Role::all();
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
            ]);
            $user->forceFill(['business_id' => $business->id, 'is_business_owner' => true])->save();
        }

        return redirect()->route('administrator.index')->with('success', 'Client account created.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $allRoles = Role::all();
        return view('Administrator.edit', compact('user', 'allRoles'));
    }

    public function update(Request $req, $id)
{
    $user = User::findOrFail($id);
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
            'role' => ['required', Rule::exists('roles', 'name')],
            'modules' => [Rule::requiredIf(fn () => $request->input('role') === 'shop_owner'), 'array', 'min:1'],
            'modules.*' => [Rule::in(['tailoring', 'clothing'])],
        ]);
    }

    public function delete($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
        }
        $users = User::all();
        return view('Administrator.index', compact('users'));
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
