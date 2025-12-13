<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitDetail;
use App\Models\Project;
use App\Models\JenisUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    public function index()
    {
        return view('master.units.list');
    }

    public function getData(Request $request)
    {
        try {
            $data = Unit::with(['project', 'jenisUnit'])->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('project', function($row) {
                    return $row->project ? $row->project->namaproject : '-';
                })
                ->addColumn('jenisunit', function($row) {
                    return $row->jenisUnit ? $row->jenisUnit->jenisunit : '-';
                })
                ->addColumn('action', function($row) {
                    $buttons = '
                        <button class="btn btn-sm btn-warning editUnit" data-id="'.$row->id.'">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deleteUnit" data-id="'.$row->id.'">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                    return $buttons;
                })
                ->rawColumns(['action'])
                ->make(true);
                
        } catch (\Exception $e) {
            \Log::error('Error in getData: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'idproject' => 'required|integer|exists:projects,id',
                'namaunit' => 'required|string|max:150',
                'idjenis' => 'required|integer|exists:jenisunit,id',
                'tipe' => 'nullable|string|max:100',
                'blok' => 'nullable|string|max:100',
                'luastanah' => 'nullable|string|max:50',
                'luasbangunan' => 'nullable|string|max:50',
                'hargadasar' => 'required|numeric|min:0',
                'jumlah' => 'required|integer|min:1',
            ]);

            DB::beginTransaction();

            $unit = Unit::create($validated);

            // Buat unit details
            for ($i = 0; $i < $unit->jumlah; $i++) {
                UnitDetail::create([
                    'idunit' => $unit->id,
                    'status' => 'tersedia',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unit berhasil ditambahkan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $unit = Unit::find($id);
            
            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $unit
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in edit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $unit = Unit::find($id);
            
            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit tidak ditemukan'
                ], 404);
            }
            
            $validated = $request->validate([
                'idproject' => 'required|integer|exists:projects,id',
                'namaunit' => 'required|string|max:150',
                'idjenis' => 'required|integer|exists:jenisunit,id',
                'tipe' => 'nullable|string|max:100',
                'blok' => 'nullable|string|max:100',
                'luastanah' => 'nullable|string|max:50',
                'luasbangunan' => 'nullable|string|max:50',
                'hargadasar' => 'required|numeric|min:0',
                'jumlah' => 'required|integer|min:1',
            ]);

            DB::beginTransaction();

            // Update unit
            $unit->update($validated);

            // Handle perubahan jumlah
            $currentDetailsCount = $unit->details()->count();
            
            if ($validated['jumlah'] > $currentDetailsCount) {
                // Tambah detail baru
                for ($i = $currentDetailsCount; $i < $validated['jumlah']; $i++) {
                    UnitDetail::create([
                        'idunit' => $unit->id,
                        'status' => 'tersedia',
                    ]);
                }
            } elseif ($validated['jumlah'] < $currentDetailsCount) {
                // Hapus detail yang tersedia (hanya yang status tersedia)
                $toDelete = $currentDetailsCount - $validated['jumlah'];
                $availableToDelete = $unit->details()
                    ->where('status', 'tersedia')
                    ->orderBy('id', 'desc')
                    ->limit($toDelete)
                    ->get();
                
                foreach ($availableToDelete as $detail) {
                    $detail->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unit berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $unit = Unit::with(['details' => function($query) {
                $query->whereIn('status', ['booking', 'terjual']);
            }])->find($id);
            
            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit tidak ditemukan'
                ], 404);
            }
            
            // Cek apakah ada detail yang tidak tersedia
            if ($unit->details->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus unit karena terdapat unit dalam status booking/terjual'
                ], 400);
            }
            
            // Hapus semua detail terlebih dahulu
            $unit->details()->delete();
            
            // Hapus unit
            $unit->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unit berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUnitsByProject($projectId)
    {
        try {
            $units = Unit::where('idproject', $projectId)
                ->orderBy('namaunit')
                ->get(['id', 'namaunit', 'blok']);
            
            return response()->json([
                'success' => true,
                'data' => $units
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getUnitsByProject: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }
}