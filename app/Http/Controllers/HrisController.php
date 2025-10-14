<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HRISController extends Controller
{
    /**
     * Halaman utama laporan absensi
     */
    public function absensi()
    {
        $bulan = date('Y-m');
        return view('hris.absensi.index', compact('bulan'));
    }

    /**
     * Ambil data absensi via stored procedure - FIXED
     */
    public function getAbsensiData(Request $request)
    {
        $bulan = $request->bulan ?? date('Y-m');

        // Jalankan prosedur
        $data = DB::select('CALL spRptAbsensiData(?)', [$bulan]);

        // Process data untuk handle <br>
        $processedData = [];
        foreach ($data as $index => $row) {
            $processedRow = [
                'no' => $index + 1,
                'user_id' => $row->user_id,
                'nik' => $row->nik,
                'nip' => $row->nip,
                'name' => $row->name,
                'jabatan' => $row->jabatan
            ];

            // Process kolom tanggal
            foreach ($row as $key => $value) {
                if (is_numeric($key)) {
                    // Simpan data asli, nanti di-handle di JavaScript
                    $processedRow[$key] = $value;
                }
            }

            $processedData[] = $processedRow;
        }

        return response()->json([
            'data' => $processedData
        ]);
    }
}