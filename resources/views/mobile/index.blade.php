@extends('layouts.mobile')

@section('content')

<link rel="stylesheet" href="{{ asset('assets/css/homes.css') }}">

<div id="user-section">
    <div id="user-detail">
        <div class="avatar">
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
<div class="performance-card">
    <div class="todaypresence">
        <div class="rekappresensi">
            <h3>
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
                    <div class="card">
                        <div class="card-body position-relative" style="padding:16px 12px !important; line-height:0.8rem;">
                            <span class="badge bg-danger position-absolute" style="top:3px; right:10px; font-size:0.6rem; z-index:999;">
                                {{ $data['value'] }}
                            </span>
                            <ion-icon name="{{ $data['icon'] }}" style="font-size:1.6rem;" class="text-{{ $data['color'] }} mb-1"></ion-icon>
                            <br>
                            <span style="font-size:0.8rem; font-weight:500;">{{ $data['label'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Tabs Bulan Ini & Leaderboard -->
<div class="performance-card mt-3">
    <div class="todaypresence">
        <div class="presencetab">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item flex-fill">
                    <a class="nav-link active text-center py-2" data-toggle="tab" href="#bulanIni" role="tab">Bulan Ini</a>
                </li>
                <li class="nav-item flex-fill">
                    <a class="nav-link text-center py-2" data-toggle="tab" href="#leaderboard" role="tab">Leaderboard</a>
                </li>
            </ul>

            <div class="tab-content mt-3">

                <!-- Bulan Ini -->
                <div class="tab-pane fade show active" id="bulanIni" role="tabpanel">
                    <div class="tab-section bg-light rounded-3 p-3 shadow-sm mb-4">
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
                                                <ion-icon name="log-in-outline" class="text-success"></ion-icon>
                                                <div class="presence-info">
                                                    <small>Absen Masuk</small>
                                                    <h6 class="mb-0 {{ $jamMasuk && $jamMasuk > '08:00' ? 'text-danger' : 'text-success' }}">
                                                        {{ $jamMasuk ? \Carbon\Carbon::parse($jamMasuk)->format('H:i') : '-' }}
                                                    </h6>
                                                </div>
                                            </div>

                                            <div class="presence-item">
                                                <ion-icon name="log-out-outline" class="text-danger"></ion-icon>
                                                <div class="presence-info">
                                                    <small>Absen Pulang</small>
                                                    <h6 class="mb-0 {{ $jamPulang ? 'text-danger' : 'text-muted' }}">
                                                        {{ $jamPulang ? \Carbon\Carbon::parse($jamPulang)->format('H:i') : 'Belum absen' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @else
                                <li class="text-center py-4">
                                    <ion-icon name="calendar-outline" style="font-size: 3rem; color: #ccc;"></ion-icon>
                                    <p class="mt-2 text-muted">Tidak ada data presensi bulan ini</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="tab-pane fade" id="leaderboard" role="tabpanel">
                    <div class="tab-section bg-light rounded-3 p-3 shadow-sm">
                        <ul class="listview image-listview leaderboard-presence">
                            @if(count($leaderboard) > 0)
                                @foreach ($leaderboard->whereNotNull('jam_masuk')->sortBy('jam_masuk') as $d)
                                    @php
                                        $jamMasuk = $d->jam_masuk ?? null;
                                        $jamPulang = $d->jam_pulang ?? null;
                                    @endphp
                                    <li>
                                        <div class="leaderboard-item">
                                            <div class="left d-flex align-items-center gap-12">
                                                <div class="avatar">
                                                    @if($d->foto)
                                                        <img src="{{ asset('storage/foto/' . $d->foto) }}" alt="avatar" loading="lazy">
                                                    @else
                                                        <img src="{{ asset('assets/img/avatar1.jpg') }}" alt="avatar" loading="lazy">
                                                    @endif
                                                </div>
                                                <div class="user-info">
                                                    <b>{{ $d->name ?? '-' }}</b><br>
                                                    <small class="text-muted">{{ $d->jabatan ?? '-' }}</small>
                                                </div>
                                            </div>

                                            <div class="right">
                                                <span class="badge px-3 {{ $jamMasuk && $jamMasuk > '08:00' ? 'bg-danger' : 'bg-success' }}">
                                                    Masuk: {{ $jamMasuk ? \Carbon\Carbon::parse($jamMasuk)->format('H:i') : '-' }}
                                                </span>
                                                <span class="badge px-3 mt-1 {{ $jamPulang ? 'bg-danger' : 'bg-secondary' }}">
                                                    Pulang: {{ $jamPulang ? \Carbon\Carbon::parse($jamPulang)->format('H:i') : '-' }}
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @else
                                <li class="text-center py-4">
                                    <ion-icon name="trophy-outline" style="font-size: 3rem; color: #ccc;"></ion-icon>
                                    <p class="mt-2 text-muted">Tidak ada data leaderboard</p>
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
.performance-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.tab-section {
    background-color: #f8f9fa;
    border-radius: 12px;
    padding: 12px 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

/* Bulan Ini Cards */
.stylish-presence .presence-card {
    background: #fff;
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
    border-left: 4px solid #007bff;
}

/* Leaderboard Cards */
.leaderboard-presence .leaderboard-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 10px 12px;
    background: #fff;
    border-radius: 10px;
    margin-bottom: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
    border-left: 4px solid #28a745;
}

/* Typography & layout */
.presence-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 8px;
}

.presence-body {
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.presence-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.presence-info small {
    display: block;
    font-size: 10px;
    color: #666;
}

.presence-info h6 {
    font-size: 14px;
    margin: 0;
}

.leaderboard-presence .left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.leaderboard-presence .right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: flex-start;
}

.nav-tabs {
    border-bottom: 1px solid #dee2e6;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    border-radius: 8px 8px 0 0;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: #fff;
    border-bottom: 2px solid #007bff;
}

.avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.badge {
    font-size: 0.7rem;
    font-weight: 500;
}
</style>

@endsection