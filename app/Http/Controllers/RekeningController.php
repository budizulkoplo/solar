<?php

namespace App\Http\Controllers;

use App\Models\Rekening;
use App\Models\CompanyUnit;
use App\Models\Project;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RekeningController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if ($request->type === 'company') {
                $data = Rekening::with('company')
                    ->whereNotNull('idcompany')
                    ->get();
            } else {
                $data = Rekening::with('project.company')
                    ->whereNotNull('idproject')
                    ->get();
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    return '
                        <span class="badge bg-info editRekening" data-id="'.$row->idrek.'"><i class="bi bi-pencil"></i></span>
                        <span class="badge bg-danger deleteRekening" data-id="'.$row->idrek.'"><i class="fa fa-trash"></i></span>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        $companies = CompanyUnit::all();
        $projects = Project::with('company')->get();

        return view('master.rekening.list', compact('companies', 'projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'norek' => 'required',
            'namarek' => 'required',
            'saldo' => 'numeric',
            'saldoakhir' => 'numeric',
            'idcompany' => 'nullable|exists:company_units,id',
            'idproject' => 'nullable|exists:projects,id',
        ]);

        if (!$request->idcompany && !$request->idproject) {
            return response()->json(['error' => 'Harus pilih Company atau Project'], 422);
        }

        Rekening::updateOrCreate(
            ['idrek' => $request->id],
            $request->only(['norek','namarek','saldo','saldoakhir','idcompany','idproject'])
        );

        return response()->json(['success' => true]);
    }

    public function edit($id)
    {
        return Rekening::findOrFail($id);
    }

    public function destroy($id)
    {
        Rekening::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function getSaldo($id)
    {
        $rek = Rekening::findOrFail($id);
        return response()->json(['saldo' => $rek->saldoakhir]);
    }
}
