<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Nota;
use App\Models\NotaTransaction;
use App\Models\Cashflow;
use App\Models\Rekening;
use App\Models\KodeTransaksi;
use App\Models\Project;
use App\Models\User;
use App\Models\Presensi;
use App\Models\PengajuanIzin;
use App\Models\Payroll;
use App\Models\Jadwal;
use App\Models\CompanyUnit;
use Carbon\Carbon;
use App\Models\Unit;
use App\Models\UnitDetail;
use App\Models\Booking;
use App\Models\Penjualan;
use App\Models\Customer;
use Yajra\DataTables\Facades\DataTables;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        $activeModule = session('active_project_module');
        
        // Tampilkan dashboard sesuai module yang dipilih
        switch($activeModule) {
            case 'project':
                return $this->dashboardProject();
            case 'hris':
                return $this->dashboardHRIS();
            case 'agency':
                return $this->dashboardMarketing();
            default:
                // Default ke project dashboard
            return view('dashboard');
        }
    }

    /**
     * Dashboard untuk module Project
     */
    private function dashboardProject()
    {
        $projectId = session('active_project_id');
        $companyId = session('active_company_id');
        
        if (!$projectId) {
            return redirect()->route('choose.project')
                ->with('error', 'Silakan pilih project terlebih dahulu.');
        }
        
        $project = Project::with('companyUnit')->find($projectId);
        
        $grafikTransaksi = $this->getGrafikTransaksi($projectId);
        $saldoRekening = $this->getSaldoRekeningForProject($projectId, $companyId);
        $cashflowDetail = $this->getCashflowDetail($projectId);
        $ringkasan = $this->getRingkasan($projectId);
        
        $projectInfo = [
            'nama' => $project->namaproject ?? 'Tidak diketahui',
            'company' => $project->companyUnit->company_name ?? 'Tidak diketahui',
            'module' => 'Project'
        ];

        return view('dashboard.project', compact(
            'grafikTransaksi',
            'saldoRekening', 
            'cashflowDetail',
            'ringkasan',
            'projectInfo'
        ));
    }

    /**
     * 1. Grafik transaksi terbanyak sampai sedikit berdasarkan COA (untuk project)
     */
    private function getGrafikTransaksi($projectId)
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $transaksi = NotaTransaction::select([
                'kodetransaksi.id',
                'kodetransaksi.kodetransaksi',
                'kodetransaksi.transaksi',
                DB::raw('SUM(nota_transactions.total) as total_nominal'),
                DB::raw('COUNT(nota_transactions.id) as jumlah_transaksi')
            ])
            ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
            ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
            ->where('notas.idproject', $projectId)
            ->whereBetween('notas.tanggal', [$startDate, $endDate])
            ->groupBy('kodetransaksi.id', 'kodetransaksi.kodetransaksi', 'kodetransaksi.transaksi')
            ->orderByDesc('total_nominal')
            ->limit(10)
            ->get();

        $chartData = [
            'labels' => [],
            'data' => [],
            'colors' => [],
            'jenis' => [],
            'detail' => []
        ];

        foreach ($transaksi as $item) {
            $label = $item->kodetransaksi . ' - ' . ($item->transaksi ?? 'Unknown');
            $chartData['labels'][] = $label;
            $chartData['data'][] = (float) $item->total_nominal;
            $chartData['jenis'][] = $item->transaksi ?? 'lainnya';
            $chartData['detail'][] = [
                'kode' => $item->kodetransaksi,
                'nama' => $item->transaksi ?? 'Unknown',
                'total' => number_format($item->total_nominal, 0, ',', '.'),
                'jumlah' => $item->jumlah_transaksi,
                'jenis' => $item->transaksi ?? 'lainnya'
            ];

            $jenis = strtolower($item->transaksi ?? '');
            if (str_contains($jenis, 'pendapatan') || str_contains($jenis, 'income')) {
                $chartData['colors'][] = '#28a745';
            } elseif (str_contains($jenis, 'beban') || str_contains($jenis, 'expense')) {
                $chartData['colors'][] = '#dc3545';
            } else {
                $chartData['colors'][] = '#6c757d';
            }
        }

        return $chartData;
    }

    /**
     * 2. Rincian saldo rekening untuk project
     */
    private function getSaldoRekeningForProject($projectId, $companyId)
    {
        $rekenings = Rekening::select([
                'rekening.idrek',
                'rekening.norek',
                'rekening.namarek',
                'rekening.saldo',
                'rekening.idproject',
                'rekening.idcompany',
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
            ->get();

        $totalSaldo = $rekenings->sum('saldo');
        $projectRekenings = $rekenings->where('rekening_type', 'project');
        $companyRekenings = $rekenings->where('rekening_type', 'company');

        $formattedData = [
            'rekenings' => $rekenings->map(function($rekening) {
                return [
                    'id' => $rekening->idrek,
                    'norek' => $rekening->norek,
                    'nama' => $rekening->namarek,
                    'saldo' => number_format($rekening->saldo, 0, ',', '.'),
                    'saldo_raw' => $rekening->saldo,
                    'type' => $rekening->rekening_type,
                    'type_label' => $rekening->rekening_type == 'project' ? 'Project' : 'Company',
                    'type_badge' => $rekening->rekening_type == 'project' ? 'primary' : 'success'
                ];
            }),
            'total_saldo' => number_format($totalSaldo, 0, ',', '.'),
            'total_saldo_raw' => $totalSaldo,
            'jumlah_rekening' => $rekenings->count(),
            'summary' => [
                'project' => [
                    'count' => $projectRekenings->count(),
                    'total' => number_format($projectRekenings->sum('saldo'), 0, ',', '.')
                ],
                'company' => [
                    'count' => $companyRekenings->count(),
                    'total' => number_format($companyRekenings->sum('saldo'), 0, ',', '.')
                ]
            ]
        ];

        return $formattedData;
    }

    /**
     * 3. Cashflow detail tiap rekening untuk project
     */
    private function getCashflowDetail($projectId)
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $cashflows = Cashflow::select([
                'cashflows.idrek',
                'rekening.norek',
                'rekening.namarek',
                'cashflows.tanggal',
                'cashflows.cashflow',
                DB::raw('SUM(cashflows.nominal) as nominal'),
                DB::raw('COUNT(cashflows.id) as jumlah_transaksi')
            ])
            ->join('rekening', 'cashflows.idrek', '=', 'rekening.idrek')
            ->whereExists(function ($query) use ($projectId) {
                $query->select(DB::raw(1))
                      ->from('notas')
                      ->whereColumn('notas.id', 'cashflows.idnota')
                      ->where('notas.idproject', $projectId);
            })
            ->whereBetween('cashflows.tanggal', [$startDate, $endDate])
            ->groupBy(
                'cashflows.idrek',
                'rekening.norek',
                'rekening.namarek',
                'cashflows.tanggal',
                'cashflows.cashflow'
            )
            ->orderBy('cashflows.tanggal', 'desc')
            ->orderBy('cashflows.idrek')
            ->get();

        return $this->formatCashflowData($cashflows);
    }

    /**
     * Format data cashflow
     */
    private function formatCashflowData($cashflows)
    {
        $groupedData = [];
        foreach ($cashflows as $cf) {
            $rekKey = $cf->idrek;
            
            if (!isset($groupedData[$rekKey])) {
                $groupedData[$rekKey] = [
                    'idrek' => $cf->idrek,
                    'norek' => $cf->norek,
                    'namarek' => $cf->namarek,
                    'transaksi' => [],
                    'total_in' => 0,
                    'total_out' => 0,
                    'net_cashflow' => 0
                ];
            }

            $nominal = (float) $cf->nominal;
            
            if ($cf->cashflow == 'in') {
                $groupedData[$rekKey]['total_in'] += $nominal;
            } else {
                $groupedData[$rekKey]['total_out'] += $nominal;
            }

            $groupedData[$rekKey]['transaksi'][] = [
                'tanggal' => Carbon::parse($cf->tanggal)->format('d/m/Y'),
                'cashflow' => $cf->cashflow,
                'total_in' => $cf->cashflow == 'in' ? $nominal : 0,
                'total_out' => $cf->cashflow == 'out' ? $nominal : 0,
                'jumlah_transaksi' => $cf->jumlah_transaksi
            ];
        }

        $formattedData = [];
        foreach ($groupedData as $rek) {
            $rek['net_cashflow'] = $rek['total_in'] - $rek['total_out'];
            
            $formattedData[] = [
                'idrek' => $rek['idrek'],
                'norek' => $rek['norek'],
                'namarek' => $rek['namarek'],
                'total_in' => number_format($rek['total_in'], 0, ',', '.'),
                'total_out' => number_format($rek['total_out'], 0, ',', '.'),
                'net_cashflow' => number_format($rek['net_cashflow'], 0, ',', '.'),
                'net_cashflow_raw' => $rek['net_cashflow'],
                'transaksi' => $rek['transaksi'],
                'transaksi_count' => count($rek['transaksi'])
            ];
        }

        return $formattedData;
    }

    /**
     * Data ringkasan dashboard untuk project
     */
    private function getRingkasan($projectId)
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $transaksiIn = Nota::where('idproject', $projectId)
            ->where('cashflow', 'in')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
            ->first()->total ?? 0;

        $transaksiOut = Nota::where('idproject', $projectId)
            ->where('cashflow', 'out')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
            ->first()->total ?? 0;

        $transaksiInLastMonth = Nota::where('idproject', $projectId)
            ->where('cashflow', 'in')
            ->whereBetween('tanggal', [$lastMonthStart, $lastMonthEnd])
            ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
            ->first()->total ?? 0;

        $transaksiOutLastMonth = Nota::where('idproject', $projectId)
            ->where('cashflow', 'out')
            ->whereBetween('tanggal', [$lastMonthStart, $lastMonthEnd])
            ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
            ->first()->total ?? 0;

        $percentageIn = $transaksiInLastMonth > 0 ? 
            (($transaksiIn - $transaksiInLastMonth) / $transaksiInLastMonth * 100) : 0;
        
        $percentageOut = $transaksiOutLastMonth > 0 ? 
            (($transaksiOut - $transaksiOutLastMonth) / $transaksiOutLastMonth * 100) : 0;

        $netCashflow = $transaksiIn - $transaksiOut;
        $netCashflowLastMonth = $transaksiInLastMonth - $transaksiOutLastMonth;
        
        $percentageNet = ($netCashflowLastMonth != 0) ? 
            (($netCashflow - $netCashflowLastMonth) / abs($netCashflowLastMonth) * 100) : 0;

        $notaOpen = Nota::where('idproject', $projectId)
            ->where('status', 'open')
            ->count();

        $notaThisMonth = Nota::where('idproject', $projectId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->count();

        return [
            'transaksi_in' => [
                'total' => number_format($transaksiIn, 0, ',', '.'),
                'total_raw' => $transaksiIn,
                'percentage' => round($percentageIn, 2),
                'trend' => $percentageIn >= 0 ? 'up' : 'down'
            ],
            'transaksi_out' => [
                'total' => number_format($transaksiOut, 0, ',', '.'),
                'total_raw' => $transaksiOut,
                'percentage' => round($percentageOut, 2),
                'trend' => $percentageOut <= 0 ? 'up' : 'down'
            ],
            'net_cashflow' => [
                'total' => number_format($netCashflow, 0, ',', '.'),
                'total_raw' => $netCashflow,
                'percentage' => round($percentageNet, 2),
                'trend' => $percentageNet >= 0 ? 'up' : 'down'
            ],
            'nota_open' => $notaOpen,
            'nota_this_month' => $notaThisMonth
        ];
    }

    /**
     * API endpoint untuk data grafik
     */
    public function getChartData(Request $request)
    {
        $type = $request->get('type', 'monthly');
        $module = session('active_project_module');
        
        try {
            if ($module == 'project') {
                $projectId = session('active_project_id');
                if (!$projectId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Project ID tidak ditemukan'
                    ], 400);
                }
                
                $data = $this->getChartDataForProject($projectId, $type);
            } elseif ($module == 'company') {
                $companyId = session('active_company_id');
                if (!$companyId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Company ID tidak ditemukan'
                    ], 400);
                }
                
                $data = $this->getChartDataForCompany($companyId, $type);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Module tidak dikenali'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getChartData:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getChartDataForProject($projectId, $type)
    {
        switch ($type) {
            case 'weekly':
                return $this->getWeeklyChartData($projectId);
            case 'daily':
                return $this->getDailyChartData($projectId);
            case 'monthly':
            default:
                return $this->getMonthlyChartData($projectId);
        }
    }

    private function getChartDataForCompany($companyId, $type)
    {
        $projects = Project::where('idcompany', $companyId)->pluck('id');
        
        if ($projects->isEmpty()) {
            return $this->getEmptyChartData($type);
        }
        
        switch ($type) {
            case 'weekly':
                return $this->getWeeklyChartDataForCompany($projects);
            case 'daily':
                return $this->getDailyChartDataForCompany($projects);
            case 'monthly':
            default:
                return $this->getMonthlyChartDataForCompany($projects);
        }
    }

    private function getEmptyChartData($type)
    {
        $data = ['labels' => [], 'in' => [], 'out' => [], 'net' => []];
        
        switch ($type) {
            case 'weekly':
                for ($i = 7; $i >= 0; $i--) {
                    $week = Carbon::now()->subWeeks($i);
                    $data['labels'][] = 'W' . $week->weekOfYear . ' ' . $week->format('M');
                    $data['in'][] = 0;
                    $data['out'][] = 0;
                    $data['net'][] = 0;
                }
                break;
                
            case 'daily':
                for ($i = 13; $i >= 0; $i--) {
                    $day = Carbon::now()->subDays($i);
                    $data['labels'][] = $day->format('d M');
                    $data['in'][] = 0;
                    $data['out'][] = 0;
                    $data['net'][] = 0;
                }
                break;
                
            case 'monthly':
            default:
                for ($i = 11; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $data['labels'][] = $month->format('M Y');
                    $data['in'][] = 0;
                    $data['out'][] = 0;
                    $data['net'][] = 0;
                }
                break;
        }
        
        return $data;
    }

    private function getMonthlyChartData($projectId)
    {
        $data = ['labels' => [], 'in' => [], 'out' => [], 'net' => []];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();

            $in = Nota::where('idproject', $projectId)
                ->where('cashflow', 'in')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('total') ?? 0;

            $out = Nota::where('idproject', $projectId)
                ->where('cashflow', 'out')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('total') ?? 0;

            $data['labels'][] = $month->format('M Y');
            $data['in'][] = (float) $in;
            $data['out'][] = (float) $out;
            $data['net'][] = (float) ($in - $out);
        }

        return $data;
    }

    private function getMonthlyChartDataForCompany($projects)
    {
        $data = ['labels' => [], 'in' => [], 'out' => [], 'net' => []];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();

            $in = Nota::whereIn('idproject', $projects)
                ->where('cashflow', 'in')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('total') ?? 0;

            $out = Nota::whereIn('idproject', $projects)
                ->where('cashflow', 'out')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('total') ?? 0;

            $data['labels'][] = $month->format('M Y');
            $data['in'][] = (float) $in;
            $data['out'][] = (float) $out;
            $data['net'][] = (float) ($in - $out);
        }

        return $data;
    }

    private function getWeeklyChartData($projectId)
    {
        $data = ['labels' => [], 'in' => [], 'out' => [], 'net' => []];
        
        for ($i = 7; $i >= 0; $i--) {
            $week = Carbon::now()->subWeeks($i);
            $startDate = $week->copy()->startOfWeek();
            $endDate = $week->copy()->endOfWeek();

            $in = Nota::where('idproject', $projectId)
                ->where('cashflow', 'in')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('total') ?? 0;

            $out = Nota::where('idproject', $projectId)
                ->where('cashflow', 'out')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('total') ?? 0;

            $data['labels'][] = 'W' . $week->weekOfYear . ' ' . $week->format('M');
            $data['in'][] = (float) $in;
            $data['out'][] = (float) $out;
            $data['net'][] = (float) ($in - $out);
        }

        return $data;
    }

    private function getWeeklyChartDataForCompany($projects)
    {
        $data = ['labels' => [], 'in' => [], 'out' => [], 'net' => []];
        
        for ($i = 7; $i >= 0; $i--) {
            $week = Carbon::now()->subWeeks($i);
            $startDate = $week->copy()->startOfWeek();
            $endDate = $week->copy()->endOfWeek();

            $in = Nota::whereIn('idproject', $projects)
                ->where('cashflow', 'in')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('total') ?? 0;

            $out = Nota::whereIn('idproject', $projects)
                ->where('cashflow', 'out')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('total') ?? 0;

            $data['labels'][] = 'W' . $week->weekOfYear . ' ' . $week->format('M');
            $data['in'][] = (float) $in;
            $data['out'][] = (float) $out;
            $data['net'][] = (float) ($in - $out);
        }

        return $data;
    }

    private function getDailyChartData($projectId)
    {
        $data = ['labels' => [], 'in' => [], 'out' => [], 'net' => []];
        
        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            
            $in = Nota::where('idproject', $projectId)
                ->where('cashflow', 'in')
                ->whereDate('tanggal', $day)
                ->sum('total') ?? 0;

            $out = Nota::where('idproject', $projectId)
                ->where('cashflow', 'out')
                ->whereDate('tanggal', $day)
                ->sum('total') ?? 0;

            $data['labels'][] = $day->format('d M');
            $data['in'][] = (float) $in;
            $data['out'][] = (float) $out;
            $data['net'][] = (float) ($in - $out);
        }

        return $data;
    }

    private function getDailyChartDataForCompany($projects)
    {
        $data = ['labels' => [], 'in' => [], 'out' => [], 'net' => []];
        
        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            
            $in = Nota::whereIn('idproject', $projects)
                ->where('cashflow', 'in')
                ->whereDate('tanggal', $day)
                ->sum('total') ?? 0;

            $out = Nota::whereIn('idproject', $projects)
                ->where('cashflow', 'out')
                ->whereDate('tanggal', $day)
                ->sum('total') ?? 0;

            $data['labels'][] = $day->format('d M');
            $data['in'][] = (float) $in;
            $data['out'][] = (float) $out;
            $data['net'][] = (float) ($in - $out);
        }

        return $data;
    }

    /**
     * Dashboard untuk module HRIS
     */
    private function dashboardHRIS()
    {
        $companyId = session('active_company_id');
        
        if (!$companyId) {
            return redirect()->route('choose.project')
                ->with('error', 'Silakan pilih PT terlebih dahulu.');
        }
        
        $company = CompanyUnit::find($companyId);
        
        $statistikKaryawan = $this->getStatistikKaryawan($companyId);
        $statistikPresensi = $this->getStatistikPresensi();
        $statistikPayroll = $this->getStatistikPayroll();
        $karyawanAktif = $this->getKaryawanAktif($companyId);
        $presensiHariIni = $this->getPresensiHariIni();
        $izinPending = $this->getIzinPending();
        $chartData = $this->getChartDataHRIS();
        
        $hrisInfo = [
            'nama' => session('active_company_name') ?? 'Tidak diketahui',
            'company' => session('active_company_name') ?? 'Tidak diketahui',
            'module' => 'HRIS',
            'total_karyawan' => $statistikKaryawan['total'],
            'periode' => Carbon::now()->translatedFormat('F Y')
        ];

        return view('dashboard.hris', compact(
            'statistikKaryawan',
            'statistikPresensi', 
            'statistikPayroll',
            'karyawanAktif',
            'presensiHariIni',
            'izinPending',
            'chartData',
            'hrisInfo',
            'company'
        ));
    }

    private function getStatistikKaryawan($companyId)
    {
        $totalKaryawan = User::where('status', 'aktif')
            ->where('id_unitkerja', $companyId)
            ->count();
        
        $karyawanBaru = User::where('status', 'aktif')
            ->where('id_unitkerja', $companyId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        $karyawanLaki = User::where('status', 'aktif')
            ->where('id_unitkerja', $companyId)
            ->whereHas('pegawaiDtl', function($query) {
                $query->where('jenis_kelamin', 'L');
            })
            ->count();
        
        $karyawanPerempuan = User::where('status', 'aktif')
            ->where('id_unitkerja', $companyId)
            ->whereHas('pegawaiDtl', function($query) {
                $query->where('jenis_kelamin', 'P');
            })
            ->count();
        
        $karyawanTetap = User::where('status', 'aktif')
            ->where('id_unitkerja', $companyId)
            ->whereHas('pegawaiDtl', function($query) {
                $query->whereNotNull('akhir_kontrak')
                    ->where('akhir_kontrak', '>', Carbon::now());
            })
            ->count();
        
        $karyawanKontrak = User::where('status', 'aktif')
            ->where('id_unitkerja', $companyId)
            ->whereHas('pegawaiDtl', function($query) {
                $query->whereNotNull('akhir_kontrak')
                    ->where('akhir_kontrak', '<=', Carbon::now());
            })
            ->count();

        return [
            'total' => $totalKaryawan,
            'baru_bulan_ini' => $karyawanBaru,
            'laki_laki' => $karyawanLaki,
            'perempuan' => $karyawanPerempuan,
            'tetap' => $karyawanTetap,
            'kontrak' => $karyawanKontrak,
            'persentase_laki' => $totalKaryawan > 0 ? round(($karyawanLaki / $totalKaryawan) * 100, 1) : 0,
            'persentase_perempuan' => $totalKaryawan > 0 ? round(($karyawanPerempuan / $totalKaryawan) * 100, 1) : 0
        ];
    }

    private function getStatistikPresensi()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $totalHariKerja = 0;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if (!$current->isWeekend()) {
                $totalHariKerja++;
            }
            $current->addDay();
        }
        
        $presensiHariIni = Presensi::whereDate('tgl_presensi', Carbon::today())
            ->where('inoutmode', 1)
            ->count();
        
        $totalPresensi = Presensi::whereBetween('tgl_presensi', [$startDate, $endDate])
            ->where('inoutmode', 1)
            ->count();
        
        $terlambatBulanIni = DB::table('presensi as p')
            ->join('jadwal as j', function($join) {
                $join->on('p.nik', '=', 'j.pegawai_nik')
                     ->on('p.tgl_presensi', '=', 'j.tgl');
            })
            ->join('kelompokjam as k', 'j.shift', '=', 'k.shift')
            ->whereBetween('p.tgl_presensi', [$startDate, $endDate])
            ->where('p.inoutmode', 1)
            ->whereRaw('TIME(p.jam_in) > TIME(k.jammasuk)')
            ->count();
        
        $izinBulanIni = PengajuanIzin::where('status_approved', 1)
            ->whereBetween('tgl_izin', [$startDate, $endDate])
            ->count();
        
        $cutiBulanIni = PengajuanIzin::where('status', 'c')
            ->where('status_approved', 1)
            ->whereBetween('tgl_izin', [$startDate, $endDate])
            ->count();

        return [
            'total_hari_kerja' => $totalHariKerja,
            'presensi_hari_ini' => $presensiHariIni,
            'total_presensi_bulan' => $totalPresensi,
            'terlambat_bulan' => $terlambatBulanIni,
            'izin_bulan' => $izinBulanIni,
            'cuti_bulan' => $cutiBulanIni,
            'persentase_hadir' => $totalHariKerja > 0 ? round(($totalPresensi / ($totalHariKerja * User::where('status', 'aktif')->count())) * 100, 1) : 0
        ];
    }

    private function getStatistikPayroll()
    {
        $periode = Carbon::now()->format('Y-m');
        $periodeLalu = Carbon::now()->subMonth()->format('Y-m');
        
        $payrollBulanIni = Payroll::where('periode', $periode)
            ->select([
                DB::raw('COUNT(*) as total_karyawan'),
                DB::raw('SUM(gajipokok) as total_gaji_pokok'),
                DB::raw('SUM(pek_tambahan) as total_tambahan'),
                DB::raw('SUM(masakerja) as total_masakerja'),
                DB::raw('SUM(transportasi) as total_transportasi'),
                DB::raw('SUM(konsumsi) as total_konsumsi'),
                DB::raw('SUM(tunj_asuransi) as total_tunj_asuransi'),
                DB::raw('SUM(jabatan) as total_jabatan'),
                DB::raw('SUM(cicilan) as total_cicilan'),
                DB::raw('SUM(asuransi) as total_asuransi'),
                DB::raw('SUM(zakat) as total_zakat')
            ])
            ->first();
        
        $payrollBulanLalu = Payroll::where('periode', $periodeLalu)
            ->select([
                DB::raw('SUM(gajipokok) as total_gaji_pokok'),
                DB::raw('SUM(pek_tambahan) as total_tambahan'),
                DB::raw('SUM(masakerja) as total_masakerja'),
                DB::raw('SUM(transportasi) as total_transportasi'),
                DB::raw('SUM(konsumsi) as total_konsumsi'),
                DB::raw('SUM(tunj_asuransi) as total_tunj_asuransi'),
                DB::raw('SUM(jabatan) as total_jabatan'),
            ])
            ->first();
        
        $totalPendapatan = ($payrollBulanIni->total_gaji_pokok ?? 0) +
                          ($payrollBulanIni->total_tambahan ?? 0) +
                          ($payrollBulanIni->total_masakerja ?? 0) +
                          ($payrollBulanIni->total_transportasi ?? 0) +
                          ($payrollBulanIni->total_konsumsi ?? 0) +
                          ($payrollBulanIni->total_tunj_asuransi ?? 0) +
                          ($payrollBulanIni->total_jabatan ?? 0);
        
        $totalPendapatanLalu = ($payrollBulanLalu->total_gaji_pokok ?? 0) +
                              ($payrollBulanLalu->total_tambahan ?? 0) +
                              ($payrollBulanLalu->total_masakerja ?? 0) +
                              ($payrollBulanLalu->total_transportasi ?? 0) +
                              ($payrollBulanLalu->total_konsumsi ?? 0) +
                              ($payrollBulanLalu->total_tunj_asuransi ?? 0) +
                              ($payrollBulanLalu->total_jabatan ?? 0);
        
        $percentage = 0;
        if ($totalPendapatanLalu > 0) {
            $percentage = (($totalPendapatan - $totalPendapatanLalu) / $totalPendapatanLalu) * 100;
        }
        
        $rataGaji = $payrollBulanIni->total_karyawan > 0 ? 
                   $totalPendapatan / $payrollBulanIni->total_karyawan : 0;

        return [
            'total_karyawan' => $payrollBulanIni->total_karyawan ?? 0,
            'total_pendapatan' => $totalPendapatan,
            'total_pendapatan_formatted' => number_format($totalPendapatan, 0, ',', '.'),
            'total_potongan' => ($payrollBulanIni->total_cicilan ?? 0) +
                               ($payrollBulanIni->total_asuransi ?? 0) +
                               ($payrollBulanIni->total_zakat ?? 0),
            'rata_gaji' => number_format($rataGaji, 0, ',', '.'),
            'persentase_perubahan' => round($percentage, 1),
            'trend' => $percentage >= 0 ? 'up' : 'down'
        ];
    }

    private function getKaryawanAktif($companyId)
    {
        return User::with(['pegawaiDtl', 'unitkerja'])
            ->where('status', 'aktif')
            ->where('id_unitkerja', $companyId)
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'nik' => $user->nik,
                    'nip' => $user->nip,
                    'nama' => $user->name,
                    'unit_kerja' => $user->unitkerja->company_name ?? '-',
                    'jabatan' => $user->pegawaiDtl->jabatan ?? '-',
                    'status_kontrak' => $this->getStatusKontrak($user->pegawaiDtl),
                    'foto' => $user->foto ?? 'default-avatar.jpg'
                ];
            });
    }

    private function getStatusKontrak($pegawaiDtl)
    {
        if (!$pegawaiDtl || !$pegawaiDtl->akhir_kontrak) {
            return 'Tidak ada data';
        }
        
        $akhirKontrak = Carbon::parse($pegawaiDtl->akhir_kontrak);
        $hariIni = Carbon::now();
        
        if ($akhirKontrak->isPast()) {
            $hariTerlambat = $hariIni->diffInDays($akhirKontrak);
            return "Kontrak habis ({$hariTerlambat} hari lalu)";
        }
        
        $hariTersisa = $hariIni->diffInDays($akhirKontrak);
        
        if ($hariTersisa <= 30) {
            return "Kontrak hampir habis ({$hariTersisa} hari)";
        }
        
        return "Aktif sampai " . $akhirKontrak->format('d/m/Y');
    }

    private function getPresensiHariIni()
    {
        $today = Carbon::today();
        
        $presensi = Presensi::with(['user.unitkerja'])
            ->whereDate('tgl_presensi', $today)
            ->where('inoutmode', 1)
            ->orderBy('jam_in', 'desc')
            ->limit(10)
            ->get()
            ->map(function($presensi) {
                $jamIn = $presensi->jam_in ? Carbon::parse($presensi->jam_in)->format('H:i') : '-';
                $status = 'Tepat Waktu';
                
                if ($presensi->user) {
                    $jadwal = Jadwal::where('pegawai_nik', $presensi->user->nik)
                        ->where('tgl', $presensi->tgl_presensi)
                        ->first();
                    
                    if ($jadwal) {
                        $shift = $jadwal->shift;
                        $kelompokJam = DB::table('kelompokjam')->where('shift', $shift)->first();
                        
                        if ($kelompokJam && $presensi->jam_in) {
                            $jamMasuk = Carbon::parse($presensi->jam_in);
                            $jamJadwal = Carbon::parse($kelompokJam->jammasuk);
                            
                            if ($jamMasuk->gt($jamJadwal)) {
                                $selisih = $jamMasuk->diffInMinutes($jamJadwal);
                                $status = "Terlambat {$selisih} menit";
                            }
                        }
                    }
                }
                
                return [
                    'nik' => $presensi->user->nik ?? '-',
                    'nama' => $presensi->user->name ?? '-',
                    'unit_kerja' => $presensi->user->unitkerja->company_name ?? '-',
                    'jam_masuk' => $jamIn,
                    'status' => $status,
                    'foto' => $presensi->foto_in ?? 'default-avatar.jpg'
                ];
            });
        
        return $presensi;
    }

    private function getIzinPending()
    {
        return PengajuanIzin::with(['user.unitkerja'])
            ->where('status_approved', 0)
            ->whereDate('tgl_izin', '>=', Carbon::today())
            ->orderBy('tgl_izin', 'asc')
            ->limit(10)
            ->get()
            ->map(function($izin) {
                $statusLabel = '';
                switch ($izin->status) {
                    case 'i': $statusLabel = 'Izin'; break;
                    case 's': $statusLabel = 'Sakit'; break;
                    case 'c': $statusLabel = 'Cuti'; break;
                    default: $statusLabel = 'Izin';
                }
                
                return [
                    'id' => $izin->id,
                    'nik' => $izin->user->nik ?? '-',
                    'nama' => $izin->user->name ?? '-',
                    'unit_kerja' => $izin->user->unitkerja->company_name ?? '-',
                    'tanggal' => Carbon::parse($izin->tgl_izin)->format('d/m/Y'),
                    'jenis' => $statusLabel,
                    'keterangan' => $izin->keterangan ?? '',
                    'status' => 'Menunggu Approval'
                ];
            });
    }

    private function getChartDataHRIS()
    {
        $data = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();
            
            $totalPresensi = Presensi::whereBetween('tgl_presensi', [$startDate, $endDate])
                ->where('inoutmode', 1)
                ->count();
            
            $totalIzin = PengajuanIzin::where('status_approved', 1)
                ->whereBetween('tgl_izin', [$startDate, $endDate])
                ->count();
            
            $totalTerlambat = DB::table('presensi as p')
                ->join('jadwal as j', function($join) {
                    $join->on('p.nik', '=', 'j.pegawai_nik')
                         ->on('p.tgl_presensi', '=', 'j.tgl');
                })
                ->join('kelompokjam as k', 'j.shift', '=', 'k.shift')
                ->whereBetween('p.tgl_presensi', [$startDate, $endDate])
                ->where('p.inoutmode', 1)
                ->whereRaw('TIME(p.jam_in) > TIME(k.jammasuk)')
                ->count();
            
            $data['labels'][] = $month->format('M Y');
            $data['presensi'][] = $totalPresensi;
            $data['izin'][] = $totalIzin;
            $data['terlambat'][] = $totalTerlambat;
        }
        
        return $data;
    }

    private function getGrafikTransaksiForCompany($companyId)
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $transaksi = NotaTransaction::select([
                'kodetransaksi.id',
                'kodetransaksi.kodetransaksi',
                'kodetransaksi.transaksi',
                DB::raw('SUM(nota_transactions.total) as total_nominal'),
                DB::raw('COUNT(nota_transactions.id) as jumlah_transaksi')
            ])
            ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
            ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
            ->where('notas.idcompany', $companyId)
            ->whereBetween('notas.tanggal', [$startDate, $endDate])
            ->groupBy('kodetransaksi.id', 'kodetransaksi.kodetransaksi', 'kodetransaksi.transaksi')
            ->orderByDesc('total_nominal')
            ->limit(10)
            ->get();

        $chartData = [
            'labels' => [],
            'data' => [],
            'colors' => [],
            'jenis' => [],
            'detail' => []
        ];

        foreach ($transaksi as $item) {
            $label = $item->kodetransaksi . ' - ' . ($item->transaksi ?? 'Unknown');
            $chartData['labels'][] = $label;
            $chartData['data'][] = (float) $item->total_nominal;
            $chartData['jenis'][] = $item->transaksi ?? 'lainnya';
            $chartData['detail'][] = [
                'kode' => $item->kodetransaksi,
                'nama' => $item->transaksi ?? 'Unknown',
                'total' => number_format($item->total_nominal, 0, ',', '.'),
                'jumlah' => $item->jumlah_transaksi,
                'jenis' => $item->transaksi ?? 'lainnya'
            ];

            $jenis = strtolower($item->transaksi ?? '');
            if (str_contains($jenis, 'pendapatan') || str_contains($jenis, 'income')) {
                $chartData['colors'][] = '#28a745';
            } elseif (str_contains($jenis, 'beban') || str_contains($jenis, 'expense')) {
                $chartData['colors'][] = '#dc3545';
            } else {
                $chartData['colors'][] = '#6c757d';
            }
        }

        return $chartData;
    }

    private function getSaldoRekeningForCompany($companyId)
    {
        $rekenings = Rekening::select([
                'rekening.idrek',
                'rekening.norek',
                'rekening.namarek',
                'rekening.saldo',
                'rekening.created_at'
            ])
            ->where('rekening.idcompany', $companyId)
            ->whereNull('rekening.idproject')
            ->orderBy('rekening.namarek')
            ->get();

        $totalSaldo = $rekenings->sum('saldo');

        $formattedData = [
            'rekenings' => $rekenings->map(function($rekening) {
                return [
                    'id' => $rekening->idrek,
                    'norek' => $rekening->norek,
                    'nama' => $rekening->namarek,
                    'saldo' => number_format($rekening->saldo, 0, ',', '.'),
                    'saldo_raw' => $rekening->saldo,
                    'created' => Carbon::parse($rekening->created_at)->format('d/m/Y'),
                    'type' => 'company',
                    'type_label' => 'Company',
                    'type_badge' => 'success'
                ];
            }),
            'total_saldo' => number_format($totalSaldo, 0, ',', '.'),
            'total_saldo_raw' => $totalSaldo,
            'jumlah_rekening' => $rekenings->count(),
            'summary' => [
                'company' => [
                    'count' => $rekenings->count(),
                    'total' => number_format($totalSaldo, 0, ',', '.')
                ]
            ]
        ];

        return $formattedData;
    }

    private function getCashflowDetailForCompany($companyId)
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $cashflows = Cashflow::select([
                'cashflows.idrek',
                'rekening.norek',
                'rekening.namarek',
                'cashflows.tanggal',
                'cashflows.cashflow',
                DB::raw('SUM(cashflows.nominal) as nominal'),
                DB::raw('COUNT(cashflows.id) as jumlah_transaksi')
            ])
            ->join('rekening', 'cashflows.idrek', '=', 'rekening.idrek')
            ->whereExists(function ($query) use ($companyId) {
                $query->select(DB::raw(1))
                      ->from('notas')
                      ->whereColumn('notas.id', 'cashflows.idnota')
                      ->where('notas.idcompany', $companyId);
            })
            ->whereBetween('cashflows.tanggal', [$startDate, $endDate])
            ->groupBy(
                'cashflows.idrek',
                'rekening.norek',
                'rekening.namarek',
                'cashflows.tanggal',
                'cashflows.cashflow'
            )
            ->orderBy('cashflows.tanggal', 'desc')
            ->orderBy('cashflows.idrek')
            ->get();

        return $this->formatCashflowData($cashflows);
    }

    private function getRingkasanForCompany($companyId)
    {
        $projects = Project::where('idcompany', $companyId)->pluck('id');
        
        if ($projects->isEmpty()) {
            return $this->getEmptyRingkasan();
        }
        
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $transaksiIn = Nota::whereIn('idproject', $projects)
            ->where('cashflow', 'in')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->sum('total') ?? 0;

        $transaksiOut = Nota::whereIn('idproject', $projects)
            ->where('cashflow', 'out')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->sum('total') ?? 0;

        $transaksiInLastMonth = Nota::whereIn('idproject', $projects)
            ->where('cashflow', 'in')
            ->whereBetween('tanggal', [$lastMonthStart, $lastMonthEnd])
            ->sum('total') ?? 0;

        $transaksiOutLastMonth = Nota::whereIn('idproject', $projects)
            ->where('cashflow', 'out')
            ->whereBetween('tanggal', [$lastMonthStart, $lastMonthEnd])
            ->sum('total') ?? 0;

        $percentageIn = $transaksiInLastMonth > 0 ? 
            (($transaksiIn - $transaksiInLastMonth) / $transaksiInLastMonth * 100) : 0;
        
        $percentageOut = $transaksiOutLastMonth > 0 ? 
            (($transaksiOut - $transaksiOutLastMonth) / $transaksiOutLastMonth * 100) : 0;

        $netCashflow = $transaksiIn - $transaksiOut;
        $netCashflowLastMonth = $transaksiInLastMonth - $transaksiOutLastMonth;
        
        $percentageNet = ($netCashflowLastMonth != 0) ? 
            (($netCashflow - $netCashflowLastMonth) / abs($netCashflowLastMonth) * 100) : 0;

        $notaOpen = Nota::whereIn('idproject', $projects)
            ->where('status', 'open')
            ->count();

        $notaThisMonth = Nota::whereIn('idproject', $projects)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->count();

        $activeProjects = Project::where('idcompany', $companyId)
            ->where('status', 'active')
            ->count();

        return [
            'transaksi_in' => [
                'total' => number_format($transaksiIn, 0, ',', '.'),
                'total_raw' => $transaksiIn,
                'percentage' => round($percentageIn, 2),
                'trend' => $percentageIn >= 0 ? 'up' : 'down'
            ],
            'transaksi_out' => [
                'total' => number_format($transaksiOut, 0, ',', '.'),
                'total_raw' => $transaksiOut,
                'percentage' => round($percentageOut, 2),
                'trend' => $percentageOut <= 0 ? 'up' : 'down'
            ],
            'net_cashflow' => [
                'total' => number_format($netCashflow, 0, ',', '.'),
                'total_raw' => $netCashflow,
                'percentage' => round($percentageNet, 2),
                'trend' => $percentageNet >= 0 ? 'up' : 'down'
            ],
            'nota_open' => $notaOpen,
            'nota_this_month' => $notaThisMonth,
            'active_projects' => $activeProjects
        ];
    }

    private function getEmptyRingkasan()
    {
        return [
            'transaksi_in' => [
                'total' => '0',
                'total_raw' => 0,
                'percentage' => 0,
                'trend' => 'up'
            ],
            'transaksi_out' => [
                'total' => '0',
                'total_raw' => 0,
                'percentage' => 0,
                'trend' => 'down'
            ],
            'net_cashflow' => [
                'total' => '0',
                'total_raw' => 0,
                'percentage' => 0,
                'trend' => 'up'
            ],
            'nota_open' => 0,
            'nota_this_month' => 0,
            'active_projects' => 0
        ];
    }

    private function dashboardCompany()
    {
        $companyId = session('active_company_id');
        
        if (!$companyId) {
            return redirect()->route('choose.project')
                ->with('error', 'Silakan pilih PT terlebih dahulu.');
        }
        
        $grafikTransaksi = $this->getGrafikTransaksiForCompany($companyId);
        $saldoRekening = $this->getSaldoRekeningForCompany($companyId);
        $cashflowDetail = $this->getCashflowDetailForCompany($companyId);
        $ringkasan = $this->getRingkasanForCompany($companyId);
        
        $companyInfo = [
            'nama' => session('active_company_name') ?? 'Tidak diketahui',
            'module' => 'Company'
        ];

        return view('dashboard.company', compact(
            'grafikTransaksi',
            'saldoRekening',
            'cashflowDetail',
            'ringkasan',
            'companyInfo'
        ));
    }
    
private function dashboardMarketing()
{
    $companyId = session('active_company_id');
    
    if (!$companyId) {
        return redirect()->route('choose.project')
            ->with('error', 'Silakan pilih PT terlebih dahulu.');
    }
    
    $company = CompanyUnit::find($companyId);
    
    // AMBIL SEMUA PROJECT TANPA FILTER - GLOBAL
    $allProjects = Project::where('idcompany', $companyId)->get();
    
    // Statistik GLOBAL - tanpa filter project
    $statistikUnit = $this->getStatistikUnitMarketingGlobal();
    $statistikBooking = $this->getStatistikBookingMarketingGlobal();
    $statistikCustomer = $this->getStatistikCustomerMarketingGlobal();
    $topProjects = $this->getTopProjectsMarketingGlobal($companyId);
    $bookingTerbaru = $this->getBookingTerbaruMarketingGlobal();
    $penjualanTerbaru = $this->getPenjualanTerbaruMarketingGlobal();
    $chartData = $this->getChartDataMarketingGlobal();
    
    $marketingInfo = [
        'nama' => session('active_company_name') ?? 'Tidak diketahui',
        'company' => session('active_company_name') ?? 'Tidak diketahui',
        'module' => 'Marketing',
        'total_projects' => $allProjects->count(),
        'periode' => 'Global / Semua Data'
    ];

    return view('dashboard.marketing', compact(
        'statistikUnit',
        'statistikBooking', 
        'statistikCustomer',
        'topProjects',
        'bookingTerbaru',
        'penjualanTerbaru',
        'chartData',
        'marketingInfo',
        'company',
        'allProjects'
    ));
}

/**
 * 1. Statistik Unit untuk Marketing - GLOBAL (Semua Project)
 */
private function getStatistikUnitMarketingGlobal()
{
    // Query builder tanpa filter project - SEMUA UNIT
    $query = UnitDetail::query();
    
    $totalUnits = $query->count();
    
    // Status unit - sama dengan UnitDetailController
    $tersedia = $query->clone()->where('status', 'tersedia')->count();
    $booking = $query->clone()->where('status', 'like', '%booking%')->count();
    $biCheck = $query->clone()->where('status', 'bi_check')->count();
    $pemberkasanBank = $query->clone()->where('status', 'pemberkasan_bank')->count();
    $pemberkasanNotaris = $query->clone()->where('status', 'pemberkasan_notaris')->count();
    $acc = $query->clone()->where('status', 'acc')->count();
    $tidakAcc = $query->clone()->where('status', 'tidak_acc')->count();
    $akad = $query->clone()->where('status', 'akad')->count();
    $pencairan = $query->clone()->where('status', 'pencairan')->count();
    $bast = $query->clone()->where('status', 'bast')->count();
    $terjual = $query->clone()->where('status', 'terjual')->count();
    
    // Tipe penjualan
    $cashCount = $query->clone()->where('tipe_penjualan', 'cash')->count();
    $kreditCount = $query->clone()->where('tipe_penjualan', 'kredit')->count();
    $belumDitentukan = $query->clone()->whereNull('tipe_penjualan')->count();
    
    // Nilai unit tersedia
    $nilaiTersedia = UnitDetail::where('status', 'tersedia')
        ->join('units', 'unit_details.idunit', '=', 'units.id')
        ->sum('units.hargadasar') ?? 0;
    
    // Nilai unit terjual
    $nilaiTerjual = UnitDetail::where('status', 'terjual')
        ->join('penjualans', 'unit_details.penjualan_id', '=', 'penjualans.id')
        ->sum('penjualans.harga_jual') ?? 0;

    return [
        'total' => $totalUnits,
        'tersedia' => $tersedia,
        'booking' => $booking,
        'bi_check' => $biCheck,
        'pemberkasan_bank' => $pemberkasanBank,
        'pemberkasan_notaris' => $pemberkasanNotaris,
        'acc' => $acc,
        'tidak_acc' => $tidakAcc,
        'akad' => $akad,
        'pencairan' => $pencairan,
        'bast' => $bast,
        'terjual' => $terjual,
        'cash_count' => $cashCount,
        'kredit_count' => $kreditCount,
        'belum_ditentukan' => $belumDitentukan,
        'tersedia_percent' => $totalUnits > 0 ? round(($tersedia / $totalUnits) * 100, 1) : 0,
        'booking_percent' => $totalUnits > 0 ? round(($booking / $totalUnits) * 100, 1) : 0,
        'terjual_percent' => $totalUnits > 0 ? round(($terjual / $totalUnits) * 100, 1) : 0,
        'nilai_tersedia' => $nilaiTersedia,
        'nilai_terjual' => $nilaiTerjual,
        'nilai_tersedia_formatted' => number_format($nilaiTersedia, 0, ',', '.'),
        'nilai_terjual_formatted' => number_format($nilaiTerjual, 0, ',', '.')
    ];
}

/**
 * 2. Statistik Booking & Penjualan untuk Marketing - GLOBAL
 */
private function getStatistikBookingMarketingGlobal()
{
    // Total booking (semua status) - GLOBAL
    $totalBooking = Booking::count();
    
    // Total DP booking
    $totalDP = Booking::sum('dp_awal') ?? 0;
    
    // Total penjualan
    $totalPenjualan = Penjualan::count();
    
    // Total nilai penjualan
    $totalNilaiPenjualan = Penjualan::sum('harga_jual') ?? 0;
    
    // Unit terjual (status terjual)
    $unitTerjual = UnitDetail::where('status', 'terjual')->count();
    
    // Conversion rate (Booking to Sale)
    $conversionRate = 0;
    if ($totalBooking > 0) {
        $conversionRate = round(($unitTerjual / $totalBooking) * 100, 1);
    }

    return [
        'total_booking' => $totalBooking,
        'total_dp' => $totalDP,
        'total_dp_formatted' => number_format($totalDP, 0, ',', '.'),
        'total_penjualan' => $totalPenjualan,
        'total_nilai_penjualan' => $totalNilaiPenjualan,
        'total_nilai_penjualan_formatted' => number_format($totalNilaiPenjualan, 0, ',', '.'),
        'unit_terjual' => $unitTerjual,
        'conversion_rate' => $conversionRate,
        'avg_dp' => $totalBooking > 0 ? round($totalDP / $totalBooking, 0) : 0,
        'avg_sale' => $totalPenjualan > 0 ? round($totalNilaiPenjualan / $totalPenjualan, 0) : 0
    ];
}

/**
 * 3. Statistik Customer untuk Marketing - GLOBAL
 */
private function getStatistikCustomerMarketingGlobal()
{
    // Total customer
    $totalCustomer = Customer::count();
    
    // Customer berdasarkan jenis kelamin
    $customerLaki = Customer::where('jenis_kelamin', 'L')->count();
    $customerPerempuan = Customer::where('jenis_kelamin', 'P')->count();
    
    // Customer repeat (beli lebih dari 1 unit)
    $customerRepeat = DB::table('customers')
        ->whereIn('id', function($query) {
            $query->select('unit_details.customer_id')
                ->from('unit_details')
                ->whereNotNull('unit_details.customer_id')
                ->groupBy('unit_details.customer_id')
                ->havingRaw('COUNT(unit_details.id) > 1');
        })
        ->count();
    
    // Customer dengan DP tertinggi
    $topDP = DB::table('bookings')
        ->join('customers', 'bookings.customer_id', '=', 'customers.id')
        ->orderBy('bookings.dp_awal', 'desc')
        ->select('customers.nama_lengkap', 'bookings.dp_awal')
        ->first();

    return [
        'total' => $totalCustomer,
        'laki_laki' => $customerLaki,
        'perempuan' => $customerPerempuan,
        'repeat' => (int)$customerRepeat,
        'persentase_laki' => $totalCustomer > 0 ? round(($customerLaki / $totalCustomer) * 100, 1) : 0,
        'persentase_perempuan' => $totalCustomer > 0 ? round(($customerPerempuan / $totalCustomer) * 100, 1) : 0,
        'persentase_repeat' => $totalCustomer > 0 ? round(($customerRepeat / $totalCustomer) * 100, 1) : 0,
        'top_dp_customer' => $topDP ? [
            'nama' => $topDP->nama_lengkap,
            'dp' => number_format($topDP->dp_awal, 0, ',', '.')
        ] : null
    ];
}

/**
 * 4. Project dengan Performa Terbaik untuk Marketing - GLOBAL
 */
private function getTopProjectsMarketingGlobal($companyId)
{
    $projects = Project::where('idcompany', $companyId)->get();
    $result = [];
    
    foreach ($projects as $project) {
        $totalUnit = Unit::where('idproject', $project->id)->count();
        
        $totalTerjual = UnitDetail::whereHas('unit', function($q) use ($project) {
                $q->where('idproject', $project->id);
            })
            ->where('status', 'terjual')
            ->count();
        
        $totalNilaiTerjual = Penjualan::whereHas('unitDetail.unit', function($q) use ($project) {
                $q->where('idproject', $project->id);
            })
            ->sum('harga_jual') ?? 0;
        
        $penjualanRate = $totalUnit > 0 ? round(($totalTerjual / $totalUnit) * 100, 1) : 0;
        
        $result[] = [
            'id' => $project->id,
            'nama' => $project->namaproject,
            'total_unit' => $totalUnit,
            'total_terjual' => $totalTerjual,
            'total_nilai_terjual' => $totalNilaiTerjual,
            'total_nilai_formatted' => number_format($totalNilaiTerjual, 0, ',', '.'),
            'penjualan_rate' => $penjualanRate
        ];
    }
    
    // Urutkan berdasarkan total terjual
    usort($result, function($a, $b) {
        return $b['total_terjual'] - $a['total_terjual'];
    });
    
    return collect(array_slice($result, 0, 5));
}

/**
 * 5. Booking Terbaru untuk Marketing - GLOBAL
 */
private function getBookingTerbaruMarketingGlobal()
{
    return Booking::with(['customer', 'unitDetail.unit.project'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get()
        ->map(function($booking) {
            return [
                'id' => $booking->id,
                'kode_booking' => $booking->kode_booking,
                'tanggal' => $booking->tanggal_booking ? Carbon::parse($booking->tanggal_booking)->format('d/m/Y') : '-',
                'customer' => $booking->customer->nama_lengkap ?? '-',
                'project' => $booking->unitDetail->unit->project->namaproject ?? '-',
                'unit' => $booking->unitDetail->unit->namaunit ?? '-',
                'dp' => number_format($booking->dp_awal, 0, ',', '.'),
                'status' => $booking->status_booking,
                'status_badge' => $this->getBookingStatusBadge($booking->status_booking)
            ];
        });
}

/**
 * 6. Penjualan Terbaru untuk Marketing - GLOBAL
 */
private function getPenjualanTerbaruMarketingGlobal()
{
    return Penjualan::with(['customer', 'unitDetail.unit.project'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get()
        ->map(function($penjualan) {
            return [
                'id' => $penjualan->id,
                'kode_penjualan' => $penjualan->kode_penjualan,
                'tanggal' => $penjualan->tanggal_akad ? Carbon::parse($penjualan->tanggal_akad)->format('d/m/Y') : '-',
                'customer' => $penjualan->customer->nama_lengkap ?? '-',
                'project' => $penjualan->unitDetail->unit->project->namaproject ?? '-',
                'unit' => $penjualan->unitDetail->unit->namaunit ?? '-',
                'harga' => number_format($penjualan->harga_jual, 0, ',', '.'),
                'metode' => $penjualan->metode_pembayaran,
                'status' => $penjualan->status_penjualan,
                'status_badge' => $this->getPenjualanStatusBadge($penjualan->status_penjualan)
            ];
        });
}

/**
 * 7. Chart Data untuk Marketing (6 bulan terakhir) - GLOBAL
 */
private function getChartDataMarketingGlobal()
{
    $data = ['labels' => [], 'booking' => [], 'penjualan' => []];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = Carbon::now()->subMonths($i);
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();
        
        // Booking bulan ini - GLOBAL
        $totalBooking = Booking::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Penjualan bulan ini - GLOBAL
        $totalPenjualan = Penjualan::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $data['labels'][] = $month->format('M Y');
        $data['booking'][] = $totalBooking;
        $data['penjualan'][] = $totalPenjualan;
    }
    
    return $data;
}

/**
 * Return empty statistik unit
 */
private function getEmptyStatistikUnit()
{
    return [
        'total' => 0,
        'tersedia' => 0,
        'booking' => 0,
        'bi_check' => 0,
        'pemberkasan_bank' => 0,
        'pemberkasan_notaris' => 0,
        'acc' => 0,
        'tidak_acc' => 0,
        'akad' => 0,
        'pencairan' => 0,
        'bast' => 0,
        'terjual' => 0,
        'cash_count' => 0,
        'kredit_count' => 0,
        'belum_ditentukan' => 0,
        'tersedia_percent' => 0,
        'booking_percent' => 0,
        'terjual_percent' => 0,
        'nilai_tersedia' => 0,
        'nilai_terjual' => 0,
        'nilai_tersedia_formatted' => '0',
        'nilai_terjual_formatted' => '0'
    ];
}

/**
 * Return empty statistik booking
 */
private function getEmptyStatistikBooking()
{
    return [
        'total_booking' => 0,
        'total_dp' => 0,
        'total_dp_formatted' => '0',
        'total_penjualan' => 0,
        'total_nilai_penjualan' => 0,
        'total_nilai_penjualan_formatted' => '0',
        'unit_terjual' => 0,
        'conversion_rate' => 0,
        'avg_dp' => 0,
        'avg_sale' => 0
    ];
}

/**
 * Return empty statistik customer
 */
private function getEmptyStatistikCustomer()
{
    return [
        'total' => 0,
        'laki_laki' => 0,
        'perempuan' => 0,
        'repeat' => 0,
        'persentase_laki' => 0,
        'persentase_perempuan' => 0,
        'persentase_repeat' => 0,
        'top_dp_customer' => null
    ];
}

    /**
     * Helper: Get booking status badge
     */
    private function getBookingStatusBadge($status)
    {
        $badges = [
            'active' => 'bg-success',
            'pending' => 'bg-warning',
            'canceled' => 'bg-danger',
            'completed' => 'bg-info',
            'expired' => 'bg-secondary'
        ];
        
        return $badges[$status] ?? 'bg-secondary';
    }

    /**
     * Helper: Get penjualan status badge
     */
    private function getPenjualanStatusBadge($status)
    {
        $badges = [
            'process' => 'bg-warning',
            'completed' => 'bg-success',
            'canceled' => 'bg-danger',
            'pending' => 'bg-info'
        ];
        
        return $badges[$status] ?? 'bg-secondary';
    }

    /**
     * Helper: Get project status badge
     */
    private function getProjectStatusBadge($status)
    {
        $badges = [
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            'completed' => 'bg-info'
        ];
        
        return $badges[$status] ?? 'bg-secondary';
    }
}