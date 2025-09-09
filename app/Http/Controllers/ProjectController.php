<?php
namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $projects = Project::query();
            return DataTables::of($projects)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    return '
                        <span class="badge rounded-pill bg-info formcell" data-id="'.$row->id.'">
                            <i class="bi bi-pencil-square"></i>
                        </span>
                        <span class="badge rounded-pill bg-danger deleteProject" data-id="'.$row->id.'">
                            <i class="fa-solid fa-trash-can"></i>
                        </span>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        return view('master.project.list');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_project' => 'required|string|max:255',
            'lokasi'       => 'nullable|string|max:255',
            'keterangan'   => 'nullable|string',
        ]);

        $project = Project::updateOrCreate(
            ['id' => $request->id],
            $validated
        );

        return response()->json(['success' => true, 'project' => $project]);
    }

    public function show($id)
    {
        return Project::findOrFail($id);
    }

    public function destroy($id)
    {
        Project::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
