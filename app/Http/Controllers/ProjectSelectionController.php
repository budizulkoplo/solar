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
            'project_id' => 'required|exists:projects,id'
        ]);

        session(['active_project_id' => $request->project_id]);

        return redirect()->route('dashboard')
            ->with('success', 'Project berhasil dipilih!');
    }
}
