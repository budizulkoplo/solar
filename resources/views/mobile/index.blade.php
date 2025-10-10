@extends('layouts.mobile')

@section('content')

<link rel="stylesheet" href="{{ asset('assets/css/homes.css') }}">

<div id="user-section">
    <div id="user-detail">
        <div class="profil">
            @if($user->foto)
                <img src="{{ asset('storage/foto/' . $user->foto) }}" alt="avatar" loading="lazy">
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
                        ['label' => 'Hadir', 'icon' => 'accessibility-outline', 'value' => $rekappresensi->jmlhadir ?? 0, 'color' => 'primary'],
                        ['label' => 'Izin', 'icon' => 'newspaper-outline', 'value' => $rekapizin->jmlizin ?? 0, 'color' => 'warning'],
                        ['label' => 'Sakit', 'icon' => 'medkit-outline', 'value' => $rekapizin->jmlsakit ?? 0, 'color' => 'danger'],
                        ['label' => 'Telat', 'icon' => 'alarm-outline', 'value' => $rekappresensi->jmlterlambat ?? 0, 'color' => 'danger'],
                    ];
                @endphp

                @foreach($presensiData as $data)
                <div class="col-3 mb-2">
                    <div class="card stat-card">
                        <div class="card-body position-relative">
                            <span class="badge bg-danger position-absolute count-badge">
                                {{ $data['value'] }}
                            </span>
                            <ion-icon name="{{ $data['icon'] }}" class="text-{{ $data['color'] }} stat-icon"></ion-icon>
                            <br>
                            <span class="stat-label">{{ $data['label'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
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
                                                    <h6 class="presence-time {{ $jamMasuk && $jamMasuk > '08:00' ? 'text-danger' : 'text-success' }}">
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
                                                    @if($d->foto)
                                                        <img src="{{ asset('storage/foto/' . $d->foto) }}" alt="avatar" loading="lazy">
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
                                                <span class="time-badge {{ $jamMasuk && $jamMasuk > '08:00' ? 'badge-late' : 'badge-ontime' }}">
                                                    {{ $jamMasuk ? \Carbon\Carbon::parse($jamMasuk)->format('H:i') : '-' }}
                                                </span>
                                                <span class="time-badge {{ $jamPulang ? 'badge-pulang' : 'badge-nopulang' }}">
                                                    {{ $jamPulang ? \Carbon\Carbon::parse($jamPulang)->format('H:i') : '-' }}
                                                </span>
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
/* General Styles */
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
    padding-bottom: 12px;
    border-bottom: 2px solid #f8f9fa;
}

/* Stat Cards */
.stat-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card .card-body {
    padding: 12px 8px !important;
    line-height: 0.8rem;
    position: relative;
}

.count-badge {
    top: 2px;
    right: 5px;
    font-size: 0.55rem;
    z-index: 999;
}

.stat-icon {
    font-size: 1.4rem;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.7rem;
    font-weight: 500;
    color: #495057;
}

/* Tabs */
.nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 16px;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    border-radius: 8px 8px 0 0;
    font-size: 0.75rem;    /* lebih kecil */
    padding: 4px 6px;      /* tinggi tab lebih rendah */
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;              /* ikon & teks rapat */
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
    margin-bottom: 16px;
    color: #2c3e50;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f3f4;
}

/* Presence Cards */
.stylish-presence .presence-card {
    background: #fff;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    border-left: 4px solid #007bff;
}

.presence-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 12px;
    color: #2c3e50;
}

.presence-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 8px 0;
}

.presence-item {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}

.presence-icon-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.presence-icon-container ion-icon {
    font-size: 1.1rem;
}

.presence-info {
    flex: 1;
}

.presence-label {
    display: block;
    font-size: 0.7rem;
    color: #6c757d;
    font-weight: 500;
}

.presence-time {
    font-size: 0.9rem;
    margin: 0;
    font-weight: 600;
}

.presence-divider {
    width: 1px;
    height: 30px;
    background: #e9ecef;
    margin: 0 8px;
}

/* Leaderboard Cards */
.leaderboard-presence .leaderboard-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #fff;
    border-radius: 10px;
    margin-bottom: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    border-left: 4px solid #28a745;
}

.leaderboard-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.leaderboard-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

.user-name {
    font-size: 0.9rem;
    color: #2c3e50;
    margin: 0;
}

.user-position {
    font-size: 0.75rem;
    color: #6c757d;
}

.time-badge {
    font-size: 0.7rem;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 6px;
    min-width: 60px;
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

/* Empty States */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state ion-icon {
    font-size: 3rem;
    color: #ced4da;
    margin-bottom: 12px;
}

.empty-state p {
    font-size: 0.9rem;
    margin: 0;
    color: #6c757d;
}


</style>

@endsection