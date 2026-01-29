<?php

namespace App\Http\Controllers;

use App\Models\Kodetransaksi;
use App\Models\Coa;
use App\Models\TransaksiHdr;
use App\Models\NeracaHdr;
use App\Models\LabaRugiHdr;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use PDF;

class KodetransaksiController extends Controller
{
    public function index()
    {
        return view('master.kodetransaksi.list', [
            'coa' => Coa::all(),
            'transaksiHeaders' => TransaksiHdr::all(),
            'neracaHeaders' => NeracaHdr::all(),
            'labaRugiHeaders' => LabaRugiHdr::all()
        ]);
    }

    public function getData(Request $request)
    {
        $data = Kodetransaksi::with(['coa', 'header', 'neraca', 'labarugi'])->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nama_coa', fn($row) => $row->coa ? $row->coa->name : '-')
            ->addColumn('nama_header', fn($row) => $row->header ? $row->header->keterangan : '-')
            ->addColumn('nama_neraca', fn($row) => $row->neraca ? $row->neraca->rincian : '-')
            ->addColumn('nama_labarugi', fn($row) => $row->labarugi ? $row->labarugi->rincian : '-')
            ->addColumn('action', function($row){
                return '
                    <button class="btn btn-sm btn-warning editData" data-id="'.$row->id.'">Edit</button>
                    <button class="btn btn-sm btn-danger deleteData" data-id="'.$row->id.'">Hapus</button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function edit($id)
    {
        $kt = Kodetransaksi::findOrFail($id);
        return response()->json($kt);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kodetransaksi' => 'required|string|unique:kodetransaksi,kodetransaksi',
            'transaksi'     => 'required|string',
            'idheader'      => 'nullable|exists:transaksi_hdr,id',
            'idcoa'         => 'nullable|exists:coa,id',
            'idneraca'      => 'nullable|exists:neraca_hdr,id',
            'idlabarugi'    => 'nullable|exists:labarugi_hdr,id'
        ]);

        Kodetransaksi::create($validated);
        return response()->json(['success' => true]);
    }

    public function update(Request $request, Kodetransaksi $kodetransaksi)
    {
        $validated = $request->validate([
            'kodetransaksi' => 'required|string|unique:kodetransaksi,kodetransaksi,'.$kodetransaksi->id,
            'transaksi'     => 'required|string',
            'idheader'      => 'nullable|exists:transaksi_hdr,id',
            'idcoa'         => 'nullable|exists:coa,id',
            'idneraca'      => 'nullable|exists:neraca_hdr,id',
            'idlabarugi'    => 'nullable|exists:labarugi_hdr,id'
        ]);

        $kodetransaksi->update($validated);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $kt = Kodetransaksi::findOrFail($id);
        $kt->delete();
        return response()->json(['success' => true]);
    }

    public function updateField(Request $request, $id)
    {
        $request->validate([
            'field' => 'required|in:idheader,idcoa,idneraca,idlabarugi',
            'value' => 'nullable|integer'
        ]);

        $kt = Kodetransaksi::findOrFail($id);
        $kt->{$request->field} = $request->value;
        $kt->save();

        return response()->json(['success' => true]);
    }

    // Export Excel (CSV format)
    public function exportExcel(Request $request)
    {
        $data = Kodetransaksi::with(['coa', 'header', 'neraca', 'labarugi'])->get();
        $filename = 'kode-transaksi-' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($data) {
            // Create output stream
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Header
            fputcsv($file, [
                'No',
                'Kode Transaksi',
                'Nama Transaksi',
                'Header Transaksi',
                'COA',
                'Neraca',
                'Laba Rugi',
                'Tanggal Dibuat',
                'Tanggal Diupdate'
            ]);

            // Data
            $no = 1;
            foreach ($data as $item) {
                fputcsv($file, [
                    $no++,
                    $item->kodetransaksi,
                    $item->transaksi,
                    $item->header ? $item->header->keterangan : '-',
                    $item->coa ? $item->coa->name : '-',
                    $item->neraca ? $item->neraca->rincian : '-',
                    $item->labarugi ? $item->labarugi->rincian : '-',
                    $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y H:i') : '-',
                    $item->updated_at ? Carbon::parse($item->updated_at)->format('d/m/Y H:i') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Export PDF
    public function exportPdf(Request $request)
    {
        $data = Kodetransaksi::with(['coa', 'header', 'neraca', 'labarugi'])->get();
        
        $pdf = PDF::loadView('master.kodetransaksi.export-pdf', [
            'data' => $data,
            'tanggal' => Carbon::now()->format('d F Y'),
            'judul' => 'Laporan Kode Transaksi'
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('kode-transaksi-' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    // Export Excel dengan format .xlsx (alternatif menggunakan PHP Spreadsheet langsung)
    public function exportExcelXlsx(Request $request)
    {
        return $this->exportExcel($request); // Untuk sementara gunakan CSV
    }
}