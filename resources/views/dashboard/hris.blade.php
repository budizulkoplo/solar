<x-app-layout>
    <x-slot name="pagetitle">Dashboard HRIS - {{ $hrisInfo['company'] }}</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard HRIS') }}
            <small class="text-sm text-muted">
                ({{ $hrisInfo['company'] }} | {{ $hrisInfo['periode'] }})
            </small>
        </h2>
    </x-slot>

    <div class="app-content">
        <div class="container-fluid my-4">
            <!-- Info HRIS -->
            <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-1">
                        <i class="bi bi-people me-2"></i>HRIS Dashboard
                        <span class="badge bg-primary ms-2">{{ $hrisInfo['module'] }}</span>
                    </h5>
                    <small class="text-muted">
                        <i class="bi bi-building me-1"></i>{{ $hrisInfo['company'] }}
                        • <i class="bi bi-person me-1"></i>{{ $hrisInfo['total_karyawan'] }} Karyawan
                    </small>
                </div>
                <div>
                    <small class="text-muted">
                        <i class="bi bi-calendar me-1"></i>
                        {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                    </small>
                </div>
            </div>

            <!-- Ringkasan Statistik -->
            <div class="row mb-4">
                <!-- Total Karyawan -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-primary border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Total Karyawan</h6>
                                    <h4 class="fw-bold mb-0">{{ $statistikKaryawan['total'] }}</h4>
                                    <small class="text-muted">Aktif</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-primary-subtle rounded-circle">
                                        <i class="bi bi-people text-primary fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">L: {{ $statistikKaryawan['laki_laki'] }}</span>
                                    <span class="text-muted">P: {{ $statistikKaryawan['perempuan'] }}</span>
                                </div>
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $statistikKaryawan['persentase_laki'] }}%"></div>
                                    <div class="progress-bar bg-pink" style="width: {{ $statistikKaryawan['persentase_perempuan'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Presensi Hari Ini -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-success border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Presensi Hari Ini</h6>
                                    <h4 class="fw-bold mb-0">{{ $statistikPresensi['presensi_hari_ini'] }}</h4>
                                    <small class="text-muted">Masuk</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-success-subtle rounded-circle">
                                        <i class="bi bi-clock text-success fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-check-circle me-1"></i>
                                    {{ $statistikPresensi['total_hari_kerja'] }} hari kerja
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Payroll -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-warning border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Total Payroll</h6>
                                    <h4 class="fw-bold mb-0">Rp {{ $statistikPayroll['total_pendapatan_formatted'] }}</h4>
                                    <small class="text-muted">Bulan Ini</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-warning-subtle rounded-circle">
                                        <i class="bi bi-cash-stack text-warning fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-{{ $statistikPayroll['trend'] == 'up' ? 'success' : 'danger' }}-subtle text-{{ $statistikPayroll['trend'] == 'up' ? 'success' : 'danger' }}">
                                    <i class="bi bi-arrow-{{ $statistikPayroll['trend'] == 'up' ? 'up' : 'down' }} me-1"></i>
                                    {{ abs($statistikPayroll['persentase_perubahan']) }}%
                                </span>
                                <small class="text-muted ms-2">vs bulan lalu</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Izin -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-danger border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Izin Pending</h6>
                                    <h4 class="fw-bold mb-0">{{ $izinPending->count() }}</h4>
                                    <small class="text-muted">Menunggu Approval</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-danger-subtle rounded-circle">
                                        <i class="bi bi-calendar-x text-danger fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-info-subtle text-info">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    {{ $statistikPresensi['izin_bulan'] + $statistikPresensi['cuti_bulan'] }} izin/cuti bulan ini
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart dan Data Karyawan -->
            <div class="row mb-4">
                <!-- Grafik Presensi 6 Bulan -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-bar-chart me-2"></i>Tren Presensi 6 Bulan Terakhir
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($chartData['labels']))
                                <canvas id="hrisChart" height="250"></canvas>
                            @else
                                <div class="text-center py-5">
                                    <i class="bi bi-graph-up display-4 text-muted"></i>
                                    <p class="text-muted mt-3">Belum ada data grafik</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Karyawan Aktif -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person-badge me-2"></i>Karyawan Aktif Terbaru
                            </h5>
                            <span class="badge bg-primary">{{ $karyawanAktif->count() }} karyawan</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach($karyawanAktif as $karyawan)
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <img src="{{ asset('storage/avatars/' . $karyawan['foto']) }}" 
                                                 alt="{{ $karyawan['nama'] }}" 
                                                 class="rounded-circle" 
                                                 width="40" 
                                                 height="40"
                                                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($karyawan['nama']) }}&background=random'">
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">{{ $karyawan['nama'] }}</h6>
                                            <small class="text-muted">{{ $karyawan['unit_kerja'] }}</small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">{{ $karyawan['jabatan'] }}</small>
                                            <span class="badge bg-{{ str_contains($karyawan['status_kontrak'], 'habis') ? 'danger' : 'success' }}-subtle text-{{ str_contains($karyawan['status_kontrak'], 'habis') ? 'danger' : 'success' }}">
                                                {{ Str::limit($karyawan['status_kontrak'], 20) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Presensi dan Izin -->
            <div class="row">
                <!-- Presensi Hari Ini -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-clock-history me-2"></i>Presensi Hari Ini
                                <small class="text-muted">({{ \Carbon\Carbon::now()->translatedFormat('d F Y') }})</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($presensiHariIni->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Unit</th>
                                                <th class="text-center">Jam Masuk</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($presensiHariIni as $presensi)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="{{ asset('storage/avatars/' . $presensi['foto']) }}" 
                                                             alt="{{ $presensi['nama'] }}" 
                                                             class="rounded-circle me-2" 
                                                             width="30" 
                                                             height="30"
                                                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($presensi['nama']) }}&background=random'">
                                                        <span>{{ $presensi['nama'] }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ $presensi['unit_kerja'] }}</td>
                                                <td class="text-center">{{ $presensi['jam_masuk'] }}</td>
                                                <td>
                                                    <span class="badge {{ str_contains($presensi['status'], 'Terlambat') ? 'bg-danger' : 'bg-success' }}-subtle text-{{ str_contains($presensi['status'], 'Terlambat') ? 'danger' : 'success' }}">
                                                        {{ $presensi['status'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-3">
                                    <i class="bi bi-clock display-4 text-muted"></i>
                                    <p class="text-muted mt-2">Belum ada presensi hari ini</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Izin Pending -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-calendar-x me-2"></i>Izin/Cuti Pending
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($izinPending->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Tanggal</th>
                                                <th>Jenis</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($izinPending as $izin)
                                            <tr>
                                                <td>{{ $izin['nama'] }}</td>
                                                <td>{{ $izin['tanggal'] }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $izin['jenis'] == 'Cuti' ? 'warning' : ($izin['jenis'] == 'Sakit' ? 'danger' : 'info') }}-subtle text-{{ $izin['jenis'] == 'Cuti' ? 'warning' : ($izin['jenis'] == 'Sakit' ? 'danger' : 'info') }}">
                                                        {{ $izin['jenis'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning">
                                                        {{ $izin['status'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-3">
                                    <i class="bi bi-check-circle display-4 text-muted"></i>
                                    <p class="text-muted mt-2">Tidak ada izin pending</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-lightning me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="{{ route('hris.absensi') }}" class="text-decoration-none">
                                        <div class="card border border-primary text-center h-100 hover-shadow">
                                            <div class="card-body">
                                                <i class="bi bi-clock-history text-primary fs-1"></i>
                                                <h6 class="mt-2">Presensi</h6>
                                                <small class="text-muted">Lihat data absensi</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="{{ route('hris.payroll.index') }}" class="text-decoration-none">
                                        <div class="card border border-success text-center h-100 hover-shadow">
                                            <div class="card-body">
                                                <i class="bi bi-cash-stack text-success fs-1"></i>
                                                <h6 class="mt-2">Payroll</h6>
                                                <small class="text-muted">Kelola penggajian</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="#" class="text-decoration-none">
                                        <div class="card border border-info text-center h-100 hover-shadow">
                                            <div class="card-body">
                                                <i class="bi bi-people text-info fs-1"></i>
                                                <h6 class="mt-2">Karyawan</h6>
                                                <small class="text-muted">Data karyawan</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="{{ route('choose.project') }}" class="text-decoration-none">
                                        <div class="card border border-warning text-center h-100 hover-shadow">
                                            <div class="card-body">
                                                <i class="bi bi-arrow-left-right text-warning fs-1"></i>
                                                <h6 class="mt-2">Ganti Module</h6>
                                                <small class="text-muted">Pilih module lain</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Grafik HRIS
                @if(!empty($chartData['labels']))
                const hrisCtx = document.getElementById('hrisChart').getContext('2d');
                const hrisChart = new Chart(hrisCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartData['labels']),
                        datasets: [
                            {
                                label: 'Presensi',
                                data: @json($chartData['presensi']),
                                backgroundColor: '#28a745',
                                borderColor: '#28a745',
                                borderWidth: 1,
                                borderRadius: 5
                            },
                            {
                                label: 'Izin/Sakit',
                                data: @json($chartData['izin']),
                                backgroundColor: '#ffc107',
                                borderColor: '#ffc107',
                                borderWidth: 1,
                                borderRadius: 5
                            },
                            {
                                label: 'Terlambat',
                                data: @json($chartData['terlambat']),
                                backgroundColor: '#dc3545',
                                borderColor: '#dc3545',
                                borderWidth: 1,
                                borderRadius: 5
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.raw;
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
                @endif

                // Auto refresh setiap 5 menit
                setInterval(() => {
                    location.reload();
                }, 300000); // 5 menit
            });
        </script>

        <style>
            .card {
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                transition: all 0.3s ease;
            }
            
            .card:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                transform: translateY(-2px);
            }
            
            .hover-shadow:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
                transform: translateY(-2px);
            }
            
            .avatar-sm {
                width: 48px;
                height: 48px;
            }
            
            .avatar-title {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 100%;
            }
            
            .border-5 {
                border-width: 5px !important;
            }
            
            .bg-pink {
                background-color: #e83e8c !important;
            }
            
            @media (max-width: 768px) {
                .alert {
                    flex-direction: column;
                    text-align: center;
                }
                
                .alert > div {
                    margin-bottom: 10px;
                }
            }
        </style>
    </x-slot>
</x-app-layout>