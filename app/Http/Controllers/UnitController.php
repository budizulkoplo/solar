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

            // Buat unit details dengan generate no_rumah
            for ($i = 1; $i <= $unit->jumlah; $i++) {
                $no_rumah = $this->generateNoRumah($unit->blok, $i, $unit->idproject);
                
                UnitDetail::create([
                    'idunit' => $unit->id,
                    'no_rumah' => $no_rumah,
                    'status' => 'tersedia',
                    'shgd' => null,
                    'customer_id' => null,
                    'booking_id' => null,
                    'penjualan_id' => null,
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

            // Simpan blok lama untuk pengecekan perubahan
            $oldBlok = $unit->blok;
            $newBlok = $validated['blok'];

            // Update unit
            $unit->update($validated);

            // Handle perubahan jumlah
            $currentDetailsCount = $unit->details()->count();
            
            if ($validated['jumlah'] > $currentDetailsCount) {
                // Tambah detail baru
                $startNumber = $currentDetailsCount + 1;
                for ($i = $startNumber; $i <= $validated['jumlah']; $i++) {
                    // Gunakan blok baru untuk generate nomor rumah
                    $blokForNoRumah = $newBlok ?? $oldBlok;
                    $no_rumah = $this->generateNoRumah($blokForNoRumah, $i, $unit->idproject);
                    
                    UnitDetail::create([
                        'idunit' => $unit->id,
                        'no_rumah' => $no_rumah,
                        'status' => 'tersedia',
                        'shgd' => null,
                        'customer_id' => null,
                        'booking_id' => null,
                        'penjualan_id' => null,
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

            // Jika blok berubah, regenerate semua no_rumah untuk unit ini
            if ($oldBlok !== $newBlok) {
                $this->regenerateAllNoRumah($unit);
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

    /**
     * Generate nomor rumah berdasarkan blok dan nomor urut
     */
    private function generateNoRumah($blok, $nomorUrut, $projectId)
    {
        // Format: {blok}-{nomor urut}
        // Jika blok kosong, gunakan format khusus
        if (empty($blok) || $blok == '-') {
            return $nomorUrut;
        }
        
        return strtoupper($blok) . '-' . $nomorUrut;
    }

    /**
     * Regenerate semua nomor rumah untuk unit tertentu
     */
    private function regenerateAllNoRumah($unit)
    {
        $details = $unit->details()
            ->orderBy('id')
            ->get();
        
        $nomorUrut = 1;
        foreach ($details as $detail) {
            $no_rumah = $this->generateNoRumah($unit->blok, $nomorUrut, $unit->idproject);
            $detail->update(['no_rumah' => $no_rumah]);
            $nomorUrut++;
        }
    }

    /**
     * Get available units for dropdown (hanya yang tersedia)
     */
    public function getAvailableUnits($unitId = null)
    {
        try {
            $query = UnitDetail::where('status', 'tersedia')
                ->with(['unit' => function($query) {
                    $query->select('id', 'namaunit', 'blok', 'hargadasar');
                }]);
            
            // Jika ada unitId, filter berdasarkan unit tertentu
            if ($unitId) {
                $query->where('idunit', $unitId);
            }
            
            $availableUnits = $query->get(['id', 'idunit', 'no_rumah', 'status']);
            
            return response()->json([
                'success' => true,
                'data' => $availableUnits
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getAvailableUnits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }

    /**
     * Get unit detail by ID
     */
    public function getUnitDetail($id)
    {
        try {
            $unitDetail = UnitDetail::with(['unit', 'customer', 'booking', 'penjualan'])
                ->find($id);
            
            if (!$unitDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit detail tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $unitDetail
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getUnitDetail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }
}