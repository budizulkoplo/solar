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

        // daftar project user
        $projects = $user->projects()->with('companyUnit')->get();

        // daftar module unik dari menu
        $modules = Menu::whereNotNull('module')
            ->where('module', '!=', 'project')
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
