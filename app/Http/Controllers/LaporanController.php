<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Presensi;
use App\Models\Jadwal;
use App\Models\KelompokJam;
use App\Models\PengajuanIzin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; 
use App\Models\Nota;
use App\Models\NotaTransaction;
use App\Models\NotaPayment;
use App\Models\Cashflow;
use App\Models\TransUpdateLog;

class LaporanController extends Controller
{
    // Halaman utama laporan
    public function rekapAbsensi()
    {
        $bulan = now()->format('m');
        $tahun = now()->format('Y');
        return view('hris.laporan.rekap_absensi', compact('bulan', 'tahun'));
    }

    // Data untuk DataTables (AJAX)
    public function rekapAbsensiData(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));

        $awal = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $akhir = $awal->copy()->endOfMonth();

        $pegawaiList = User::with('unitkerja')
        ->where('status', 'aktif')                // hanya pegawai aktif
        ->whereHas('pegawaiDtl')                  // hanya yang punya detail pegawai
        ->get();
        $data = [];

        foreach ($pegawaiList as $p) {
            $jadwalCollection = Jadwal::where('pegawai_nik', $p->nik)
                ->whereBetween('tgl', [$awal, $akhir])
                ->get()
                ->keyBy('tgl');

            $presensiCollection = Presensi::where('nik', $p->nik)
                ->whereBetween('tgl_presensi', [$awal, $akhir])
                ->get()
                ->groupBy('tgl_presensi');

            $cutiCount = PengajuanIzin::where('nik', $p->nik)
                ->whereMonth('tgl_izin', $bulan)
                ->whereYear('tgl_izin', $tahun)
                ->where('status', 'c')
                ->where('status_approved', 1)
                ->count();

            $jmlAbsensi = 0;
            $totalTerlambatSeconds = 0;
            $totalLemburSeconds = 0;

            $cursor = $awal->copy();
            while ($cursor->lte($akhir)) {
                $tgl = $cursor->format('Y-m-d');
                $jadwalRow = $jadwalCollection->get($tgl);
                $shift = $jadwalRow->shift ?? null;
                $jam = KelompokJam::firstWhere('shift', $shift);
                $jammasuk = $jam->jammasuk ?? null;

                $absensiHari = $presensiCollection->get($tgl) ?? collect();
                $in = optional($absensiHari->firstWhere('inoutmode', 1))->jam_in;

                if ($in) $jmlAbsensi++;

                if ($jammasuk && $in && strtolower($shift) !== 'libur') {
                    $shiftStart = Carbon::parse("$tgl $jammasuk");
                    $inDt = Carbon::parse("$tgl $in");
                    if ($inDt->gt($shiftStart)) {
                        $totalTerlambatSeconds += $shiftStart->diffInSeconds($inDt);
                    }
                }

                $lemburIn = optional($absensiHari->firstWhere('inoutmode', 3))->jam_in;
                $lemburOut = optional($absensiHari->firstWhere('inoutmode', 4))->jam_in;
                if ($lemburIn && $lemburOut) {
                    $inDt = Carbon::parse("$tgl $lemburIn");
                    $outDt = Carbon::parse("$tgl $lemburOut");
                    if ($outDt->lt($inDt)) $outDt->addDay();
                    $totalLemburSeconds += $inDt->diffInSeconds($outDt);
                }

                $cursor->addDay();
            }

            $data[] = [
                'nik' => $p->nik,
                'nama' => $p->name,
                'unitkerja' => optional($p->unitkerja)->company_name ?? '-',
                'jml_absensi' => $jmlAbsensi,
                'lembur' => gmdate('H:i', $totalLemburSeconds),
                'terlambat' => gmdate('H:i', $totalTerlambatSeconds),
                'cuti' => $cutiCount,
                'total' => $jmlAbsensi + $cutiCount
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function exportPayroll(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        if (!$bulan || !$tahun) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter bulan dan tahun wajib diisi.'
            ]);
        }

        // Ambil data rekap absensi
        $rekapData = collect($this->rekapAbsensiData($request)->getData()->data);

        // Ambil semua pegawai aktif
        $pegawaiList = User::where('status', 'aktif')->get();

        if ($pegawaiList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Tidak ada pegawai aktif untuk periode $periode."
            ]);
        }

        // Hapus payroll lama periode yang sama
        DB::table('payroll')->where('periode', $periode)->delete();

        $inserted = 0;
        $debugLog = [];

        foreach ($pegawaiList as $p) {
            // Ambil mastergaji terakhir per NIK
            $gaji = DB::table('mastergaji')
                ->where('nik', $p->nik)
                ->whereNull('deleted_at')
                ->orderByDesc('tgl_aktif')
                ->first();

            if (!$gaji) {
                $debugLog[] = "Mastergaji untuk NIK {$p->nik} tidak ditemukan, dilewati.";
                continue;
            }

            // Ambil data rekap absensi per pegawai
            $rekap = $rekapData->firstWhere('nik', $p->nik);

            $jmlabsen  = $rekap->jml_absensi ?? 0;
            $lembur    = $rekap->lembur ?? '00:00:00';
            $terlambat = $rekap->terlambat ?? '00:00:00';
            $cuti      = $rekap->cuti ?? 0;

            // Hitung total pendapatan
            $totalPendapatan = ($gaji->gajipokok ?? 0)
                            + ($gaji->masakerja ?? 0)
                            + ($gaji->komunikasi ?? 0)
                            + ($gaji->transportasi ?? 0)
                            + ($gaji->konsumsi ?? 0)
                            + ($gaji->tunj_asuransi ?? 0)
                            + ($gaji->jabatan ?? 0)
                            + ($gaji->pek_tambahan ?? 0);

            // Hitung zakat 2.5%
            $zakat = round($totalPendapatan * 0.025, 2);

            // Siapkan data insert payroll
            $data = [
                'periode'       => $periode,
                'nik'           => $p->nik,
                'nama'          => $p->name,
                'jmlabsen'      => $jmlabsen,
                'lembur'        => $lembur,
                'terlambat'     => $terlambat,
                'cuti'          => $cuti,
                'gajipokok'     => round($gaji->gajipokok ?? 0, 2),
                'pek_tambahan'  => round($gaji->pek_tambahan ?? 0, 2),
                'masakerja'     => round($gaji->masakerja ?? 0, 2),
                'komunikasi'    => round($gaji->komunikasi ?? 0, 2),
                'transportasi'  => round($gaji->transportasi ?? 0, 2),
                'konsumsi'      => round($gaji->konsumsi ?? 0, 2),
                'tunj_asuransi' => round($gaji->tunj_asuransi ?? 0, 2),
                'jabatan'       => round($gaji->jabatan ?? 0, 2),
                'cicilan'       => 0,
                'asuransi'      => round($gaji->asuransi ?? 0, 2),
                'zakat'         => $zakat,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            try {
                DB::table('payroll')->insert($data);
                $inserted++;
            } catch (\Exception $e) {
                $debugLog[] = "Gagal insert NIK {$p->nik}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Data payroll periode $periode berhasil diexport. ($inserted pegawai)",
            'log'     => $debugLog,
        ]);
    }

    // === Holidays ===
    protected function getNationalHolidays(string $bulan): array
    {
        try {
            $year = date('Y', strtotime($bulan . '-01'));
            $cacheKey = 'national_holidays_' . $year;

            return cache()->remember($cacheKey, now()->addMonth(), function () use ($year) {
                $response = Http::timeout(5)->get("https://hari-libur-api.vercel.app/api", [
                    'year' => $year
                ]);

                return $response->ok() ? $this->parseHolidayResponse($response->json()) : [];
            });
        } catch (\Exception $e) {
            logger()->error("Libur API error: " . $e->getMessage());
            return [];
        }
    }

    protected function parseHolidayResponse(array $holidays): array
    {
        $result = [];
        foreach ($holidays as $holiday) {
            if (($holiday['is_national_holiday'] ?? false) === true) {
                $result[$holiday['event_date']] = $holiday['event_name'];
            }
        }
        return $result;
    }

    protected function filterHolidaysByMonth(string $bulan): array
    {
        $holidays = $this->getNationalHolidays($bulan);
        $selectedMonth = date('m', strtotime($bulan));

        return array_filter($holidays, function ($key) use ($selectedMonth) {
            return date('m', strtotime($key)) == $selectedMonth;
        }, ARRAY_FILTER_USE_KEY);
    }

    // ==========================
    // LAPORAN PAYROLL
    // ==========================
    public function laporanPayroll()
    {
        $bulan = now()->format('m');
        $tahun = now()->format('Y');
        return view('hris.laporan.laporan_payroll', compact('bulan', 'tahun'));
    }

    public function laporanPayrollData(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));
        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        $data = DB::table('payroll')
            ->where('periode', $periode)
            ->join('users', 'payroll.nik', '=', 'users.nik')
            ->leftJoin('company_units', 'users.id_unitkerja', '=', 'company_units.id')
            ->select(
                'payroll.*','users.nip as nip',
                'users.name as nama',
                'company_units.company_name as unitkerja'
            )
            ->orderBy('users.name')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function monitoringPresensi()
    {
        return view('hris.laporan.monitoring_presensi');
    }

    public function monitoringPresensiData(Request $request)
    {
        $tanggal = $request->tanggal ?? date('Y-m-d');

        $data = DB::table('presensi as p')
            ->select(
                'p.nik',
                'k.nip',
                'k.name',
                'u.company_name',
                DB::raw('MAX(CASE WHEN p.inoutmode = 1 THEN p.jam_in END) as jam_masuk'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 2 THEN p.jam_in END) as jam_pulang'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 1 THEN p.foto_in END) as foto_masuk'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 2 THEN p.foto_in END) as foto_pulang'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 1 THEN p.lokasi END) as lokasi_masuk'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 2 THEN p.lokasi END) as lokasi_pulang')
            )
            ->join('users as k', 'p.nik', '=', 'k.nik')
            ->leftJoin('company_units as u', 'k.id_unitkerja', '=', 'u.id')
            ->where('p.tgl_presensi', $tanggal)
            ->groupBy('k.nip','p.nik', 'k.name', 'u.company_name')
            ->orderBy('k.name')
            ->get();

        return response()->json(['data' => $data]);
    }

    // ==========================
    // LAPORAN CASHFLOW PROJECT
    // ==========================
    public function cashflowProject()
    {
        $startDate = now()->format('Y-m-01');
        $endDate = now()->format('Y-m-t');
        return view('transaksi.laporan.cashflow_project', compact('startDate', 'endDate'));
    }

    public function cashflowProjectData(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-01'));
        $endDate = $request->input('end_date', now()->format('Y-m-t'));
        
        // Validasi tanggal
        if (empty($startDate) || empty($endDate)) {
            return response()->json([
                'data' => [], 
                'total' => [
                    'pemasukan' => 0,
                    'pengeluaran' => 0,
                    'saldo_akhir' => 0
                ]
            ]);
        }
        
        // Query data transaksi Project (idproject tidak null)
        $data = DB::table('notas as n')
            ->select(
                'n.id',
                'np.id as id_payment',
                'n.nota_no',
                'n.tanggal',
                DB::raw('"Transaksi" as kategori'),
                'n.namatransaksi',
                'np.jumlah as jumlah_transaksi',
                DB::raw('CASE WHEN n.cashflow = "in" THEN np.jumlah ELSE 0 END as pemasukan'),
                DB::raw('CASE WHEN n.cashflow = "out" THEN np.jumlah ELSE 0 END as pengeluaran'),
                DB::raw('COALESCE(cf.saldo_akhir, 0) as saldo'), // Ambil langsung dari cashflows
                'v.namavendor',
                'r.namarek as rekening',
                'p.namaproject',
                'n.idproject'
            )
            ->join('nota_payments as np', 'n.id', '=', 'np.idnota')
            ->leftJoin('vendors as v', 'n.vendor_id', '=', 'v.id')
            ->leftJoin('rekening as r', 'np.idrek', '=', 'r.idrek')
            ->leftJoin('projects as p', 'n.idproject', '=', 'p.id')
            ->leftJoin('cashflows as cf', 'n.id', '=', 'cf.idnota') 
            ->where('n.status', 'paid')
            ->where('n.idproject', session('active_project_id'))
            ->whereNotNull('n.idproject') // Hanya yang punya project
            ->whereBetween('n.tanggal', [$startDate, $endDate])
            ->orderBy('n.tanggal', 'asc')
            ->orderBy('n.id', 'asc')
            ->get();

        // TIDAK PERLU hitung saldo running karena sudah diambil langsung dari cashflows
        
        // Hitung total pemasukan dan pengeluaran
        $totals = DB::table('notas as n')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN n.cashflow = "in" THEN np.jumlah ELSE 0 END), 0) as total_pemasukan,
                COALESCE(SUM(CASE WHEN n.cashflow = "out" THEN np.jumlah ELSE 0 END), 0) as total_pengeluaran
            ')
            ->join('nota_payments as np', 'n.id', '=', 'np.idnota')
            ->where('n.status', 'paid')
            ->whereNotNull('n.idproject')
            ->whereBetween('n.tanggal', [$startDate, $endDate])
            ->first();

        // Ambil saldo akhir dari cashflows terakhir yang sesuai filter
        $lastCashflow = DB::table('cashflows as cf')
            ->join('notas as n', 'cf.idnota', '=', 'n.id')
            ->where('n.status', 'paid')
            ->whereNotNull('n.idproject')
            ->whereBetween('n.tanggal', [$startDate, $endDate])
            ->orderBy('cf.tanggal', 'desc')
            ->orderBy('cf.id', 'desc')
            ->select('cf.saldo_akhir')
            ->first();

        $saldoAkhir = $lastCashflow ? $lastCashflow->saldo_akhir : 0;

        // Debug: cek apakah data memiliki saldo dari cashflows
        if ($data->count() > 0) {
            $firstItem = $data->first();
            Log::info('Data pertama Project:', [
                'id' => $firstItem->id,
                'nota_no' => $firstItem->nota_no,
                'pemasukan' => $firstItem->pemasukan,
                'pengeluaran' => $firstItem->pengeluaran,
                'saldo_dari_cashflows' => $firstItem->saldo
            ]);
        }

        Log::info('Cashflow Project Data', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_records' => $data->count(),
            'total_pemasukan' => $totals->total_pemasukan ?? 0,
            'total_pengeluaran' => $totals->total_pengeluaran ?? 0,
            'saldo_akhir' => $saldoAkhir
        ]);

        return response()->json([
            'data' => $data,
            'total' => [
                'pemasukan' => $totals->total_pemasukan ?? 0,
                'pengeluaran' => $totals->total_pengeluaran ?? 0,
                'saldo_akhir' => $saldoAkhir
            ]
        ]);
    }

    public function viewNotaDetail(Request $request)
    {
        try {
            $id = $request->id;
            
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID nota tidak valid'
                ], 400);
            }

            $nota = Nota::with([
                'project:id,namaproject',
                'vendor:id,namavendor',
                'rekening:idrek,norek,namarek',
                'transactions' => function($q) {
                    $q->with('kodeTransaksi:id,kodetransaksi,transaksi')
                    ->orderBy('id');
                },
                'payments' => function($q) {
                    $q->with('rekening:idrek,norek,namarek')
                    ->orderBy('tanggal');
                },
                'updateLogs' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])->find($id); // Gunakan find() bukan findOrFail()

            if (!$nota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota dengan ID ' . $id . ' tidak ditemukan'
                ], 404);
            }

            // Format data untuk response
            $formattedData = [
                'nota' => [
                    'id' => $nota->id,
                    'nota_no' => $nota->nota_no,
                    'tanggal' => date('d/m/Y', strtotime($nota->tanggal)),
                    'namatransaksi' => $nota->namatransaksi,
                    'status' => $nota->status,
                    'paymen_method' => $nota->paymen_method == 'cash' ? 'Cash' : 'Tempo',
                    'tgl_tempo' => $nota->tgl_tempo ? date('d/m/Y', strtotime($nota->tgl_tempo)) : '-',
                    'subtotal' => number_format($nota->subtotal, 0, ',', '.'),
                    'ppn' => number_format($nota->ppn, 0, ',', '.'),
                    'diskon' => number_format($nota->diskon, 0, ',', '.'),
                    'total' => number_format($nota->total, 0, ',', '.'),
                    'vendor' => $nota->vendor ? $nota->vendor->namavendor : '-',
                    'rekening' => $nota->rekening ? $nota->rekening->norek . ' - ' . $nota->rekening->namarek : '-',
                    'project' => $nota->project ? $nota->project->namaproject : '-',
                    'namauser' => $nota->namauser,
                    'created_at' => date('d/m/Y H:i', strtotime($nota->created_at)),
                    'cashflow' => $nota->cashflow == 'in' ? 'Pemasukan' : 'Pengeluaran',
                ],
                'items' => [],
                'payments' => [],
                'logs' => []
            ];

            // Format items
            foreach ($nota->transactions as $item) {
                $formattedData['items'][] = [
                    'kodetransaksi' => $item->kodeTransaksi ? $item->kodeTransaksi->kodetransaksi : '-',
                    'namatransaksi' => $item->kodeTransaksi ? $item->kodeTransaksi->namatransaksi : '-',
                    'description' => $item->description,
                    'nominal' => number_format($item->nominal, 0, ',', '.'),
                    'jml' => number_format($item->jml, 0, ',', '.'),
                    'total' => number_format($item->total, 0, ',', '.'),
                ];
            }

            // Format payments
            foreach ($nota->payments as $payment) {
                $formattedData['payments'][] = [
                    'tanggal' => date('d/m/Y', strtotime($payment->tanggal)),
                    'rekening' => $payment->rekening ? $payment->rekening->norek . ' - ' . $payment->rekening->namarek : '-',
                    'jumlah' => number_format($payment->jumlah, 0, ',', '.'),
                ];
            }

            // Format logs
            foreach ($nota->updateLogs as $log) {
                $formattedData['logs'][] = [
                    'tanggal' => date('d/m/Y H:i', strtotime($log->created_at)),
                    'keterangan' => $log->update_log,
                ];
            }

            Log::info('Detail nota berhasil diambil', ['nota_id' => $id]);

            return response()->json([
                'success' => true,
                'data' => $formattedData
            ]);

        } catch (\Exception $e) {
            Log::error('Error viewing nota detail:', [
                'error' => $e->getMessage(),
                'nota_id' => $request->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail nota: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==========================
    // LAPORAN CASHFLOW PT
    // ==========================
    public function cashflowPT()
    {
        $startDate = now()->format('Y-m-01');
        $endDate = now()->format('Y-m-t');
        return view('transaksi.laporan.cashflow_pt', compact('startDate', 'endDate'));
    }

    public function cashflowPTData(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-01'));
        $endDate = $request->input('end_date', now()->format('Y-m-t'));
        
        // Validasi tanggal
        if (empty($startDate) || empty($endDate)) {
            return response()->json([
                'data' => [], 
                'total' => [
                    'pemasukan' => 0,
                    'pengeluaran' => 0,
                    'saldo_akhir' => 0
                ]
            ]);
        }
        
        // Query data transaksi PT (idcompany tidak null, idproject null)
        $data = DB::table('notas as n')
            ->select(
                'n.id',
                'np.id as id_payment',
                'n.nota_no',
                'n.tanggal',
                DB::raw('"Transaksi" as kategori'),
                'n.namatransaksi',
                'np.jumlah as jumlah_transaksi',
                DB::raw('CASE WHEN n.cashflow = "in" THEN np.jumlah ELSE 0 END as pemasukan'),
                DB::raw('CASE WHEN n.cashflow = "out" THEN np.jumlah ELSE 0 END as pengeluaran'),
                DB::raw('COALESCE(cf.saldo_akhir, 0) as saldo'), // Ambil langsung dari cashflows
                'v.namavendor',
                'r.namarek as rekening',
                'cu.company_name as nama_company',
                'n.idcompany'
            )
            ->join('nota_payments as np', 'n.id', '=', 'np.idnota')
            ->leftJoin('vendors as v', 'n.vendor_id', '=', 'v.id')
            ->leftJoin('rekening as r', 'np.idrek', '=', 'r.idrek')
            ->leftJoin('company_units as cu', 'n.idcompany', '=', 'cu.id')
            ->leftJoin('cashflows as cf', 'n.id', '=', 'cf.idnota') // Join langsung berdasarkan idnota
            ->where('n.status', 'paid')
            ->whereNotNull('n.idcompany') // Hanya yang punya company (PT)
            ->whereNull('n.idproject')    // idproject harus null untuk PT
            ->whereBetween('n.tanggal', [$startDate, $endDate])
            ->orderBy('n.tanggal', 'asc')
            ->orderBy('n.id', 'asc')
            ->get();

        // TIDAK PERLU hitung saldo running karena sudah diambil langsung dari cashflows
        
        // Hitung total pemasukan dan pengeluaran
        $totals = DB::table('notas as n')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN n.cashflow = "in" THEN np.jumlah ELSE 0 END), 0) as total_pemasukan,
                COALESCE(SUM(CASE WHEN n.cashflow = "out" THEN np.jumlah ELSE 0 END), 0) as total_pengeluaran
            ')
            ->join('nota_payments as np', 'n.id', '=', 'np.idnota')
            ->where('n.status', 'paid')
            ->whereNotNull('n.idcompany')
            ->whereNull('n.idproject') // Tambahkan kondisi ini
            ->whereBetween('n.tanggal', [$startDate, $endDate])
            ->first();

        // Ambil saldo akhir dari cashflows terakhir yang sesuai filter
        $lastCashflow = DB::table('cashflows as cf')
            ->join('notas as n', 'cf.idnota', '=', 'n.id')
            ->where('n.status', 'paid')
            ->whereNotNull('n.idcompany')
            ->whereNull('n.idproject') // Tambahkan kondisi ini
            ->whereBetween('n.tanggal', [$startDate, $endDate])
            ->orderBy('cf.tanggal', 'desc')
            ->orderBy('cf.id', 'desc')
            ->select('cf.saldo_akhir')
            ->first();

        $saldoAkhir = $lastCashflow ? $lastCashflow->saldo_akhir : 0;

        // Debug: cek apakah data memiliki saldo dari cashflows
        if ($data->count() > 0) {
            $firstItem = $data->first();
            Log::info('Data pertama PT:', [
                'id' => $firstItem->id,
                'nota_no' => $firstItem->nota_no,
                'pemasukan' => $firstItem->pemasukan,
                'pengeluaran' => $firstItem->pengeluaran,
                'saldo_dari_cashflows' => $firstItem->saldo
            ]);
        }

        Log::info('Cashflow PT Data', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_records' => $data->count(),
            'total_pemasukan' => $totals->total_pemasukan ?? 0,
            'total_pengeluaran' => $totals->total_pengeluaran ?? 0,
            'saldo_akhir' => $saldoAkhir,
            'filter_conditions' => [
                'status' => 'paid',
                'idcompany_not_null' => true,
                'idproject_null' => true
            ]
        ]);

        return response()->json([
            'data' => $data,
            'total' => [
                'pemasukan' => $totals->total_pemasukan ?? 0,
                'pengeluaran' => $totals->total_pengeluaran ?? 0,
                'saldo_akhir' => $saldoAkhir
            ]
        ]);
    }

    // Fungsi view detail untuk PT (menggunakan fungsi yang sama dengan project)
    public function viewNotaDetailPT(Request $request)
    {
        try {
            $id = $request->id;
            
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID nota tidak valid'
                ], 400);
            }

            $nota = Nota::with([
                'companyUnit:id,company_name', // Relasi ke company unit untuk PT
                'vendor:id,namavendor',
                'rekening:idrek,norek,namarek',
                'transactions' => function($q) {
                    $q->with('kodeTransaksi:id,kodetransaksi,namatransaksi')
                    ->orderBy('id');
                },
                'payments' => function($q) {
                    $q->with('rekening:idrek,norek,namarek')
                    ->orderBy('tanggal');
                },
                'updateLogs' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])->find($id);

            if (!$nota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota dengan ID ' . $id . ' tidak ditemukan'
                ], 404);
            }

            // Tentukan apakah ini nota PT atau Project
            $isPT = !is_null($nota->idcompany) && is_null($nota->idproject);
            $isProject = !is_null($nota->idproject);

            Log::info('Detail nota ditemukan', [
                'nota_id' => $id,
                'nota_no' => $nota->nota_no,
                'idcompany' => $nota->idcompany,
                'idproject' => $nota->idproject,
                'is_pt' => $isPT,
                'is_project' => $isProject
            ]);

            // Format data untuk response
            $formattedData = [
                'nota' => [
                    'id' => $nota->id,
                    'nota_no' => $nota->nota_no,
                    'tanggal' => date('d/m/Y', strtotime($nota->tanggal)),
                    'namatransaksi' => $nota->namatransaksi,
                    'status' => $nota->status,
                    'paymen_method' => $nota->paymen_method == 'cash' ? 'Cash' : 'Tempo',
                    'tgl_tempo' => $nota->tgl_tempo ? date('d/m/Y', strtotime($nota->tgl_tempo)) : '-',
                    'subtotal' => number_format($nota->subtotal, 0, ',', '.'),
                    'ppn' => number_format($nota->ppn, 0, ',', '.'),
                    'diskon' => number_format($nota->diskon, 0, ',', '.'),
                    'total' => number_format($nota->total, 0, ',', '.'),
                    'vendor' => $nota->vendor ? $nota->vendor->namavendor : '-',
                    'rekening' => $nota->rekening ? $nota->rekening->norek . ' - ' . $nota->rekening->namarek : '-',
                    'company' => $nota->companyUnit ? $nota->companyUnit->company_name : '-',
                    'project' => $nota->project ? $nota->project->namaproject : '-',
                    'type' => $isPT ? 'PT' : ($isProject ? 'Project' : 'Unknown'),
                    'namauser' => $nota->namauser,
                    'created_at' => date('d/m/Y H:i', strtotime($nota->created_at)),
                    'cashflow' => $nota->cashflow == 'in' ? 'Pemasukan' : 'Pengeluaran',
                ],
                'items' => [],
                'payments' => [],
                'logs' => []
            ];

            // Format items
            foreach ($nota->transactions as $item) {
                $formattedData['items'][] = [
                    'kodetransaksi' => $item->kodeTransaksi ? $item->kodeTransaksi->kodetransaksi : '-',
                    'namatransaksi' => $item->kodeTransaksi ? $item->kodeTransaksi->namatransaksi : '-',
                    'description' => $item->description,
                    'nominal' => number_format($item->nominal, 0, ',', '.'),
                    'jml' => number_format($item->jml, 0, ',', '.'),
                    'total' => number_format($item->total, 0, ',', '.'),
                ];
            }

            // Format payments
            foreach ($nota->payments as $payment) {
                $formattedData['payments'][] = [
                    'tanggal' => date('d/m/Y', strtotime($payment->tanggal)),
                    'rekening' => $payment->rekening ? $payment->rekening->norek . ' - ' . $payment->rekening->namarek : '-',
                    'jumlah' => number_format($payment->jumlah, 0, ',', '.'),
                ];
            }

            // Format logs
            foreach ($nota->updateLogs as $log) {
                $formattedData['logs'][] = [
                    'tanggal' => date('d/m/Y H:i', strtotime($log->created_at)),
                    'keterangan' => $log->update_log,
                ];
            }

            Log::info('Detail nota berhasil diambil', [
                'nota_id' => $id,
                'type' => $isPT ? 'PT' : 'Project',
                'total_items' => count($formattedData['items']),
                'total_payments' => count($formattedData['payments'])
            ]);

            return response()->json([
                'success' => true,
                'data' => $formattedData
            ]);

        } catch (\Exception $e) {
            Log::error('Error viewing nota detail:', [
                'error' => $e->getMessage(),
                'nota_id' => $request->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail nota: ' . $e->getMessage()
            ], 500);
        }
    }
}
