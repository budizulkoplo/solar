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

            <!-- Tabs Bulan Ini & Leaderboard -->
            <div class="presencetab mt-2">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#home" role="tab">Bulan Ini</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#profile" role="tab">Leaderboard</a>
                    </li>
                </ul>

                <div class="tab-content mt-2" style="margin-bottom:100px;">
                    <!-- Bulan Ini -->
                    <div class="tab-pane fade show active" id="home" role="tabpanel">
                        <ul class="listview image-listview stylish-presence">
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
                        </ul>
                    </div>

                    <!-- Leaderboard -->
                    <div class="tab-pane fade" id="profile" role="tabpanel">
                        <ul class="listview image-listview leaderboard-presence">
                            @foreach ($leaderboard->whereNotNull('jam_masuk')->sortBy('jam_masuk') as $d)
                                @php
                                    $jamMasuk = $d->jam_masuk ?? null;
                                    $jamPulang = $d->jam_pulang ?? null;
                                @endphp
                                <li>
                                    <div class="leaderboard-item">
                                        <!-- Kiri: Avatar + Nama/Jabatan -->
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

                                        <!-- Kanan: Jam Masuk & Pulang 2 row -->
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
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Bulan Ini */
.stylish-presence .presence-card {
    background: #fff;
    border-radius: 10px;
    padding: 10px 15px;
    margin-bottom: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

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

.leaderboard-presence .leaderboard-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start; /* Penting! supaya badge tidak ikut center */
    padding: 10px 12px;
    background: #fff;
    border-radius: 10px;
    margin-bottom: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.leaderboard-presence .left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.leaderboard-presence .user-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: left;
}

.leaderboard-presence .right {
    display: flex;
    flex-direction: column;  /* 2 baris Masuk & Pulang */
    justify-content: flex-start; 
    align-items: flex-end;   /* Rata kanan */
}

</style>

@endsection
