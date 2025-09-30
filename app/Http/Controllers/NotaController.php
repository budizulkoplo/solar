<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Transaction;
use App\Models\Rekening;
use App\Models\Cashflow;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use DB;

class NotaController extends Controller
{
    public function index()
{
    // ambil data yg dibutuhkan untuk select2
    $vendors = \App\Models\Vendor::whereNull('deleted_at')->get();
    $rekenings = \App\Models\Rekening::where('idproject', session('active_project_id'))->get();
    $coas = \App\Models\Coa::all();

    return view('transaksi.notas.list', compact('vendors','rekenings','coas'));
}


    public function getData(Request $request)
    {
        $data = Nota::with(['transactions.coa','project','company'])->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('project', fn($row) => $row->project->namaproject ?? '-')
            ->addColumn('company', fn($row) => $row->company->company_name ?? '-')
            ->addColumn('total', fn($row) => number_format($row->total,2))
            ->addColumn('status', fn($row) => ucfirst($row->status))
            ->addColumn('action', function($row){
                return '
                    <button class="btn btn-sm btn-warning editNota" data-id="'.$row->id.'">Edit</button>
                    <button class="btn btn-sm btn-danger deleteNota" data-id="'.$row->id.'">Delete</button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'idproject' => 'required|integer',
            'idcompany' => 'required|integer',
            'tanggal'   => 'required|date',
            'jenis'     => 'required|in:cash,tempo',
            'idrek'     => 'nullable|integer',
            'nota_no'   => 'nullable|string',
            'transactions' => 'required|array|min:1',
            'transactions.*.coa_id'      => 'required|integer',
            'transactions.*.description' => 'required|string',
            'transactions.*.qty'         => 'required|numeric|min:1',
            'transactions.*.harga'       => 'required|numeric|min:0',
            'transactions.*.total'       => 'required|numeric|min:0',
            'transactions.*.posisi'      => 'required|in:debit,credit',
        ]);

        DB::beginTransaction();
        try {
            // Generate nomor invoice otomatis jika tidak diisi manual
            $notaNo = $validated['nota_no'] ?? $this->generateNotaNo($validated['idproject'], $validated['tanggal']);

            // Hitung total dari transaksi
            $total = collect($validated['transactions'])->sum('total');

            $nota = Nota::create([
                'nota_no'   => $notaNo,
                'idproject' => $validated['idproject'],
                'idcompany' => $validated['idcompany'],
                'tanggal'   => $validated['tanggal'],
                'jenis'     => $validated['jenis'],
                'status'    => 'open',
                'total'     => $total,
                'idrek'     => $validated['idrek'] ?? null,
            ]);

            foreach ($validated['transactions'] as $t) {
                Transaction::create([
                    'nota_id'     => $nota->id,
                    'coa_id'      => $t['coa_id'],
                    'description' => $t['description'],
                    'qty'         => $t['qty'],
                    'harga'       => $t['harga'],
                    'total'       => $t['total'],
                    'posisi'      => $t['posisi'],
                ]);
            }

            // Jika cash, update rekening & buat cashflow
            if ($nota->jenis === 'cash' && !empty($validated['idrek'])) {
                $rekening = Rekening::find($validated['idrek']);
                if ($rekening) {
                    $rekening->saldo += $total;
                    $rekening->saldoakhir = $rekening->saldo;
                    $rekening->save();

                    Cashflow::create([
                        'rekening_id' => $rekening->id,
                        'nota_id'     => $nota->id,
                        'amount'      => $total,
                        'saldo_akhir' => $rekening->saldo,
                        'keterangan'  => 'Nota '.$nota->nota_no,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nota berhasil dibuat',
                'nota_no' => $nota->nota_no
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate nomor nota otomatis
     * Format: {idproject}-{tgl}-00001
     */
    private function generateNotaNo($idProject, $tanggal)
    {
        $date = date('Ymd', strtotime($tanggal));
        $count = Nota::where('idproject', $idProject)
            ->whereDate('tanggal', $tanggal)
            ->count();

        $urut = str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        return "{$idProject}-{$date}-{$urut}";
    }
}
