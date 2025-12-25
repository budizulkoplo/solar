<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitDetail;
use App\Models\Penjualan;
use App\Models\Customer;
use App\Models\Booking;
use App\Models\KodeTransaksi;
use App\Models\Nota;
use App\Models\NotaTransaction;
use App\Models\NotaPayment;
use App\Models\Cashflow;
use App\Models\Rekening;
use App\Models\Vendor;
use App\Models\Project;
use App\Models\TransUpdateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class AgencySaleController extends Controller
{
    /**
     * Menampilkan halaman utama Penjualan Agency
     */
    public function index()
    {
        $projects = Project::whereHas('units.details', function($query) {
            $query->where('status', 'terjual');
        })
        ->orderBy('namaproject')
        ->get(['id', 'namaproject']);

        return view('sales.agency.index', compact('projects'));
    }

    /**
     * Get data unit terjual untuk datatable
     */
    public function getData(Request $request)
    {
        $query = UnitDetail::with([
                'unit:id,namaunit,blok,idproject',
                'unit.project:id,namaproject',
                'customer:id,nama_lengkap',
                'penjualan:id,kode_penjualan,harga_jual',
                'booking:id,kode_booking'
            ])
            ->where('status', 'terjual')
            ->whereHas('penjualan');

        // Filter berdasarkan project jika ada
        if ($request->has('project_id') && !empty($request->project_id)) {
            $query->whereHas('unit', function($q) use ($request) {
                $q->where('idproject', $request->project_id);
            });
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                $btn = '<button class="btn btn-sm btn-primary btn-select" 
                         data-id="'.$row->id.'"
                         data-unit-name="'.$row->unit->namaunit.'"
                         data-customer="'.($row->customer ? $row->customer->nama_lengkap : '-').'"
                         data-harga-jual="'.($row->penjualan ? number_format($row->penjualan->harga_jual, 0, ',', '.') : '0').'">
                         <i class="bi bi-cart-plus"></i> Pilih
                       </button>';
                return $btn;
            })
            ->editColumn('unit.namaunit', function($row) {
                $blok = $row->unit->blok ? ' Blok ' . $row->unit->blok : '';
                return $row->unit->namaunit . $blok;
            })
            ->editColumn('penjualan.harga_jual', function($row) {
                if ($row->penjualan) {
                    return 'Rp ' . number_format($row->penjualan->harga_jual, 0, ',', '.');
                }
                return '-';
            })
            ->filterColumn('unit.namaunit', function($query, $keyword) {
                $query->whereHas('unit', function($q) use ($keyword) {
                    $q->where('namaunit', 'like', "%{$keyword}%")
                      ->orWhere('blok', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('customer.nama_lengkap', function($query, $keyword) {
                $query->whereHas('customer', function($q) use ($keyword) {
                    $q->where('nama_lengkap', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    /**
     * Show form untuk membuat transaksi agency
     */
    public function create($unitDetailId)
    {
        $unitDetail = UnitDetail::with([
            'unit',
            'unit.project',
            'customer',
            'penjualan',
            'booking'
        ])->findOrFail($unitDetailId);

        if ($unitDetail->status !== 'terjual') {
            abort(404, 'Unit belum terjual');
        }

        $existingAgencySale = Nota::where('unit_detail_id', $unitDetailId)
            ->where('type', 'agency_sale')
            ->first();

        if ($existingAgencySale) {
            return redirect()->route('agency-sales.edit', $existingAgencySale->id)
                ->with('warning', 'Unit ini sudah memiliki transaksi agency.');
        }

        $kodeTransaksi = KodeTransaksi::where('transaksi', 'like', '%fee%marketing%')
            ->orWhere('transaksi', 'like', '%komisi%')
            ->orWhere('transaksi', 'like', '%agency%')
            ->get(['id', 'kodetransaksi', 'transaksi']);

        $activeCompanyId = session('active_company_id');

        if (!$activeCompanyId) {
            abort(403, 'Company aktif belum dipilih');
        }

        $rekening = Rekening::where('idcompany', $activeCompanyId)
            ->get(['idrek', 'norek', 'namarek', 'saldo']);

        return view('sales.agency.create', compact(
            'unitDetail',
            'kodeTransaksi',
            'rekening'
        ));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
            $request->validate([
                'unit_detail_id' => 'required|exists:unit_details,id',
                'nota_no' => 'required|string|max:50',
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:1',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'subtotal' => 'required|numeric|min:0',
            ]);

            // Ambil unit detail
            $unitDetail = UnitDetail::with(['unit', 'penjualan'])->findOrFail($request->unit_detail_id);

            // Validasi unit dalam status terjual
            if ($unitDetail->status !== 'terjual') {
                throw new \Exception('Unit belum dalam status terjual');
            }

            // Ambil user yang login
            $user = auth()->user();
            $nip = $user->nip ?? $user->id;
            $namauser = $user->name;

            // Ambil project dari unit
            $project = $unitDetail->unit->project;
            $projectId = $project->id;
            $idcompany = $project->idcompany ?? null;
            $idretail = $project->idretail ?? null;

            // Handle upload bukti nota
            $buktiNotaPath = null;
            if ($request->hasFile('bukti_nota')) {
                $file = $request->file('bukti_nota');
                $filename = 'agency_sale_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('agency_sales', $filename, 'public');
            }

            // Hitung total
            $subtotal = $request->subtotal;
            $ppn = $request->ppn ?? 0;
            $diskon = $request->diskon ?? 0;
            $total = $subtotal + $ppn - $diskon;

            // Data untuk nota header
            $notaData = [
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'type' => 'agency_sale',
                'idproject' => $projectId,
                'idcompany' => $idcompany,
                'idretail' => $idretail,
                'unit_detail_id' => $unitDetail->id,
                'vendor_id' => $request->vendor_id,
                'idrek' => $request->idrek,
                'tanggal' => $request->tanggal,
                'cashflow' => 'in', 
                'paymen_method' => $request->paymen_method ?? 'cash',
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'ppn' => $ppn,
                'diskon' => $diskon,
                'total' => $total,
                'status' => ($request->paymen_method ?? 'cash') == 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath,
                'nip' => $nip,
                'namauser' => $namauser,
            ];

            // Buat nota header
            $nota = Nota::create($notaData);

            // Simpan detail transaksi
            foreach ($request->transactions as $transaction) {
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

            // Simpan PPN jika ada
            if ($ppn > 0) {
                $kodePpn = KodeTransaksi::where('kodetransaksi', $request->ppn_kode ?? '3001')->first();
                if ($kodePpn) {
                    NotaTransaction::create([
                        'idnota' => $nota->id,
                        'idkodetransaksi' => $kodePpn->id,
                        'description' => 'PPN',
                        'nominal' => $ppn,
                        'jml' => 1,
                        'total' => $ppn,
                    ]);
                }
            }

            // Simpan Diskon jika ada
            if ($diskon > 0) {
                $kodeDiskon = KodeTransaksi::where('kodetransaksi', $request->diskon_kode ?? '5001')->first();
                if ($kodeDiskon) {
                    NotaTransaction::create([
                        'idnota' => $nota->id,
                        'idkodetransaksi' => $kodeDiskon->id,
                        'description' => 'Diskon',
                        'nominal' => $diskon,
                        'jml' => 1,
                        'total' => $diskon,
                    ]);
                }
            }

            // Buat log
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Transaksi agency sale dibuat untuk unit " . $unitDetail->unit->namaunit);

            // Jika cash, langsung buat pembayaran
            if (($request->paymen_method ?? 'cash') == 'cash') {
                $this->processCashPayment($nota, $request->idrek, $total, $request->tanggal);
            }

            DB::commit();

            return redirect()->route('agency-sales.show', $nota->id)
        ->with('success', 'Transaksi agency sale berhasil disimpan');

        
    }

    /**
     * Show detail transaksi agency
     */
    public function show($id)
    {
        $nota = Nota::with([
                'project',
                'vendor',
                'rekening',
                'unitDetail.unit',
                'unitDetail.customer',
                'unitDetail.penjualan',
                'transactions' => function($q) {
                    $q->with('kodeTransaksi')
                      ->orderBy('id');
                },
                'payments.rekening',
                'cashflows',
                'updateLogs' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])
            ->where('type', 'agency_sale')
            ->findOrFail($id);

        return view('sales.agency.show', compact('nota'));
    }

    /**
     * Edit transaksi agency
     */
    public function edit($id)
    {
        $nota = Nota::with([
                'unitDetail.unit',
                'unitDetail.customer',
                'transactions' => function($q) {
                    $q->with('kodeTransaksi')
                      ->orderBy('id');
                }
            ])
            ->where('type', 'agency_sale')
            ->findOrFail($id);

        // Get kode transaksi untuk penjualan agency
        $kodeTransaksi = KodeTransaksi::where('transaksi', 'like', '%fee%marketing%')
            ->orWhere('transaksi', 'like', '%komisi%')
            ->orWhere('transaksi', 'like', '%agency%')
            ->get(['id', 'kodetransaksi', 'transaksi']);

        // Get rekening
        $activeCompanyId = session('active_company_id');

        if (!$activeCompanyId) {
            abort(403, 'Company aktif belum dipilih');
        }

        $rekening = Rekening::where('idcompany', $activeCompanyId)
            ->get(['idrek', 'norek', 'namarek', 'saldo']);

        return view('sales.agency.edit', compact(
            'nota', 
            'kodeTransaksi',
            'rekening',
        ));
    }

    /**
     * Update transaksi agency
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'vendor_id' => 'required|exists:vendors,id',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:1',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'subtotal' => 'required|numeric|min:0',
                'old_rekening' => 'nullable|exists:rekening,idrek',
                'old_grand_total' => 'nullable|numeric|min:0',
            ]);

            $nota = Nota::with(['payments', 'cashflows'])->findOrFail($id);
            $oldData = $nota->toArray();
            $oldTotal = $nota->total;
            $oldRekening = $request->old_rekening ?? $nota->idrek;

            // Handle upload bukti nota baru
            $buktiNotaPath = $nota->bukti_nota;
            if ($request->hasFile('bukti_nota')) {
                if ($buktiNotaPath && Storage::disk('public')->exists($buktiNotaPath)) {
                    Storage::disk('public')->delete($buktiNotaPath);
                }
                
                $file = $request->file('bukti_nota');
                $filename = 'agency_sale_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('agency_sales', $filename, 'public');
            }

            // Hitung total
            $subtotal = $request->subtotal;
            $ppn = $request->ppn ?? 0;
            $diskon = $request->diskon ?? 0;
            $newTotal = $subtotal + $ppn - $diskon;

            // Rollback jika transaksi lama adalah CASH dan PAID
            if ($nota->paymen_method == 'cash' && $nota->status == 'paid') {
                $this->rollbackCashPayment($nota);
            }

            // Update data nota
            $updateData = [
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'tanggal' => $request->tanggal,
                'vendor_id' => $request->vendor_id,
                'idrek' => $request->idrek,
                'paymen_method' => $request->paymen_method ?? 'cash',
                'tgl_tempo' => ($request->paymen_method ?? 'cash') == 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'ppn' => $ppn,
                'diskon' => $diskon,
                'total' => $newTotal,
                'status' => ($request->paymen_method ?? 'cash') == 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath,
            ];

            $nota->update($updateData);

            // Hapus semua transaksi lama
            NotaTransaction::where('idnota', $nota->id)->delete();

            // Simpan detail transaksi baru
            foreach ($request->transactions as $transaction) {
                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idkodetransaksi' => $transaction['idkodetransaksi'],
                    'description' => $transaction['description'],
                    'nominal' => $transaction['nominal'],
                    'jml' => $transaction['jml'],
                    'total' => $transaction['nominal'] * $transaction['jml'],
                ]);
            }

            // Simpan PPN jika ada
            if ($ppn > 0) {
                $kodePpn = KodeTransaksi::where('kodetransaksi', $request->ppn_kode ?? '3001')->first();
                if ($kodePpn) {
                    NotaTransaction::create([
                        'idnota' => $nota->id,
                        'idkodetransaksi' => $kodePpn->id,
                        'description' => 'PPN',
                        'nominal' => $ppn,
                        'jml' => 1,
                        'total' => $ppn,
                    ]);
                }
            }

            // Simpan Diskon jika ada
            if ($diskon > 0) {
                $kodeDiskon = KodeTransaksi::where('kodetransaksi', $request->diskon_kode ?? '5001')->first();
                if ($kodeDiskon) {
                    NotaTransaction::create([
                        'idnota' => $nota->id,
                        'idkodetransaksi' => $kodeDiskon->id,
                        'description' => 'Diskon',
                        'nominal' => $diskon,
                        'jml' => 1,
                        'total' => $diskon,
                    ]);
                }
            }

            // Log perubahan
            $newData = array_merge($updateData, [
                'transactions' => $request->transactions
            ]);
            
            $changes = $this->getChangesForLog($oldData, $newData, $nota->id);
            
            if (!empty($changes)) {
                $logMessage = "Transaksi agency diupdate: " . implode(", ", $changes);
                $this->createUpdateLog($nota->id, $nota->nota_no, $logMessage);
            }

            // Proses pembayaran baru jika transaksi baru adalah CASH
            if (($request->paymen_method ?? 'cash') == 'cash') {
                $this->processCashPayment($nota, $request->idrek, $newTotal, $request->tanggal);
            }

            DB::commit();

            return redirect()->route('agency-sales.show', $nota->id)
                ->with('success', 'Transaksi agency sale berhasil diupdate');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Agency sale update error: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Destroy transaksi agency
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $nota = Nota::with(['payments', 'cashflows'])->findOrFail($id);

            // Cek role user
            $user = auth()->user();
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                return redirect()->back()
                    ->with('error', 'Anda tidak memiliki izin untuk menghapus transaksi');
            }

            // Rollback pembayaran jika transaksi cash dan status paid
            if ($nota->paymen_method == 'cash' && $nota->status == 'paid') {
                $this->rollbackCashPayment($nota);
            }

            // Buat log untuk penghapusan
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Transaksi agency sale dihapus - No: {$nota->nota_no}");

            // Hapus file bukti nota jika ada
            if ($nota->bukti_nota) {
                Storage::disk('public')->delete($nota->bukti_nota);
            }

            // Hapus data terkait
            NotaTransaction::where('idnota', $nota->id)->delete();
            NotaPayment::where('idnota', $nota->id)->delete();
            Cashflow::where('idnota', $nota->id)->delete();

            // Hapus nota
            $nota->delete();

            DB::commit();

            return redirect()->route('agency-sales.index')
                ->with('success', 'Transaksi agency sale berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Agency sale delete error: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Get list transaksi agency untuk datatable
     */
    public function getTransactions(Request $request)
    {
        $query = Nota::with([
                'project:id,namaproject',
                'vendor:id,namavendor',
                'unitDetail.unit:id,namaunit,blok',
                'unitDetail.customer:id,nama_lengkap'
            ])
            ->where('type', 'agency_sale');

        // Filter berdasarkan project jika ada
        if ($request->has('project_id') && !empty($request->project_id)) {
            $query->where('idproject', $request->project_id);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                $user = auth()->user();
                $canDelete = $user->hasRole('direktur') || $user->hasRole('keuangan');
                
                $deleteBtn = $canDelete ? 
                    '<button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'"><i class="bi bi-trash"></i></button>' :
                    '<button class="btn btn-sm btn-danger" disabled><i class="bi bi-trash"></i></button>';

                return '<div class="btn-group">
                    <a href="'.route('agency-sales.show', $row->id).'" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                    <a href="'.route('agency-sales.edit', $row->id).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                    '.$deleteBtn.'
                </div>';
            })
            ->editColumn('tanggal', function($row) {
                return date('d/m/Y', strtotime($row->tanggal));
            })
            ->editColumn('total', function($row) {
                return 'Rp ' . number_format($row->total, 0, ',', '.');
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
            ->addColumn('unit_info', function($row) {
                if ($row->unitDetail && $row->unitDetail->unit) {
                    $blok = $row->unitDetail->unit->blok ? ' Blok ' . $row->unitDetail->unit->blok : '';
                    return $row->unitDetail->unit->namaunit . $blok;
                }
                return '-';
            })
            ->addColumn('customer_info', function($row) {
                if ($row->unitDetail && $row->unitDetail->customer) {
                    return $row->unitDetail->customer->nama_lengkap;
                }
                return '-';
            })
            ->rawColumns(['action', 'status'])
            ->toJson();
    }

    /**
     * Helper methods (sama dengan ProjectController)
     */
    private function createUpdateLog($notaId, $notaNo, $logMessage)
    {
        return TransUpdateLog::create([
            'idnota' => $notaId,
            'nota_no' => $notaNo,
            'update_log' => $logMessage
        ]);
    }

    private function getChangesForLog($oldData, $newData, $notaId)
    {
        $changes = [];
        
        $fields = [
            'nota_no' => 'Nomor Nota',
            'namatransaksi' => 'Nama Transaksi',
            'tanggal' => 'Tanggal',
            'vendor_id' => 'Vendor',
            'idrek' => 'Rekening',
            'paymen_method' => 'Payment Method',
            'tgl_tempo' => 'Tanggal Tempo',
            'subtotal' => 'Subtotal',
            'ppn' => 'PPN',
            'diskon' => 'Diskon',
            'total' => 'Total',
            'status' => 'Status'
        ];
        
        foreach ($fields as $field => $label) {
            if (isset($oldData[$field]) && isset($newData[$field])) {
                if ($oldData[$field] != $newData[$field]) {
                    $oldValue = $oldData[$field];
                    $newValue = $newData[$field];
                    
                    if ($field == 'vendor_id') {
                        $oldVendor = Vendor::find($oldValue);
                        $newVendor = Vendor::find($newValue);
                        $oldValue = $oldVendor ? $oldVendor->namavendor : 'Tidak ada';
                        $newValue = $newVendor ? $newVendor->namavendor : 'Tidak ada';
                    } elseif ($field == 'idrek') {
                        $oldRek = Rekening::find($oldValue);
                        $newRek = Rekening::find($newValue);
                        $oldValue = $oldRek ? $oldRek->norek . ' - ' . $oldRek->namarek : 'Tidak ada';
                        $newValue = $newRek ? $newRek->norek . ' - ' . $newRek->namarek : 'Tidak ada';
                    } elseif (in_array($field, ['subtotal', 'ppn', 'diskon', 'total'])) {
                        $oldValue = 'Rp ' . number_format($oldValue, 0, ',', '.');
                        $newValue = 'Rp ' . number_format($newValue, 0, ',', '.');
                    } elseif ($field == 'tanggal' || $field == 'tgl_tempo') {
                        $oldValue = date('d/m/Y', strtotime($oldValue));
                        $newValue = date('d/m/Y', strtotime($newValue));
                    }
                    
                    $changes[] = "{$label} diubah: {$oldValue} → {$newValue}";
                }
            }
        }
        
        if (isset($newData['transactions'])) {
            $changes[] = "Detail transaksi diubah: " . count($newData['transactions']) . " item";
        }
        
        return $changes;
    }

    private function processCashPayment($nota, $idrek, $jumlah, $tanggal)
    {
        try {
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

            NotaPayment::create([
                'idnota' => $nota->id,
                'idrek' => $idrek,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah
            ]);

            Cashflow::create([
                'idrek' => $idrek,
                'idnota' => $nota->id,
                'tanggal' => $tanggal,
                'cashflow' => $nota->cashflow,
                'nominal' => $jumlah,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => "Pembayaran agency sale nota {$nota->nota_no}"
            ]);

            return true;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function rollbackCashPayment($nota)
    {
        try {
            // Rollback saldo rekening
            $rekening = Rekening::find($nota->idrek);
            
            if (!$rekening) {
                throw new \Exception("Rekening dengan ID {$nota->idrek} tidak ditemukan untuk rollback");
            }

            if ($nota->cashflow == 'out') {
                $rekening->saldo += $nota->total;
            } else {
                $rekening->saldo -= $nota->total;
            }
            
            $rekening->save();

            // Hapus payment dan cashflow
            NotaPayment::where('idnota', $nota->id)->delete();
            Cashflow::where('idnota', $nota->id)->delete();

            return true;

        } catch (\Exception $e) {
            throw $e;
        }
    }
}