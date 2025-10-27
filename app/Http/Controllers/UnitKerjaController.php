<?php

namespace App\Http\Controllers;

use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\View\View;

class UnitKerjaController extends Controller
{
    public function index(): View
    {
        return view('master.unitkerja.list');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:150',
            'lokasi'   => 'nullable|string|max:150',
        ]);

        $fid = $request->fidunit ?? null;

        if (!empty($fid)) {
            $id = $fid;
            if (!is_numeric($fid)) {
                try {
                    $id = Crypt::decryptString($fid);
                } catch (DecryptException $e) {}
            }
            $unit = UnitKerja::find($id);
            if (!$unit) {
                return response()->json(['success' => false, 'message' => 'Unit kerja tidak ditemukan'], 404);
            }
        } else {
            $unit = new UnitKerja();
        }

        $unit->company_name = $request->company_name;
        $unit->lokasi   = $request->lokasi;
        $unit->save();

        return response()->json([
            'success' => true,
            'message' => 'Data Unit Kerja berhasil disimpan',
            'data'    => $unit
        ]);
    }

    public function getdata(Request $request)
    {
        $unit = UnitKerja::select(['id', 'company_name', 'lokasi', 'lokasi_lock']);

        return DataTables::of($unit)
            ->addIndexColumn()
            ->addColumn('enc_id', fn($row) => Crypt::encryptString($row->id))
            ->addColumn('aksi', function ($row) {
                $enc_id = Crypt::encryptString($row->id);
                return '
                    <span class="badge bg-info btn-edit" data-id="'.$enc_id.'" style="cursor:pointer;"><i class="bi bi-pencil-square"></i></span>
                    <span class="badge bg-danger btn-hapus" data-id="'.$enc_id.'" style="cursor:pointer;"><i class="fa-solid fa-trash-can"></i></span>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function show($id)
    {
        if (!is_numeric($id)) {
            try {
                $id = Crypt::decryptString($id);
            } catch (DecryptException $e) {}
        }

        $unit = UnitKerja::find($id);
        if (!$unit) {
            return response()->json(['success' => false, 'message' => 'Unit kerja tidak ditemukan'], 404);
        }

        return response()->json($unit);
    }

    public function destroy($id)
    {
        if (!is_numeric($id)) {
            try {
                $id = Crypt::decryptString($id);
            } catch (DecryptException $e) {}
        }

        $unit = UnitKerja::find($id);
        if (!$unit) {
            return response()->json(['success' => false, 'message' => 'Unit kerja tidak ditemukan'], 404);
        }

        $unit->delete();
        return response()->json(['success' => true, 'message' => 'Unit kerja dihapus']);
    }

    public function toggleLock(Request $request)
    {
        try {
            $id = \Crypt::decryptString($request->id);
            $unit = \App\Models\UnitKerja::find($id);

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit tidak ditemukan'
                ]);
            }

            // Ubah logika toggle jadi string, karena kolom enum('0','1')
            $unit->lokasi_lock = $unit->lokasi_lock === '1' ? '0' : '1';
            $unit->save();

            return response()->json([
                'success' => true,
                'message' => 'Status lokasi_lock diubah',
                'lokasi_lock' => $unit->lokasi_lock
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ]);
        }
    }

}
