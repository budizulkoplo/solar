<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Menu;

class ProjectSelectionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userRole = $user->getRoleNames()->first(); // contoh: 'admin', 'hrd', 'superadmin', dst.

        // 🔹 Ambil daftar project user
        $projects = $user->projects()->with('companyUnit')->get();

        // 🔹 Ambil module unik dari tabel menu, yang:
        // - Punya kolom 'module' tidak null
        // - Bukan 'project'
        // - Mengandung role user di kolom 'role'
        $modules = Menu::whereNotNull('module')
            ->where('module', '!=', 'project')
            ->where(function ($q) use ($userRole) {
                $q->where('role', 'like', "%;$userRole;%")
                ->orWhere('role', 'like', "$userRole;%")
                ->orWhere('role', 'like', "%;$userRole")
                ->orWhere('role', '=', $userRole);
            })
            ->select('module', \DB::raw('MIN(icon) as icon'))
            ->groupBy('module')
            ->orderBy('module')
            ->get();

        return view('projects.choose', compact('projects', 'modules'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Jika memilih MODULE saja
        if ($request->filled('module')) {
            $module = $request->module;

            session([
                'active_project_id' => $module,
                'active_project_name' => $module,
                'active_project_module' => $module,
            ]);

            return redirect()->route('dashboard')
                ->with('success', "Module '$module' berhasil dipilih!");
        }

        // Jika memilih PROJECT (seperti sebelumnya)
        $request->validate([
            'project_id' => 'required|exists:user_projects,project_id,user_id,' . $user->id,
        ]);

        $project = Project::findOrFail($request->project_id);

        session([
            'active_project_id' => $project->id,
            'active_project_name' => $project->namaproject,
            'active_project_module' => $project->module ?? 'project',
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Project berhasil dipilih!');
    }
}
