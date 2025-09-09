<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        return view('master.users.list', [
            'roles' => Role::with('permissions')->get(),
            'allroles' => Role::all(),
            'unit' => Unit::all(),
        ]);
    }
    public function updatePassword(Request $request)
    {
        // Validate the input
        $request->validate([
            'new_password' => 'required|min:8',
            'userid' => 'required',
        ]);
        
        $user = User::find($request->userid);
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Check if current password matches
        // if (!Hash::check($request->current_password, $user->password)) {
        //     return back()->withErrors(['current_password' => 'Current password is incorrect']);
        // }

        // Update the password
        return back()->with('success', 'Password updated successfully');
    }
    public function kasihRole(Request $request)
    {
        $user = User::find($request->iduser);
        $user->syncRoles([]); 
        //$user->removeRole('users', 'personalia','admin');
        foreach ($request->name as $key => $value) {
            $user->assignRole($value);
        }
        return response()->json(true);
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

    public function Store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required',
            'tanggal_masuk' => 'required',
            'nik' => 'required',
        ]);
        
        if ($validatedData) {
            
            if (!empty($request->fidusers)) {
                $id = Crypt::decryptString($request->fidusers);
                $usr = User::find($id);
            } else {
                $usr = new User;
                $usr->nomor_anggota = $this->genCode();
                $usr->username = $request->username;
                $usr->password = Hash::make('12345678');
                $usr->email_verified_at = now(); // auto verified
            }
            
            $usr->name = $request->name;
            $usr->email = $request->email;
            $usr->tanggal_masuk = $request->tanggal_masuk;
            $usr->nik = $request->nik;
            $usr->jabatan = $request->jabatan;
            $usr->unit_kerja = $request->unit_kerja;

            // Tambahkan field UI berdasarkan unit_kerja
            $usr->ui = $request->unit_kerja == 1 ? 'admin' : 'user';

            $usr->status = $request->status ? 'aktif' : 'nonaktif';
            $usr->save();

            if (empty($request->fidusers)) {
            // misal otomatis jadi admin
            $usr->assignRole('admin');
            }

            return response()->json($usr ? 'success' : 'gagal', $usr ? 200 : 500);
        }
    }

    function genCode(){
        $total = User::withTrashed()->whereDate('created_at', date("Y-m-d"))->count();
        $nomorUrut = $total + 1;
        $newcode='KTX-'.date("ymd").str_pad($nomorUrut, 3, '0', STR_PAD_LEFT);
        return $newcode;
    }
    public function getCode(){
        return response()->json($this->genCode(), 200);
    }
    public function getdata(Request $request){
        $user = User::leftJoin('unit','unit.id','users.unit_kerja')
        ->leftJoin('model_has_roles as radmin', function ($join) {
            $join->on('radmin.model_id', '=', 'users.id')->where('radmin.role_id', '=', 1);
        })
        ->leftJoin('model_has_roles as rsuperadmin', function ($join) {
            $join->on('rsuperadmin.model_id', '=', 'users.id')->where('rsuperadmin.role_id', '=', 2);
        })
        ->leftJoin('model_has_roles as rpengurus', function ($join) {
            $join->on('rpengurus.model_id', '=', 'users.id')->where('rpengurus.role_id', '=', 4);
        })
        ->leftJoin('model_has_roles as rbendahara', function ($join) {
            $join->on('rbendahara.model_id', '=', 'users.id')->where('rbendahara.role_id', '=', 5);
        })
        ->leftJoin('model_has_roles as ranggota', function ($join) {
            $join->on('ranggota.model_id', '=', 'users.id')->where('ranggota.role_id', '=', 6);
        })
        ->leftJoin('model_has_roles as hrd', function ($join) {
            $join->on('hrd.model_id', '=', 'users.id')->where('hrd.role_id', '=', 7);
        })
        ->leftJoin('roles as r1','r1.id', 'rsuperadmin.role_id')
        ->leftJoin('roles as r2','r2.id', 'radmin.role_id')
        ->leftJoin('roles as r3','r3.id', 'rpengurus.role_id')
        ->leftJoin('roles as r4','r4.id', 'rbendahara.role_id')
        ->leftJoin('roles as r5','r5.id', 'ranggota.role_id')
        ->leftJoin('roles as r6','r6.id', 'hrd.role_id')
        ->select(
            'users.*',
            'unit.nama_unit',
            'r1.name as rsuperadmin',
            'r2.name as radmin',
            'r3.name as rpengurus',
            'r4.name as rbendahara',
            'r5.name as ranggota',
            'r6.name as hrd'
        );

        return DataTables::of($user)
                ->addIndexColumn()
                ->addColumn('idusers', function($row) {
                    return Crypt::encryptString($row->id);
                })
                ->filter(function ($query) use ($request) {
                    if($request->role != 'all'){
                        $query->where(function ($query) use ($request) {
                            $query->orWhere('r1.id', $request->role)
                                  ->orWhere('r2.id', $request->role)
                                  ->orWhere('r3.id', $request->role)
                                  ->orWhere('r4.id', $request->role)
                                  ->orWhere('r5.id', $request->role)
                                  ->orWhere('r6.id', $request->role);
                        });
                    }
                    if ($request->has('search') && $request->search != '') {
                        $query->where(function ($query2) use($request) {
                            return $query2
                            ->orWhere('users.name','like','%'.$request->search['value'].'%')
                            ->orWhere('users.nomor_anggota','like','%'.$request->search['value'].'%');
                        }); 
                    }
                })
                ->addColumn('allrole', function ($user) {
                // Calculate or set your custom value here
                return Role::all();
                })->make(true);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['success' => true]);
    }

}
