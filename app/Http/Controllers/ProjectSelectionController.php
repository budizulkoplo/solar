<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class ProjectSelectionController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $projects = $user->projects()->with('companyUnit')->get();

        return view('projects.choose', compact('projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:user_projects,project_id,user_id,' . auth()->id()
        ]);

        $project = Project::findOrFail($request->project_id);

        session([
            'active_project_id' => $project->id,
            'active_project_name' => $project->namaproject,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Project berhasil dipilih!');
    }

}
