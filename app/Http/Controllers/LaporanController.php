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
use App\Models\Booking;
use App\Models\Penjualan;
use App\Models\UnitDetail;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Unit;
use App\Models\KodeTransaksi;
use Yajra\DataTables\Facades\DataTables;


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
                    'namatransaksi' => $item->kodeTransaksi ? $item->kodeTransaksi->transaksi : '-',
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
        $endDate   = now()->format('Y-m-t');

        return view('transaksi.laporan.cashflow_pt', compact('startDate', 'endDate'));
    }

    public function cashflowPTData(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-01'));
        $endDate   = $request->input('end_date', now()->format('Y-m-t'));

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

        /**
         * ==================================================
         * 1. TRANSAKSI NOTA (PT)
         * ==================================================
         */
        $notaQuery = DB::table('notas as n')
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
                DB::raw('COALESCE(cf.saldo_akhir, 0) as saldo'),
                'v.namavendor',
                'r.namarek as rekening',
                'cu.company_name as nama_company',
                'n.idcompany'
            )
            ->join('nota_payments as np', 'n.id', '=', 'np.idnota')
            ->leftJoin('vendors as v', 'n.vendor_id', '=', 'v.id')
            ->leftJoin('rekening as r', 'np.idrek', '=', 'r.idrek')
            ->leftJoin('company_units as cu', 'n.idcompany', '=', 'cu.id')
            ->leftJoin('cashflows as cf', 'n.id', '=', 'cf.idnota')
            ->where('n.status', 'paid')
            ->whereNotNull('n.idcompany')
            ->whereNull('n.idproject')
            ->whereBetween('n.tanggal', [$startDate, $endDate]);

        /**
         * ==================================================
         * 2. PINDAH BUKU - REKENING ASAL (OUT)
         * ==================================================
         */
        $pbkOut = DB::table('transaksi_pindah_buku as pbk')
            ->select(
                DB::raw('pbk.id * -1 as id'),
                DB::raw('NULL as id_payment'),
                'pbk.kode_transaksi as nota_no',
                'pbk.tanggal',
                DB::raw('"Pindah Buku" as kategori'),
                'pbk.keterangan as namatransaksi',
                'pbk.nominal as jumlah_transaksi',
                DB::raw('0 as pemasukan'),
                DB::raw('pbk.nominal as pengeluaran'),
                DB::raw('0 as saldo'),
                DB::raw('NULL as namavendor'),
                'r_asal.namarek as rekening',
                'cu.company_name as nama_company',
                'pbk.idcompany'
            )
            ->join('rekening as r_asal', 'pbk.rekening_asal_id', '=', 'r_asal.idrek')
            ->join('company_units as cu', 'pbk.idcompany', '=', 'cu.id')
            ->where('pbk.status', 'completed')
            ->whereNotNull('pbk.idcompany')
         
            ->whereBetween('pbk.tanggal', [$startDate, $endDate]);

        /**
         * ==================================================
         * 3. PINDAH BUKU - REKENING TUJUAN (IN)
         * ==================================================
         */
        $pbkIn = DB::table('transaksi_pindah_buku as pbk')
            ->select(
                DB::raw('pbk.id * -1 as id'),
                DB::raw('NULL as id_payment'),
                'pbk.kode_transaksi as nota_no',
                'pbk.tanggal',
                DB::raw('"Pindah Buku" as kategori'),
                'pbk.keterangan as namatransaksi',
                'pbk.nominal as jumlah_transaksi',
                DB::raw('pbk.nominal as pemasukan'),
                DB::raw('0 as pengeluaran'),
                DB::raw('0 as saldo'),
                DB::raw('NULL as namavendor'),
                'r_tujuan.namarek as rekening',
                'cu.company_name as nama_company',
                'pbk.idcompany'
            )
            ->join('rekening as r_tujuan', 'pbk.rekening_tujuan_id', '=', 'r_tujuan.idrek')
            ->join('company_units as cu', 'pbk.idcompany', '=', 'cu.id')
            ->where('pbk.status', 'completed')
            ->whereNotNull('pbk.idcompany')
            ->whereBetween('pbk.tanggal', [$startDate, $endDate]);

        /**
         * ==================================================
         * 4. UNION & SORTING
         * ==================================================
         */
        $data = $notaQuery
            ->unionAll($pbkOut)
            ->unionAll($pbkIn)
            ->orderBy('tanggal', 'asc')
            ->orderBy('nota_no', 'asc')
            ->get();

        /**
         * ==================================================
         * 5. TOTAL (PBK TIDAK DIHITUNG)
         * ==================================================
         */
        $totals = DB::table('notas as n')
            ->join('nota_payments as np', 'n.id', '=', 'np.idnota')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN n.cashflow = "in" THEN np.jumlah ELSE 0 END),0) as total_pemasukan,
                COALESCE(SUM(CASE WHEN n.cashflow = "out" THEN np.jumlah ELSE 0 END),0) as total_pengeluaran
            ')
            ->where('n.status', 'paid')
            ->whereNotNull('n.idcompany')
            ->whereNull('n.idproject')
            ->whereBetween('n.tanggal', [$startDate, $endDate])
            ->first();

        /**
         * ==================================================
         * 6. SALDO AKHIR
         * ==================================================
         */
        $lastCashflow = DB::table('cashflows as cf')
            ->join('notas as n', 'cf.idnota', '=', 'n.id')
            ->where('n.status', 'paid')
            ->whereNotNull('n.idcompany')
            ->whereNull('n.idproject')
            ->whereBetween('n.tanggal', [$startDate, $endDate])
            ->orderBy('cf.tanggal', 'desc')
            ->orderBy('cf.id', 'desc')
            ->select('cf.saldo_akhir')
            ->first();

        return response()->json([
            'data' => $data,
            'total' => [
                'pemasukan'   => $totals->total_pemasukan ?? 0,
                'pengeluaran' => $totals->total_pengeluaran ?? 0,
                'saldo_akhir' => $lastCashflow->saldo_akhir ?? 0
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
                    'namatransaksi' => $item->kodeTransaksi ? $item->kodeTransaksi->transaksi : '-',
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

    /**
     * Display laporan bookings
     */
    public function bookings(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataBookings($request);
        }
        
        $projects = Project::all();
        $statuses = ['active', 'canceled', 'expired', 'completed'];
        
        return view('laporan.bookings', compact('projects', 'statuses'));
    }
    
    /**
     * Get data for bookings report
     */
    private function getDataBookings(Request $request)
    {
        $query = Booking::with([
            'unitDetail.unit.project',
            'customer',
            'createdBy'
        ]);
        
        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal_booking', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal_booking', '<=', $request->end_date);
        }
        
        if ($request->filled('project_id')) {
            $query->whereHas('unitDetail.unit', function($q) use ($request) {
                $q->where('idproject', $request->project_id);
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status_booking', $request->status);
        }
        
        return DataTables::of($query) // PERBAIKAN: HAPUS \App\Models\
            ->addIndexColumn()
            ->addColumn('project_name', function($row) {
                return $row->unitDetail->unit->project->namaproject ?? '-';
            })
            ->addColumn('unit_name', function($row) {
                return $row->unitDetail->unit->namaunit ?? '-';
            })
            ->addColumn('customer_name', function($row) {
                return $row->customer->nama_lengkap ?? '-';
            })
            ->addColumn('customer_nik', function($row) {
                return $row->customer->nik ?? '-';
            })
            ->addColumn('customer_hp', function($row) {
                return $row->customer->no_hp ?? '-';
            })
            ->addColumn('dp_formatted', function($row) {
                return 'Rp ' . number_format($row->dp_awal, 0, ',', '.');
            })
            ->addColumn('tanggal_booking_formatted', function($row) {
                return $row->tanggal_booking ? date('d/m/Y', strtotime($row->tanggal_booking)) : '-';
            })
            ->addColumn('tanggal_jatuh_tempo_formatted', function($row) {
                return $row->tanggal_jatuh_tempo ? date('d/m/Y', strtotime($row->tanggal_jatuh_tempo)) : '-';
            })
            ->addColumn('status_badge', function($row) {
                $badgeClass = [
                    'active' => 'bg-success',
                    'canceled' => 'bg-danger',
                    'expired' => 'bg-warning',
                    'completed' => 'bg-info'
                ][$row->status_booking] ?? 'bg-secondary';
                
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($row->status_booking) . '</span>';
            })
            ->addColumn('created_by_name', function($row) {
                return $row->createdBy->name ?? '-';
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }
    
    /**
     * Export bookings to PDF
     */
    public function exportBookingsPDF(Request $request)
    {
        $query = Booking::with([
            'unitDetail.unit.project',
            'customer',
            'createdBy'
        ]);
        
        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal_booking', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal_booking', '<=', $request->end_date);
        }
        
        if ($request->filled('project_id')) {
            $query->whereHas('unitDetail.unit', function($q) use ($request) {
                $q->where('idproject', $request->project_id);
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status_booking', $request->status);
        }
        
        $bookings = $query->get();
        
        // Calculate totals
        $totalDp = $bookings->sum('dp_awal');
        $totalBookings = $bookings->count();
        
        $data = [
            'bookings' => $bookings,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_dp' => $totalDp,
            'total_bookings' => $totalBookings,
            'filter_project' => $request->project_id ? Project::find($request->project_id)->namaproject ?? 'Semua' : 'Semua',
            'filter_status' => $request->status ? ucfirst($request->status) : 'Semua'
        ];
        
        $pdf = PDF::loadView('laporan.pdf.bookings', $data);
        
        $filename = 'laporan-bookings-' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
    
    /**
     * Display laporan penjualan
     */
    public function penjualan(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataPenjualan($request);
        }
        
        $projects = Project::all();
        
        return view('laporan.penjualan', compact('projects'));
    }
    
    /**
     * Get data for penjualan report
     */
    private function getDataPenjualan(Request $request)
    {
        $query = Penjualan::with([
            'unitDetail.unit.project',
            'customer'
        ]);
        
        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal_akad', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal_akad', '<=', $request->end_date);
        }
        
        if ($request->filled('project_id')) {
            $query->whereHas('unitDetail.unit', function($q) use ($request) {
                $q->where('idproject', $request->project_id);
            });
        }
        
        if ($request->filled('metode_pembayaran')) {
            $query->where('metode_pembayaran', $request->metode_pembayaran);
        }
        
        return DataTables::of($query) // PERBAIKAN: HAPUS \App\Models\
            ->addIndexColumn()
            ->addColumn('project_name', function($row) {
                return $row->unitDetail->unit->project->namaproject ?? '-';
            })
            ->addColumn('unit_name', function($row) {
                return $row->unitDetail->unit->namaunit ?? '-';
            })
            ->addColumn('customer_name', function($row) {
                return $row->customer->nama_lengkap ?? '-';
            })
            ->addColumn('customer_nik', function($row) {
                return $row->customer->nik ?? '-';
            })
            ->addColumn('harga_jual_formatted', function($row) {
                return 'Rp ' . number_format($row->harga_jual, 0, ',', '.');
            })
            ->addColumn('dp_awal_formatted', function($row) {
                return $row->dp_awal ? 'Rp ' . number_format($row->dp_awal, 0, ',', '.') : '-';
            })
            ->addColumn('tanggal_akad_formatted', function($row) {
                return $row->tanggal_akad ? date('d/m/Y', strtotime($row->tanggal_akad)) : '-';
            })
            ->addColumn('metode_badge', function($row) {
                $badgeClass = [
                    'cash' => 'bg-success',
                    'kredit' => 'bg-primary'
                ][$row->metode_pembayaran] ?? 'bg-secondary';
                
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($row->metode_pembayaran) . '</span>';
            })
            ->addColumn('kredit_info', function($row) {
                if ($row->metode_pembayaran === 'kredit') {
                    return $row->bank_kredit . ' (' . $row->tenor_kredit . ' bulan)';
                }
                return '-';
            })
            ->rawColumns(['metode_badge'])
            ->make(true);
    }
    
    /**
     * Export penjualan to PDF
     */
    public function exportPenjualanPDF(Request $request)
    {
        $query = Penjualan::with([
            'unitDetail.unit.project',
            'customer'
        ]);
        
        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal_akad', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal_akad', '<=', $request->end_date);
        }
        
        if ($request->filled('project_id')) {
            $query->whereHas('unitDetail.unit', function($q) use ($request) {
                $q->where('idproject', $request->project_id);
            });
        }
        
        if ($request->filled('metode_pembayaran')) {
            $query->where('metode_pembayaran', $request->metode_pembayaran);
        }
        
        $penjualans = $query->get();
        
        // Calculate totals
        $totalHargaJual = $penjualans->sum('harga_jual');
        $totalDp = $penjualans->sum('dp_awal');
        $totalPenjualan = $penjualans->count();
        
        $data = [
            'penjualans' => $penjualans,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_harga_jual' => $totalHargaJual,
            'total_dp' => $totalDp,
            'total_penjualan' => $totalPenjualan,
            'filter_project' => $request->project_id ? Project::find($request->project_id)->namaproject ?? 'Semua' : 'Semua',
            'filter_metode' => $request->metode_pembayaran ? ucfirst($request->metode_pembayaran) : 'Semua'
        ];
        
        $pdf = PDF::loadView('laporan.pdf.penjualan', $data);
        
        $filename = 'laporan-penjualan-' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
    
    /**
     * Get statistics for dashboard
     */
    public function getStatistics(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-t');
        
        // Bookings statistics
        $bookingsQuery = Booking::whereBetween('tanggal_booking', [$startDate, $endDate]);
        
        if ($request->filled('project_id')) {
            $bookingsQuery->whereHas('unitDetail.unit', function($q) use ($request) {
                $q->where('idproject', $request->project_id);
            });
        }
        
        $totalBookings = $bookingsQuery->count();
        $totalDpBookings = $bookingsQuery->sum('dp_awal');
        
        $activeBookings = $bookingsQuery->where('status_booking', 'active')->count();
        $canceledBookings = $bookingsQuery->where('status_booking', 'canceled')->count();
        $completedBookings = $bookingsQuery->where('status_booking', 'completed')->count();
        
        // Penjualan statistics
        $penjualanQuery = Penjualan::whereBetween('tanggal_akad', [$startDate, $endDate]);
        
        if ($request->filled('project_id')) {
            $penjualanQuery->whereHas('unitDetail.unit', function($q) use ($request) {
                $q->where('idproject', $request->project_id);
            });
        }
        
        $totalPenjualan = $penjualanQuery->count();
        $totalHargaJual = $penjualanQuery->sum('harga_jual');
        $totalDpPenjualan = $penjualanQuery->sum('dp_awal');
        
        $cashSales = $penjualanQuery->where('metode_pembayaran', 'cash')->count();
        $creditSales = $penjualanQuery->where('metode_pembayaran', 'kredit')->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'bookings' => [
                    'total' => $totalBookings,
                    'total_dp' => $totalDpBookings,
                    'active' => $activeBookings,
                    'canceled' => $canceledBookings,
                    'completed' => $completedBookings,
                ],
                'penjualan' => [
                    'total' => $totalPenjualan,
                    'total_harga_jual' => $totalHargaJual,
                    'total_dp' => $totalDpPenjualan,
                    'cash' => $cashSales,
                    'credit' => $creditSales,
                ]
            ]
        ]);
    }

    public function neracaSaldo()
    {
        $startDate = now()->startOfMonth()->format('Y-m-d');
        $endDate = now()->endOfMonth()->format('Y-m-d');
        $module = session('active_project_module');
        
        return view('transaksi.laporan.neraca_saldo', compact('startDate', 'endDate', 'module'));
    }

    /**
     * Get data Neraca Saldo (Trial Balance)
     */
    public function neracaSaldoData(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $module = $request->input('module', session('active_project_module'));
        
        // Validasi input
        if (empty($startDate) || empty($endDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal awal dan akhir harus diisi'
            ], 400);
        }

        try {
            if ($module == 'project') {
                $data = $this->getNeracaSaldoProject($startDate, $endDate);
            } elseif ($module == 'company') {
                $data = $this->getNeracaSaldoCompany($startDate, $endDate);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Module tidak dikenali'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $data['accounts'],
                'summary' => $data['summary'],
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'module' => $module
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating neraca saldo:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Neraca Saldo untuk Project
     */
    private function getNeracaSaldoProject($startDate, $endDate)
    {
        $projectId = session('active_project_id');
        
        if (!$projectId) {
            throw new \Exception('Project ID tidak ditemukan');
        }

        // Ambil semua kode transaksi (COA)
        $coaList = KodeTransaksi::orderBy('kodetransaksi')->get();

        $accounts = [];
        $totalDebit = 0;
        $totalKredit = 0;

        foreach ($coaList as $coa) {
            // Hitung total debit dan kredit per COA
            $transactions = NotaTransaction::select([
                    'nota_transactions.total',
                    'notas.cashflow',
                    'kodetransaksi.kodetransaksi',
                    DB::raw('CASE 
                        WHEN (kodetransaksi.transaksi = "pendapatan" OR kodetransaksi.kodetransaksi LIKE "4%") 
                        AND notas.cashflow = "in" THEN "kredit"
                        WHEN (kodetransaksi.transaksi = "pendapatan" OR kodetransaksi.kodetransaksi LIKE "4%") 
                        AND notas.cashflow = "out" THEN "debit"
                        WHEN (kodetransaksi.transaksi = "beban" OR kodetransaksi.kodetransaksi LIKE "5%") 
                        AND notas.cashflow = "out" THEN "debit"
                        WHEN (kodetransaksi.transaksi = "beban" OR kodetransaksi.kodetransaksi LIKE "5%") 
                        AND notas.cashflow = "in" THEN "kredit"
                        WHEN (kodetransaksi.kodetransaksi LIKE "1%" OR kodetransaksi.kodetransaksi LIKE "2%") 
                        AND notas.cashflow = "in" THEN "debit"
                        WHEN (kodetransaksi.kodetransaksi LIKE "1%" OR kodetransaksi.kodetransaksi LIKE "2%") 
                        AND notas.cashflow = "out" THEN "kredit"
                        WHEN (kodetransaksi.kodetransaksi LIKE "3%") 
                        AND notas.cashflow = "out" THEN "debit"
                        WHEN (kodetransaksi.kodetransaksi LIKE "3%") 
                        AND notas.cashflow = "in" THEN "kredit"
                        ELSE "debit"
                    END as position')
                ])
                ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
                ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
                ->where('notas.idproject', $projectId)
                ->where('notas.status', 'paid')
                ->whereBetween('notas.tanggal', [$startDate, $endDate])
                ->where('nota_transactions.idkodetransaksi', $coa->id)
                ->get();

            $debit = 0;
            $kredit = 0;

            foreach ($transactions as $trans) {
                if ($trans->position == 'debit') {
                    $debit += $trans->total;
                } else {
                    $kredit += $trans->total;
                }
            }

            // Hanya tampilkan akun yang memiliki transaksi
            if ($debit > 0 || $kredit > 0) {
                $accounts[] = [
                    'kode' => $coa->kodetransaksi,
                    'nama_akun' => $coa->transaksi,
                    'jenis' => $coa->transaksi ?? 'lainnya',
                    'debit' => number_format($debit, 0, ',', '.'),
                    'kredit' => number_format($kredit, 0, ',', '.'),
                    'debit_raw' => $debit,
                    'kredit_raw' => $kredit
                ];

                $totalDebit += $debit;
                $totalKredit += $kredit;
            }
        }

        // Tambahkan saldo rekening (Aset)
        $rekenings = $this->getSaldoRekeningProject($projectId);
        
        foreach ($rekenings as $rekening) {
            if ($rekening['saldo_raw'] != 0) {
                $position = $rekening['saldo_raw'] > 0 ? 'debit' : 'kredit';
                $debitAmount = $position == 'debit' ? abs($rekening['saldo_raw']) : 0;
                $kreditAmount = $position == 'kredit' ? abs($rekening['saldo_raw']) : 0;
                
                $accounts[] = [
                    'kode' => '1' . str_pad($rekening['id'], 3, '0', STR_PAD_LEFT),
                    'nama_akun' => 'Kas/Bank - ' . $rekening['nama'],
                    'jenis' => 'aset',
                    'debit' => number_format($debitAmount, 0, ',', '.'),
                    'kredit' => number_format($kreditAmount, 0, ',', '.'),
                    'debit_raw' => $debitAmount,
                    'kredit_raw' => $kreditAmount,
                    'is_rekening' => true
                ];

                $totalDebit += $debitAmount;
                $totalKredit += $kreditAmount;
            }
        }

        // Urutkan berdasarkan kode akun
        usort($accounts, function($a, $b) {
            return strcmp($a['kode'], $b['kode']);
        });

        return [
            'accounts' => $accounts,
            'summary' => [
                'total_debit' => number_format($totalDebit, 0, ',', '.'),
                'total_kredit' => number_format($totalKredit, 0, ',', '.'),
                'total_debit_raw' => $totalDebit,
                'total_kredit_raw' => $totalKredit,
                'balance' => $totalDebit == $totalKredit,
                'difference' => number_format(abs($totalDebit - $totalKredit), 0, ',', '.'),
                'total_accounts' => count($accounts)
            ]
        ];
    }

    /**
     * Get saldo rekening untuk project
     */
    private function getSaldoRekeningProject($projectId)
    {
        $companyId = session('active_company_id');
        
        $rekenings = DB::table('rekening')
            ->select([
                'rekening.idrek as id',
                'rekening.norek',
                'rekening.namarek as nama',
                'rekening.saldo',
                DB::raw('CASE 
                    WHEN rekening.idproject IS NOT NULL THEN "project" 
                    ELSE "company" 
                END as rekening_type')
            ])
            ->where(function($query) use ($projectId, $companyId) {
                $query->where('rekening.idproject', $projectId)
                      ->orWhere(function($q) use ($companyId) {
                          $q->whereNull('rekening.idproject')
                            ->where('rekening.idcompany', $companyId);
                      });
            })
            ->orderBy('rekening_type', 'desc')
            ->orderBy('rekening.namarek')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'norek' => $item->norek,
                    'nama' => $item->nama,
                    'saldo' => number_format($item->saldo, 0, ',', '.'),
                    'saldo_raw' => $item->saldo,
                    'type' => $item->rekening_type
                ];
            })
            ->toArray();

        return $rekenings;
    }

    /**
     * Get Neraca Saldo untuk Company (PT)
     */
    private function getNeracaSaldoCompany($startDate, $endDate)
    {
        $companyId = session('active_company_id');
        
        if (!$companyId) {
            throw new \Exception('Company ID tidak ditemukan');
        }

        // Ambil semua kode transaksi (COA)
        $coaList = KodeTransaksi::orderBy('kodetransaksi')->get();

        $accounts = [];
        $totalDebit = 0;
        $totalKredit = 0;

        // Ambil semua project dalam company
        $projects = DB::table('projects')
            ->where('idcompany', $companyId)
            ->pluck('id');

        foreach ($coaList as $coa) {
            // Hitung total debit dan kredit per COA untuk semua project
            $transactions = NotaTransaction::select([
                    'nota_transactions.total',
                    'notas.cashflow',
                    'kodetransaksi.kodetransaksi',
                    DB::raw('CASE 
                        WHEN (kodetransaksi.transaksi = "pendapatan" OR kodetransaksi.kodetransaksi LIKE "4%") 
                        AND notas.cashflow = "in" THEN "kredit"
                        WHEN (kodetransaksi.transaksi = "pendapatan" OR kodetransaksi.kodetransaksi LIKE "4%") 
                        AND notas.cashflow = "out" THEN "debit"
                        WHEN (kodetransaksi.transaksi = "beban" OR kodetransaksi.kodetransaksi LIKE "5%") 
                        AND notas.cashflow = "out" THEN "debit"
                        WHEN (kodetransaksi.transaksi = "beban" OR kodetransaksi.kodetransaksi LIKE "5%") 
                        AND notas.cashflow = "in" THEN "kredit"
                        WHEN (kodetransaksi.kodetransaksi LIKE "1%" OR kodetransaksi.kodetransaksi LIKE "2%") 
                        AND notas.cashflow = "in" THEN "debit"
                        WHEN (kodetransaksi.kodetransaksi LIKE "1%" OR kodetransaksi.kodetransaksi LIKE "2%") 
                        AND notas.cashflow = "out" THEN "kredit"
                        WHEN (kodetransaksi.kodetransaksi LIKE "3%") 
                        AND notas.cashflow = "out" THEN "debit"
                        WHEN (kodetransaksi.kodetransaksi LIKE "3%") 
                        AND notas.cashflow = "in" THEN "kredit"
                        ELSE "debit"
                    END as position')
                ])
                ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
                ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
                ->whereIn('notas.idproject', $projects)
                ->where('notas.status', 'paid')
                ->whereBetween('notas.tanggal', [$startDate, $endDate])
                ->where('nota_transactions.idkodetransaksi', $coa->id)
                ->get();

            $debit = 0;
            $kredit = 0;

            foreach ($transactions as $trans) {
                if ($trans->position == 'debit') {
                    $debit += $trans->total;
                } else {
                    $kredit += $trans->total;
                }
            }

            // Hanya tampilkan akun yang memiliki transaksi
            if ($debit > 0 || $kredit > 0) {
                $accounts[] = [
                    'kode' => $coa->kodetransaksi,
                    'nama_akun' => $coa->transaksi,
                    'jenis' => $coa->transaksi ?? 'lainnya',
                    'debit' => number_format($debit, 0, ',', '.'),
                    'kredit' => number_format($kredit, 0, ',', '.'),
                    'debit_raw' => $debit,
                    'kredit_raw' => $kredit
                ];

                $totalDebit += $debit;
                $totalKredit += $kredit;
            }
        }

        // Tambahkan saldo rekening PT (Aset)
        $rekenings = $this->getSaldoRekeningCompany($companyId);
        
        foreach ($rekenings as $rekening) {
            if ($rekening['saldo_raw'] != 0) {
                $position = $rekening['saldo_raw'] > 0 ? 'debit' : 'kredit';
                $debitAmount = $position == 'debit' ? abs($rekening['saldo_raw']) : 0;
                $kreditAmount = $position == 'kredit' ? abs($rekening['saldo_raw']) : 0;
                
                $accounts[] = [
                    'kode' => '1' . str_pad($rekening['id'], 3, '0', STR_PAD_LEFT),
                    'nama_akun' => 'Kas/Bank PT - ' . $rekening['nama'],
                    'jenis' => 'aset',
                    'debit' => number_format($debitAmount, 0, ',', '.'),
                    'kredit' => number_format($kreditAmount, 0, ',', '.'),
                    'debit_raw' => $debitAmount,
                    'kredit_raw' => $kreditAmount,
                    'is_rekening' => true
                ];

                $totalDebit += $debitAmount;
                $totalKredit += $kreditAmount;
            }
        }

        // Urutkan berdasarkan kode akun
        usort($accounts, function($a, $b) {
            return strcmp($a['kode'], $b['kode']);
        });

        return [
            'accounts' => $accounts,
            'summary' => [
                'total_debit' => number_format($totalDebit, 0, ',', '.'),
                'total_kredit' => number_format($totalKredit, 0, ',', '.'),
                'total_debit_raw' => $totalDebit,
                'total_kredit_raw' => $totalKredit,
                'balance' => $totalDebit == $totalKredit,
                'difference' => number_format(abs($totalDebit - $totalKredit), 0, ',', '.'),
                'total_accounts' => count($accounts),
                'total_projects' => count($projects)
            ]
        ];
    }

    /**
     * Get saldo rekening untuk company
     */
    private function getSaldoRekeningCompany($companyId)
    {
        $rekenings = DB::table('rekening')
            ->select([
                'rekening.idrek as id',
                'rekening.norek',
                'rekening.namarek as nama',
                'rekening.saldo'
            ])
            ->where('rekening.idcompany', $companyId)
            ->whereNull('rekening.idproject')
            ->orderBy('rekening.namarek')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'norek' => $item->norek,
                    'nama' => $item->nama,
                    'saldo' => number_format($item->saldo, 0, ',', '.'),
                    'saldo_raw' => $item->saldo
                ];
            })
            ->toArray();

        return $rekenings;
    }

    /**
     * Export Neraca Saldo ke Excel
     */
    public function exportNeracaSaldoExcel(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $module = $request->input('module', session('active_project_module'));
        
        try {
            if ($module == 'project') {
                $data = $this->getNeracaSaldoProject($startDate, $endDate);
                $title = 'Neraca Saldo Project';
            } elseif ($module == 'company') {
                $data = $this->getNeracaSaldoCompany($startDate, $endDate);
                $title = 'Neraca Saldo PT/Company';
            } else {
                return response()->back()->with('error', 'Module tidak dikenali');
            }

            // Generate Excel
            return $this->generateExcelNeracaSaldo($data, $title, $startDate, $endDate);

        } catch (\Exception $e) {
            \Log::error('Error exporting neraca saldo:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->back()->with('error', 'Gagal export: ' . $e->getMessage());
        }
    }

    /**
     * Generate Excel file for Neraca Saldo
     */
    private function generateExcelNeracaSaldo($data, $title, $startDate, $endDate)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title and headers
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', 'Periode: ' . Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y'));
        
        $sheet->setCellValue('A4', 'Kode Akun');
        $sheet->setCellValue('B4', 'Nama Akun');
        $sheet->setCellValue('C4', 'Debit');
        $sheet->setCellValue('D4', 'Kredit');
        
        // Style for headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE0E0E0']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
            ]
        ];
        
        $sheet->getStyle('A4:D4')->applyFromArray($headerStyle);
        
        // Fill data
        $row = 5;
        foreach ($data['accounts'] as $account) {
            $sheet->setCellValue('A' . $row, $account['kode']);
            $sheet->setCellValue('B' . $row, $account['nama_akun']);
            $sheet->setCellValue('C' . $row, $account['debit_raw']);
            $sheet->setCellValue('D' . $row, $account['kredit_raw']);
            
            // Format numbers
            $sheet->getStyle('C' . $row . ':D' . $row)->getNumberFormat()
                ->setFormatCode('#,##0');
            
            $row++;
        }
        
        // Add totals
        $totalRow = $row + 1;
        $sheet->setCellValue('B' . $totalRow, 'TOTAL');
        $sheet->setCellValue('C' . $totalRow, $data['summary']['total_debit_raw']);
        $sheet->setCellValue('D' . $totalRow, $data['summary']['total_kredit_raw']);
        
        $totalStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD9EAD3']
            ],
            'borders' => [
                'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE],
                'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE]
            ]
        ];
        
        $sheet->getStyle('B' . $totalRow . ':D' . $totalRow)->applyFromArray($totalStyle);
        $sheet->getStyle('C' . $totalRow . ':D' . $totalRow)->getNumberFormat()
            ->setFormatCode('#,##0');
        
        // Auto size columns
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Add balance status
        $statusRow = $totalRow + 1;
        $status = $data['summary']['balance'] ? 'SEIMBANG' : 'TIDAK SEIMBANG';
        $statusColor = $data['summary']['balance'] ? '00FF00' : 'FF0000';
        
        $sheet->setCellValue('B' . $statusRow, 'Status:');
        $sheet->setCellValue('C' . $statusRow, $status);
        $sheet->mergeCells('C' . $statusRow . ':D' . $statusRow);
        
        $statusStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FF' . $statusColor]
            ]
        ];
        $sheet->getStyle('C' . $statusRow)->applyFromArray($statusStyle);
        
        if (!$data['summary']['balance']) {
            $diffRow = $statusRow + 1;
            $sheet->setCellValue('B' . $diffRow, 'Selisih:');
            $sheet->setCellValue('C' . $diffRow, $data['summary']['difference']);
            $sheet->getStyle('C' . $diffRow)->getNumberFormat()
                ->setFormatCode('#,##0');
        }
        
        // Save file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'neraca-saldo-' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    /**
     * Print Neraca Saldo
     */
    public function printNeracaSaldo(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $module = $request->input('module', session('active_project_module'));
        
        try {
            if ($module == 'project') {
                $data = $this->getNeracaSaldoProject($startDate, $endDate);
                $title = 'Neraca Saldo Project';
            } elseif ($module == 'company') {
                $data = $this->getNeracaSaldoCompany($startDate, $endDate);
                $title = 'Neraca Saldo PT/Company';
            } else {
                return response()->back()->with('error', 'Module tidak dikenali');
            }

            $viewData = [
                'accounts' => $data['accounts'],
                'summary' => $data['summary'],
                'title' => $title,
                'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
                'end_date' => Carbon::parse($endDate)->format('d/m/Y'),
                'print_date' => Carbon::now()->format('d/m/Y H:i:s')
            ];

            $pdf = \PDF::loadView('transaksi.laporan.pdf.neraca_saldo', $viewData);
            
            return $pdf->stream('neraca-saldo-' . date('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            \Log::error('Error printing neraca saldo:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->back()->with('error', 'Gagal print: ' . $e->getMessage());
        }
    }
}
