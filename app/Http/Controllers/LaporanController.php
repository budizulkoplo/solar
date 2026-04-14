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
use App\Exports\VisitExport;
use App\Exports\CashflowDetailExport;
use Maatwebsite\Excel\Facades\Excel;

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

    protected function attachNotaTransactionExportColumns($data)
    {
        $notaIds = collect($data)
            ->filter(function ($row) {
                return ($row->kategori ?? null) === 'Transaksi' && !empty($row->id) && $row->id > 0;
            })
            ->pluck('id')
            ->unique()
            ->values();

        if ($notaIds->isEmpty()) {
            return $data;
        }

        $itemsByNota = DB::table('nota_transactions as nt')
            ->leftJoin('kodetransaksi as kt', 'nt.idkodetransaksi', '=', 'kt.id')
            ->select(
                'nt.idnota',
                'nt.description',
                'nt.nominal',
                'nt.jml',
                'nt.total',
                'kt.kodetransaksi',
                'kt.transaksi as nama_transaksi'
            )
            ->whereIn('nt.idnota', $notaIds)
            ->orderBy('nt.idnota')
            ->orderBy('nt.id')
            ->get()
            ->groupBy('idnota');

        return collect($data)->map(function ($row) use ($itemsByNota) {
            $row->detail_no_export = '-';
            $row->detail_kode_transaksi_export = '-';
            $row->detail_deskripsi_export = '-';
            $row->detail_nominal_export = '-';
            $row->detail_jumlah_export = '-';
            $row->detail_total_export = '-';
            $row->detail_items_export = [];

            if (($row->kategori ?? null) !== 'Transaksi' || empty($row->id) || $row->id <= 0) {
                return $row;
            }

            $items = $itemsByNota->get($row->id, collect());

            if ($items->isEmpty()) {
                return $row;
            }

            $row->detail_no_export = $items->values()->map(function ($item, $index) {
                return (string) ($index + 1);
            })->implode("\n");

            $row->detail_kode_transaksi_export = $items->map(function ($item) {
                $kode = $item->kodetransaksi ?: '-';
                $nama = $item->nama_transaksi ?: '-';
                return $kode . "\n(" . $nama . ')';
            })->implode("\n");

            $row->detail_deskripsi_export = $items->map(function ($item) {
                return $item->description ?: '-';
            })->implode("\n");

            $row->detail_nominal_export = $items->map(function ($item) {
                return 'Rp ' . number_format((float) $item->nominal, 0, ',', '.');
            })->implode("\n");

            $row->detail_jumlah_export = $items->map(function ($item) {
                return number_format((float) $item->jml, 0, ',', '.');
            })->implode("\n");

            $row->detail_total_export = $items->map(function ($item) {
                return 'Rp ' . number_format((float) $item->total, 0, ',', '.');
            })->implode("\n");

            $row->detail_items_export = $items->values()->map(function ($item, $index) {
                return [
                    'no' => (string) ($index + 1),
                    'kode_transaksi' => $item->kodetransaksi ?: '-',
                    'nama_transaksi' => $item->nama_transaksi ?: '-',
                    'deskripsi' => $item->description ?: '-',
                    'nominal' => 'Rp ' . number_format((float) $item->nominal, 0, ',', '.'),
                    'jumlah' => number_format((float) $item->jml, 0, ',', '.'),
                    'total' => 'Rp ' . number_format((float) $item->total, 0, ',', '.'),
                ];
            })->all();

            return $row;
        });
    }

    protected function flattenCashflowRowsForExcel(array $data, bool $includeCompany = false): array
    {
        $rows = [];

        foreach ($data as $index => $row) {
            $baseColumns = [
                $index + 1,
                $row['nota_no'] ?? '-',
                !empty($row['tanggal']) ? Carbon::parse($row['tanggal'])->format('d/m/Y') : '-',
                $row['kodetransaksi'] ?? '-',
                $row['namatransaksi'] ?? '-',
                $this->formatExcelRupiah($row['pemasukan'] ?? 0),
                $this->formatExcelRupiah($row['pengeluaran'] ?? 0),
                $this->formatExcelRupiah($row['saldo'] ?? 0),
                $row['namavendor'] ?? '-',
                $row['rekening'] ?? '-',
            ];

            if ($includeCompany) {
                $baseColumns[] = $row['nama_company'] ?? '-';
            }

            $detailItems = $row['detail_items_export'] ?? [];

            if (($row['kategori'] ?? null) === 'Transaksi' && is_array($detailItems) && count($detailItems) > 0) {
                foreach ($detailItems as $item) {
                    $rows[] = array_merge($baseColumns, [
                        $item['no'] ?? '-',
                        trim(($item['kode_transaksi'] ?? '-') . ' ' . ($item['nama_transaksi'] ?? '')),
                        $item['deskripsi'] ?? '-',
                        $item['nominal'] ?? '-',
                        $item['jumlah'] ?? '-',
                        $item['total'] ?? '-',
                    ]);
                }

                continue;
            }

            $rows[] = array_merge($baseColumns, ['-', '-', '-', '-', '-', '-']);
        }

        return $rows;
    }

    protected function formatExcelRupiah($value): string
    {
        $number = (float) $value;

        if ($number == 0.0) {
            return '-';
        }

        return 'Rp ' . number_format($number, 0, ',', '.');
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
        $activeProjectId = session('active_project_id');
        
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
        
        $kodeTransaksiSubquery = DB::table('nota_transactions as nt')
            ->leftJoin('kodetransaksi as kt', 'nt.idkodetransaksi', '=', 'kt.id')
            ->select(
                'nt.idnota',
                DB::raw("
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(
                            CONCAT('(', COALESCE(kt.kodetransaksi, '-'), ') ', COALESCE(kt.transaksi, '-'))
                            ORDER BY
                                CASE
                                    WHEN nt.description IN ('PPN', 'Diskon') THEN 1
                                    ELSE 0
                                END,
                                nt.id
                            SEPARATOR '||'
                        ),
                        '||',
                        1
                    ) as kode_transaksi_display
                ")
            )
            ->groupBy('nt.idnota');

        $penjualanPaymentCashflowWhere = "
                cf2.idnota IS NULL
                    AND cf2.tanggal = pp.tanggal_payment
                    AND cf2.nominal = pp.nominal
                    AND cf2.cashflow = 'in'
                    AND (
                        cf2.idrek = pp.idrek
                        OR cf2.keterangan LIKE CONCAT('%Penjualan: ', pj.kode_penjualan, '%')
                    )
        ";

        $penjualanPaymentSaldoSubquery = "
            (
                SELECT cf2.saldo_akhir
                FROM cashflows cf2
                WHERE {$penjualanPaymentCashflowWhere}
                ORDER BY cf2.id DESC
                LIMIT 1
            )
        ";

        $penjualanPaymentRekeningSubquery = "
            (
                SELECT r2.namarek
                FROM cashflows cf2
                LEFT JOIN rekening r2 ON cf2.idrek = r2.idrek
                WHERE {$penjualanPaymentCashflowWhere}
                ORDER BY cf2.id DESC
                LIMIT 1
            )
        ";

        // Query data transaksi Project (idproject tidak null)
        $notaQuery = DB::table('notas as n')
            ->select(
                'n.id',
                'np.id as id_payment',
                'n.nota_no',
                'n.tanggal',
                DB::raw('COALESCE(kts.kode_transaksi_display, "-") as kodetransaksi'),
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
            ->leftJoinSub($kodeTransaksiSubquery, 'kts', function ($join) {
                $join->on('n.id', '=', 'kts.idnota');
            })
            ->where('n.status', 'paid')
            ->where('n.idproject', $activeProjectId)
            ->whereNotNull('n.idproject') // Hanya yang punya project
            ->whereBetween('n.tanggal', [$startDate, $endDate]);

        $penjualanPaymentQuery = DB::table('penjualan_payments as pp')
            ->join('penjualans as pj', 'pp.penjualan_id', '=', 'pj.id')
            ->join('unit_details as ud', 'pj.unit_detail_id', '=', 'ud.id')
            ->join('units as u', 'ud.idunit', '=', 'u.id')
            ->leftJoin('customers as c', 'pj.customer_id', '=', 'c.id')
            ->leftJoin('rekening as r', 'pp.idrek', '=', 'r.idrek')
            ->leftJoin('projects as p', 'u.idproject', '=', 'p.id')
            ->select(
                DB::raw('pp.id * -1000000 as id'),
                'pp.id as id_payment',
                'pp.kode_payment as nota_no',
                'pp.tanggal_payment as tanggal',
                DB::raw("
                    CONCAT(
                        '(PAY) ',
                        CASE pp.jenis_payment
                            WHEN 'dp_awal' THEN 'DP Awal'
                            WHEN 'dp_uang_muka' THEN 'DP Uang Muka'
                            WHEN 'termin_1' THEN 'Termin 1'
                            WHEN 'termin_2' THEN 'Termin 2'
                            WHEN 'termin_3' THEN 'Termin 3'
                            WHEN 'retensi' THEN 'Retensi'
                            WHEN 'sbum' THEN 'SBUM'
                            WHEN 'lunas' THEN 'Pelunasan'
                            ELSE 'Pembayaran Lainnya'
                        END
                    ) as kodetransaksi
                "),
                DB::raw('"Penjualan" as kategori'),
                DB::raw("
                    CONCAT(
                        'Pembayaran ',
                        CASE pp.jenis_payment
                            WHEN 'dp_awal' THEN 'DP Awal'
                            WHEN 'dp_uang_muka' THEN 'DP Uang Muka'
                            WHEN 'termin_1' THEN 'Termin 1'
                            WHEN 'termin_2' THEN 'Termin 2'
                            WHEN 'termin_3' THEN 'Termin 3'
                            WHEN 'retensi' THEN 'Retensi'
                            WHEN 'sbum' THEN 'SBUM'
                            WHEN 'lunas' THEN 'Pelunasan'
                            ELSE 'Lainnya'
                        END,
                        ' - Penjualan: ',
                        pj.kode_penjualan,
                        ' - Unit: ',
                        COALESCE(u.namaunit, '-')
                    ) as namatransaksi
                "),
                'pp.nominal as jumlah_transaksi',
                DB::raw('pp.nominal as pemasukan'),
                DB::raw('0 as pengeluaran'),
                DB::raw("COALESCE({$penjualanPaymentSaldoSubquery}, 0) as saldo"),
                'c.nama_lengkap as namavendor',
                DB::raw("COALESCE(r.namarek, {$penjualanPaymentRekeningSubquery}, '-') as rekening"),
                'p.namaproject',
                'u.idproject'
            )
            ->where('pp.status_payment', 'realized')
            ->where('u.idproject', $activeProjectId)
            ->whereBetween('pp.tanggal_payment', [$startDate, $endDate]);

        $pembiayaanQuery = DB::table('cashflows as cf')
            ->select(
                DB::raw('cf.id * -1 as id'),
                DB::raw('NULL as id_payment'),
                DB::raw('COALESCE(cf.kode_transaksi, "-") as nota_no'),
                'cf.tanggal',
                DB::raw("CONCAT('(', COALESCE(cf.kode_transaksi, '-'), ') Pembiayaan') as kodetransaksi"),
                DB::raw('"Pembiayaan" as kategori'),
                'cf.keterangan as namatransaksi',
                'cf.nominal as jumlah_transaksi',
                DB::raw('CASE WHEN cf.cashflow = "in" THEN cf.nominal ELSE 0 END as pemasukan'),
                DB::raw('CASE WHEN cf.cashflow = "out" THEN cf.nominal ELSE 0 END as pengeluaran'),
                'cf.saldo_akhir as saldo',
                DB::raw('NULL as namavendor'),
                'r.namarek as rekening',
                'p.namaproject',
                'r.idproject'
            )
            ->join('rekening as r', 'cf.idrek', '=', 'r.idrek')
            ->leftJoin('projects as p', 'r.idproject', '=', 'p.id')
            ->whereNull('cf.idnota')
            ->where('r.idproject', $activeProjectId)
            ->whereBetween('cf.tanggal', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan as p')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("p.kode_pembiayaan = SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, '(', -1), ')', 1)");
                        });
                })->orWhere(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Setoran Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan_setoran as ps')
                                ->join('pembiayaan as p', 'ps.pembiayaan_id', '=', 'p.id')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("ps.kode_setoran = SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, '(', -1), ')', 1)");
                        });
                })->orWhere(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Penyesuaian Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan as p')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("p.judul = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, ':', -1), '(', 1))");
                        });
                });
            });

        $data = $notaQuery
            ->unionAll($penjualanPaymentQuery)
            ->unionAll($pembiayaanQuery)
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $data = $this->attachNotaTransactionExportColumns($data);

        // TIDAK PERLU hitung saldo running karena sudah diambil langsung dari cashflows
        
        // Hitung total pemasukan dan pengeluaran
        $totals = DB::table('notas as n')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN n.cashflow = "in" THEN np.jumlah ELSE 0 END), 0) as total_pemasukan,
                COALESCE(SUM(CASE WHEN n.cashflow = "out" THEN np.jumlah ELSE 0 END), 0) as total_pengeluaran
            ')
            ->join('nota_payments as np', 'n.id', '=', 'np.idnota')
            ->where('n.status', 'paid')
            ->where('n.idproject', $activeProjectId)
            ->whereBetween('n.tanggal', [$startDate, $endDate])
            ->first();

        $penjualanPaymentTotals = DB::table('penjualan_payments as pp')
            ->join('penjualans as pj', 'pp.penjualan_id', '=', 'pj.id')
            ->join('unit_details as ud', 'pj.unit_detail_id', '=', 'ud.id')
            ->join('units as u', 'ud.idunit', '=', 'u.id')
            ->selectRaw('COALESCE(SUM(pp.nominal), 0) as total_pemasukan')
            ->where('pp.status_payment', 'realized')
            ->where('u.idproject', $activeProjectId)
            ->whereBetween('pp.tanggal_payment', [$startDate, $endDate])
            ->first();

        $pembiayaanTotals = DB::table('cashflows as cf')
            ->join('rekening as r', 'cf.idrek', '=', 'r.idrek')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN cf.cashflow = "in" THEN cf.nominal ELSE 0 END), 0) as total_pemasukan,
                COALESCE(SUM(CASE WHEN cf.cashflow = "out" THEN cf.nominal ELSE 0 END), 0) as total_pengeluaran
            ')
            ->whereNull('cf.idnota')
            ->where('r.idproject', $activeProjectId)
            ->whereBetween('cf.tanggal', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan as p')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("p.kode_pembiayaan = SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, '(', -1), ')', 1)");
                        });
                })->orWhere(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Setoran Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan_setoran as ps')
                                ->join('pembiayaan as p', 'ps.pembiayaan_id', '=', 'p.id')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("ps.kode_setoran = SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, '(', -1), ')', 1)");
                        });
                })->orWhere(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Penyesuaian Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan as p')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("p.judul = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, ':', -1), '(', 1))");
                        });
                });
            })
            ->first();

        $saldoAkhir = $data->count() > 0 ? ($data->last()->saldo ?? 0) : 0;

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
                'pemasukan' => ($totals->total_pemasukan ?? 0) + ($penjualanPaymentTotals->total_pemasukan ?? 0) + ($pembiayaanTotals->total_pemasukan ?? 0),
                'pengeluaran' => ($totals->total_pengeluaran ?? 0) + ($pembiayaanTotals->total_pengeluaran ?? 0),
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

        $kodeTransaksiSubquery = DB::table('nota_transactions as nt')
            ->leftJoin('kodetransaksi as kt', 'nt.idkodetransaksi', '=', 'kt.id')
            ->select(
                'nt.idnota',
                DB::raw("
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(
                            CONCAT('(', COALESCE(kt.kodetransaksi, '-'), ') ', COALESCE(kt.transaksi, '-'))
                            ORDER BY
                                CASE
                                    WHEN nt.description IN ('PPN', 'Diskon') THEN 1
                                    ELSE 0
                                END,
                                nt.id
                            SEPARATOR '||'
                        ),
                        '||',
                        1
                    ) as kode_transaksi_display
                ")
            )
            ->groupBy('nt.idnota');

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
                DB::raw('COALESCE(kts.kode_transaksi_display, "-") as kodetransaksi'),
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
            ->leftJoinSub($kodeTransaksiSubquery, 'kts', function ($join) {
                $join->on('n.id', '=', 'kts.idnota');
            })
            ->where('n.status', 'paid')
            ->where('n.idcompany', session('active_company_id'))
            ->whereNotNull('n.idcompany')
            ->whereNull('n.idproject')
            ->whereBetween('n.tanggal', [$startDate, $endDate]);

        $pembiayaanQuery = DB::table('cashflows as cf')
            ->select(
                DB::raw('cf.id * -1 as id'),
                DB::raw('NULL as id_payment'),
                DB::raw('COALESCE(cf.kode_transaksi, "-") as nota_no'),
                'cf.tanggal',
                DB::raw("CONCAT('(', COALESCE(cf.kode_transaksi, '-'), ') Pembiayaan') as kodetransaksi"),
                DB::raw('"Pembiayaan" as kategori'),
                'cf.keterangan as namatransaksi',
                'cf.nominal as jumlah_transaksi',
                DB::raw('CASE WHEN cf.cashflow = "in" THEN cf.nominal ELSE 0 END as pemasukan'),
                DB::raw('CASE WHEN cf.cashflow = "out" THEN cf.nominal ELSE 0 END as pengeluaran'),
                'cf.saldo_akhir as saldo',
                DB::raw('NULL as namavendor'),
                'r.namarek as rekening',
                'cu.company_name as nama_company',
                'r.idcompany'
            )
            ->join('rekening as r', 'cf.idrek', '=', 'r.idrek')
            ->leftJoin('company_units as cu', 'r.idcompany', '=', 'cu.id')
            ->whereNull('cf.idnota')
            ->where('r.idcompany', session('active_company_id'))
            ->whereNull('r.idproject')
            ->whereBetween('cf.tanggal', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan as p')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("p.kode_pembiayaan = SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, '(', -1), ')', 1)");
                        });
                })->orWhere(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Setoran Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan_setoran as ps')
                                ->join('pembiayaan as p', 'ps.pembiayaan_id', '=', 'p.id')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("ps.kode_setoran = SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, '(', -1), ')', 1)");
                        });
                })->orWhere(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Penyesuaian Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan as p')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("p.judul = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, ':', -1), '(', 1))");
                        });
                });
            });

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
                DB::raw("CONCAT('(', pbk.kode_transaksi, ') Pindah Buku') as kodetransaksi"),
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
                DB::raw("CONCAT('(', pbk.kode_transaksi, ') Pindah Buku') as kodetransaksi"),
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
            ->unionAll($pembiayaanQuery)
            ->unionAll($pbkOut)
            ->unionAll($pbkIn)
            ->orderBy('tanggal', 'asc')
            ->orderBy('nota_no', 'asc')
            ->get();

        $data = $this->attachNotaTransactionExportColumns($data);

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

        $pembiayaanTotals = DB::table('cashflows as cf')
            ->join('rekening as r', 'cf.idrek', '=', 'r.idrek')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN cf.cashflow = "in" THEN cf.nominal ELSE 0 END),0) as total_pemasukan,
                COALESCE(SUM(CASE WHEN cf.cashflow = "out" THEN cf.nominal ELSE 0 END),0) as total_pengeluaran
            ')
            ->whereNull('cf.idnota')
            ->where('r.idcompany', session('active_company_id'))
            ->whereNull('r.idproject')
            ->whereBetween('cf.tanggal', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan as p')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("p.kode_pembiayaan = SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, '(', -1), ')', 1)");
                        });
                })->orWhere(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Setoran Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan_setoran as ps')
                                ->join('pembiayaan as p', 'ps.pembiayaan_id', '=', 'p.id')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("ps.kode_setoran = SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, '(', -1), ')', 1)");
                        });
                })->orWhere(function ($sub) {
                    $sub->where('cf.keterangan', 'like', 'Penyesuaian Pembiayaan:%')
                        ->whereExists(function ($pb) {
                            $pb->select(DB::raw(1))
                                ->from('pembiayaan as p')
                                ->whereNull('p.deleted_at')
                                ->whereRaw("p.judul = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(cf.keterangan, ':', -1), '(', 1))");
                        });
                });
            })
            ->first();

        /**
         * ==================================================
         * 6. SALDO AKHIR
         * ==================================================
         */
        $lastCashflow = DB::table('cashflows as cf')
            ->join('rekening as r', 'cf.idrek', '=', 'r.idrek')
            ->where('r.idcompany', session('active_company_id'))
            ->whereNull('r.idproject')
            ->whereBetween('cf.tanggal', [$startDate, $endDate])
            ->orderBy('cf.tanggal', 'desc')
            ->orderBy('cf.id', 'desc')
            ->select('cf.saldo_akhir')
            ->first();

        return response()->json([
            'data' => $data,
            'total' => [
                'pemasukan'   => ($totals->total_pemasukan ?? 0) + ($pembiayaanTotals->total_pemasukan ?? 0),
                'pengeluaran' => ($totals->total_pengeluaran ?? 0) + ($pembiayaanTotals->total_pengeluaran ?? 0),
                'saldo_akhir' => $lastCashflow->saldo_akhir ?? 0
            ]
        ]);
    }

    public function exportCashflowProjectExcel(Request $request)
    {
        $response = $this->cashflowProjectData($request)->getData(true);
        $rows = $this->flattenCashflowRowsForExcel($response['data'] ?? [], false);

        $headings = [
            'No',
            'No. Nota',
            'Tgl. Trans',
            'Kode Transaksi',
            'Nama Transaksi',
            'Pemasukan',
            'Pengeluaran',
            'Saldo',
            'Vendor',
            'Rekening',
            '#',
            'Kode Transaksi Detail',
            'Deskripsi',
            'Nominal',
            'Jumlah',
            'Total',
        ];

        $start = Carbon::parse($request->input('start_date', now()->format('Y-m-01')))->format('Ymd');
        $end = Carbon::parse($request->input('end_date', now()->format('Y-m-t')))->format('Ymd');
        $filename = "Cashflow_Project_{$start}_{$end}.xlsx";

        return Excel::download(new CashflowDetailExport($rows, $headings), $filename);
    }

    public function exportCashflowPTExcel(Request $request)
    {
        $response = $this->cashflowPTData($request)->getData(true);
        $rows = $this->flattenCashflowRowsForExcel($response['data'] ?? [], true);

        $headings = [
            'No',
            'No. Nota',
            'Tgl. Trans',
            'Kode Transaksi',
            'Nama Transaksi',
            'Pemasukan',
            'Pengeluaran',
            'Saldo',
            'Vendor',
            'Rekening',
            'PT/Company',
            '#',
            'Kode Transaksi Detail',
            'Deskripsi',
            'Nominal',
            'Jumlah',
            'Total',
        ];

        $start = Carbon::parse($request->input('start_date', now()->format('Y-m-01')))->format('Ymd');
        $end = Carbon::parse($request->input('end_date', now()->format('Y-m-t')))->format('Ymd');
        $filename = "Cashflow_PT_{$start}_{$end}.xlsx";

        return Excel::download(new CashflowDetailExport($rows, $headings), $filename);
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

    /**
     * Halaman laporan Neraca
     */
    public function neraca()
    {
        $startDate = now()->startOfMonth()->format('Y-m-d');
        $endDate = now()->endOfMonth()->format('Y-m-d');
        $module = session('active_project_module');

        return view('transaksi.laporan.neraca', compact('startDate', 'endDate', 'module'));
    }

    /**
     * Data laporan Neraca (Aktiva vs Pasiva)
     */
    public function neracaData(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $module = $request->input('module', session('active_project_module'));

        if (empty($startDate) || empty($endDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal awal dan akhir harus diisi'
            ], 400);
        }

        try {
            if ($module == 'project') {
                $saldoData = $this->getNeracaSaldoProject($startDate, $endDate);
            } elseif ($module == 'company') {
                $saldoData = $this->getNeracaSaldoCompany($startDate, $endDate);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Module tidak dikenali'
                ], 400);
            }

            $neracaData = $this->buildNeracaFromSaldo($saldoData['accounts']);

            if (in_array($module, ['company', 'project'], true)) {
                $customAktiva = $this->buildAktivaTemplate($module, $endDate);
                $neracaData['data']['aktiva'] = $customAktiva['rows'];
                $neracaData['data']['aktiva_groups'] = $customAktiva['groups'];
                $neracaData['summary']['total_aktiva_raw'] = $customAktiva['total_raw'];
                $neracaData['summary']['total_aktiva'] = number_format($customAktiva['total_raw'], 0, ',', '.');
                $neracaData['summary']['balance'] = abs($customAktiva['total_raw'] - ($neracaData['summary']['total_pasiva_raw'] ?? 0)) < 0.5;
                $neracaData['summary']['difference_raw'] = abs($customAktiva['total_raw'] - ($neracaData['summary']['total_pasiva_raw'] ?? 0));
                $neracaData['summary']['difference'] = number_format($neracaData['summary']['difference_raw'], 0, ',', '.');
            }

            return response()->json([
                'success' => true,
                'data' => $neracaData['data'],
                'summary' => $neracaData['summary'],
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'module' => $module
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating neraca:', [
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
     * Halaman laporan Laba Rugi
     */
    public function labaRugi()
    {
        $startDate = now()->startOfMonth()->format('Y-m-d');
        $endDate = now()->endOfMonth()->format('Y-m-d');
        $module = session('active_project_module');

        return view('transaksi.laporan.laba_rugi', compact('startDate', 'endDate', 'module'));
    }

    /**
     * Data laporan Laba Rugi
     */
    public function labaRugiData(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $module = $request->input('module', session('active_project_module'));

        if (empty($startDate) || empty($endDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal awal dan akhir harus diisi'
            ], 400);
        }

        try {
            $query = NotaTransaction::query()
                ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
                ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
                ->leftJoin('labarugi_hdr', 'kodetransaksi.idlabarugi', '=', 'labarugi_hdr.id')
                ->where('notas.status', 'paid')
                ->whereBetween('notas.tanggal', [$startDate, $endDate]);

            if ($module === 'project') {
                $projectId = session('active_project_id');
                if (!$projectId) {
                    throw new \Exception('Project ID tidak ditemukan');
                }
                $query->where('notas.idproject', $projectId);
            } elseif ($module === 'company') {
                $companyId = session('active_company_id');
                if (!$companyId) {
                    throw new \Exception('Company ID tidak ditemukan');
                }

                $projects = Project::query()
                    ->where('idcompany', $companyId)
                    ->pluck('id');

                $query->whereIn('notas.idproject', $projects);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Module tidak dikenali'
                ], 400);
            }

            $rows = $query
                ->selectRaw('
                    kodetransaksi.id as id_kodetransaksi,
                    kodetransaksi.kodetransaksi as kode_akun,
                    kodetransaksi.transaksi as nama_akun,
                    kodetransaksi.idlabarugi as id_labarugi,
                    labarugi_hdr.rincian as rincian_labarugi,
                    labarugi_hdr.cashflow as cashflow_labarugi,
                    labarugi_hdr.kode_pemasukan as kode_pemasukan,
                    labarugi_hdr.kode_pengeluaran as kode_pengeluaran,
                    COALESCE(SUM(CASE WHEN notas.cashflow = "in" THEN nota_transactions.total ELSE 0 END), 0) as total_in,
                    COALESCE(SUM(CASE WHEN notas.cashflow = "out" THEN nota_transactions.total ELSE 0 END), 0) as total_out
                ')
                ->groupBy([
                    'kodetransaksi.id',
                    'kodetransaksi.kodetransaksi',
                    'kodetransaksi.transaksi',
                    'kodetransaksi.idlabarugi',
                    'labarugi_hdr.rincian',
                    'labarugi_hdr.cashflow',
                    'labarugi_hdr.kode_pemasukan',
                    'labarugi_hdr.kode_pengeluaran'
                ])
                ->orderBy('kodetransaksi.kodetransaksi')
                ->get();

            $pendapatanGroups = [];
            $bebanGroups = [];
            $unmappedAccounts = [];
            $totalPendapatan = 0;
            $totalBeban = 0;
            $totalHpp = 0;

            foreach ($rows as $row) {
                if (!$row->id_labarugi) {
                    $unmappedAccounts[] = [
                        'kode' => (string) $row->kode_akun,
                        'nama_akun' => (string) $row->nama_akun
                    ];
                    continue;
                }

                $isPemasukan = strtolower((string) $row->cashflow_labarugi) === 'pemasukan';
                $isPengeluaran = strtolower((string) $row->cashflow_labarugi) === 'pengeluaran';
                $nominal = $isPemasukan
                    ? ((float) $row->total_in - (float) $row->total_out)
                    : ((float) $row->total_out - (float) $row->total_in);

                if (abs($nominal) < 0.5) {
                    continue;
                }

                $item = [
                    'id_kodetransaksi' => (int) $row->id_kodetransaksi,
                    'id_labarugi' => (int) $row->id_labarugi,
                    'kode_akun' => (string) $row->kode_akun,
                    'nama_akun' => (string) $row->nama_akun,
                    'rincian' => (string) ($row->rincian_labarugi ?? '-'),
                    'nominal_raw' => $nominal,
                    'nominal' => number_format($nominal, 0, ',', '.'),
                ];

                if ($isPemasukan) {
                    $groupName = (string) ($row->kode_pemasukan ?: 'PENDAPATAN LAINNYA');
                    $groupKey = 'P-' . $groupName;

                    if (!isset($pendapatanGroups[$groupKey])) {
                        $pendapatanGroups[$groupKey] = [
                            'kategori' => $groupName,
                            'items' => [],
                            'subtotal_raw' => 0,
                            'subtotal' => '0'
                        ];
                    }

                    $pendapatanGroups[$groupKey]['items'][] = $item;
                    $pendapatanGroups[$groupKey]['subtotal_raw'] += $nominal;
                    $totalPendapatan += $nominal;
                    continue;
                }

                if ($isPengeluaran) {
                    $groupName = (string) ($row->kode_pengeluaran ?: 'BEBAN LAINNYA');
                    $groupKey = 'B-' . $groupName;

                    if (!isset($bebanGroups[$groupKey])) {
                        $bebanGroups[$groupKey] = [
                            'kategori' => $groupName,
                            'items' => [],
                            'subtotal_raw' => 0,
                            'subtotal' => '0'
                        ];
                    }

                    $bebanGroups[$groupKey]['items'][] = $item;
                    $bebanGroups[$groupKey]['subtotal_raw'] += $nominal;
                    $totalBeban += $nominal;

                    if (str_contains(strtolower($groupName), 'harga pokok penjualan')) {
                        $totalHpp += $nominal;
                    }
                }
            }

            foreach ($pendapatanGroups as &$group) {
                usort($group['items'], fn($a, $b) => strcmp($a['kode_akun'], $b['kode_akun']));
                $group['subtotal'] = number_format($group['subtotal_raw'], 0, ',', '.');
            }
            unset($group);

            foreach ($bebanGroups as &$group) {
                usort($group['items'], fn($a, $b) => strcmp($a['kode_akun'], $b['kode_akun']));
                $group['subtotal'] = number_format($group['subtotal_raw'], 0, ',', '.');
            }
            unset($group);

            $labaKotor = $totalPendapatan - $totalHpp;
            $labaBersih = $totalPendapatan - $totalBeban;

            return response()->json([
                'success' => true,
                'data' => [
                    'pendapatan_groups' => array_values($pendapatanGroups),
                    'beban_groups' => array_values($bebanGroups),
                ],
                'summary' => [
                    'total_pendapatan_raw' => $totalPendapatan,
                    'total_beban_raw' => $totalBeban,
                    'total_hpp_raw' => $totalHpp,
                    'laba_kotor_raw' => $labaKotor,
                    'laba_bersih_raw' => $labaBersih,
                    'total_pendapatan' => number_format($totalPendapatan, 0, ',', '.'),
                    'total_beban' => number_format($totalBeban, 0, ',', '.'),
                    'total_hpp' => number_format($totalHpp, 0, ',', '.'),
                    'laba_kotor' => number_format($labaKotor, 0, ',', '.'),
                    'laba_bersih' => number_format($labaBersih, 0, ',', '.'),
                    'status' => $labaBersih >= 0 ? 'LABA' : 'RUGI',
                    'unmapped_accounts' => count($unmappedAccounts),
                    'unmapped_account_list' => $unmappedAccounts,
                ],
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'module' => $module
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating laba rugi:', [
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
     * Halaman laporan perubahan ekuitas
     */
    public function perubahanEkuitas()
    {
        $startDate = now()->startOfYear()->format('Y-m-d');
        $endDate = now()->endOfYear()->format('Y-m-d');
        $module = session('active_project_module');

        return view('transaksi.laporan.perubahan_ekuitas', compact('startDate', 'endDate', 'module'));
    }

    /**
     * Data laporan perubahan ekuitas
     */
    public function perubahanEkuitasData(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfYear()->format('Y-m-d'));
        $module = $request->input('module', session('active_project_module'));

        if (empty($startDate) || empty($endDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal awal dan akhir harus diisi'
            ], 400);
        }

        try {
            $report = $this->buildPerubahanEkuitasReport($startDate, $endDate, $module);

            return response()->json([
                'success' => true,
                'data' => $report['data'],
                'summary' => $report['summary'],
                'period' => $report['period'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating perubahan ekuitas:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildPerubahanEkuitasReport(string $startDate, string $endDate, string $module): array
    {
        $openingDate = Carbon::parse($startDate)->subDay()->format('Y-m-d');

        $openingBalances = $this->getPerubahanEkuitasBalances(null, $openingDate, $module);
        $currentYearBalances = $this->getPerubahanEkuitasBalances($startDate, $endDate, $module);
        $profitLoss = $this->calculateLabaRugiRingkas($startDate, $endDate, $module);

        $modalAwal = $openingBalances['categories']['modal_disetor'] ?? 0;
        $labaDitahanAwal = $openingBalances['categories']['laba_ditahan'] ?? 0;
        $tambahanModal = $currentYearBalances['categories']['modal_disetor'] ?? 0;
        $pembagianDividen = $currentYearBalances['categories']['dividen'] ?? 0;
        $koreksiEkuitas = $currentYearBalances['categories']['koreksi_ekuitas'] ?? 0;
        $labaRugiBerjalan = $profitLoss['laba_bersih_raw'] ?? 0;

        $modalAkhir = $modalAwal + $tambahanModal;
        $labaDitahanAkhir = $labaDitahanAwal + $labaRugiBerjalan + $pembagianDividen + $koreksiEkuitas;
        $totalAkhir = $modalAkhir + $labaDitahanAkhir;

        $rows = [
            [
                'keterangan' => 'Saldo Awal ' . Carbon::parse($startDate)->format('d F Y') . ' (berdasarkan saldo akhir periode sebelumnya)',
                'modal_disetor_raw' => $modalAwal,
                'laba_ditahan_raw' => $labaDitahanAwal,
            ],
            [
                'keterangan' => 'Laba/Rugi Tahun Berjalan',
                'modal_disetor_raw' => 0,
                'laba_ditahan_raw' => $labaRugiBerjalan,
            ],
            [
                'keterangan' => 'Pembagian Dividen',
                'modal_disetor_raw' => 0,
                'laba_ditahan_raw' => $pembagianDividen,
            ],
            [
                'keterangan' => 'Tambahan Modal Disetor',
                'modal_disetor_raw' => $tambahanModal,
                'laba_ditahan_raw' => 0,
            ],
            [
                'keterangan' => 'Koreksi Ekuitas',
                'modal_disetor_raw' => 0,
                'laba_ditahan_raw' => $koreksiEkuitas,
            ],
            [
                'keterangan' => 'Saldo Akhir ' . Carbon::parse($endDate)->format('d F Y'),
                'modal_disetor_raw' => $modalAkhir,
                'laba_ditahan_raw' => $labaDitahanAkhir,
            ],
        ];

        foreach ($rows as &$row) {
            $row['total_ekuitas_raw'] = $row['modal_disetor_raw'] + $row['laba_ditahan_raw'];
            $row['modal_disetor'] = number_format($row['modal_disetor_raw'], 0, ',', '.');
            $row['laba_ditahan'] = number_format($row['laba_ditahan_raw'], 0, ',', '.');
            $row['total_ekuitas'] = number_format($row['total_ekuitas_raw'], 0, ',', '.');
        }
        unset($row);

        return [
            'data' => [
                'rows' => $rows,
            ],
            'summary' => [
                'modal_awal_raw' => $modalAwal,
                'laba_ditahan_awal_raw' => $labaDitahanAwal,
                'tambahan_modal_raw' => $tambahanModal,
                'pembagian_dividen_raw' => $pembagianDividen,
                'koreksi_ekuitas_raw' => $koreksiEkuitas,
                'laba_rugi_berjalan_raw' => $labaRugiBerjalan,
                'modal_akhir_raw' => $modalAkhir,
                'laba_ditahan_akhir_raw' => $labaDitahanAkhir,
                'total_akhir_raw' => $totalAkhir,
                'unmapped_accounts' => array_merge(
                    $openingBalances['unmapped_accounts'],
                    $currentYearBalances['unmapped_accounts']
                ),
            ],
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'module' => $module
            ]
        ];
    }

    private function getPerubahanEkuitasBalances(?string $startDate, string $endDate, string $module): array
    {
        $query = NotaTransaction::query()
            ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
            ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
            ->leftJoin('neraca_hdr', 'kodetransaksi.idneraca', '=', 'neraca_hdr.id')
            ->where('notas.status', 'paid')
            ->where('kodetransaksi.kodetransaksi', 'like', '3%')
            ->whereDate('notas.tanggal', '<=', $endDate);

        if (!empty($startDate)) {
            $query->whereDate('notas.tanggal', '>=', $startDate);
        }

        $this->applyFinancialModuleFilter($query, $module);

        $rows = $query
            ->selectRaw('
                kodetransaksi.id as id_kodetransaksi,
                kodetransaksi.kodetransaksi as kode_akun,
                kodetransaksi.transaksi as nama_akun,
                kodetransaksi.idneraca as id_neraca,
                neraca_hdr.rincian as rincian_neraca,
                COALESCE(SUM(CASE WHEN notas.cashflow = "in" THEN nota_transactions.total ELSE 0 END), 0) as total_in,
                COALESCE(SUM(CASE WHEN notas.cashflow = "out" THEN nota_transactions.total ELSE 0 END), 0) as total_out
            ')
            ->groupBy([
                'kodetransaksi.id',
                'kodetransaksi.kodetransaksi',
                'kodetransaksi.transaksi',
                'kodetransaksi.idneraca',
                'neraca_hdr.rincian',
            ])
            ->orderBy('kodetransaksi.kodetransaksi')
            ->get();

        $categories = [
            'modal_disetor' => 0,
            'laba_ditahan' => 0,
            'dividen' => 0,
            'koreksi_ekuitas' => 0,
        ];
        $unmappedAccounts = [];

        foreach ($rows as $row) {
            $balance = (float) $row->total_in - (float) $row->total_out;
            if (abs($balance) < 0.5) {
                continue;
            }

            $category = $this->classifyPerubahanEkuitasAccount([
                'nama_akun' => $row->nama_akun,
                'rincian_neraca' => $row->rincian_neraca,
            ]);

            if (!$category) {
                $unmappedAccounts[] = [
                    'kode' => (string) $row->kode_akun,
                    'nama_akun' => (string) $row->nama_akun,
                ];
                continue;
            }

            $categories[$category] += $balance;
        }

        return [
            'categories' => $categories,
            'unmapped_accounts' => $unmappedAccounts,
        ];
    }

    private function classifyPerubahanEkuitasAccount(array $account): ?string
    {
        $name = strtolower((string) ($account['nama_akun'] ?? ''));
        $neraca = strtolower((string) ($account['rincian_neraca'] ?? ''));
        $haystack = trim($name . ' ' . $neraca);

        if ($haystack === '') {
            return null;
        }

        if (str_contains($haystack, 'dividen')) {
            return 'dividen';
        }

        if (str_contains($haystack, 'koreksi')) {
            return 'koreksi_ekuitas';
        }

        if (str_contains($haystack, 'laba ditahan') || str_contains($haystack, 'saldo laba') || str_contains($haystack, 'retained earning')) {
            return 'laba_ditahan';
        }

        if (str_contains($haystack, 'modal')) {
            return 'modal_disetor';
        }

        return null;
    }

    private function calculateLabaRugiRingkas(string $startDate, string $endDate, string $module): array
    {
        $query = NotaTransaction::query()
            ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
            ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
            ->leftJoin('labarugi_hdr', 'kodetransaksi.idlabarugi', '=', 'labarugi_hdr.id')
            ->where('notas.status', 'paid')
            ->whereBetween('notas.tanggal', [$startDate, $endDate]);

        $this->applyFinancialModuleFilter($query, $module);

        $rows = $query
            ->selectRaw('
                labarugi_hdr.cashflow as cashflow_labarugi,
                labarugi_hdr.kode_pengeluaran as kode_pengeluaran,
                COALESCE(SUM(CASE WHEN notas.cashflow = "in" THEN nota_transactions.total ELSE 0 END), 0) as total_in,
                COALESCE(SUM(CASE WHEN notas.cashflow = "out" THEN nota_transactions.total ELSE 0 END), 0) as total_out
            ')
            ->groupBy([
                'labarugi_hdr.cashflow',
                'labarugi_hdr.kode_pengeluaran',
            ])
            ->get();

        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($rows as $row) {
            $cashflow = strtolower((string) ($row->cashflow_labarugi ?? ''));

            if ($cashflow === 'pemasukan') {
                $totalPendapatan += ((float) $row->total_in - (float) $row->total_out);
                continue;
            }

            if ($cashflow === 'pengeluaran') {
                $totalBeban += ((float) $row->total_out - (float) $row->total_in);
            }
        }

        return [
            'laba_bersih_raw' => $totalPendapatan - $totalBeban,
            'total_pendapatan_raw' => $totalPendapatan,
            'total_beban_raw' => $totalBeban,
        ];
    }

    private function applyFinancialModuleFilter($query, string $module): void
    {
        if ($module === 'project') {
            $projectId = session('active_project_id');
            if (!$projectId) {
                throw new \Exception('Project ID tidak ditemukan');
            }

            $query->where('notas.idproject', $projectId);
            return;
        }

        if ($module === 'company') {
            $companyId = session('active_company_id');
            if (!$companyId) {
                throw new \Exception('Company ID tidak ditemukan');
            }

            $projects = Project::query()
                ->where('idcompany', $companyId)
                ->pluck('id');

            $query->whereIn('notas.idproject', $projects);
            return;
        }

        throw new \Exception('Module tidak dikenali');
    }

    /**
     * Bentuk struktur neraca dari data neraca saldo
     */
    private function buildNeracaFromSaldo(array $accounts): array
    {
        $neracaHdrList = \App\Models\NeracaHdr::query()->orderBy('id')->get();
        $neracaHdrMap = $neracaHdrList->keyBy('id');
        $blueprint = $this->getNeracaBlueprint();

        $aktivaRows = [];
        $pasivaRows = [];
        $aktivaGroups = [];
        $pasivaGroups = [];
        $labaRugiBerjalan = 0;
        $unmappedCount = 0;
        $unmappedAccounts = [];

        $addToGroup = function (array $row, array $category) use (&$aktivaGroups, &$pasivaGroups) {
            $targetSide = $category['side'];
            $groupKey = $category['key'] . '|' . $targetSide;
            $collection = &$aktivaGroups;
            if ($targetSide !== 'aktiva') {
                $collection = &$pasivaGroups;
            }

            if (!isset($collection[$groupKey])) {
                $collection[$groupKey] = [
                    'key' => $category['key'],
                    'rincian' => $category['label'],
                    'parent' => $category['parent'] ?? '-',
                    'parent_order' => $category['parent_order'] ?? 999,
                    'order' => $category['order'] ?? 9999,
                    'items' => [],
                    'subtotal_raw' => 0,
                    'subtotal' => '0'
                ];
            }

            $collection[$groupKey]['items'][] = $row;
            $collection[$groupKey]['subtotal_raw'] += $row['nilai_raw'];
        };

        foreach ($accounts as $account) {
            $kode = (string) ($account['kode'] ?? '');
            $nama = $account['nama_akun'] ?? '-';
            $debit = (float) ($account['debit_raw'] ?? 0);
            $kredit = (float) ($account['kredit_raw'] ?? 0);
            $idNeraca = $account['idneraca'] ?? null;

            // Pendapatan & Beban dialihkan menjadi laba/rugi berjalan
            if (str_starts_with($kode, '4') || str_starts_with($kode, '5')) {
                $labaRugiBerjalan += ($kredit - $debit);
                continue;
            }

            $category = $this->matchBlueprintCategory($account, $neracaHdrMap, $blueprint);

            if (!$category && $idNeraca && $neracaHdrMap->has($idNeraca)) {
                $neracaHdr = $neracaHdrMap->get($idNeraca);
                $resolvedSide = $this->resolveNeracaSide($neracaHdr);
                $category = [
                    'key' => 'NERACA-' . $neracaHdr->id,
                    'label' => (string) ($neracaHdr->rincian ?? '-'),
                    'side' => $resolvedSide,
                    'parent' => $resolvedSide === 'aktiva' ? 'Aktiva Lainnya' : 'Pasiva Lainnya',
                    'parent_order' => $resolvedSide === 'aktiva' ? 35 : 70,
                    'order' => (int) ($neracaHdr->id ?? 9999)
                ];
            }

            if (!$category) {
                $unmappedCount++;
                $unmappedAccounts[] = [
                    'kode' => $kode,
                    'nama_akun' => $nama
                ];
                continue;
            }

            $sidePreference = $category['side'];
            $targetSide = $sidePreference;

            // Tampilkan akun tetap di sisi naturalnya agar struktur neraca tidak berpindah kolom.
            // Jika saldo berlawanan, biarkan nilainya minus pada sisi yang sama.
            $amountSigned = $sidePreference === 'aktiva'
                ? ($debit - $kredit)
                : ($kredit - $debit);

            if (abs($amountSigned) < 0.5) {
                continue;
            }

            $row = [
                'kode' => $kode,
                'nama_akun' => $nama,
                'plotting' => $category['label'],
                'nilai_raw' => $amountSigned,
                'nilai' => number_format($amountSigned, 0, ',', '.'),
                'parent' => $category['parent'] ?? null
            ];

            if ($targetSide === 'aktiva') {
                $aktivaRows[] = $row;
            } else {
                $pasivaRows[] = $row;
            }

            $addToGroup($row, array_merge($category, ['side' => $targetSide]));
        }

        if (abs($labaRugiBerjalan) > 0.5) {
            $category = $blueprint['laba_ditahan'] ?? [
                'key' => 'LABA-DITAHAN',
                'label' => 'Laba Ditahan / Berjalan',
                'side' => 'pasiva',
                'parent' => 'Ekuitas',
                'parent_order' => 60,
                'order' => 3
            ];

            $row = [
                'kode' => '3-LR',
                'nama_akun' => $labaRugiBerjalan >= 0 ? 'Laba Berjalan' : 'Rugi Berjalan',
                'plotting' => $category['label'],
                'nilai_raw' => $labaRugiBerjalan,
                'nilai' => number_format($labaRugiBerjalan, 0, ',', '.'),
                'parent' => $category['parent']
            ];

            $targetSide = $category['side'];
            if ($targetSide === 'aktiva') {
                $aktivaRows[] = $row;
            } else {
                $pasivaRows[] = $row;
            }

            $addToGroup($row, $category);
        }

        usort($aktivaRows, fn($a, $b) => strcmp($a['kode'], $b['kode']));
        usort($pasivaRows, fn($a, $b) => strcmp($a['kode'], $b['kode']));

        foreach ($aktivaGroups as &$group) {
            usort($group['items'], fn($a, $b) => strcmp($a['kode'], $b['kode']));
            $group['subtotal'] = number_format($group['subtotal_raw'], 0, ',', '.');
        }
        unset($group);

        foreach ($pasivaGroups as &$group) {
            usort($group['items'], fn($a, $b) => strcmp($a['kode'], $b['kode']));
            $group['subtotal'] = number_format($group['subtotal_raw'], 0, ',', '.');
        }
        unset($group);

        uasort($aktivaGroups, function ($a, $b) {
            $p = ($a['parent_order'] ?? 999) <=> ($b['parent_order'] ?? 999);
            if ($p !== 0) return $p;
            $c = ($a['order'] ?? 9999) <=> ($b['order'] ?? 9999);
            if ($c !== 0) return $c;
            return strcmp($a['rincian'], $b['rincian']);
        });

        uasort($pasivaGroups, function ($a, $b) {
            $p = ($a['parent_order'] ?? 999) <=> ($b['parent_order'] ?? 999);
            if ($p !== 0) return $p;
            $c = ($a['order'] ?? 9999) <=> ($b['order'] ?? 9999);
            if ($c !== 0) return $c;
            return strcmp($a['rincian'], $b['rincian']);
        });

        $totalAktiva = array_sum(array_column($aktivaRows, 'nilai_raw'));
        $totalPasiva = array_sum(array_column($pasivaRows, 'nilai_raw'));

        return [
            'data' => [
                'aktiva' => $aktivaRows,
                'pasiva' => $pasivaRows,
                'aktiva_groups' => array_values($aktivaGroups),
                'pasiva_groups' => array_values($pasivaGroups)
            ],
            'summary' => [
                'total_aktiva_raw' => $totalAktiva,
                'total_pasiva_raw' => $totalPasiva,
                'total_aktiva' => number_format($totalAktiva, 0, ',', '.'),
                'total_pasiva' => number_format($totalPasiva, 0, ',', '.'),
                'balance' => abs($totalAktiva - $totalPasiva) < 0.5,
                'unmapped_accounts' => $unmappedCount,
                'unmapped_account_list' => $unmappedAccounts,
                'difference_raw' => abs($totalAktiva - $totalPasiva),
                'difference' => number_format(abs($totalAktiva - $totalPasiva), 0, ',', '.')
            ]
        ];
    }

    private function buildAktivaTemplate(string $module, string $endDate): array
    {
        if ($module === 'company') {
            $scopeId = (int) session('active_company_id');
            if (!$scopeId) {
                throw new \Exception('Company ID tidak ditemukan');
            }
        } elseif ($module === 'project') {
            $scopeId = (int) session('active_project_id');
            if (!$scopeId) {
                throw new \Exception('Project ID tidak ditemukan');
            }
        } else {
            throw new \Exception('Module tidak dikenali');
        }

        $kasBank = $this->getAktivaKasBank($module, $scopeId);
        $piutangUsaha = $this->getAktivaPiutangUsaha($module, $scopeId, $endDate);
        $uangMukaPembelian = $this->getAktivaByKodeTransaksi($module, $scopeId, $endDate, '2012');
        $sewaDibayarDimuka = $this->getAktivaByKodeTransaksi($module, $scopeId, $endDate, '2013');

        $groups = [
            $this->makeAktivaGroup('a.', 'Aktiva Lancar', 10, 1, [
                ['kode' => 'AL-01', 'nomor' => '1', 'nama_akun' => 'Kas dan Bank (saldo)', 'nilai_raw' => $kasBank],
                ['kode' => 'AL-02', 'nomor' => '2', 'nama_akun' => 'Piutang Usaha', 'nilai_raw' => $piutangUsaha],
                ['kode' => 'AL-03', 'nomor' => '3', 'nama_akun' => 'Biaya Dibayar Dimuka', 'nilai_raw' => 0],
                ['kode' => 'AL-04', 'nomor' => '4', 'nama_akun' => 'Uang Muka Pembelian', 'nilai_raw' => $uangMukaPembelian],
                ['kode' => 'AL-05', 'nomor' => '5', 'nama_akun' => 'Sewa Dibayar Dimuka', 'nilai_raw' => $sewaDibayarDimuka],
                ['kode' => 'AL-06', 'nomor' => '6', 'nama_akun' => 'Persediaan Real Estate (Tanah & Bangunan Siap Jual)', 'nilai_raw' => 0],
                ['kode' => 'AL-07', 'nomor' => '', 'nama_akun' => 'Bangunan', 'nilai_raw' => 0, 'indent' => 1],
                ['kode' => 'AL-08', 'nomor' => '', 'nama_akun' => 'Bahan Baku', 'nilai_raw' => 0, 'indent' => 1],
                ['kode' => 'AL-09', 'nomor' => '', 'nama_akun' => 'Tanah', 'nilai_raw' => 0, 'indent' => 1],
            ]),
        ];

        $rows = [];
        $total = 0;

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $rows[] = $item;
            }
            $total += $group['subtotal_raw'];
        }

        return [
            'rows' => $rows,
            'groups' => $groups,
            'total_raw' => $total,
        ];
    }

    private function makeAktivaGroup(string $prefix, string $label, int $parentOrder, int $order, array $items): array
    {
        $normalizedItems = array_map(function (array $item) use ($label) {
            $nilaiRaw = (float) ($item['nilai_raw'] ?? 0);
            $indent = (int) ($item['indent'] ?? 0);
            $namaAkun = (string) ($item['nama_akun'] ?? '-');

            if ($indent > 0) {
                $namaAkun = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $indent) . $namaAkun;
            }

            return [
                'kode' => (string) ($item['kode'] ?? ''),
                'nomor' => (string) ($item['nomor'] ?? ''),
                'nama_akun' => $namaAkun,
                'plotting' => $label,
                'nilai_raw' => $nilaiRaw,
                'nilai' => number_format($nilaiRaw, 0, ',', '.'),
                'parent' => 'Aktiva',
            ];
        }, $items);

        $subtotal = array_sum(array_column($normalizedItems, 'nilai_raw'));

        return [
            'key' => str_replace(' ', '_', strtolower($label)),
            'rincian' => $label,
            'prefix' => $prefix,
            'parent' => 'Aktiva',
            'parent_order' => $parentOrder,
            'order' => $order,
            'items' => $normalizedItems,
            'subtotal_raw' => $subtotal,
            'subtotal' => number_format($subtotal, 0, ',', '.'),
            'subtotal_label' => 'Sub Total ' . $label,
            'template_style' => true,
        ];
    }

    private function getAktivaKasBank(string $module, int $scopeId): float
    {
        if ($module === 'company') {
            return (float) DB::table('rekening')
                ->where('idcompany', $scopeId)
                ->whereNull('idproject')
                ->sum('saldo');
        }

        $rekenings = $this->getSaldoRekeningProject($scopeId);
        return (float) collect($rekenings)->sum('saldo_raw');
    }

    private function getAktivaPiutangUsaha(string $module, int $scopeId, string $endDate): float
    {
        $angsuranSubQuery = DB::table('angsuran')
            ->select('idnota', DB::raw('SUM(jumlah) as total_angsuran'))
            ->groupBy('idnota');

        $query = DB::table('notas')
            ->leftJoinSub($angsuranSubQuery, 'angsuran_total', function ($join) {
                $join->on('notas.id', '=', 'angsuran_total.idnota');
            })
            ->where('notas.cashflow', 'out')
            ->where('notas.paymen_method', 'tempo')
            ->where('notas.status', '!=', 'paid')
            ->whereDate('notas.tanggal', '<=', $endDate)
            ->whereNull('notas.deleted_at');

        if ($module === 'company') {
            $query->where('notas.idcompany', $scopeId)
                ->whereNull('notas.idproject');
        } else {
            $query->where('notas.idproject', $scopeId);
        }

        return (float) $query
            ->selectRaw('COALESCE(SUM(GREATEST(notas.total - COALESCE(angsuran_total.total_angsuran, 0), 0)), 0) as total_piutang')
            ->value('total_piutang');
    }

    private function getAktivaByKodeTransaksi(string $module, int $scopeId, string $endDate, string $kodeTransaksi): float
    {
        $query = DB::table('nota_transactions')
            ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
            ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
            ->where('kodetransaksi.kodetransaksi', $kodeTransaksi)
            ->where('notas.status', 'paid')
            ->whereDate('notas.tanggal', '<=', $endDate)
            ->whereNull('notas.deleted_at')
            ->whereNull('nota_transactions.deleted_at');

        if ($module === 'company') {
            $query->where('notas.idcompany', $scopeId)
                ->whereNull('notas.idproject');
        } else {
            $query->where('notas.idproject', $scopeId);
        }

        return (float) $query->sum('nota_transactions.total');
    }

    private function resolveNeracaSide($neraca): string
    {
        $isAktiva = $this->normalizeNeracaFlag($neraca->aktiva ?? null);
        $isPasiva = $this->normalizeNeracaFlag($neraca->pasiva ?? null);

        if ($isAktiva && !$isPasiva) {
            return 'aktiva';
        }

        if ($isPasiva && !$isAktiva) {
            return 'pasiva';
        }

        // Default aman: pasiva jika flag tidak jelas
        return 'pasiva';
    }

    private function normalizeNeracaFlag($value): bool
    {
        if (is_null($value)) {
            return false;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'y', 'yes', 'true', 'aktiva', 'asset', 'aset'], true);
    }

    /**
     * Blueprint kategori neraca sesuai kebutuhan user
     */
    private function getNeracaBlueprint(): array
    {
        $parentOrder = [
            'Aktiva Lancar' => 10,
            'Aktiva Tetap' => 20,
            'Aktiva Lancar Lainnya' => 30,
            'Hutang Jangka Pendek' => 40,
            'Hutang Jangka Panjang' => 50,
            'Ekuitas' => 60,
        ];

        return [
            'kas_bank' => [
                'key' => 'kas_bank',
                'label' => 'Kas dan Bank',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar',
                'parent_order' => $parentOrder['Aktiva Lancar'],
                'order' => 1,
                'keywords' => ['kas', 'bank', 'cash', 'rekening', 'giro', 'kas kecil'],
                'match_rekening' => true,
            ],
            'piutang_usaha' => [
                'key' => 'piutang_usaha',
                'label' => 'Piutang Usaha',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar',
                'parent_order' => $parentOrder['Aktiva Lancar'],
                'order' => 2,
                'keywords' => ['piutang usaha', 'piutang penjualan', 'penjualan tempo', 'tagihan progress', 'tagihan pembangunan', 'tagihan vendor', 'tagihan kontraktor', 'penyewaan', 'piutang sewa', 'piutang proyek'],
            ],
            'biaya_dimuka' => [
                'key' => 'biaya_dimuka',
                'label' => 'Biaya Dibayar Dimuka',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar',
                'parent_order' => $parentOrder['Aktiva Lancar'],
                'order' => 3,
                'keywords' => ['biaya dibayar dimuka', 'beban dibayar dimuka', 'prepaid expense', 'dibayar dimuka'],
            ],
            'uang_muka_pembelian' => [
                'key' => 'uang_muka_pembelian',
                'label' => 'Uang Muka Pembelian',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar',
                'parent_order' => $parentOrder['Aktiva Lancar'],
                'order' => 4,
                'keywords' => ['uang muka pembelian', 'dp pembelian', 'dp tanah', 'dp material', 'advance purchase', 'uang muka tanah'],
            ],
            'sewa_dimuka' => [
                'key' => 'sewa_dimuka',
                'label' => 'Sewa Dibayar Dimuka',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar',
                'parent_order' => $parentOrder['Aktiva Lancar'],
                'order' => 5,
                'keywords' => ['sewa dibayar dimuka', 'prepaid rent', 'sewa bayar dimuka'],
            ],
            'persediaan_real_estate' => [
                'key' => 'persediaan_real_estate',
                'label' => 'Persediaan Real Estate (Tanah & Bangunan Siap Jual)',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar',
                'parent_order' => $parentOrder['Aktiva Lancar'],
                'order' => 6,
                'keywords' => ['persediaan', 'inventory', 'stok', 'real estate', 'tanah', 'bangunan', 'bahan baku', 'material'],
            ],
            'tanah_tetap' => [
                'key' => 'tanah_tetap',
                'label' => 'Tanah',
                'side' => 'aktiva',
                'parent' => 'Aktiva Tetap',
                'parent_order' => $parentOrder['Aktiva Tetap'],
                'order' => 1,
                'keywords' => ['tanah', 'lahan'],
            ],
            'bangunan_tetap' => [
                'key' => 'bangunan_tetap',
                'label' => 'Bangunan',
                'side' => 'aktiva',
                'parent' => 'Aktiva Tetap',
                'parent_order' => $parentOrder['Aktiva Tetap'],
                'order' => 2,
                'keywords' => ['bangunan', 'gedung'],
            ],
            'inventaris_kantor' => [
                'key' => 'inventaris_kantor',
                'label' => 'Inventaris Kantor',
                'side' => 'aktiva',
                'parent' => 'Aktiva Tetap',
                'parent_order' => $parentOrder['Aktiva Tetap'],
                'order' => 3,
                'keywords' => ['inventaris', 'furniture', 'perabot'],
            ],
            'kendaraan' => [
                'key' => 'kendaraan',
                'label' => 'Kendaraan',
                'side' => 'aktiva',
                'parent' => 'Aktiva Tetap',
                'parent_order' => $parentOrder['Aktiva Tetap'],
                'order' => 4,
                'keywords' => ['kendaraan', 'mobil', 'motor', 'truck', 'truk'],
            ],
            'peralatan_kantor' => [
                'key' => 'peralatan_kantor',
                'label' => 'Peralatan Kantor',
                'side' => 'aktiva',
                'parent' => 'Aktiva Tetap',
                'parent_order' => $parentOrder['Aktiva Tetap'],
                'order' => 5,
                'keywords' => ['peralatan kantor', 'alat kantor', 'komputer', 'printer', 'laptop', 'elektronik'],
            ],
            'peralatan_proyek' => [
                'key' => 'peralatan_proyek',
                'label' => 'Peralatan Proyek',
                'side' => 'aktiva',
                'parent' => 'Aktiva Tetap',
                'parent_order' => $parentOrder['Aktiva Tetap'],
                'order' => 6,
                'keywords' => ['peralatan proyek', 'alat proyek', 'alat berat', 'mesin', 'tool', 'scaffold'],
            ],
            'akumulasi_penyusutan' => [
                'key' => 'akumulasi_penyusutan',
                'label' => 'Akumulasi Penyusutan (-)',
                'side' => 'aktiva',
                'parent' => 'Aktiva Tetap',
                'parent_order' => $parentOrder['Aktiva Tetap'],
                'order' => 99,
                'keywords' => ['akumulasi', 'penyusutan', 'depresiasi'],
                'is_contra' => true,
            ],
            'piutang_pengurus' => [
                'key' => 'piutang_pengurus',
                'label' => 'Piutang Pengurus',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar Lainnya',
                'parent_order' => $parentOrder['Aktiva Lancar Lainnya'],
                'order' => 1,
                'keywords' => ['piutang pengurus', 'piutang direktur', 'piutang owner', 'piutang mas edy', 'piutang mas ipul', 'piutang ghozali', 'piutang amal', 'piutang next project'],
            ],
            'piutang_karyawan' => [
                'key' => 'piutang_karyawan',
                'label' => 'Piutang Karyawan',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar Lainnya',
                'parent_order' => $parentOrder['Aktiva Lancar Lainnya'],
                'order' => 2,
                'keywords' => ['piutang karyawan', 'kasbon', 'pinjaman karyawan', 'employee loan'],
            ],
            'piutang_lainnya' => [
                'key' => 'piutang_lainnya',
                'label' => 'Piutang Lainnya',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar Lainnya',
                'parent_order' => $parentOrder['Aktiva Lancar Lainnya'],
                'order' => 3,
                'keywords' => ['piutang konsumen', 'piutang pihak lain', 'piutang lainnya', 'piutang lain'],
            ],
            'piutang_antar_perusahaan' => [
                'key' => 'piutang_antar_perusahaan',
                'label' => 'Piutang Antar Perusahaan',
                'side' => 'aktiva',
                'parent' => 'Aktiva Lancar Lainnya',
                'parent_order' => $parentOrder['Aktiva Lancar Lainnya'],
                'order' => 4,
                'keywords' => ['piutang antar perusahaan', 'piutang perusahaan', 'piutang group', 'piutang afiliasi', 'intercompany'],
            ],
            'hutang_usaha_pendek' => [
                'key' => 'hutang_usaha_pendek',
                'label' => 'Hutang Usaha',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Pendek',
                'parent_order' => $parentOrder['Hutang Jangka Pendek'],
                'order' => 1,
                'keywords' => ['hutang usaha', 'utang usaha', 'hutang vendor', 'hutang kontraktor', 'hutang jasa', 'hutang supplier', 'hutang pemasok'],
            ],
            'hutang_bank_pendek' => [
                'key' => 'hutang_bank_pendek',
                'label' => 'Hutang Bank',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Pendek',
                'parent_order' => $parentOrder['Hutang Jangka Pendek'],
                'order' => 2,
                'keywords' => ['hutang bank', 'utang bank', 'pinjaman bank', 'kredit bank', 'overdraft'],
            ],
            'hutang_pembiayaan_pendek' => [
                'key' => 'hutang_pembiayaan_pendek',
                'label' => 'Hutang Pembiayaan / Kredit Modal Kerja',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Pendek',
                'parent_order' => $parentOrder['Hutang Jangka Pendek'],
                'order' => 3,
                'keywords' => ['pembiayaan', 'kredit modal kerja', 'leasing', 'kmk', 'btm', 'bprs', 'weleri', 'bkk', 'binama', 'kjks'],
            ],
            'hutang_pajak_pendek' => [
                'key' => 'hutang_pajak_pendek',
                'label' => 'Hutang Pajak',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Pendek',
                'parent_order' => $parentOrder['Hutang Jangka Pendek'],
                'order' => 4,
                'keywords' => ['hutang pajak', 'utang pajak', 'ppn', 'pph', 'bphtb', 'pbb', 'tax payable'],
            ],
            'hutang_aset_pendek' => [
                'key' => 'hutang_aset_pendek',
                'label' => 'Hutang Aset',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Pendek',
                'parent_order' => $parentOrder['Hutang Jangka Pendek'],
                'order' => 5,
                'keywords' => ['hutang aset', 'utang aset', 'hutang pembelian aset'],
            ],
            'uang_muka_diterima' => [
                'key' => 'uang_muka_diterima',
                'label' => 'Uang Muka yang Diterima (Pendapatan Diterima Dimuka)',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Pendek',
                'parent_order' => $parentOrder['Hutang Jangka Pendek'],
                'order' => 6,
                'keywords' => ['uang muka diterima', 'booking fee', 'dp', 'pendapatan diterima dimuka', 'advance received', 'uang muka penjualan'],
            ],
            'hutang_lain_pendek' => [
                'key' => 'hutang_lain_pendek',
                'label' => 'Hutang Lain-Lain',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Pendek',
                'parent_order' => $parentOrder['Hutang Jangka Pendek'],
                'order' => 7,
                'keywords' => ['hutang sewa', 'hutang bunga', 'hutang dividen', 'hutang karyawan', 'hutang gaji', 'hutang pengurus', 'pinjam', 'hutang lain'],
            ],
            'hutang_usaha_panjang' => [
                'key' => 'hutang_usaha_panjang',
                'label' => 'Hutang Usaha (Jangka Panjang)',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Panjang',
                'parent_order' => $parentOrder['Hutang Jangka Panjang'],
                'order' => 1,
                'keywords' => ['hutang usaha jangka panjang', 'utang usaha jangka panjang', 'long term account payable'],
            ],
            'hutang_bank_panjang' => [
                'key' => 'hutang_bank_panjang',
                'label' => 'Hutang Bank (Jangka Panjang)',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Panjang',
                'parent_order' => $parentOrder['Hutang Jangka Panjang'],
                'order' => 2,
                'keywords' => ['jangka panjang', 'long term loan', 'lt loan', 'hutang bank jangka panjang', 'pinjaman bank jangka panjang'],
            ],
            'hutang_pembiayaan_panjang' => [
                'key' => 'hutang_pembiayaan_panjang',
                'label' => 'Hutang Pembiayaan (Jangka Panjang)',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Panjang',
                'parent_order' => $parentOrder['Hutang Jangka Panjang'],
                'order' => 3,
                'keywords' => ['pembiayaan jangka panjang', 'long term financing', 'leasing jangka panjang'],
            ],
            'hutang_pajak_panjang' => [
                'key' => 'hutang_pajak_panjang',
                'label' => 'Hutang Pajak (Jangka Panjang)',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Panjang',
                'parent_order' => $parentOrder['Hutang Jangka Panjang'],
                'order' => 4,
                'keywords' => ['hutang pajak jangka panjang', 'tax long term'],
            ],
            'hutang_aset_panjang' => [
                'key' => 'hutang_aset_panjang',
                'label' => 'Hutang Aset (Jangka Panjang)',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Panjang',
                'parent_order' => $parentOrder['Hutang Jangka Panjang'],
                'order' => 5,
                'keywords' => ['hutang aset jangka panjang', 'utang aset jangka panjang'],
            ],
            'hutang_lain_panjang' => [
                'key' => 'hutang_lain_panjang',
                'label' => 'Hutang Lain - lain (Jangka Panjang)',
                'side' => 'pasiva',
                'parent' => 'Hutang Jangka Panjang',
                'parent_order' => $parentOrder['Hutang Jangka Panjang'],
                'order' => 6,
                'keywords' => ['hutang jangka panjang lainnya', 'utang jangka panjang lainnya', 'long term payable'],
            ],
            'modal_disetor' => [
                'key' => 'modal_disetor',
                'label' => 'Modal Disetor',
                'side' => 'pasiva',
                'parent' => 'Ekuitas',
                'parent_order' => $parentOrder['Ekuitas'],
                'order' => 1,
                'keywords' => ['modal disetor', 'modal setor', 'paid up capital'],
            ],
            'laba_ditahan' => [
                'key' => 'laba_ditahan',
                'label' => 'Laba Ditahan',
                'side' => 'pasiva',
                'parent' => 'Ekuitas',
                'parent_order' => $parentOrder['Ekuitas'],
                'order' => 2,
                'keywords' => ['laba ditahan', 'retained earning'],
            ],
        ];
    }

    /**
     * Mapping akun ke kategori blueprint neraca
     */
    private function matchBlueprintCategory(array $account, $neracaHdrMap, array $blueprint): ?array
    {
        $name = strtolower((string) ($account['nama_akun'] ?? ''));
        $kode = strtolower((string) ($account['kode'] ?? ''));
        $isRekening = (bool) ($account['is_rekening'] ?? false);
        $idNeraca = $account['idneraca'] ?? null;
        $neracaName = null;
        $isLongTerm = str_contains($name, 'jangka panjang') || str_contains($name, 'long term');

        if ($idNeraca && $neracaHdrMap->has($idNeraca)) {
            $neracaName = strtolower((string) ($neracaHdrMap->get($idNeraca)->rincian ?? ''));
            $isLongTerm = $isLongTerm || str_contains($neracaName, 'jangka panjang') || str_contains($neracaName, 'long term');
        }

        foreach ($blueprint as $key => $def) {
            if (!isset($def['key'])) {
                $def['key'] = $key;
            }

            if (!empty($def['match_rekening']) && $isRekening) {
                return $def;
            }

            $parentLabel = strtolower((string) ($def['parent'] ?? ''));
            $isCategoryLong = str_contains($parentLabel, 'jangka panjang');
            $isCategoryShort = str_contains($parentLabel, 'jangka pendek');

            if ($isLongTerm && $isCategoryShort) {
                continue;
            }

            if (!$isLongTerm && $isCategoryLong) {
                continue;
            }

            if (!empty($def['codes'])) {
                foreach ($def['codes'] as $prefix) {
                    if (str_starts_with($kode, strtolower($prefix))) {
                        return $def;
                    }
                }
            }

            foreach ($def['keywords'] ?? [] as $kw) {
                if ($kw === '') {
                    continue;
                }

                if (str_contains($name, $kw) || ($neracaName && str_contains($neracaName, $kw))) {
                    return $def;
                }
            }
        }

        return null;
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
                    'idkodetransaksi' => $coa->id,
                    'idneraca' => $coa->idneraca,
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
                    'idkodetransaksi' => $coa->id,
                    'idneraca' => $coa->idneraca,
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

    // ==========================
    // LAPORAN PRESENSI VISIT
    // ==========================
    public function rekapVisit()
    {
        $bulan = now()->format('m');
        $tahun = now()->format('Y');
        return view('hris.laporan.rekap_visit', compact('bulan', 'tahun'));
    }

    public function rekapVisitData(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));

        $awal = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $akhir = $awal->copy()->endOfMonth();

        // Ambil data pegawai aktif
        $pegawaiList = User::with('unitkerja')
            ->where('status', 'aktif')
            ->whereHas('pegawaiDtl')
            ->get();

        $data = [];

        foreach ($pegawaiList as $p) {
            // Ambil data presensi visit berdasarkan bulan dan tahun
            $presensiVisit = DB::table('presensi_visit')
                ->where('nik', $p->nik)
                ->whereYear('tgl_presensi', $tahun)
                ->whereMonth('tgl_presensi', $bulan)
                ->orderBy('tgl_presensi')
                ->orderBy('jam_in')
                ->get();

            // Kelompokkan berdasarkan tanggal
            $presensiByDate = $presensiVisit->groupBy('tgl_presensi');

            $totalHariVisit = 0;
            
            foreach ($presensiByDate as $tgl => $presensiHari) {
                $visitMasuk = $presensiHari->where('inoutmode', 1)->first();
                $visitPulang = $presensiHari->where('inoutmode', 2)->first();

                // Hitung total hari visit (jika ada masuk dan pulang)
                if ($visitMasuk && $visitPulang) {
                    $totalHariVisit++;
                }
            }

            $data[] = [
                'nik' => $p->nik,
                'nama' => $p->name,
                'unitkerja' => optional($p->unitkerja)->company_name ?? '-',
                'total_hari_visit' => $totalHariVisit,
            ];
        }

        // Untuk DataTables server-side, kita perlu mengembalikan format khusus
        if ($request->ajax() && $request->has('draw')) {
            // DataTables server-side processing
            $draw = $request->input('draw');
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            
            // Pagination manual
            $totalRecords = count($data);
            $paginatedData = array_slice($data, $start, $length);
            
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $paginatedData
            ]);
        }
        
        // Untuk non-Datatables request (backward compatibility)
        return response()->json(['data' => $data]);
    }

    // ==========================
    // MONITORING PRESENSI VISIT
    // ==========================
    public function monitoringVisit()
    {
        return view('hris.laporan.monitoring_visit');
    }

    public function monitoringVisitData(Request $request)
    {
        $tanggal = $request->tanggal ?? date('Y-m-d');

        $data = DB::table('presensi_visit as pv')
            ->select(
                'pv.nik',
                'u.nip',
                'u.name',
                'cu.company_name',
                DB::raw('MAX(CASE WHEN pv.inoutmode = 1 THEN pv.jam_in END) as visit_masuk'),
                DB::raw('MAX(CASE WHEN pv.inoutmode = 2 THEN pv.jam_in END) as visit_pulang'),
                DB::raw('MAX(CASE WHEN pv.inoutmode = 3 THEN pv.jam_in END) as lembur_masuk'),
                DB::raw('MAX(CASE WHEN pv.inoutmode = 4 THEN pv.jam_in END) as lembur_pulang'),
                DB::raw('GROUP_CONCAT(DISTINCT CASE WHEN pv.inoutmode = 1 THEN pv.keterangan END) as keterangan_masuk'),
                DB::raw('GROUP_CONCAT(DISTINCT CASE WHEN pv.inoutmode = 2 THEN pv.keterangan END) as keterangan_pulang'),
                DB::raw('MAX(CASE WHEN pv.inoutmode = 1 THEN pv.foto_in END) as foto_masuk'),
                DB::raw('MAX(CASE WHEN pv.inoutmode = 2 THEN pv.foto_in END) as foto_pulang'),
                DB::raw('MAX(CASE WHEN pv.inoutmode = 1 THEN pv.lokasi END) as lokasi_masuk'),
                DB::raw('MAX(CASE WHEN pv.inoutmode = 2 THEN pv.lokasi END) as lokasi_pulang')
            )
            ->join('users as u', 'pv.nik', '=', 'u.nik')
            ->leftJoin('company_units as cu', 'u.id_unitkerja', '=', 'cu.id')
            ->where('pv.tgl_presensi', $tanggal)
            ->groupBy('pv.nik', 'u.nip', 'u.name', 'cu.company_name')
            ->orderBy('u.name')
            ->get();

        // Format data untuk response
        $formattedData = $data->map(function ($item) {
            // Hitung durasi visit jika ada masuk dan pulang
            $durasiVisit = null;
            if ($item->visit_masuk && $item->visit_pulang) {
                $masuk = Carbon::parse($item->visit_masuk);
                $pulang = Carbon::parse($item->visit_pulang);
                $durasiVisit = $masuk->diff($pulang)->format('%H jam %I menit');
            }

            // Hitung durasi lembur jika ada
            $durasiLembur = null;
            if ($item->lembur_masuk && $item->lembur_pulang) {
                $lemburIn = Carbon::parse($item->lembur_masuk);
                $lemburOut = Carbon::parse($item->lembur_pulang);
                $durasiLembur = $lemburIn->diff($lemburOut)->format('%H jam %I menit');
            }

            return [
                'nik' => $item->nik,
                'nip' => $item->nip,
                'nama' => $item->name,
                'unitkerja' => $item->company_name,
                'visit_masuk' => $item->visit_masuk ? Carbon::parse($item->visit_masuk)->format('H:i') : '-',
                'visit_pulang' => $item->visit_pulang ? Carbon::parse($item->visit_pulang)->format('H:i') : '-',
                'durasi_visit' => $durasiVisit ?? '-',
                'lembur_masuk' => $item->lembur_masuk ? Carbon::parse($item->lembur_masuk)->format('H:i') : '-',
                'lembur_pulang' => $item->lembur_pulang ? Carbon::parse($item->lembur_pulang)->format('H:i') : '-',
                'durasi_lembur' => $durasiLembur ?? '-',
                'keterangan_masuk' => $item->keterangan_masuk ?? '-',
                'keterangan_pulang' => $item->keterangan_pulang ?? '-',
                'lokasi_masuk' => $item->lokasi_masuk ?? '-',
                'lokasi_pulang' => $item->lokasi_pulang ?? '-',
                'foto_masuk' => $item->foto_masuk,
                'foto_pulang' => $item->foto_pulang,
                'status' => $item->visit_masuk ? ($item->visit_pulang ? 'Complete' : 'Belum Pulang') : 'Belum Presensi'
            ];
        });

        return response()->json(['data' => $formattedData]);
    }

    // ==========================
    // DETAIL PRESENSI VISIT PER PEGAWAI
    // ==========================
    public function detailVisit(Request $request)
    {
        $nik = $request->nik;
        $bulan = $request->bulan ?? now()->format('m');
        $tahun = $request->tahun ?? now()->format('Y');
        
        $pegawai = User::with('unitkerja')->where('nik', $nik)->first();
        
        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan'
            ]);
        }

        $awal = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $akhir = $awal->copy()->endOfMonth();

        // Ambil data presensi visit
        $presensiVisit = DB::table('presensi_visit')
            ->where('nik', $nik)
            ->whereYear('tgl_presensi', $tahun)
            ->whereMonth('tgl_presensi', $bulan)
            ->orderBy('tgl_presensi')
            ->orderBy('jam_in')
            ->get();

        // Kelompokkan berdasarkan tanggal
        $presensiByDate = $presensiVisit->groupBy('tgl_presensi');

        $detailPresensi = [];
        $totalHariVisit = 0;
        $totalJamLembur = 0;

        foreach ($presensiByDate as $tgl => $presensiHari) {
            $visitMasuk = $presensiHari->where('inoutmode', 1)->first();
            $visitPulang = $presensiHari->where('inoutmode', 2)->first();
            $lemburMasuk = $presensiHari->where('inoutmode', 3)->first();
            $lemburPulang = $presensiHari->where('inoutmode', 4)->first();

            // Hitung durasi visit
            $durasiVisit = null;
            $durasiJam = 0;
            if ($visitMasuk && $visitPulang) {
                $jamMasuk = Carbon::parse($visitMasuk->jam_in);
                $jamPulang = Carbon::parse($visitPulang->jam_in);
                $durasiJam = $jamMasuk->diffInHours($jamPulang);
                $durasiVisit = $jamMasuk->diff($jamPulang)->format('%H:%I');
                $totalHariVisit++;
            }

            // Hitung durasi lembur
            $durasiLembur = null;
            $lemburJam = 0;
            if ($lemburMasuk && $lemburPulang) {
                $lemburIn = Carbon::parse($lemburMasuk->jam_in);
                $lemburOut = Carbon::parse($lemburPulang->jam_in);
                $lemburJam = $lemburIn->diffInHours($lemburOut);
                $durasiLembur = $lemburIn->diff($lemburOut)->format('%H:%I');
                $totalJamLembur += $lemburJam;
            }

            $detailPresensi[] = [
                'tanggal' => Carbon::parse($tgl)->format('d/m/Y'),
                'hari' => Carbon::parse($tgl)->translatedFormat('l'),
                'visit_masuk' => $visitMasuk ? Carbon::parse($visitMasuk->jam_in)->format('H:i') : '-',
                'visit_pulang' => $visitPulang ? Carbon::parse($visitPulang->jam_in)->format('H:i') : '-',
                'durasi_visit' => $durasiVisit ?? '-',
                'durasi_jam' => $durasiJam,
                'lembur_masuk' => $lemburMasuk ? Carbon::parse($lemburMasuk->jam_in)->format('H:i') : '-',
                'lembur_pulang' => $lemburPulang ? Carbon::parse($lemburPulang->jam_in)->format('H:i') : '-',
                'durasi_lembur' => $durasiLembur ?? '-',
                'durasi_lembur_jam' => $lemburJam,
                'keterangan' => $visitMasuk->keterangan ?? ($visitPulang->keterangan ?? '-'),
                'lokasi' => $visitMasuk->lokasi ?? ($visitPulang->lokasi ?? '-'),
                'foto_masuk' => $visitMasuk ? asset('storage/uploads/visit/' . $visitMasuk->foto_in) : null,
                'foto_pulang' => $visitPulang ? asset('storage/uploads/visit/' . $visitPulang->foto_in) : null,
                'foto_lembur_masuk' => $lemburMasuk ? asset('storage/uploads/visit/' . $lemburMasuk->foto_in) : null,
                'foto_lembur_pulang' => $lemburPulang ? asset('storage/uploads/visit/' . $lemburPulang->foto_in) : null
            ];
        }

        // Statistik
        $statistik = [
            'total_hari_visit' => $totalHariVisit,
            'total_jam_lembur' => sprintf('%02d:%02d', floor($totalJamLembur), fmod($totalJamLembur, 1) * 60),
            'rata_rata_jam_per_hari' => $totalHariVisit > 0 ? number_format(array_sum(array_column($detailPresensi, 'durasi_jam')) / $totalHariVisit, 2) : 0,
            'persentase_hadir' => $totalHariVisit > 0 ? round(($totalHariVisit / $awal->daysInMonth) * 100, 2) : 0
        ];

        return response()->json([
            'success' => true,
            'pegawai' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->name,
                'unitkerja' => optional($pegawai->unitkerja)->company_name ?? '-',
                'jabatan' => $pegawai->jabatan ?? '-'
            ],
            'periode' => [
                'bulan' => Carbon::createFromDate($tahun, $bulan, 1)->translatedFormat('F'),
                'tahun' => $tahun,
                'range' => $awal->format('d/m/Y') . ' - ' . $akhir->format('d/m/Y')
            ],
            'statistik' => $statistik,
            'detail_presensi' => $detailPresensi,
            'total_data' => count($detailPresensi)
        ]);
    }

    // ==========================
    // EXPORT LAPORAN PRESENSI VISIT
    // ==========================
    public function exportVisitExcel(Request $request)
    {
        $bulan = $request->bulan ?? now()->format('m');
        $tahun = $request->tahun ?? now()->format('Y');
        $nik = $request->nik; // jika ingin export per pegawai

        // Ambil data berdasarkan parameter
        if ($nik) {
            $data = $this->getDetailVisitForExport($nik, $bulan, $tahun);
            $filename = "laporan-visit-{$nik}-{$bulan}-{$tahun}.xlsx";
        } else {
            $data = $this->getAllVisitForExport($bulan, $tahun);
            $filename = "laporan-visit-all-{$bulan}-{$tahun}.xlsx";
        }

        // Generate Excel
        return Excel::download(new VisitExport($data), $filename);
    }

    private function getDetailVisitForExport($nik, $bulan, $tahun)
    {
        $pegawai = User::where('nik', $nik)->first();
        
        $presensiVisit = DB::table('presensi_visit')
            ->where('nik', $nik)
            ->whereYear('tgl_presensi', $tahun)
            ->whereMonth('tgl_presensi', $bulan)
            ->orderBy('tgl_presensi')
            ->orderBy('jam_in')
            ->get();

        $grouped = $presensiVisit->groupBy('tgl_presensi');

        $result = [];
        foreach ($grouped as $tgl => $items) {
            $visitMasuk = $items->where('inoutmode', 1)->first();
            $visitPulang = $items->where('inoutmode', 2)->first();
            $lemburMasuk = $items->where('inoutmode', 3)->first();
            $lemburPulang = $items->where('inoutmode', 4)->first();

            $durasiVisit = null;
            if ($visitMasuk && $visitPulang) {
                $masuk = Carbon::parse($visitMasuk->jam_in);
                $pulang = Carbon::parse($visitPulang->jam_in);
                $durasiVisit = $masuk->diff($pulang)->format('%H:%I');
            }

            $result[] = [
                'NIK' => $pegawai->nik ?? $nik,
                'Nama' => $pegawai->name ?? '-',
                'Tanggal' => Carbon::parse($tgl)->format('d/m/Y'),
                'Visit Masuk' => $visitMasuk ? Carbon::parse($visitMasuk->jam_in)->format('H:i') : '-',
                'Visit Pulang' => $visitPulang ? Carbon::parse($visitPulang->jam_in)->format('H:i') : '-',
                'Durasi Visit' => $durasiVisit ?? '-',
                'Lembur Masuk' => $lemburMasuk ? Carbon::parse($lemburMasuk->jam_in)->format('H:i') : '-',
                'Lembur Pulang' => $lemburPulang ? Carbon::parse($lemburPulang->jam_in)->format('H:i') : '-',
                'Keterangan' => $visitMasuk->keterangan ?? ($visitPulang->keterangan ?? '-'),
                'Lokasi' => $visitMasuk->lokasi ?? ($visitPulang->lokasi ?? '-')
            ];
        }

        return $result;
    }

    private function getAllVisitForExport($bulan, $tahun)
    {
        $pegawaiList = User::with('unitkerja')
            ->where('status', 'aktif')
            ->get();

        $result = [];
        
        foreach ($pegawaiList as $pegawai) {
            $presensiVisit = DB::table('presensi_visit')
                ->where('nik', $pegawai->nik)
                ->whereYear('tgl_presensi', $tahun)
                ->whereMonth('tgl_presensi', $bulan)
                ->get();

            $grouped = $presensiVisit->groupBy('tgl_presensi');

            foreach ($grouped as $tgl => $items) {
                $visitMasuk = $items->where('inoutmode', 1)->first();
                $visitPulang = $items->where('inoutmode', 2)->first();

                $durasiVisit = null;
                if ($visitMasuk && $visitPulang) {
                    $masuk = Carbon::parse($visitMasuk->jam_in);
                    $pulang = Carbon::parse($visitPulang->jam_in);
                    $durasiVisit = $masuk->diff($pulang)->format('%H:%I');
                }

                $result[] = [
                    'NIK' => $pegawai->nik,
                    'Nama' => $pegawai->name,
                    'Unit Kerja' => optional($pegawai->unitkerja)->company_name ?? '-',
                    'Tanggal' => Carbon::parse($tgl)->format('d/m/Y'),
                    'Visit Masuk' => $visitMasuk ? Carbon::parse($visitMasuk->jam_in)->format('H:i') : '-',
                    'Visit Pulang' => $visitPulang ? Carbon::parse($visitPulang->jam_in)->format('H:i') : '-',
                    'Durasi Visit' => $durasiVisit ?? '-',
                    'Keterangan' => $visitMasuk->keterangan ?? ($visitPulang->keterangan ?? '-'),
                    'Lokasi' => $visitMasuk->lokasi ?? ($visitPulang->lokasi ?? '-')
                ];
            }
        }

        return $result;
    }

    // ==========================
    // VIEW FOTO PRESENSI VISIT
    // ==========================
    public function viewFotoVisit(Request $request)
    {
        $filename = $request->filename;
        
        if (!$filename) {
            return response()->json([
                'success' => false,
                'message' => 'Nama file tidak valid'
            ]);
        }

        $path = storage_path('app/public/uploads/visit/' . $filename);
        
        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File foto tidak ditemukan'
            ]);
        }

        $image = base64_encode(file_get_contents($path));
        $type = pathinfo($path, PATHINFO_EXTENSION);
        
        return response()->json([
            'success' => true,
            'image' => 'data:image/' . $type . ';base64,' . $image,
            'filename' => $filename
        ]);
    }

}
