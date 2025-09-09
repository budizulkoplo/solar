<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectController extends BaseMobileController
{
    // Tampilkan form pilih project
    public function select()
    {
        $user = Auth::user();

        // Ambil semua project, bisa juga filter sesuai user
        $projects = DB::table('projects')->orderBy('nama_project')->get();

        return view('mobile.project.select', compact('projects'));
    }

    // Set project yang dipilih ke session
    public function set(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id'
        ]);

        $project = DB::table('projects')->where('id', $request->project_id)->first();

        session([
            'project_id' => $project->id,
            'nama_project' => $project->nama_project
        ]);

        return redirect()->route('mobile.home');
    }
}
