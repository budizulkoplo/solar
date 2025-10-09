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
                        <ul class="listview image-listview">
                            @foreach ($historibulanini as $d)
                                <li>
                                    <div class="item">
                                        <div class="icon-box bg-primary">
                                            <ion-icon name="finger-print-outline"></ion-icon>
                                        </div>
                                        <div class="in">
                                            <div>{{ \Carbon\Carbon::parse($d->tgl_presensi)->format('d-m-Y') }}</div>
                                            <span class="badge {{ $d->jam_in < '15:00' ? 'bg-primary' : 'bg-danger' }}">
                                                {{ $d->jam_in ?? '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Leaderboard -->
                    <div class="tab-pane fade" id="profile" role="tabpanel">
                        <ul class="listview image-listview">
                            @foreach ($leaderboard as $d)
                                <li>
                                    <div class="item">
                                        <img src="{{ asset('assets/img/sample/avatar/avatar1.jpg') }}" alt="image" class="image">
                                        <div class="in">
                                            <div>
                                                <b>{{ $d->nama_lengkap ?? '-' }}</b><br>
                                                <small class="text-muted">{{ $d->jabatan ?? '-' }}</small>
                                            </div>
                                            <span class="badge {{ $d->jam_in < '15:00' ? 'bg-primary' : 'bg-danger' }}">
                                                {{ $d->jam_in ?? '-' }}
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

@endsection
