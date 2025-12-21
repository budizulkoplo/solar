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
use Carbon\Carbon;
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
            case 'company':
                return $this->dashboardCompany();
            case 'mobile':
                return redirect()->route('mobile.home');
            default:
                // Default ke project dashboard
                return $this->dashboardProject();
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
            return redirect()->route('project.choose')
                ->with('error', 'Silakan pilih project terlebih dahulu.');
        }
        
        // Ambil detail project
        $project = Project::with('companyUnit')->find($projectId);
        
        // 1. Data untuk grafik transaksi berdasarkan COA
        $grafikTransaksi = $this->getGrafikTransaksi($projectId);
        
        // 2. Data rincian saldo rekening untuk project
        $saldoRekening = $this->getSaldoRekeningForProject($projectId, $companyId);
        
        // 3. Data cashflow detail per rekening
        $cashflowDetail = $this->getCashflowDetail($projectId);
        
        // Data ringkasan (summary)
        $ringkasan = $this->getRingkasan($projectId);
        
        // Debug data - hapus ini setelah fix
        // \Log::info('Dashboard Data:', [
        //     'project_id' => $projectId,
        //     'cashflow_count' => count($cashflowDetail),
        //     'grafik_labels' => count($grafikTransaksi['labels'] ?? [])
        // ]);
        
        // Info project
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
        // Ambil data 30 hari terakhir
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $transaksi = NotaTransaction::select([
                'kodetransaksi.id',
                'kodetransaksi.kodetransaksi',
                'kodetransaksi.transaksi',
                'kodetransaksi.transaksi',
                DB::raw('SUM(nota_transactions.total) as total_nominal'),
                DB::raw('COUNT(nota_transactions.id) as jumlah_transaksi')
            ])
            ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
            ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
            ->where('notas.idproject', $projectId)
            ->whereBetween('notas.tanggal', [$startDate, $endDate])
            ->groupBy(
                'kodetransaksi.id',
                'kodetransaksi.kodetransaksi', 
                'kodetransaksi.transaksi',
                'kodetransaksi.transaksi'
            )
            ->orderByDesc('total_nominal')
            ->limit(10)
            ->get();

        // Format untuk chart.js
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

            // Beri warna berdasarkan jenis transaksi
            $jenis = strtolower($item->transaksi ?? '');
            if (str_contains($jenis, 'pendapatan') || str_contains($jenis, 'income')) {
                $chartData['colors'][] = '#28a745'; // Hijau untuk pendapatan
            } elseif (str_contains($jenis, 'beban') || str_contains($jenis, 'expense')) {
                $chartData['colors'][] = '#dc3545'; // Merah untuk beban
            } else {
                $chartData['colors'][] = '#6c757d'; // Abu-abu untuk lainnya
            }
        }

        return $chartData;
    }

    /**
     * 2. Rincian saldo rekening untuk project
     */
    private function getSaldoRekeningForProject($projectId, $companyId)
    {
        // Ambil semua rekening yang terkait dengan project:
        // 1. Rekening khusus project (idproject = projectId)
        // 2. Rekening company yang bisa digunakan di project (idcompany = companyId, idproject IS NULL)
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
                // Rekening khusus project
                $query->where('rekening.idproject', $projectId)
                      // Rekening company yang bisa digunakan di semua project company
                      ->orWhere(function($q) use ($companyId) {
                          $q->whereNull('rekening.idproject')
                            ->where('rekening.idcompany', $companyId);
                      });
            })
            ->orderBy('rekening_type', 'desc') // project rekening duluan
            ->orderBy('rekening.namarek')
            ->get();

        // Hitung total saldo
        $totalSaldo = $rekenings->sum('saldo');
        
        // Hitung per type
        $projectRekenings = $rekenings->where('rekening_type', 'project');
        $companyRekenings = $rekenings->where('rekening_type', 'company');

        // Format untuk display
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
     * 3. Cashflow detail tiap rekening untuk project - PERBAIKAN
     */
    private function getCashflowDetail($projectId)
    {
        // Ambil data 7 hari terakhir
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        // Query yang lebih sederhana dan akurat
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

        // \Log::info('Cashflow Query Result:', [
        //     'count' => $cashflows->count(),
        //     'project_id' => $projectId,
        //     'start_date' => $startDate,
        //     'end_date' => $endDate
        // ]);

        return $this->formatCashflowData($cashflows);
    }

    /**
     * Format data cashflow - PERBAIKAN
     */
    private function formatCashflowData($cashflows)
    {
        // Group by rekening
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

        // Format untuk display
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
        // Ambil data bulan ini
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Data bulan lalu untuk perbandingan
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Total transaksi masuk bulan ini
        $transaksiIn = Nota::where('idproject', $projectId)
            ->where('cashflow', 'in')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
            ->first()->total ?? 0;

        // Total transaksi keluar bulan ini
        $transaksiOut = Nota::where('idproject', $projectId)
            ->where('cashflow', 'out')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
            ->first()->total ?? 0;

        // Total transaksi masuk bulan lalu
        $transaksiInLastMonth = Nota::where('idproject', $projectId)
            ->where('cashflow', 'in')
            ->whereBetween('tanggal', [$lastMonthStart, $lastMonthEnd])
            ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
            ->first()->total ?? 0;

        // Total transaksi keluar bulan lalu
        $transaksiOutLastMonth = Nota::where('idproject', $projectId)
            ->where('cashflow', 'out')
            ->whereBetween('tanggal', [$lastMonthStart, $lastMonthEnd])
            ->select(DB::raw('COALESCE(SUM(total), 0) as total'))
            ->first()->total ?? 0;

        // Hitung persentase perubahan
        $percentageIn = $transaksiInLastMonth > 0 ? 
            (($transaksiIn - $transaksiInLastMonth) / $transaksiInLastMonth * 100) : 0;
        
        $percentageOut = $transaksiOutLastMonth > 0 ? 
            (($transaksiOut - $transaksiOutLastMonth) / $transaksiOutLastMonth * 100) : 0;

        // Net cashflow bulan ini
        $netCashflow = $transaksiIn - $transaksiOut;
        $netCashflowLastMonth = $transaksiInLastMonth - $transaksiOutLastMonth;
        
        $percentageNet = ($netCashflowLastMonth != 0) ? 
            (($netCashflow - $netCashflowLastMonth) / abs($netCashflowLastMonth) * 100) : 0;

        // Jumlah nota open
        $notaOpen = Nota::where('idproject', $projectId)
            ->where('status', 'open')
            ->count();

        // Jumlah nota bulan ini
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
     * API endpoint untuk data grafik (digunakan oleh AJAX)
     */
    public function getChartData(Request $request)
    {
        $type = $request->get('type', 'monthly'); // monthly, weekly, daily
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

    /**
     * Get chart data for project
     */
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

    /**
     * Get chart data for company
     */
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

    /**
     * Return empty chart data structure
     */
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

    /**
     * Data chart bulanan (12 bulan terakhir) untuk project
     */
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

    /**
     * Data chart bulanan untuk Company
     */
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

    /**
     * Data chart mingguan (8 minggu terakhir) untuk project
     */
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

    /**
     * Data chart mingguan untuk Company
     */
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

    /**
     * Data chart harian (14 hari terakhir) untuk project
     */
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

    /**
     * Data chart harian untuk Company
     */
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
     * Dashboard untuk module Company (PT) - simplified version
     */
    private function dashboardCompany()
    {
        $companyId = session('active_company_id');
        
        if (!$companyId) {
            return redirect()->route('project.choose')
                ->with('error', 'Silakan pilih PT terlebih dahulu.');
        }
        
        // Ambil semua project dalam PT ini
        $projects = Project::where('idcompany', $companyId)->get();
        $projectIds = $projects->pluck('id');
        
        // 1. Data untuk grafik transaksi berdasarkan COA untuk seluruh project di PT
        $grafikTransaksi = $this->getGrafikTransaksiForCompany($companyId);
        
        // 2. Data rincian saldo rekening untuk PT
        $saldoRekening = $this->getSaldoRekeningForCompany($companyId);
        
        // 3. Data cashflow detail per rekening untuk PT
        $cashflowDetail = $this->getCashflowDetailForCompany($companyId);
        
        // Data ringkasan (summary) untuk PT
        $ringkasan = $this->getRingkasanForCompany($companyId);
        
        // Info company
        $projectInfo = [
            'nama' => session('active_company_name') ?? 'Tidak diketahui',
            'company' => session('active_company_name') ?? 'Tidak diketahui',
            'module' => 'Company/PT',
            'total_projects' => $projects->count()
        ];

        return view('dashboard.company', compact(
            'grafikTransaksi',
            'saldoRekening', 
            'cashflowDetail',
            'ringkasan',
            'projectInfo',
            'projects'
        ));
    }

    /**
     * Grafik transaksi untuk Company (semua project dalam PT)
     */
    private function getGrafikTransaksiForCompany($companyId)
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $transaksi = NotaTransaction::select([
                'kodetransaksi.id',
                'kodetransaksi.kodetransaksi',
                'kodetransaksi.transaksi',
                'kodetransaksi.transaksi',
                DB::raw('SUM(nota_transactions.total) as total_nominal'),
                DB::raw('COUNT(nota_transactions.id) as jumlah_transaksi')
            ])
            ->join('notas', 'nota_transactions.idnota', '=', 'notas.id')
            ->join('kodetransaksi', 'nota_transactions.idkodetransaksi', '=', 'kodetransaksi.id')
            ->where('notas.idcompany', $companyId)
            ->whereBetween('notas.tanggal', [$startDate, $endDate])
            ->groupBy(
                'kodetransaksi.id',
                'kodetransaksi.kodetransaksi', 
                'kodetransaksi.transaksi',
                'kodetransaksi.transaksi'
            )
            ->orderByDesc('total_nominal')
            ->limit(10)
            ->get();

        // Format untuk chart.js
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
     * Rincian saldo rekening untuk Company (semua rekening PT)
     */
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

    /**
     * Cashflow detail untuk Company (semua project dalam PT)
     */
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

    /**
     * Data ringkasan dashboard untuk Company
     */
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

    /**
     * Return empty ringkasan data
     */
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
}