<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function index(Request $request): view
    {
        return view('master.role.list', [
            'user' => $request->user(),
            'roles' =>  Role::with('permissions')->get(),
            'permissions' => Permission::all()
        ]);
    }
    public function addRole(Request $request)
    {
        $role = Role::create(['name' => $request->name]);
        return response()->json($role);
    }
    public function deleteRole(Request $request)
    {
        $role = Role::findByName($request->name); // Replace with your role name
        // Get all users that have the role
        $usersWithRole = User::role($role->name)->get();
        // Detach the role from all users
        foreach ($usersWithRole as $user) {
            $user->removeRole($role);
        }
        $rtn=$role->delete();
        return response()->json($rtn);
    }
    public function deletePermission(Request $request)
    {
        $permission = Permission::findByName($request->name);
        $users = $permission->users; // Get all users with this permission
        foreach ($users as $user) {
            $user->revokePermissionTo($permission->name); // Remove the permission from each user
        }
        $rtn=$permission->delete(); // Delete the permission from the system
        return response()->json($rtn);
    }
    public function PermissionByRole(Request $request)
    {
        $role = Role::findByName($request->name);
        $permissions = $role->permissions;
        return response()->json($permissions);
    }
    public function PermissionfromRole(Request $request)
    {
        $role = Role::findByName($request->role);
        if($request->chk == true){
            $role->givePermissionTo($request->permission);
            return response()->json(['status','Give Permission']);
        }else{
            $role->revokePermissionTo($request->permission); 
            return response()->json(['status'=>'Revoke Permission']);
        }
    }
}
