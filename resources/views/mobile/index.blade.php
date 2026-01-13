@extends('layouts.mobile')

@section('content')

<link rel="stylesheet" href="{{ asset('assets/css/homes.css') }}">

<div id="user-section">
    <div id="user-detail">
        <div class="profil">
            @if($user->foto)
                <img src="{{ asset('storage/uploads/karyawan/' . $user->foto) }}" alt="avatar" loading="lazy">
            @else
                <img src="{{ asset('assets/img/avatar1.jpg') }}" alt="avatar" loading="lazy">
            @endif
        </div>
        
        <div id="user-info">
            @php
                use App\Models\Setting;
                $setting = Setting::first();

                function singkatPerusahaan($nama) {
                    $parts = explode(' ', trim($nama));
                    $result = '';
                    foreach ($parts as $p) {
                        if (strlen($p) > 2) {
                            $result .= strtoupper(substr($p, 0, 1));
                        }
                    }
                    return $result;
                }

                $namaPendek = singkatPerusahaan($setting->nama_perusahaan ?? 'Perusahaan');
            @endphp
            <div id="user-role">{{ $setting->nama_perusahaan ?? 'Perusahaan' }}</div>
            <h3>{{ $user->name ?? 'Nama User' }}</h3>
            <div id="user-role">Unit: {{ $user->unitkerja?->company_name ?? '-' }}</div>
        </div>
    </div>
</div>

<!-- Rekap Presensi Section -->
<div class="performance-card mb-3">
    <div class="todaypresence">
        <div class="rekappresensi">
            <h3 class="section-title">
                Rekap Presensi Bulan {{ $namabulan[$bulanini] ?? 'Bulan' }} Tahun {{ $tahunini ?? date('Y') }}
            </h3>

            <div class="row text-center">
                @php
                    $presensiData = [
                        ['label' => 'Hadir', 'icon' => 'accessibility-outline', 'value' => $rekappresensi->jmlhadir ?? 0, 'color' => 'primary', 'type' => 'presensi'],
                        ['label' => 'Izin', 'icon' => 'newspaper-outline', 'value' => $rekapizin->jmlizin ?? 0, 'color' => 'warning', 'type' => 'presensi'],
                        ['label' => 'Sakit', 'icon' => 'medkit-outline', 'value' => $rekapizin->jmlsakit ?? 0, 'color' => 'danger', 'type' => 'presensi'],
                        ['label' => 'Telat', 'icon' => 'alarm-outline', 'value' => $rekappresensi->jmlterlambat ?? 0, 'color' => 'danger', 'type' => 'presensi'],
                    ];
                @endphp

                @foreach($presensiData as $data)
                <div class="col-3 mb-2">
                    <div class="card stat-card" data-type="{{ $data['type'] }}" data-label="{{ $data['label'] }}">
                        <div class="card-body position-relative">
                            @if($data['value'] > 0)
                                <span class="badge bg-danger position-absolute count-badge">
                                    {{ $data['value'] }}
                                </span>
                            @endif
                            <ion-icon name="{{ $data['icon'] }}" class="text-{{ $data['color'] }} stat-icon"></ion-icon>
                            
                            <span class="stat-label">{{ $data['label'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Task Management Summary -->
<div class="performance-card mb-3">
    <div class="todaypresence">
        <div class="rekappresensi">
            <h3 class="section-title">Task Management</h3>
            <div class="row text-center">
                @if($ticketSummary->count() > 0)
                    @foreach($ticketSummary as $status => $count)
                        @php
                            $color = match(strtolower($status)) {
                                'to do' => 'secondary',
                                'in progress' => 'info',
                                'review' => 'warning',
                                'done' => 'success',
                                default => 'dark'
                            };
                            
                            // Hitung total komentar untuk tiket dengan status ini
                            $ticketsForStatus = $userTickets->where('ticket_status', $status);
                            $totalComments = $ticketsForStatus->sum('comment_count');
                        @endphp
                        <div class="col-3 mb-2">
                            <div class="card stat-card" data-type="ticket" data-status="{{ $status }}">
                                <div class="card-body position-relative p-2">
                                    <!-- Jumlah Tiket di pojok KIRI ATAS -->
                                    @if($count > 0)
                                        <span class="badge bg-{{ $color }} position-absolute count-badge" 
                                              style="top: -5px; left: -5px; font-size: 0.5rem; padding: 2px 4px;">
                                            {{ $count }}
                                        </span>
                                    @endif
                                    
                                    <!-- Jumlah Komentar di pojok KANAN ATAS -->
                                    @if($totalComments > 0)
                                        <span class="badge bg-danger position-absolute comment-badge" 
                                              style="top: -5px; right: -5px; font-size: 0.5rem; padding: 2px 4px;">
                                            {{ $totalComments }}
                                            <ion-icon name="chatbubble" style="font-size: 0.5rem;"></ion-icon>
                                        </span>
                                    @endif
                                    
                                    <!-- Icon dan Label -->
                                    <ion-icon name="clipboard-outline" class="text-{{ $color }} stat-icon" style="font-size: 1.5rem;"></ion-icon>
                                 
                                    <span class="stat-label" style="font-size: 0.65rem;">{{ $status }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <p class="text-muted small">Tidak ada tiket</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Transaksi Tempo Section -->
<div class="performance-card mb-3">
    <div class="todaypresence">
        <div class="rekappresensi">
            <h3 class="section-title">Transaksi Tempo</h3>
            <div class="row text-center">
                @php
                    $tempoData = [
                        ['label' => 'Total Tempo', 'icon' => 'calendar-outline', 'value' => $transaksiTempo->total_tempo ?? 0, 'color' => 'primary', 'type' => 'tempo'],
                        ['label' => 'Jatuh Tempo', 'icon' => 'alert-circle-outline', 'value' => $transaksiTempo->jatuh_tempo ?? 0, 'color' => 'danger', 'type' => 'tempo'],
                        ['label' => 'Total Nominal', 'icon' => 'cash-outline', 'value' => 'Rp ' . number_format($transaksiTempo->total_nominal ?? 0, 0, ',', '.'), 'color' => 'success', 'type' => 'tempo'],
                    ];
                @endphp

                @foreach($tempoData as $data)
                <div class="col-4 mb-2">
                    <div class="card stat-card" data-type="{{ $data['type'] }}" data-label="{{ $data['label'] }}">
                        <div class="card-body position-relative p-2">
                            @if($data['value'] > 0 && !is_string($data['value']))
                                <span class="badge bg-danger position-absolute count-badge">
                                    {{ $data['value'] }}
                                </span>
                            @endif
                            <ion-icon name="{{ $data['icon'] }}" class="text-{{ $data['color'] }} stat-icon" style="font-size: 1.5rem;"></ion-icon>
                            
                            <span class="stat-label" style="font-size: 0.65rem;">{{ $data['label'] }}</span>
                            @if($data['label'] == 'Total Nominal')
                                <small class="d-block text-muted" style="font-size: 0.6rem;">{{ $data['value'] }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Modal Transaksi Tempo -->
<div class="modal fade" id="tempoModal" tabindex="-1" aria-labelledby="tempoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="section-title text-white" id="tempoModalLabel">Daftar Transaksi Tempo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <!-- Filter Status -->
        <!-- <div class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Filter berdasarkan:</small>
                </div>
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="tempoFilter" id="filterAll" checked>
                    <label class="btn btn-outline-primary" for="filterAll">Semua</label>
                    
                    <input type="radio" class="btn-check" name="tempoFilter" id="filterOverdue">
                    <label class="btn btn-outline-danger" for="filterOverdue">Jatuh Tempo</label>
                    
                    <input type="radio" class="btn-check" name="tempoFilter" id="filterNearDue">
                    <label class="btn btn-outline-warning" for="filterNearDue">Mendekati</label>
                    
                    <input type="radio" class="btn-check" name="tempoFilter" id="filterFuture">
                    <label class="btn btn-outline-info" for="filterFuture">Akan Datang</label>
                </div>
            </div>
        </div> -->
        
        <!-- Daftar Transaksi -->
        <div class="p-3">
            <div id="tempoListContainer">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Memuat data transaksi tempo...</p>
                </div>
            </div>
            
            <!-- Summary -->
            <div class="mt-3 p-3 bg-light rounded" id="tempoSummary">
                <!-- Summary akan diisi lewat JS -->
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Agenda Section -->
<div class="performance-card mb-3">
    <div class="todaypresence">
        <div class="rekappresensi">
            <h3 class="section-title">Agenda</h3>
            <div class="row">
                <div class="col-12">
                    <div class="card stat-card" id="agendaCard" data-type="agenda">
                        <div class="card-body position-relative">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <ion-icon name="calendar-outline" class="text-primary stat-icon"></ion-icon>
                                    <div class="text-start">
                                        <div class="stat-label">Lihat Agenda</div>
                                        <small class="text-muted" id="agendaCount">Memuat agenda...</small>
                                    </div>
                                </div>
                                <ion-icon name="chevron-forward-outline" class="text-primary"></ion-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agenda -->
<div class="modal fade" id="agendaModal" tabindex="-1" aria-labelledby="agendaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="section-title text-white" id="agendaModalLabel">Daftar Agenda</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <!-- Filter Bulan -->
        <div class="p-3 border-bottom">
            <form id="agendaFilterForm">
                <div class="row g-2">
                    <div class="col-8">
                        <input type="month" id="agendaMonth" name="bulan" class="form-control form-control-sm" 
                               value="{{ date('Y-m') }}">
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <ion-icon name="filter-outline" class="me-1"></ion-icon> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Daftar Agenda -->
        <div class="p-3">
            <div id="agendaListContainer">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Memuat agenda...</p>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Presensi -->
<div class="modal fade" id="presensiModal" tabindex="-1" aria-labelledby="presensiModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="section-title text-white" id="presensiModalLabel">Detail Presensi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="list-group" id="presensiList">
          <!-- List presensi akan diisi lewat JS -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tiket -->
<div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="section-title text-white" id="ticketModalLabel">Daftar Tiket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="list-group" id="ticketList">
          <!-- List tiket akan diisi lewat JS -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Tabs Bulan Ini & Leaderboard -->
<div class="performance-card">
    <div class="todaypresence">
        <div class="presencetab">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item flex-fill">
                    <a class="nav-link active text-center" data-toggle="tab" href="#bulanIni" role="tab">
                        <ion-icon name="calendar-outline" class="tab-icon"></ion-icon>
                        <span>Bulan Ini</span>
                    </a>
                </li>
                <li class="nav-item flex-fill">
                    <a class="nav-link text-center" data-toggle="tab" href="#leaderboard" role="tab">
                        <ion-icon name="trophy-outline" class="tab-icon"></ion-icon>
                        <span>Leaderboard</span>
                    </a>
                </li>
            </ul>
            <div class="tab-content">
                <!-- Bulan Ini -->
                <div class="tab-pane fade show active" id="bulanIni" role="tabpanel">
                    <div class="tab-section">
                        <h5 class="tab-section-title">Riwayat Presensi Bulan Ini</h5>
                        <ul class="listview image-listview stylish-presence">
                            @if(count($rekapPresensiBulanIni) > 0)
                                @foreach ($rekapPresensiBulanIni as $tanggal => $data)
                                    @php
                                        \Carbon\Carbon::setLocale('id');
                                        $tglLabel = \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y');
                                        $jamMasuk = $data['masuk']->jam_in ?? null;
                                        $jamPulang = $data['pulang']->jam_in ?? null;
                                        $jamMasukShift = $data['jam_masuk_shift'];
                                    @endphp

                                    <li class="presence-card">
                                        <div class="presence-header">
                                            <ion-icon name="calendar-outline" class="text-primary"></ion-icon>
                                            <span class="presence-date">{{ $tglLabel }}</span>
                                        </div>

                                        <div class="presence-body">
                                            <div class="presence-item">
                                                <div class="presence-icon-container">
                                                    <ion-icon name="log-in-outline" class="text-success"></ion-icon>
                                                </div>
                                                <div class="presence-info">
                                                    <small class="presence-label">Absen Masuk</small>
                                                    <h6 class="presence-time {{ $jamMasuk && $jamMasuk > $jamMasukShift ? 'text-danger' : 'text-success' }}">
                                                        {{ $jamMasuk ? \Carbon\Carbon::parse($jamMasuk)->format('H:i') : '-' }}
                                                    </h6>
                                                </div>
                                            </div>

                                            <div class="presence-divider"></div>

                                            <div class="presence-item">
                                                <div class="presence-icon-container">
                                                    <ion-icon name="log-out-outline" class="text-danger"></ion-icon>
                                                </div>
                                                <div class="presence-info">
                                                    <small class="presence-label">Absen Pulang</small>
                                                    <h6 class="presence-time {{ $jamPulang ? 'text-danger' : 'text-muted' }}">
                                                        {{ $jamPulang ? \Carbon\Carbon::parse($jamPulang)->format('H:i') : 'Belum absen' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @else
                                <li class="empty-state">
                                    <ion-icon name="calendar-outline"></ion-icon>
                                    <p>Tidak ada data presensi bulan ini</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="tab-pane fade" id="leaderboard" role="tabpanel">
                    <div class="tab-section">
                        <h5 class="tab-section-title">Leaderboard Presensi</h5>
                        <ul class="listview image-listview leaderboard-presence">
                            @if(count($leaderboard) > 0)
                                @foreach ($leaderboard->whereNotNull('jam_masuk')->sortBy('jam_masuk') as $d)
                                    @php
                                        $jamMasuk = $d->jam_masuk ?? null;
                                        $jamPulang = $d->jam_pulang ?? null;
                                    @endphp
                                    <li>
                                        <div class="leaderboard-item">
                                            <div class="leaderboard-left">
                                                <div class="avatar">
                                                    @if(!empty($d->foto_in))
                                                        <img src="{{ asset('storage/uploads/absensi/' . $d->foto_in) }}" alt="Foto Absen Masuk" loading="lazy">
                                                    @else
                                                        <img src="{{ asset('assets/img/avatar1.jpg') }}" alt="avatar" loading="lazy">
                                                    @endif
                                                </div>
                                                <div class="user-info">
                                                    <b class="user-name">{{ $d->name ?? '-' }}</b>
                                                    <small class="user-position">{{ $d->jabatan ?? '-' }}</small>
                                                </div>
                                            </div>

                                            <div class="leaderboard-right">
                                                <div class="time-row">
                                                    <span class="time-label">Masuk:</span>
                                                    <span class="time-badge {{ isset($jamMasukShift) && $jamMasuk && $jamMasuk > $jamMasukShift ? 'badge-late' : 'badge-ontime' }}">
                                                        {{ $jamMasuk ? \Carbon\Carbon::parse($jamMasuk)->format('H:i') : '-' }}
                                                    </span>
                                                </div>
                                                <div class="time-row">
                                                    <span class="time-label">Pulang:</span>
                                                    <span class="time-badge {{ $jamPulang ? 'badge-pulang' : 'badge-nopulang' }}">
                                                        {{ $jamPulang ? \Carbon\Carbon::parse($jamPulang)->format('H:i') : '-' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @else
                                <li class="empty-state">
                                    <ion-icon name="trophy-outline"></ion-icon>
                                    <p>Tidak ada data leaderboard</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* ===== GENERAL STYLES ===== */
.performance-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.section-title {
    font-size: 1.1rem;
    margin-bottom: 16px;
    color: #2c3e50;
    font-weight: 600;
    text-align: center;
    padding-bottom: 0;
}

/* ===== STAT CARDS ===== */
.stat-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
    cursor: pointer;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

.stat-card .card-body {
    padding: 8px !important;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 50px;
}

/* Badge positioning - FIXED */
.count-badge {
    position: absolute;
    top: -8px;
    left: -8px;
    font-size: 0.5rem;
    padding: 2px 4px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    z-index: 10;
    border: 1px solid #fff;
}

.comment-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    font-size: 0.5rem;
    padding: 2px 4px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(220,53,69,0.3);
    z-index: 10;
    background: linear-gradient(135deg, #dc3545, #ff6b6b) !important;
    border: 1px solid #fff;
}

.comment-badge ion-icon {
    margin-left: 1px;
    font-size: 0.5rem;
}

/* ICON CENTERED */
.stat-icon-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    margin-bottom: 4px;
    height: 40px;
}

.stat-icon {
    font-size: 1.8rem;
    text-align: center;
}

.stat-label {
    font-size: 0.65rem;
    font-weight: 500;
    color: #495057;
    text-align: center;
    margin-top: 2px;
    width: 100%;
}

/* ===== TABS ===== */
.nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 16px;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    border-radius: 6px 6px 0 0;
    font-size: 0.7rem;
    padding: 2px 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1px;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: #f8f9fa;
    border-bottom: 3px solid #007bff;
}

.tab-icon {
    font-size: 1.2rem;
}

/* Tab Sections */
.tab-section {
    background-color: transparent;
    border-radius: 8px;
    padding: 0;
}

.tab-section-title {
    font-size: 0.95rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 12px;
    color: #2c3e50;
    padding-bottom: 0;
    border-bottom: none;
}

/* ===== PRESENCE CARDS ===== */
.stylish-presence .presence-card {
    background: #fff;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    border-left: 3px solid #007bff;
}

.presence-header {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 0.8rem;
    margin-bottom: 8px;
    color: #2c3e50;
}

.presence-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
}

.presence-item {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
}

.presence-icon-container {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
}

.presence-icon-container ion-icon {
    font-size: 0.9rem;
}

.presence-info {
    flex: 1;
}

.presence-label {
    display: block;
    font-size: 0.65rem;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 1px;
}

.presence-time {
    font-size: 0.8rem;
    margin: 0;
    font-weight: 600;
    line-height: 1.2;
}

.presence-divider {
    width: 1px;
    height: 24px;
    background: #e9ecef;
    margin: 0 6px;
}

/* ===== LEADERBOARD CARDS ===== */
.leaderboard-presence .leaderboard-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #fff;
    border-radius: 8px;
    margin-bottom: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    border-left: 3px solid #28a745;
}

.leaderboard-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.leaderboard-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 3px;
}

.avatar img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #e9ecef;
}

.profil img {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

.user-name {
    font-size: 0.85rem;
    color: #2c3e50;
    margin: 0;
    line-height: 1.2;
}

.user-position {
    font-size: 0.7rem;
    color: #6c757d;
    line-height: 1.2;
}

.time-badge {
    font-size: 0.65rem;
    font-weight: 500;
    padding: 3px 6px;
    border-radius: 4px;
    min-width: 55px;
    text-align: center;
}

.badge-ontime {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.badge-late {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f1b0b7;
}

.badge-pulang {
    background: #ffeaa7;
    color: #856404;
    border: 1px solid #ffda6a;
}

.badge-nopulang {
    background: #e9ecef;
    color: #495057;
    border: 1px solid #dee2e6;
}

/* ===== EMPTY STATES ===== */
.empty-state {
    text-align: center;
    padding: 30px 20px;
    color: #6c757d;
}

.empty-state ion-icon {
    font-size: 2.5rem;
    color: #ced4da;
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 0.85rem;
    margin: 0;
    color: #6c757d;
}

/* ===== TICKET & COMMENT STYLES ===== */
.ticket-description {
    line-height: 1.5;
}

.ticket-description p {
    margin-bottom: 0.5rem;
}

.ticket-description figure {
    margin: 1rem 0;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.ticket-description img,
.comment-text-bg img {
    max-width: 100%;
    height: auto;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: block;
    margin: 8px 0;
}

.comment-background {
    background-color: #fff8e1 !important;
    border-left: 3px solid #ffc107 !important;
    border-radius: 8px !important;
    margin-bottom: 10px !important;
}

.comment-text-bg {
    background-color: #fffde7 !important;
    font-style: italic !important;
    line-height: 1.5 !important;
    color: #5d4037 !important;
    border-radius: 6px !important;
    border: 1px solid #ffecb3 !important;
}

.comment-author {
    color: #e65100 !important;
    font-size: 0.9rem !important;
}

.comment-time {
    color: #8d6e63 !important;
    font-size: 0.75rem !important;
}

.comments-section {
    background: linear-gradient(to bottom, #f9f9f9, #fff);
    border-radius: 10px;
    padding: 15px;
    margin-top: 20px;
}

.empty-comments {
    background-color: #f5f5f5;
    border-radius: 8px;
    border: 2px dashed #bdbdbd;
    text-align: center;
    padding: 20px;
    color: #6c757d;
}

/* ===== CLEANUP ===== */
.listview.image-listview::before,
.listview.image-listview::after,
.presence-card::before,
.presence-card::after,
.leaderboard-item::before,
.leaderboard-item::after {
    display: none !important;
}

.stylish-presence,
.leaderboard-presence {
    border: none !important;
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 576px) {
    .performance-card {
        padding: 12px;
    }
    
    .section-title {
        font-size: 1rem;
    }
    
    .stat-card .card-body {
        padding: 6px !important;
        min-height: 80px;
    }
    
    .count-badge,
    .comment-badge {
        font-size: 0.45rem !important;
        padding: 1px 3px !important;
        min-width: 16px;
        height: 16px;
        top: -6px;
    }
    
    .count-badge {
        left: -6px;
    }
    
    .comment-badge {
        right: -6px;
    }
    
    .stat-icon {
        font-size: 1.6rem !important;
    }
    
    .stat-icon-container {
        height: 35px;
    }
    
    .stat-label {
        font-size: 0.6rem !important;
    }
    
    .presence-card {
        padding: 8px 10px;
    }
    
    .leaderboard-item {
        padding: 8px 10px;
    }
    
    .avatar img {
        width: 32px;
        height: 32px;
    }
    
    .comment-background {
        padding: 12px !important;
    }
    
    .comment-text-bg {
        font-size: 0.9rem !important;
    }
}

/* ===== ANIMATIONS ===== */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.comment-badge {
    animation: pulse 2s infinite;
}

/* ===== UTILITY CLASSES ===== */
.img-fluid {
    max-width: 100%;
    height: auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const allTickets = @json($userTickets ?? []);
    const presensiModalEl = document.getElementById('presensiModal');
    const ticketModalEl = document.getElementById('ticketModal');
    const presensiList = document.getElementById('presensiList');
    const ticketList = document.getElementById('ticketList');

    // Data presensi dari controller (akan diisi via AJAX atau langsung dari data yang ada)
    const presensiData = @json($rekapPresensiBulanIni ?? []);

    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', function () {
            const type = this.getAttribute('data-type');
            const label = this.getAttribute('data-label');
            const status = this.getAttribute('data-status');

            if (type === 'presensi') {
                showPresensiModal(label);
            } else if (type === 'ticket') {
                showTicketModal(status);
            }
        });
    });

    function showPresensiModal(label) {
        let filteredData = [];
        let modalTitle = 'Detail Presensi';
        
        // Filter data berdasarkan label
        Object.keys(presensiData).forEach(tanggal => {
            const data = presensiData[tanggal];
            const jamMasuk = data.masuk?.jam_in;
            const jamPulang = data.pulang?.jam_in;
            const jamMasukShift = data.jam_masuk_shift;
            
            let status = 'Hadir';
            if (!jamMasuk && !jamPulang) {
                status = 'Tidak Hadir';
            } else if (jamMasuk && jamMasukShift) {
                const isTerlambat = new Date(`1970-01-01T${jamMasuk}`) > new Date(`1970-01-01T${jamMasukShift}`);
                if (isTerlambat) {
                    status = 'Terlambat';
                }
            }

            // Filter sesuai label
            if (label === 'Hadir' && (status === 'Hadir' || status === 'Terlambat')) {
                filteredData.push({ tanggal, data, status });
            } else if (label === 'Telat' && status === 'Terlambat') {
                filteredData.push({ tanggal, data, status });
            }
        });

        // Jika label Izin atau Sakit, tampilkan pesan kosong
        if (label === 'Izin' || label === 'Sakit') {
            presensiList.innerHTML = `<div class="text-center p-3 text-muted">
                Tidak ada data ${label.toLowerCase()} untuk bulan ini.
            </div>`;
            document.getElementById('presensiModalLabel').textContent = `${modalTitle} - ${label}`;
            showModal(presensiModalEl);
            return;
        }

        // Tampilkan hasil filter
        presensiList.innerHTML = '';

        if (filteredData.length === 0) {
            presensiList.innerHTML = `<div class="text-center p-3 text-muted">
                Tidak ada data presensi untuk <b>${label}</b>.
            </div>`;
        } else {
            filteredData.forEach(({ tanggal, data, status }) => {
                const jamMasuk = data.masuk?.jam_in;
                const jamPulang = data.pulang?.jam_in;
                const jamMasukShift = data.jam_masuk_shift;
                
                const listItem = document.createElement('div');
                listItem.className = 'list-group-item list-group-item-action border-bottom';
                
                const tglLabel = new Date(tanggal).toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                listItem.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-dark">${tglLabel}</h6>
                        <span class="badge px-3 bg-${getPresensiStatusColor(status)}">
                            ${status}
                        </span>
                    </div>
                    <div class="row small text-muted">
                        <div class="col-6">
                            <strong>Masuk:</strong> ${jamMasuk ? new Date(`1970-01-01T${jamMasuk}`).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) : '-'}
                        </div>
                        <div class="col-6">
                            <strong>Pulang:</strong> ${jamPulang ? new Date(`1970-01-01T${jamPulang}`).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) : '-'}
                        </div>
                    </div>
                    ${data.shift && data.shift !== '-' ? `<small class="text-muted">Shift: ${data.shift}</small>` : ''}
                    ${jamMasukShift ? `<small class="text-muted d-block">Jam Shift: ${jamMasukShift}</small>` : ''}
                `;
                presensiList.appendChild(listItem);
            });
        }

        document.getElementById('presensiModalLabel').textContent = `${modalTitle} - ${label}`;
        showModal(presensiModalEl);
    }


    function showTicketModal(status) {
    // Filter tiket berdasarkan status
    const filtered = allTickets.filter(t => 
        t.ticket_status && t.ticket_status.toLowerCase() === status.toLowerCase()
    );

    ticketList.innerHTML = '';

    if (!filtered.length) {
        ticketList.innerHTML = `<div class="text-center p-3 text-muted">
            Tidak ada tiket dengan status <b>${status}</b>.
        </div>`;
    } else {
        filtered.forEach(ticket => {
            const listItem = document.createElement('div');
            listItem.className = 'list-group-item border-bottom p-3';
            
            const processedDescription = decodeHtmlEntities(ticket.description || '');
            const hasComments = ticket.comments && ticket.comments.length > 0;
            
            listItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 text-dark fw-bold">${ticket.ticket_name || '-'}</h6>
                        <small class="text-muted d-block">
                            <ion-icon name="time-outline" class="align-middle me-1"></ion-icon>
                            ${formatDate(ticket.status_created_at)}
                        </small>
                    </div>
                    <span class="badge px-3 py-2 bg-${getStatusColor(ticket.ticket_status)} ms-2">
                        ${ticket.ticket_status}
                    </span>
                </div>
                
                <div class="ticket-description mb-3 p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">
                        <ion-icon name="document-text-outline" class="me-1"></ion-icon>
                        Deskripsi
                    </h6>
                    ${processedDescription}
                </div>
                
                <div class="ticket-meta d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <small class="text-muted">
                            <ion-icon name="calendar-outline" class="me-1"></ion-icon>
                            Mulai: <span class="text-success fw-semibold">${formatSimpleDate(ticket.start_date)}</span>
                        </small>
                        <small class="text-muted ms-3">
                            <ion-icon name="flag-outline" class="me-1"></ion-icon>
                            Deadline: <span class="text-danger fw-semibold">${formatSimpleDate(ticket.due_date)}</span>
                        </small>
                    </div>
                    
                    ${hasComments ? `
                        <small class="text-primary">
                            <ion-icon name="chatbubble-ellipses-outline" class="me-1"></ion-icon>
                            ${ticket.comments.length} komentar
                        </small>
                    ` : ''}
                </div>
                
                ${hasComments ? `
                    <div class="comments-section mt-3 pt-3 border-top">
                        <h6 class="text-muted mb-3">
                            <ion-icon name="chatbubble-outline" class="me-1"></ion-icon>
                            Komentar (${ticket.comments.length})
                        </h6>
                        ${renderComments(ticket.comments)}
                    </div>
                ` : ''}
            `;
            ticketList.appendChild(listItem);
        });
    }

    document.getElementById('ticketModalLabel').textContent = `Tiket - ${status}`;
    showModal(ticketModalEl);
}

// Fungsi untuk render komentar dengan styling khusus
function renderComments(comments) {
    if (!comments || comments.length === 0) {
        return '<div class="empty-comments text-center p-4 text-muted">Tidak ada komentar</div>';
    }
    
    // Filter komentar unik berdasarkan ID
    const uniqueComments = comments.filter((comment, index, self) =>
        index === self.findIndex((c) => (
            c.id === comment.id && c.id !== null && c.id !== undefined
        ))
    );
    
    // Sort comments by date (newest first)
    const sortedComments = uniqueComments.sort((a, b) => {
        const dateA = a.created_at ? new Date(a.created_at) : new Date(0);
        const dateB = b.created_at ? new Date(b.created_at) : new Date(0);
        return dateB - dateA;
    });
    
    return sortedComments.map((comment, idx) => {
        // Process teks komentar
        let processedText = '';
        if (comment.text && comment.text.trim() !== '') {
            processedText = decodeHtmlEntities(comment.text);
        } else {
            processedText = '<span class="text-muted fst-italic">[Tidak ada teks]</span>';
        }
        
        return `
            <div class="comment-item mb-3 p-3 rounded comment-background">
                <div class="comment-meta d-flex justify-content-between align-items-center mb-2">
                    <span class="comment-user d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 28px; height: 28px; margin-right: 8px; font-size: 0.8rem;">
                            <ion-icon name="person" style="font-size: 0.9rem;"></ion-icon>
                        </div>
                        <div>
                            <strong class="comment-author">${comment.user_name || 'Anonymous'}</strong>
                            <div class="comment-time text-muted" style="font-size: 0.7rem;">
                                ${formatCommentTime(comment.created_at)}
                            </div>
                        </div>
                    </span>
                </div>
                <div class="comment-text mt-2 p-2 rounded comment-text-bg">
                    ${processedText}
                </div>
            </div>
        `;
    }).join('');
}

// Fungsi format waktu komentar
function formatCommentTime(timeString) {
    if (!timeString) return '';
    
    try {
        const date = new Date(timeString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Baru saja';
        if (diffMins < 60) return `${diffMins} menit lalu`;
        if (diffHours < 24) return `${diffHours} jam lalu`;
        if (diffDays < 7) return `${diffDays} hari lalu`;
        
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return timeString;
    }
}

// Fungsi format tanggal lengkap
function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

// Fungsi format tanggal singkat
function formatSimpleDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

    function showModal(modalEl) {
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        document.body.classList.add('modal-open');
        
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    // Close handlers untuk semua modal
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            closeAllModals();
        });
    });

    // Close ketika klik outside modal
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeAllModals();
            }
        });
    });

    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
            modal.classList.remove('show');
        });
        document.body.classList.remove('modal-open');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }

    // Helper functions
    function decodeHtmlEntities(text) {
        if (!text) return '<span class="text-muted">Tidak ada deskripsi</span>';
        
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        const decoded = textArea.value;
        
        return processImages(decoded);
    }

    function processImages(html) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        const images = tempDiv.querySelectorAll('img');
        images.forEach(img => {
            img.classList.add('img-fluid', 'rounded');
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            
            const figure = img.closest('figure');
            if (figure) {
                figure.style.margin = '10px 0';
                figure.style.textAlign = 'center';
            }
        });
        
        const links = tempDiv.querySelectorAll('a');
        links.forEach(link => {
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        });
        
        return tempDiv.innerHTML;
    }

    function getStatusColor(status) {
        if (!status) return 'dark';
        switch(status.toLowerCase()) {
            case 'done': return 'success';
            case 'review': return 'warning';
            case 'in progress': return 'info';
            case 'to do': return 'secondary';
            default: return 'dark';
        }
    }

    function getPresensiStatusColor(status) {
        switch(status) {
            case 'Hadir': return 'success';
            case 'Terlambat': return 'danger';
            case 'Tidak Hadir': return 'secondary';
            default: return 'dark';
        }
    }
});

// Click handler untuk card agenda
document.getElementById('agendaCard')?.addEventListener('click', function() {
    showAgendaModal();
    loadAgendaData();
});

// Function untuk menampilkan modal agenda
function showAgendaModal() {
    const modal = new bootstrap.Modal(document.getElementById('agendaModal'));
    modal.show();
}

// Function untuk load data agenda
function loadAgendaData(month = null) {
    const container = document.getElementById('agendaListContainer');
    const monthInput = document.getElementById('agendaMonth');
    const selectedMonth = month || monthInput.value;
    
    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2">Memuat agenda...</p>
        </div>
    `;
    
    fetch(`/mobile/dashboard/agenda?bulan=${selectedMonth}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderAgendaList(data.agenda, data.bulan_label, data.total);
            updateAgendaCount(data.total, data.bulan_label);
        } else {
            container.innerHTML = `
                <div class="alert alert-danger">
                    Gagal memuat data agenda
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading agenda:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                Terjadi kesalahan saat memuat agenda
            </div>
        `;
    });
}

// Function untuk render list agenda
function renderAgendaList(agenda, monthLabel, total) {
    const container = document.getElementById('agendaListContainer');
    
    if (total === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <ion-icon name="calendar-outline" style="font-size: 3rem; color: #ccc;"></ion-icon>
                <p class="text-muted mt-2">Tidak ada agenda untuk ${monthLabel}(</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="mb-3">
            <small class="text-muted">Menampilkan ${total} agenda untuk ${monthLabel}</small>
        </div>
    `;
    
    agenda.forEach(item => {
        html += `
            <div class="card mb-3" style="background-color: ${item.bg_color}; border-left: 3px solid ${item.is_past ? '#dc3545' : '#28a745'}">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="text-warning mb-2">${item.namaagenda}</h6>
                            <div class="small text-muted">
                                <div class="d-flex align-items-center mb-1">
                                    <ion-icon name="calendar-outline" class="me-1"></ion-icon>
                                    &nbsp; ${item.formatted_date}
                                </div>
                                <div class="d-flex align-items-center mb-1">
                                    <ion-icon name="time-outline" class="me-1"></ion-icon>
                                    &nbsp; ${item.formatted_time}
                                </div>
                                <div class="d-flex align-items-center mb-1">
                                    <ion-icon name="pricetag-outline" class="me-1"></ion-icon>
                                    &nbsp; ${item.jenis}
                                </div>
                                <div class="d-flex align-items-center mb-1">
                                    <ion-icon name="location-outline" class="me-1"></ion-icon>
                                    &nbsp; ${item.lokasi}
                                </div>
                                <div class="d-flex align-items-center mb-1">
                                    <ion-icon name="people-outline" class="me-1"></ion-icon>
                                    &nbsp; ${item.peserta}
                                </div>
                                <div class="d-flex align-items-center">
                                    <ion-icon name="person-outline" class="me-1"></ion-icon>
                                    &nbsp; ${item.creator}
                                </div>
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <span class="badge ${item.badge_class} px-3 py-1 rounded-pill">
                                ${item.status}
                            </span>
                            ${item.is_owner ? `
                            <div class="mt-2">
                                <button class="btn btn-sm btn-danger delete-agenda" 
                                        data-id="${item.id}" 
                                        onclick="deleteAgenda(${item.id})">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Function untuk update agenda count di card
function updateAgendaCount(count, monthLabel) {
    const agendaCountElement = document.getElementById('agendaCount');
    if (agendaCountElement) {
        agendaCountElement.innerHTML = `&nbsp; ${count} agenda (${monthLabel}) &nbsp;`;
    }
}

// Function untuk delete agenda
function deleteAgenda(id) {
    if (!confirm('Yakin ingin menghapus agenda ini?')) {
        return;
    }
    
    fetch(`/agenda/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Agenda berhasil dihapus');
            // Reload data agenda
            const currentMonth = document.getElementById('agendaMonth').value;
            loadAgendaData(currentMonth);
        } else {
            showToast('error', data.message || 'Gagal menghapus agenda');
        }
    })
    .catch(error => {
        console.error('Error deleting agenda:', error);
        showToast('error', 'Terjadi kesalahan saat menghapus agenda');
    });
}

// Function untuk show toast notification
function showToast(type, message) {
    // Buat toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Tambahkan ke container toast
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    document.querySelector('.toast-container').appendChild(toast);
    
    // Inisialisasi dan tampilkan toast
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    
    // Hapus toast setelah selesai
    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
}

// Event listener untuk form filter agenda
document.getElementById('agendaFilterForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const month = document.getElementById('agendaMonth').value;
    loadAgendaData(month);
});

// Load agenda count saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Load initial agenda count
    const currentMonth = new Date().toISOString().slice(0, 7);
    fetch(`/mobile/dashboard/agenda?bulan=${currentMonth}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAgendaCount(data.total, data.bulan_label);
            }
        })
        .catch(error => {
            console.error('Error loading agenda count:', error);
            document.getElementById('agendaCount').textContent = 'Gagal memuat';
        });
});

// Di bagian document.addEventListener('DOMContentLoaded', function () { ...
const tempoModalEl = document.getElementById('tempoModal');
const tempoListContainer = document.getElementById('tempoListContainer');
const tempoSummary = document.getElementById('tempoSummary');

// Data transaksi tempo dari controller
const tempoData = @json($tempoModalData ?? []);

// Di bagian event listener stat-card, tambahkan handler untuk tempo
document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('click', function () {
        const type = this.getAttribute('data-type');
        const label = this.getAttribute('data-label');
        const status = this.getAttribute('data-status');

        if (type === 'presensi') {
            showPresensiModal(label);
        } else if (type === 'ticket') {
            showTicketModal(status);
        } else if (type === 'tempo') {
            showTempoModal(label);
        }
    });
});

// Function untuk menampilkan modal transaksi tempo
function showTempoModal(label) {
    // Render daftar transaksi
    renderTempoList();
    
    // Render summary
    renderTempoSummary();
    
    // Set judul modal berdasarkan label
    let modalTitle = 'Daftar Transaksi Tempo';
    if (label === 'Total Tempo') {
        modalTitle = 'Semua Transaksi Tempo';
    } else if (label === 'Jatuh Tempo') {
        modalTitle = 'Transaksi Jatuh Tempo';
        // Otomatis filter jatuh tempo
        document.getElementById('filterOverdue').checked = true;
        filterTempoList('overdue');
    } else if (label === 'Total Nominal') {
        modalTitle = 'Ringkasan Transaksi Tempo';
    }
    
    document.getElementById('tempoModalLabel').textContent = modalTitle;
    
    // Tampilkan modal
    const modal = new bootstrap.Modal(tempoModalEl);
    modal.show();
}

// Function untuk render daftar transaksi tempo
function renderTempoList(filterType = 'all') {
    let filteredData = tempoData;
    
    // Filter data berdasarkan tipe
    if (filterType === 'overdue') {
        filteredData = tempoData.filter(item => item.is_overdue);
    } else if (filterType === 'near-due') {
        filteredData = tempoData.filter(item => item.is_near_due);
    } else if (filterType === 'future') {
        filteredData = tempoData.filter(item => !item.is_overdue && !item.is_near_due);
    }
    
    if (filteredData.length === 0) {
        tempoListContainer.innerHTML = `
            <div class="text-center py-4">
                <ion-icon name="receipt-outline" style="font-size: 3rem; color: #ccc;"></ion-icon>
                <p class="text-muted mt-2">Tidak ada transaksi tempo</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="mb-2">
            <small class="text-muted">Menampilkan ${filteredData.length} transaksi</small>
        </div>
    `;
    
    filteredData.forEach((item, index) => {
        // Warna border berdasarkan status
        let borderColor = '#28a745'; // default hijau
        if (item.is_overdue) {
            borderColor = '#dc3545'; // merah untuk jatuh tempo
        } else if (item.is_near_due) {
            borderColor = '#ffc107'; // kuning untuk mendekati
        }
        
        // Icon berdasarkan jenis transaksi
        let iconName = item.cashflow === 'in' ? 'arrow-down-circle-outline' : 'arrow-up-circle-outline';
        let iconColor = item.cashflow === 'in' ? 'text-success' : 'text-primary';
        
        html += `
            <div class="card mb-3 border-start" style="border-left-color: ${borderColor} !important; border-left-width: 4px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-1">
                                <ion-icon name="${iconName}" class="${iconColor} me-2"></ion-icon>
                                <h6 class="mb-0 text-dark">${item.nota_no}</h6>
                            </div>
                            <h6 class="text-primary mb-1">${item.namatransaksi}</h6>
                            
                            <div class="row small text-muted mb-2">
                                <div class="col-6">
                                    <ion-icon name="calendar-outline" class="me-1"></ion-icon>
                                    Tanggal: ${item.tanggal}
                                </div>
                                <div class="col-6">
                                    <ion-icon name="time-outline" class="me-1"></ion-icon>
                                    Jatuh Tempo: ${item.tgl_tempo}
                                </div>
                            </div>
                            
                            <div class="row small text-muted mb-2">
                                <div class="col-6">
                                    <ion-icon name="${item.cashflow === 'in' ? 'trending-up' : 'trending-down'}" class="me-1"></ion-icon>
                                    ${item.jenis_transaksi}
                                </div>
                                <div class="col-6">
                                    <ion-icon name="business-outline" class="me-1"></ion-icon>
                                    ${item.project || item.company || '-'}
                                </div>
                            </div>
                            
                            <div class="small text-muted">
                                <ion-icon name="person-outline" class="me-1"></ion-icon>
                                ${item.vendor}
                            </div>
                        </div>
                        
                        <div class="text-end ms-3">
                            <div class="mb-2">
                                <span class="badge ${item.jenis_badge} px-3 py-1">
                                    ${item.jenis_transaksi}
                                </span>
                            </div>
                            <div class="mb-2">
                                <span class="badge ${item.badge_tempo_class} px-3 py-1">
                                    ${item.status_tempo}
                                </span>
                            </div>
                            <div>
                                <h5 class="text-danger mb-0">Rp ${item.total}</h5>
                                <small class="text-muted">${Math.abs(item.sisa_hari)} hari ${item.sisa_hari < 0 ? 'lewat' : 'lagi'}</small>
                            </div>
                        </div>
                    </div>
                    
                    ${item.sisa_hari < 0 ? `
                        <div class="alert alert-danger py-2 mt-2 mb-0">
                            <div class="d-flex align-items-center">
                                <ion-icon name="warning-outline" class="me-2"></ion-icon>
                                <small>Telah lewat ${Math.abs(item.sisa_hari)} hari dari tanggal jatuh tempo</small>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${item.sisa_hari <= 3 && item.sisa_hari >= 0 ? `
                        <div class="alert alert-warning py-2 mt-2 mb-0">
                            <div class="d-flex align-items-center">
                                <ion-icon name="alert-circle-outline" class="me-2"></ion-icon>
                                <small>Akan jatuh tempo dalam ${item.sisa_hari} hari</small>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    tempoListContainer.innerHTML = html;
}

// Function untuk render summary
function renderTempoSummary() {
    // Hitung total per kategori
    const totalItems = tempoData.length;
    const overdueItems = tempoData.filter(item => item.is_overdue).length;
    const nearDueItems = tempoData.filter(item => item.is_near_due).length;
    const futureItems = tempoData.filter(item => !item.is_overdue && !item.is_near_due).length;
    
    // Hitung total nominal
    const totalNominal = tempoData.reduce((sum, item) => sum + item.total_raw, 0);
    const overdueNominal = tempoData.filter(item => item.is_overdue)
        .reduce((sum, item) => sum + item.total_raw, 0);
    
    let html = `
        <h6 class="mb-3">Ringkasan Transaksi Tempo</h6>
        <div class="row">
            <div class="col-4 mb-2">
                <div class="text-center">
                    <div class="text-primary fw-bold">${totalItems}</div>
                    <small class="text-muted">Total Transaksi</small>
                </div>
            </div>
            <div class="col-4 mb-2">
                <div class="text-center">
                    <div class="text-danger fw-bold">${overdueItems}</div>
                    <small class="text-muted">Jatuh Tempo</small>
                </div>
            </div>
            <div class="col-4 mb-2">
                <div class="text-center">
                    <div class="text-warning fw-bold">${nearDueItems}</div>
                    <small class="text-muted">Mendekati</small>
                </div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-6">
                <div class="text-center">
                    <div class="text-success fw-bold">Rp ${formatNumber(totalNominal)}</div>
                    <small class="text-muted">Total Nominal</small>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center">
                    <div class="text-danger fw-bold">Rp ${formatNumber(overdueNominal)}</div>
                    <small class="text-muted">Nominal Jatuh Tempo</small>
                </div>
            </div>
        </div>
    `;
    
    tempoSummary.innerHTML = html;
}

// Function untuk filter daftar tempo
function filterTempoList(filterType) {
    renderTempoList(filterType);
}

// Helper function untuk format number
function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

// Event listener untuk filter radio buttons
document.querySelectorAll('input[name="tempoFilter"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const filterType = this.id.replace('filter', '').toLowerCase();
        filterTempoList(filterType);
    });
});

// Event listener untuk modal show
tempoModalEl.addEventListener('show.bs.modal', function() {
    // Reset ke filter semua saat modal dibuka
    document.getElementById('filterAll').checked = true;
    renderTempoList('all');
    renderTempoSummary();
});
</script>

@endsection