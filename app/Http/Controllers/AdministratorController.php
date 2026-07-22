<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\ServerNotifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Notifications\AdminNotification;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Notification;

class AdministratorController extends Controller
{
    public function showData()
    {
        $users = User::all();
        return view('Administrator.index', compact('users'));
    }
    public function index()
    {
        $allRoles = Role::all();
        $allPermissions = Permission::all();
        return view('Administrator.create', compact('allRoles', 'allPermissions'));
    }
    public function show()
    {
        $users = User::with('roles.permissions')->get();
        return view('Administrator.roles', compact('users'));
    }

    public function insert(Request $req)
    {
        $user_name = $req->input('name');
        $user_mail = $req->input('email');
        $user_password = $req->input('password');
        $role = $req->input('role');
        $permission = $req->input('permission');

        $user = User::create([
            'name' => $user_name,
            'email' => $user_mail,
            'password' => Hash::make($user_password)
        ]);
        // Assign role
        if ($role) {
            $roleModel = Role::where('name', $role)->first();
            if ($roleModel) {
                $user->assignRole($roleModel);
            }
        }

        // Assign permissions
        if ($permission) {
            $permissionModel = Permission::where('name', $permission)->first();

            if ($permissionModel) {
                $user->givePermissionTo($permissionModel);
                $roleModel->givePermissionTo($permissionModel);
            }
        }

        return redirect()->route('administrator.index');
    }

    public function edit($id)
    {
        $user = User::find($id);
        $allRoles = Role::all();
        $allPermissions = Permission::all();
        $user->with('roles.permissions')->get();
        return view('Administrator.edit', compact('user', 'allRoles', 'allPermissions'));
    }

    public function update(Request $req, $id)
{
    $user = User::find($id);

    $user_name = $req->input('name');
    $user_mail = $req->input('email');
    $user_password = $req->input('password');  // Password field from the form
    $role = $req->input('role');
    $permission = $req->input('permission');

    // Update the user's name and email
    $user->name = $user_name;
    $user->email = $user_mail;

    // Only update the password if a new one is provided
    if (!empty($user_password)) {
        $user->password = Hash::make($user_password);
    }

    // Save the updated user data
    $user->save();

    // Assign role
    if ($role) {
        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $user->syncRoles([$roleModel->name]);
        }
    }

    // Assign permissions
    if ($permission) {
        $permissionModel = Permission::where('name', $permission)->first();
        if ($permissionModel) {
            $user->syncPermissions([$permissionModel->name]);
            if ($roleModel) {
                $roleModel->syncPermissions([$permissionModel->name]);
            }
        }
    }

    return redirect()->route('administrator.index');
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

            // Get the user's role after syncing roles
            $role = $user->roles->first();

            // Check if the user has a role before giving permissions
            if ($role) {
                $permissions = $req->input('userPermissions');
                $role->syncPermissions($permissions);
            }
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
