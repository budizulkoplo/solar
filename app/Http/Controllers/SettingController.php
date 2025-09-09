<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        // ambil setting pertama
        $setting = Setting::first();
        return view('master.setting.form', compact('setting'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'alamat'          => 'nullable|string',
            'telepon'         => 'nullable|string|max:50',
            'logo'            => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $setting = Setting::first();

        if (!$setting) {
            $setting = new Setting();
        }

        // upload logo ke public/logopt
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('logopt'), $filename);

            // simpan path relatif untuk diakses dengan asset()
            $validated['path_logo'] = 'logopt/' . $filename;
        }

        $setting->fill($validated)->save();

        return redirect()->route('setting.index')
            ->with('success', 'Setting perusahaan berhasil diperbarui');
    }
}
