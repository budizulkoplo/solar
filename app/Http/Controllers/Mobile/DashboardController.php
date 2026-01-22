<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Nota;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman utama mobile
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        $hariIni = Carbon::today()->format('Y-m-d');
        $bulanIni = (int) Carbon::now()->format('m');
        $tahunIni = Carbon::now()->format('Y');
        $nik = $user->nik;

        // Ambil data jadwal untuk bulan ini sekaligus
        $startDate = Carbon::create($tahunIni, $bulanIni, 1)->startOfMonth();
        $endDate = Carbon::create($tahunIni, $bulanIni, 1)->endOfMonth();

        $jadwalBulanIni = DB::table('jadwal')
            ->where('pegawai_nik', $nik)
            ->whereBetween('tgl', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy('tgl');

        // Ambil semua shift yang digunakan untuk mendapatkan jam shift sekaligus
        $shiftsUsed = $jadwalBulanIni->pluck('shift')->unique()->filter()->values();
        $jamShiftCollection = collect();

        if ($shiftsUsed->count() > 0) {
            $jamShiftCollection = DB::table('kelompokjam')
                ->whereIn('shift', $shiftsUsed)
                ->get()
                ->keyBy('shift');
        }

        // Presensi bulan ini: group by tanggal dengan informasi shift
        $presensiBulanIni = DB::table('presensi')
            ->where('nik', $nik)
            ->whereMonth('tgl_presensi', $bulanIni)
            ->whereYear('tgl_presensi', $tahunIni)
            ->orderBy('tgl_presensi')
            ->get()
            ->groupBy('tgl_presensi')
            ->map(function ($items, $tgl) use ($jadwalBulanIni, $jamShiftCollection) {
                $jadwalHariIni = $jadwalBulanIni->get($tgl);
                $shift = $jadwalHariIni->shift ?? '-';
                
                $jamShift = null;
                $jamPulangShift = null;
                
                if ($shift && $shift !== '-') {
                    $shiftData = $jamShiftCollection->get($shift);
                    $jamShift = $shiftData->jammasuk ?? null;
                    $jamPulangShift = $shiftData->jampulang ?? null;
                }

                return [
                    'masuk' => $items->where('inoutmode', 1)->first(),
                    'pulang' => $items->where('inoutmode', 2)->first(),
                    'shift' => $shift,
                    'jam_masuk_shift' => $jamShift,
                    'jam_pulang_shift' => $jamPulangShift,
                ];
            });

        // Hitung rekap presensi berdasarkan jadwal shift
        $rekapPresensi = $this->calculateRekapPresensi($nik, $bulanIni, $tahunIni);

        // Rekap izin & sakit
        $rekapIzin = DB::table('pengajuan_izin')
            ->selectRaw('SUM(IF(status="i",1,0)) as jmlizin, SUM(IF(status="s",1,0)) as jmlsakit')
            ->where('nik', $nik)
            ->whereMonth('tgl_izin', $bulanIni)
            ->whereYear('tgl_izin', $tahunIni)
            ->where('status_approved', 1)
            ->first();

        // Ambil jadwal untuk leaderboard hari ini
        $jadwalHariIni = DB::table('jadwal')
            ->where('tgl', $hariIni)
            ->get()
            ->keyBy('pegawai_nik');

        $shiftsLeaderboard = $jadwalHariIni->pluck('shift')->unique()->filter()->values();
        $jamShiftLeaderboard = collect();

        if ($shiftsLeaderboard->count() > 0) {
            $jamShiftLeaderboard = DB::table('kelompokjam')
                ->whereIn('shift', $shiftsLeaderboard)
                ->get()
                ->keyBy('shift');
        }

        // Leaderboard hari ini dengan informasi shift
        $leaderboard = DB::table('users')
            ->leftJoin('presensi as masuk', function($join) use ($hariIni) {
                $join->on('users.nik', '=', 'masuk.nik')
                    ->where('masuk.tgl_presensi', $hariIni)
                    ->where('masuk.inoutmode', 1);
            })
            ->leftJoin('presensi as pulang', function($join) use ($hariIni) {
                $join->on('users.nik', '=', 'pulang.nik')
                    ->where('pulang.tgl_presensi', $hariIni)
                    ->where('pulang.inoutmode', 2);
            })
            ->select(
                'users.nik',
                'users.name',
                'users.foto',
                'users.jabatan',
                'masuk.jam_in as jam_masuk',
                'masuk.foto_in as foto_in',
                'pulang.jam_in as jam_pulang',
                'pulang.foto_in as foto_out'
            )
            ->orderBy('masuk.jam_in')
            ->get()
            ->map(function ($item) use ($jadwalHariIni, $jamShiftLeaderboard) {
                $jadwalUser = $jadwalHariIni->get($item->nik);
                $shift = $jadwalUser->shift ?? '-';
                
                $jamShift = null;
                $jamPulangShift = null;
                
                if ($shift && $shift !== '-') {
                    $shiftData = $jamShiftLeaderboard->get($shift);
                    $jamShift = $shiftData->jammasuk ?? null;
                    $jamPulangShift = $shiftData->jampulang ?? null;
                }

                return (object) array_merge((array) $item, [
                    'shift' => $shift,
                    'jam_masuk_shift' => $jamShift,
                    'jam_pulang_shift' => $jamPulangShift,
                ]);
            });

        $namaBulan = [
            "", "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember"
        ];

        // Ambil data tiket dari API eksternal
        try {
            $response = Http::timeout(5)->get('https://pm.mentarimultitrada.com/api/tickets/latest-status');

            if ($response->successful()) {
                $tickets = collect($response->json());

                // Filter tiket sesuai user login
                $userTickets = $tickets->filter(fn($t) => ($t['user_id'] ?? null) == $user->id)
                    ->unique('uuid') // HINDARI DUPLIKASI BERDASARKAN UUID
                    ->values()
                    ->map(function($t) use ($tickets) {
                        // Ambil semua komentar untuk tiket ini (berdasarkan UUID) dengan filter UNIQUE
                        $comments = $tickets->filter(fn($comment) => 
                            ($comment['uuid'] ?? null) === ($t['uuid'] ?? null) && 
                            isset($comment['comment_id']) && 
                            $comment['comment_id'] !== null
                        )
                        ->unique('comment_id') // TAMBAHKAN UNIQUE BERDASARKAN COMMENT_ID
                        ->map(function($comment) {
                            return [
                                'id' => $comment['comment_id'] ?? null,
                                'text' => $comment['comment'] ?? '',
                                'user_id' => $comment['comment_user'] ?? null,
                                'created_at' => $comment['comment_created_at'] ?? null,
                                'user_name' => ($comment['comment_user_name'] ?? 'Unknown'),
                                'formatted_time' => $comment['comment_created_at'] ? 
                                    \Carbon\Carbon::parse($comment['comment_created_at'])->diffForHumans() : null
                            ];
                        })
                        ->sortByDesc('created_at')
                        ->values()
                        ->toArray();

                        return [
                            'uuid' => $t['uuid'] ?? '-',
                            'ticket_name' => $t['ticket_name'] ?? '-',
                            'ticket_status' => $t['ticket_status'] ?? '-',
                            'status_created_at' => $t['status_created_at'] ?? '-',
                            'description' => $t['description'] ?? '-',
                            'start_date' => $t['start_date'] ?? '-',
                            'due_date' => $t['due_date'] ?? '-',
                            'comments' => $comments,
                            'comment_count' => count($comments),
                        ];
                    });

                // Summary untuk Task Management
                $ticketSummary = $userTickets->groupBy('ticket_status')->map(fn($group) => $group->count());
            } else {
                $userTickets = collect();
                $ticketSummary = collect();
            }
        } catch (\Exception $e) {
            $userTickets = collect();
            $ticketSummary = collect();
        }

        // Data untuk modal presensi
        $presensiModalData = $this->getPresensiModalData($presensiBulanIni);

        // Data transaksi tempo yang belum lunas
        $transaksiTempo = $this->getTransaksiTempoBelumLunas();

        // Data untuk modal transaksi tempo
        $tempoModalData = $this->getTempoModalData();

        return view('mobile.index', [
            'user' => $user,
            'rekapPresensiBulanIni' => $presensiBulanIni,
            'rekappresensi' => $rekapPresensi,
            'rekapizin' => $rekapIzin,
            'leaderboard' => $leaderboard,
            'namabulan' => $namaBulan,
            'bulanini' => $bulanIni,
            'tahunini' => $tahunIni,
            'ticketSummary' => $ticketSummary,
            'userTickets' => $userTickets,
            'presensiModalData' => $presensiModalData,
            'transaksiTempo' => $transaksiTempo,
            'tempoModalData' => $tempoModalData,
        ]);
    }

    /**
     * Hitung rekap presensi berdasarkan jadwal shift
     */
    protected function calculateRekapPresensi(string $nik, int $bulan, int $tahun)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();

        // Ambil data jadwal untuk bulan ini
        $jadwalCollection = DB::table('jadwal')
            ->where('pegawai_nik', $nik)
            ->whereBetween('tgl', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy('tgl');

        // Ambil data presensi untuk bulan ini
        $presensiCollection = DB::table('presensi')
            ->where('nik', $nik)
            ->whereBetween('tgl_presensi', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('tgl_presensi');

        $jmlHadir = 0;
        $jmlTerlambat = 0;

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $tgl = $currentDate->format('Y-m-d');
            
            // Cek apakah ada jadwal untuk tanggal ini
            $jadwal = $jadwalCollection->get($tgl);
            $shift = $jadwal->shift ?? '-';

            // Cek apakah ada presensi masuk untuk tanggal ini
            $presensiHariIni = $presensiCollection->get($tgl);
            $presensiMasuk = $presensiHariIni ? $presensiHariIni->firstWhere('inoutmode', 1) : null;

            if ($presensiMasuk) {
                $jmlHadir++;

                // Hitung keterlambatan berdasarkan shift
                if ($shift !== '-' && $shift !== 'libur' && strtolower($shift) !== 'libur') {
                    // Ambil jam masuk shift dari tabel kelompokjam
                    $jamShift = DB::table('kelompokjam')
                        ->where('shift', $shift)
                        ->first();

                    if ($jamShift && $jamShift->jammasuk) {
                        $jamMasukShift = $jamShift->jammasuk;
                        $jamMasukAktual = $presensiMasuk->jam_in;

                        // Hitung keterlambatan
                        if ($this->isTerlambat($tgl, $jamMasukAktual, $jamMasukShift, $shift)) {
                            $jmlTerlambat++;
                        }
                    }
                }
            }

            $currentDate->addDay();
        }

        return (object) [
            'jmlhadir' => $jmlHadir,
            'jmlterlambat' => $jmlTerlambat
        ];
    }

    /**
     * Cek apakah karyawan terlambat berdasarkan shift
     */
    protected function isTerlambat(string $tgl, string $jamAktual, string $jamShift, string $shift): bool
    {
        try {
            $shiftStart = Carbon::parse("$tgl $jamShift");
            $actualTime = Carbon::parse("$tgl $jamAktual");

            // Untuk shift malam (misal 22:00–06:00)
            $isNightShift = Carbon::parse($jamShift)->hour >= 18 && Carbon::parse($jamShift)->hour <= 23;

            if ($isNightShift) {
                // Untuk shift malam, tidak dihitung terlambat jika masuk sebelum jam shift
                // karena mungkin lembur dari hari sebelumnya
                if ($actualTime->lt($shiftStart) && $actualTime->hour >= 18) {
                    return false;
                }
                
                // Hitung terlambat hanya jika masuk setelah jam shift
                if ($actualTime->gt($shiftStart)) {
                    $diffSeconds = $shiftStart->diffInSeconds($actualTime);
                    return $diffSeconds > 60; // lebih dari 1 menit
                }
                
                return false;
            } else {
                // Untuk shift reguler
                if ($actualTime->gt($shiftStart)) {
                    $diffSeconds = $shiftStart->diffInSeconds($actualTime);
                    return $diffSeconds > 60; // lebih dari 1 menit
                }
                
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Konversi detik ke format jam:menit
     */
    protected function secondsToTime(int $seconds): string
    {
        $sign = $seconds < 0 ? '-' : '';
        $seconds = abs($seconds);
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf('%s%d:%02d', $sign, $hours, $minutes);
    }

    /**
     * Data untuk modal presensi
     */
    protected function getPresensiModalData($presensiBulanIni)
    {
        $data = [];
        
        foreach ($presensiBulanIni as $tanggal => $presensi) {
            $jamMasuk = $presensi['masuk']->jam_in ?? null;
            $jamPulang = $presensi['pulang']->jam_in ?? null;
            $jamMasukShift = $presensi['jam_masuk_shift'] ?? null;
            $status = 'Tidak Hadir'; // default

            // Tentukan status kehadiran
            if ($jamMasuk || $jamPulang) {
                // Anggap hadir dulu
                $status = 'Hadir';

                // Jika ada jam shift dan masuk lebih dari jadwal, ubah jadi Terlambat
                if ($jamMasuk && $jamMasukShift && 
                    $this->isTerlambat($tanggal, $jamMasuk, $jamMasukShift, $presensi['shift'])) {
                    $status = 'Terlambat';
                }
            }

            $data[] = [
                'tanggal' => $tanggal,
                'tanggal_label' => Carbon::parse($tanggal)->translatedFormat('l, d F Y'),
                'jam_masuk' => $jamMasuk ? Carbon::parse($jamMasuk)->format('H:i') : '-',
                'jam_pulang' => $jamPulang ? Carbon::parse($jamPulang)->format('H:i') : '-',
                'jam_masuk_shift' => $jamMasukShift,
                'status' => $status,
                'shift' => $presensi['shift']
            ];
        }

        return $data;
    }

    public function agendaList(Request $request)
    {
        try {
            $bulan = $request->input('bulan', date('Y-m'));

            // Validasi format bulan
            if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
                $bulan = date('Y-m');
            }

            $user = Auth::user();

            // Debug: Cek apakah user terautentikasi
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }

            $agenda = DB::table('agenda')
                ->whereYear('tgl', date('Y', strtotime($bulan)))
                ->whereMonth('tgl', date('m', strtotime($bulan)))
                ->orderBy('tgl')
                ->orderBy('waktu')
                ->get();

            // Parse tanggal untuk tampilan
            $agenda = $agenda->map(function($item) use ($user) {
                try {
                    $carbonDate = \Carbon\Carbon::parse($item->tgl . ' ' . $item->waktu);
                    $item->is_past = $carbonDate->isPast();
                    $item->status = $item->is_past ? 'Berakhir' : 'Akan Datang';
                    $item->badge_class = $item->is_past ? 'bg-danger' : 'bg-success';
                    $item->bg_color = $item->is_past ? '#ffe6ec' : '#e3fcec';
                    $item->formatted_date = \Carbon\Carbon::parse($item->tgl)->translatedFormat('d F Y');
                    $item->formatted_time = substr($item->waktu, 0, 5);
                    $item->is_owner = $user->nik === $item->nik;
                } catch (\Exception $e) {
                    // Tangani error parsing
                    $item->is_past = false;
                    $item->status = 'Error';
                    $item->badge_class = 'bg-secondary';
                    $item->bg_color = '#f8f9fa';
                    $item->formatted_date = $item->tgl;
                    $item->formatted_time = $item->waktu;
                    $item->is_owner = false;
                }
                
                return $item;
            });

            return response()->json([
                'success' => true,
                'agenda' => $agenda,
                'bulan' => $bulan,
                'bulan_label' => \Carbon\Carbon::parse($bulan . '-01')->translatedFormat('F Y'),
                'total' => $agenda->count()
            ]);

        } catch (\Exception $e) {
            // Log error untuk debugging
            \Log::error('Error in agendaList: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    protected function getTransaksiTempoBelumLunas()
    {
        $today = Carbon::today()->format('Y-m-d');
        $projectIds = $this->accessibleProjectIds();

        $baseQuery = Nota::where('paymen_method', 'tempo')
            ->where('status', '!=', 'paid');

        if ($projectIds) {
            $baseQuery->whereIn('idproject', $projectIds);
        }

        return (object) [
            'total_tempo' => (clone $baseQuery)
                ->whereRaw('DATEDIFF(tgl_tempo, CURDATE()) BETWEEN 0 AND 15')
                ->count(),

            'jatuh_tempo' => (clone $baseQuery)
                ->whereRaw('DATEDIFF(tgl_tempo, CURDATE()) < 0')
                ->count(),

            'total_nominal' => (clone $baseQuery)
                ->whereRaw('DATEDIFF(tgl_tempo, CURDATE()) BETWEEN 0 AND 15')
                ->sum('total'),
        ];

    }

    /**
     * Data untuk modal transaksi tempo
     */
    protected function getTempoModalData()
    {
        $today = Carbon::today()->format('Y-m-d');
        $projectIds = $this->accessibleProjectIds();

        $query = Nota::select([
                'notas.id',
                'notas.nota_no',
                'notas.namatransaksi',
                'notas.tanggal',
                'notas.tgl_tempo',
                'notas.total',
                'notas.cashflow',
                'notas.status',
                DB::raw('DATEDIFF(tgl_tempo, CURDATE()) as sisa_hari'),
                'vendors.namavendor',
                'projects.namaproject',
                'company_units.company_name'
            ])
            ->leftJoin('vendors', 'notas.vendor_id', '=', 'vendors.id')
            ->leftJoin('projects', 'notas.idproject', '=', 'projects.id')
            ->leftJoin('company_units', 'notas.idcompany', '=', 'company_units.id')
            ->where('paymen_method', 'tempo')
            ->where('status', '!=', 'paid')
            ->whereRaw('DATEDIFF(tgl_tempo, CURDATE()) BETWEEN 0 AND 15');

        // 🔒 Batasi project sesuai hak user
        if ($projectIds) {
            $query->whereIn('notas.idproject', $projectIds);
        }

        return $query
            ->orderBy('notas.tgl_tempo', 'asc')
            ->get()
            ->map(function ($item) {
                $statusTempo = 'Akan Jatuh Tempo';
                $badgeClass = 'badge-warning';

                if ($item->sisa_hari < 0) {
                    $statusTempo = 'Jatuh Tempo';
                    $badgeClass = 'badge-danger';
                } elseif ($item->sisa_hari <= 3) {
                    $statusTempo = 'Mendekati Jatuh Tempo';
                    $badgeClass = 'badge-info';
                }

                $jenisTransaksi = $item->cashflow == 'in' ? 'Penerimaan' : 'Pengeluaran';
                $jenisBadge = $item->cashflow == 'in' ? 'badge-success' : 'badge-primary';

                return [
                    'id' => $item->id,
                    'nota_no' => $item->nota_no,
                    'namatransaksi' => $item->namatransaksi,
                    'tanggal' => Carbon::parse($item->tanggal)->format('d/m/Y'),
                    'tgl_tempo' => Carbon::parse($item->tgl_tempo)->format('d/m/Y'),
                    'total' => number_format($item->total, 0, ',', '.'),
                    'total_raw' => $item->total,
                    'sisa_hari' => $item->sisa_hari,
                    'status_tempo' => $statusTempo,
                    'badge_tempo_class' => $badgeClass,
                    'jenis_transaksi' => $jenisTransaksi,
                    'jenis_badge' => $jenisBadge,
                    'vendor' => $item->namavendor ?? '-',
                    'project' => $item->namaproject ?? '-',
                    'company' => $item->company_name ?? '-',
                    'cashflow' => $item->cashflow,
                    'is_overdue' => $item->sisa_hari < 0,
                    'is_near_due' => $item->sisa_hari <= 3 && $item->sisa_hari >= 0,
                ];
            });
    }

    protected function accessibleProjectIds()
    {
        $user = auth()->user();

        return $user->projects()->pluck('projects.id');
    }

}