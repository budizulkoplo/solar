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
        return view('transaksi.notas.list');
    }

    public function getData(Request $request)
    {
        $data = Nota::with(['transactions.coa','project','company'])->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('project', fn($row) => $row->project->namaproject ?? '-')
            ->addColumn('company', fn($row) => $row->company->name ?? '-')
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
            'idproject'=>'required|integer',
            'idcompany'=>'required|integer',
            'idretail'=>'nullable|integer',
            'tanggal'=>'required|date',
            'jenis'=>'required|in:cash,tempo',
            'transactions'=>'required|array|min:1',
            'transactions.*.coa_id'=>'required|integer',
            'transactions.*.amount'=>'required|numeric|min:0.01',
            'transactions.*.keterangan'=>'nullable|string',
        ]);

        DB::transaction(function() use($validated){
            // Hitung total
            $total = collect($validated['transactions'])->sum('amount');

            $nota = Nota::create([
                'nota_no'=>Str::upper('N-'.time()),
                'idproject'=>$validated['idproject'],
                'idcompany'=>$validated['idcompany'],
                'idretail'=>$validated['idretail'] ?? null,
                'tanggal'=>$validated['tanggal'],
                'jenis'=>$validated['jenis'],
                'status'=>'open',
                'total'=>$total
            ]);

            collect($validated['transactions'])->each(function($t) use($nota){
                Transaction::create([
                    'nota_id'=>$nota->id,
                    'coa_id'=>$t['coa_id'],
                    'amount'=>$t['amount'],
                    'keterangan'=>$t['keterangan'] ?? null
                ]);

                // update saldo rekening jika cash
                if($nota->jenis=='cash'){
                    $rekening = Rekening::where('idproject',$nota->idproject)->first(); // bisa disesuaikan logic rekening
                    if($rekening){
                        $rekening->saldo += $t['amount'];
                        $rekening->saldoakhir = $rekening->saldo;
                        $rekening->save();

                        Cashflow::create([
                            'rekening_id'=>$rekening->id,
                            'nota_id'=>$nota->id,
                            'amount'=>$t['amount'],
                            'saldo_akhir'=>$rekening->saldo,
                            'keterangan'=>$t['keterangan'] ?? null
                        ]);
                    }
                }
            });
        });

        return response()->json([
            'success'=>true,
            'message'=>'Nota berhasil dibuat',
            'nota_no'=>$nota->nota_no
        ]);
    }
}
