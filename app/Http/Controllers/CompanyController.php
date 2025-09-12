<?php
// app/Http/Controllers/CompanyController.php
namespace App\Http\Controllers;

use App\Models\CompanyUnit;
use App\Models\Project;
use App\Models\Retail;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CompanyController extends Controller
{
    public function index(Request $request)
{
    if ($request->ajax()) {
        // Panggil relasi + hitung jumlah project
        $companies = CompanyUnit::select('company_units.*')
            ->withCount('projects'); // penting: taruh setelah select

        return DataTables::of($companies)
            ->addIndexColumn()
            ->addColumn('logo', function ($row) {
                return $row->logo 
                    ? '<img src="'.asset('storage/'.$row->logo).'" width="40">' 
                    : '-';
            })
            // pastikan kolom projects_count selalu ada
            ->addColumn('projects_count', function ($row) {
                return $row->projects_count ?? 0;
            })
            ->addColumn('aksi', function ($row) {
                return '
                    <span class="badge bg-info editCompany" data-id="'.$row->id.'">
                        <i class="bi bi-pencil"></i>
                    </span>
                    <span class="badge bg-danger deleteCompany" data-id="'.$row->id.'">
                        <i class="fa fa-trash"></i>
                    </span>
                ';
            })
            ->rawColumns(['logo','aksi'])
            ->make(true);
    }

    $retails = Retail::all();
    return view('master.company.list', compact('retails'));
}


    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:150',
            'siup'         => 'nullable|string|max:50',
            'npwp'         => 'nullable|string|max:50',
            'alamat'       => 'nullable|string',
            'logo'         => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $company = CompanyUnit::updateOrCreate(['id' => $request->id], $validated);
        return response()->json(['success' => true, 'company' => $company]);
    }

    public function show($id)
    {
        return CompanyUnit::with('projects.retail')->findOrFail($id);
    }

    public function destroy($id)
    {
        CompanyUnit::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // CRUD Project inline
    public function storeProject(Request $request)
    {
        $validated = $request->validate([
            'idcompany'   => 'required|exists:company_units,id',
            'idretail'    => 'required|exists:retails,id',
            'namaproject' => 'required|string|max:150',
            'lokasi'      => 'nullable|string|max:150',
            'luas'        => 'nullable|string|max:50',
            'logo'        => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('project_logos', 'public');
        }

        $project = Project::updateOrCreate(['id' => $request->id], $validated);

        return response()->json(['success' => true, 'project' => $project]);
    }


    public function destroyProject($id)
    {
        Project::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ambil detail company (untuk edit)
    public function edit($id)
    {
        return CompanyUnit::findOrFail($id);
    }

    // ambil detail project (untuk edit)
    public function editProject($id)
    {
        return Project::findOrFail($id);
    }
}
