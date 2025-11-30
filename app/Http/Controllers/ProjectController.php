<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaTransaction;
use App\Models\NotaPayment;
use App\Models\Cashflow;
use App\Models\KodeTransaksi;
use App\Models\Rekening;
use App\Models\Vendor;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    // Halaman transaksi masuk (in)
    public function in()
    {
        return view('transaksi.project.in');
    }

    // Halaman transaksi keluar (out)
    public function out()
    {
        return view('transaksi.project.out');
    }

    // Datatable untuk transaksi
    public function getdata($type)
    {
        $query = Nota::with(['project', 'transactions.kodeTransaksi'])
            ->where('cashflow', $type)
            ->where('idproject', session('active_project_id'))
            ->select('notas.*');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                $btn = '<div class="btn-group">
                    <button class="btn btn-sm btn-info view-btn" data-id="'.$row->id.'" title="View"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-warning edit-btn" data-id="'.$row->id.'" title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'" title="Delete"><i class="bi bi-trash"></i></button>
                </div>';
                return $btn;
            })
            ->addColumn('project_name', function($row) {
                return $row->project ? $row->project->namaproject : '-';
            })
            ->editColumn('total', function($row) {
                return 'Rp ' . number_format($row->total, 2, ',', '.');
            })
            ->editColumn('status', function($row) {
                $badge = [
                    'open' => 'bg-warning',
                    'paid' => 'bg-success',
                    'partial' => 'bg-info',
                    'cancel' => 'bg-danger'
                ];
                return '<span class="badge '.$badge[$row->status].'">'.ucfirst($row->status).'</span>';
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    // Simpan transaksi - VERSI YANG DIPERBAIKI
    public function store(Request $request, $type)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'tanggal' => 'required|date',
                'vendor_id' => 'required|exists:vendors,id',
                'idrek' => 'required|exists:rekening,idrek',
                'paymen_method' => 'required|in:cash,tempo',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:0',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // Validasi file
            ]);

            // Debug: Log session data
            \Log::info('Session Data:', [
                'active_project_id' => session('active_project_id'),
                'active_project_company_id' => session('active_project_company_id')
            ]);

            // Ambil project berdasarkan session
            $projectId = session('active_project_id');
            $project = Project::find($projectId);

            if (!$project) {
                throw new \Exception("Project dengan ID {$projectId} tidak ditemukan");
            }

            // Dapatkan idcompany
            $idcompany = $project->idcompany ?? session('active_project_company_id');
            
            if (empty($idcompany)) {
                throw new \Exception("Project '{$project->namaproject}' tidak memiliki company yang terkait");
            }

            // Dapatkan idretail dari project (bisa NULL)
            $idretail = $project->idretail;

            // Handle upload bukti nota
            $buktiNotaPath = null;
            if ($request->hasFile('bukti_nota')) {
                $file = $request->file('bukti_nota');
                $filename = 'nota_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota', $filename, 'public');
                
                \Log::info('Bukti nota uploaded:', ['path' => $buktiNotaPath]);
            }

            // Hitung total transaksi
            $total = 0;
            foreach ($request->transactions as $index => $transaction) {
                $itemTotal = $transaction['nominal'] * $transaction['jml'];
                $total += $itemTotal;
            }

            // Data untuk nota header
            $notaData = [
                'nota_no' => $request->nota_no,
                'idproject' => $project->id,
                'idcompany' => $idcompany,
                'idretail' => $idretail,
                'tanggal' => $request->tanggal,
                'cashflow' => $type,
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'total' => $total,
                'status' => $request->paymen_method == 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath, // SIMPAN PATH FILE
            ];

            \Log::info('Nota Data to be saved:', $notaData);

            // Buat nota header
            $nota = Nota::create($notaData);
            \Log::info('Nota created:', ['nota_id' => $nota->id]);

            // Simpan detail transaksi
            foreach ($request->transactions as $index => $transaction) {
                $itemTotal = $transaction['nominal'] * $transaction['jml'];
                
                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idkodetransaksi' => $transaction['idkodetransaksi'],
                    'description' => $transaction['description'],
                    'nominal' => $transaction['nominal'],
                    'jml' => $transaction['jml'],
                    'total' => $itemTotal,
                ]);
            }

            // Jika cash, langsung buat pembayaran dan catat cashflow
            if ($request->paymen_method == 'cash') {
                $this->processCashPayment($nota, $request->idrek, $total, $request->tanggal);
            }

            DB::commit();

            \Log::info('Transaction completed successfully');

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transaction Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proses pembayaran cash lengkap
     */
    private function processCashPayment($nota, $idrek, $jumlah, $tanggal)
    {
        try {
            \Log::info('Processing cash payment:', [
                'nota_id' => $nota->id,
                'rekening_id' => $idrek,
                'jumlah' => $jumlah,
                'tanggal' => $tanggal
            ]);

            // 1. Update saldo rekening
            $rekening = Rekening::find($idrek);
            if (!$rekening) {
                throw new \Exception("Rekening dengan ID {$idrek} tidak ditemukan");
            }

            $saldoAwal = $rekening->saldo;
            
            if ($nota->cashflow == 'out') {
                $rekening->saldo -= $jumlah;
            } else {
                $rekening->saldo += $jumlah;
            }
            
            $rekening->save();

            \Log::info('Rekening updated:', [
                'rekening_id' => $rekening->idrek,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo
            ]);

            // 2. Buat nota payment
            $notaPayment = NotaPayment::create([
                'idnota' => $nota->id,
                'idrek' => $idrek,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah
            ]);

            \Log::info('Nota payment created:', ['payment_id' => $notaPayment->id]);

            // 3. Catat di cashflows
            $cashflow = Cashflow::create([
                'idrek' => $idrek,
                'idnota' => $nota->id,
                'tanggal' => $tanggal,
                'cashflow' => $nota->cashflow,
                'nominal' => $jumlah,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => "Pembayaran nota {$nota->nota_no} - {$nota->cashflow}"
            ]);

            \Log::info('Cashflow recorded:', ['cashflow_id' => $cashflow->id]);

        } catch (\Exception $e) {
            \Log::error('Cash payment processing error:', [
                'message' => $e->getMessage(),
                'nota_id' => $nota->id
            ]);
            throw $e;
        }
    }

    // Ambil saldo rekening
    public function saldoRekening($id)
    {
        try {
            $rekening = Rekening::find($id);
            if (!$rekening) {
                return response()->json(['saldo' => 0]);
            }

            return response()->json(['saldo' => $rekening->saldo]);
        } catch (\Exception $e) {
            \Log::error('Error getting saldo rekening:', [
                'rekening_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['saldo' => 0]);
        }
    }

    // Method untuk debug project data
    public function debugProject()
    {
        try {
            $projectId = session('active_project_id');
            $project = Project::find($projectId);
            
            $data = [
                'success' => true,
                'project_id' => $projectId,
                'project_exists' => !is_null($project),
                'project_attributes' => $project ? $project->getAttributes() : null,
                'session_company_id' => session('active_project_company_id'),
                'all_columns' => \Schema::getColumnListing('projects')
            ];
            
            \Log::info('Debug Project Data:', $data);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}