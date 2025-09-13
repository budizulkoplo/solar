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

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_ids' => 'nullable|array', // bisa kosong
            'project_ids.*' => 'exists:projects,id',
        ]);

        // Hapus akses lama
        UserProject::where('user_id', $request->user_id)->delete();

        // Simpan akses baru
        if($request->project_ids){
            foreach($request->project_ids as $projectId){
                UserProject::create([
                    'user_id' => $request->user_id,
                    'project_id' => $projectId,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function getUserProjects($userId)
    {
        $projectIds = UserProject::where('user_id', $userId)->pluck('project_id');
        return response()->json($projectIds);
    }
}
