<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Menu;
use App\Models\CompanyUnit;
use App\Models\UserProject;
use Illuminate\Support\Facades\DB;

class ProjectSelectionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userRole = $user->getRoleNames()->first();

        // 🔹 Ambil daftar project user
        $projects = $user->projects()
            ->with('companyUnit')
            ->whereHas('companyUnit') // Hanya project yang punya company
            ->get();

        // 🔹 Ambil daftar PT (Company) yang terkait dengan project user
        $companies = CompanyUnit::whereIn('id', function($query) use ($user) {
                $query->select('idcompany')
                    ->from('projects')
                    ->whereIn('id', function($subQuery) use ($user) {
                        $subQuery->select('project_id')
                            ->from('user_projects')
                            ->where('user_id', $user->id);
                    })
                    ->whereNotNull('idcompany');
            })
            ->orderBy('company_name')
            ->get();

        // 🔹 Ambil module unik dari tabel menu
        $modules = Menu::whereNotNull('module')
            ->where('module', '!=', 'project')
            ->where(function ($q) use ($userRole) {
                $q->where('role', 'like', "%;$userRole;%")
                    ->orWhere('role', 'like', "$userRole;%")
                    ->orWhere('role', 'like', "%;$userRole")
                    ->orWhere('role', '=', $userRole);
            })
            ->select('module', DB::raw('MIN(icon) as icon'))
            ->groupBy('module')
            ->orderBy('module')
            ->get();

        return view('projects.choose', compact('projects', 'modules', 'companies'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // 🔸 Jika user memilih MODULE saja
        if ($request->filled('module')) {
            $module = $request->module;

        session([
            'active_project_id' => $module,
            'active_project_name' => $module,
            'active_project_module' => $module,
        ]);


            // 🔹 Redirect dinamis berdasarkan module
            if ($module === 'mobile') {
                return redirect()->route('mobile.home')
                    ->with('success', "Module '$module' berhasil dipilih!");
            }

            return redirect()->route('dashboard')
                ->with('success', "Module '$module' berhasil dipilih!");
        }

        // 🔸 Jika user memilih PT (Company) langsung
        if ($request->filled('company_id') && $request->filled('company_only')) {
            // Cek apakah user boleh akses PT
            if (!$this->canAccessPT($user)) {
                return back()->with('error', 'Anda tidak memiliki izin untuk mengakses menu PT.');
            }

            $company = CompanyUnit::find($request->company_id);
            
            if (!$company) {
                return back()->with('error', 'PT tidak ditemukan.');
            }

            // Validasi user punya akses ke PT ini melalui project
            $hasAccess = Project::where('idcompany', $company->id)
                ->whereHas('userProjects', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->exists();

            if (!$hasAccess && !$user->hasRole('superadmin')) {
                return back()->with('error', 'Anda tidak memiliki akses ke PT ini.');
            }

            // Set session untuk PT
            session([
                'active_project_id' => null,
                'active_project_name' => null,
                'active_project_module' => 'company',
                'active_company_id' => $company->id,
                'active_company_name' => $company->company_name,
                'active_company_siup' => $company->siup,
                'active_company_npwp' => $company->npwp,
            ]);

            return redirect()->route('dashboard')
                ->with('success', "PT '{$company->company_name}' berhasil dipilih!");
        }

        // 🔸 Jika user memilih PROJECT
        $request->validate([
            'project_id' => 'required|exists:user_projects,project_id,user_id,' . $user->id,
        ]);

        $project = Project::findOrFail($request->project_id);
        $company = $project->companyUnit;

        // Set session untuk project
        session([
            'active_project_id' => $project->id,
            'active_project_name' => $project->namaproject,
            'active_project_module' => $project->module ?? 'project',
            // Juga set company dari project
            'active_company_id' => $company ? $company->id : null,
            'active_company_name' => $company ? $company->company_name : null,
            'active_company_siup' => $company ? $company->siup : null,
            'active_company_npwp' => $company ? $company->npwp : null,
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Project '{$project->namaproject}' berhasil dipilih!");
    }

    /**
     * Cek apakah user bisa akses menu PT
     * Hanya keuangan dan direktur yang bisa akses transaksi PT
     */
    private function canAccessPT($user)
    {
        $allowedRoles = ['direktur', 'keuangan', 'superadmin'];
        
        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }
        
        return false;
    }
}