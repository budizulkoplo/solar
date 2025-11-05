<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:8',
            'userid' => 'required',
        ]);
        
        $user = User::find($request->userid);
        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Password updated successfully');
    }

    public function kasihRole(Request $request)
    {
        $request->validate([
            'iduser' => 'required', // hapus exists sementara
            'name'   => 'required|array'
        ]);

        $user = User::find($request->iduser);

        if(!$user){
            return response()->json(['success' => false, 'message' => 'ID User tidak valid']);
        }

        $user->syncRoles($request->name);

        return response()->json(['success' => true, 'message' => 'Role berhasil diupdate']);
    }

    public function addRole(Request $request)
    {
        $role = Role::create(['name' => $request->name]);
        return response()->json($role);
    }

    public function deleteRole(Request $request)
    {
        $role = Role::findByName($request->name);
        $usersWithRole = User::role($role->name)->get();
        foreach ($usersWithRole as $user) {
            $user->removeRole($role);
        }
        $rtn = $role->delete();
        return response()->json($rtn);
    }

    public function deletePermission(Request $request)
    {
        $permission = Permission::findByName($request->name);
        $users = $permission->users;
        foreach ($users as $user) {
            $user->revokePermissionTo($permission->name);
        }
        $rtn = $permission->delete();
        return response()->json($rtn);
    }

    public function PermissionByRole(Request $request)
    {
        $role = Role::findByName($request->name);
        $permissions = $role->permissions;
        return response()->json($permissions);
    }

    public function Store(Request $request){
        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required',
            'tanggal_masuk' => 'required',
            'nik' => 'required',
        ]);
        
        if($validatedData){
            if(!empty($request->fidusers)){
                $usr = User::find($request->fidusers);
            }else{
                $usr = new User;
                $usr->nip = $this->genCode();
                $usr->username = $request->username;
                $usr->password = Hash::make('12345678');
            }
            
            $usr->name = $request->name;
            $usr->email = $request->email;
            $usr->tanggal_masuk = $request->tanggal_masuk;
            $usr->nik = $request->nik;
            $usr->jabatan = $request->jabatan;
            $usr->status = $request->status ? 'aktif' : 'nonaktif';
            $usr->save();
    
            return response()->json('success', 200);
        }
    }


    function genCode()
    {
        // Ambil user terakhir yang punya kode dengan format IDxxxxx
        $lastUser = User::withTrashed()
            ->where('nip', 'like', 'ID%')
            ->orderBy('nip', 'desc')
            ->first();
    
        if ($lastUser) {
            // Ambil angka setelah "ID"
            $lastNumber = (int) substr($lastUser->nip, 2);
            $nomorUrut = $lastNumber + 1;
        } else {
            $nomorUrut = 1;
        }
    
        $newcode = 'ID' . str_pad($nomorUrut, 5, '0', STR_PAD_LEFT);
        return $newcode;
    }

    public function getCode(){
        return response()->json($this->genCode(), 200);
    }

    public function getdata(Request $request)
{
    $users = User::with('roles:id,name');

    return DataTables::of($users)
        ->addIndexColumn()
            ->addColumn('idusers', function ($row) {
        return $row->id; // langsung ID asli
    })
        ->filter(function ($query) use ($request) {
            if ($request->role != 'all') {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('id', $request->role);
                });
            }

            if ($request->has('search') && $request->search != '') {
                $query->where(function ($query2) use ($request) {
                    $query2
                        ->orWhere('users.name', 'like', '%' . $request->search['value'] . '%')
                        ->orWhere('users.nip', 'like', '%' . $request->search['value'] . '%');
                });
            }
        })
        ->make(true);
}


}
