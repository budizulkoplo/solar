<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use App\Models\UserProject;
use Illuminate\Http\Request;

class UserProjectController extends Controller
{
    public function index()
    {
        $users = User::with('projects.company')->get(); // eager load projects + company
        $projects = Project::with('company')->get();

        return view('master.user_projects.list', compact('users', 'projects'));
    }

    // Toggle akses satu project
    public function toggle(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
        ]);

        $userId = $request->user_id;
        $projectId = $request->project_id;

        $exists = UserProject::where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first();

        if ($exists) {
            $exists->delete();
            return response()->json(['status' => 'removed']);
        } else {
            UserProject::create([
                'user_id' => $userId,
                'project_id' => $projectId,
            ]);
            return response()->json(['status' => 'added']);
        }
    }

    public function getUserProjects($userId)
    {
        $projectIds = UserProject::where('user_id', $userId)->pluck('project_id');
        return response()->json($projectIds);
    }
}
