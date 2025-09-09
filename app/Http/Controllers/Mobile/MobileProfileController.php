<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class MobileProfileController extends BaseMobileController
{
    public function index()
    {
        $user = Auth::user();
        return view('mobile.profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nik' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:6|confirmed',
            'foto' => 'nullable|image|max:2048',
            'gaji' => 'nullable|numeric',
            'email' => 'nullable|email',
            'nohp' => 'nullable|numeric',
            'alamat' => 'nullable|string|max:255',
        ]);

        $user->nik = $request->nik;
        $user->gaji = $request->gaji;
        $user->email = $request->email;
        $user->nohp = $request->nohp;
        $user->alamat = $request->alamat;

        // Update password jika diisi
        if($request->password) {
            $user->password = Hash::make($request->password);
        }

        // Update foto jika ada upload
        if($request->hasFile('foto')) {
            if($user->foto && Storage::exists($user->foto)){
                Storage::delete($user->foto);
            }
            $path = $request->file('foto')->store('profile');
            $user->foto = $path;
        }

        $user->save();

        return redirect()->back()->with('success', 'Profil berhasil diperbarui');
    }
}
