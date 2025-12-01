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
            $type = $request->type;

            $query = Rekening::with(['company', 'project.company']);

            if ($type === 'company') {
                $query->whereNotNull('idcompany');
            } else if ($type === 'project') {
                $query->whereNotNull('idproject');
            }

            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('aksi', function($row) {
                    $type = $row->idcompany ? 'company' : 'project';
                    return '
                        <button class="btn btn-sm btn-info editRekening" data-id="'.$row->idrek.'" data-type="'.$type.'">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deleteRekening" data-id="'.$row->idrek.'">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        $companies = CompanyUnit::all();
        $projects = Project::with('company')->get();
        return view('master.rekening.list', compact('companies','projects'));
    }

    public function edit($id)
    {
        try {
            $rek = Rekening::with('company', 'project.company')->findOrFail($id);
            
            \Log::info('Edit rekening:', [
                'id' => $id,
                'data' => $rek->toArray()
            ]);
            
            return response()->json([
                'idrek' => $rek->idrek,
                'norek' => $rek->norek,
                'namarek' => $rek->namarek,
                'saldo' => $rek->saldo,
<<<<<<< HEAD
                'saldoakhir' => $rek->saldoakhir,
=======
                'saldoawal' => $rek->saldoawal,
>>>>>>> a94c95226e8293ff865a2c297dc1f323427b5910
                'idcompany' => $rek->idcompany,
                'idproject' => $rek->idproject
            ]);

        } catch (\Exception $e) {
            \Log::error('Edit rekening error:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'norek' => 'required',
            'namarek' => 'required',
            'saldo' => 'nullable|numeric',
            'saldoawal' => 'nullable|numeric',
            'idcompany' => 'nullable|exists:company_units,id',
            'idproject' => 'nullable|exists:projects,id',
        ]);

        // Validasi: harus memilih company atau project, tidak boleh keduanya
        if (!$request->idcompany && !$request->idproject) {
            return response()->json([
                'error' => 'Harus memilih Company atau Project'
            ], 422);
        }

        if ($request->idcompany && $request->idproject) {
            return response()->json([
                'error' => 'Hanya boleh memilih Company ATAU Project, tidak boleh keduanya'
            ], 422);
        }

        try {
            Rekening::updateOrCreate(
                ['idrek' => $request->idrek],
                [
                    'norek' => $request->norek,
                    'namarek' => $request->namarek,
                    'saldo' => $request->saldo ?? 0,
                    'saldoawal' => $request->saldoawal ?? 0,
                    'idcompany' => $request->idcompany,
                    'idproject' => $request->idproject
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $rekening = Rekening::findOrFail($id);
            $rekening->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSaldo($id)
    {
        try {
            $rek = Rekening::findOrFail($id);
            return response()->json([
                'saldo' => $rek->saldoawal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Data tidak ditemukan'
            ], 404);
        }
    }
}