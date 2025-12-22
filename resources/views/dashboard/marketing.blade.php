
<x-app-layout>
    <x-slot name="pagetitle">Dashboard Marketing - {{ $marketingInfo['company'] }}</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard Marketing') }}
            <small class="text-sm text-muted">
                ({{ $marketingInfo['company'] }} | {{ $marketingInfo['periode'] }})
            </small>
        </h2>
    </x-slot>

    <div class="app-content">
        <div class="container-fluid my-4">
            <!-- Info Marketing -->
            <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-1">
                        <i class="bi bi-graph-up me-2"></i>Marketing Dashboard
                        <span class="badge bg-primary ms-2">{{ $marketingInfo['module'] }}</span>
                    </h5>
                    <small class="text-muted">
                        <i class="bi bi-building me-1"></i>{{ $marketingInfo['company'] }}
                        • <i class="bi bi-grid me-1"></i>{{ $marketingInfo['total_projects'] }} Project
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
                <!-- Total Unit -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-primary border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Total Unit</h6>
                                    <h4 class="fw-bold mb-0">{{ $statistikUnit['total'] }}</h4>
                                    <small class="text-muted">Semua Project</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-primary-subtle rounded-circle">
                                        <i class="bi bi-house text-primary fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $statistikUnit['tersedia_percent'] }}%" 
                                         title="Tersedia {{ $statistikUnit['tersedia'] }} unit"></div>
                                    <div class="progress-bar bg-warning" style="width: {{ $statistikUnit['booking_percent'] }}%" 
                                         title="Booking {{ $statistikUnit['booking'] }} unit"></div>
                                    <div class="progress-bar bg-info" style="width: {{ $statistikUnit['terjual_percent'] }}%" 
                                         title="Terjual {{ $statistikUnit['terjual'] }} unit"></div>
                                </div>
                                <div class="d-flex justify-content-between small mt-2">
                                    <span class="text-success">Tersedia</span>
                                    <span class="text-warning">Booking</span>
                                    <span class="text-info">Terjual</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Bulan Ini -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-warning border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Booking</h6>
                                    <h4 class="fw-bold mb-0">{{ $statistikBooking['booking_bulan_ini'] }}</h4>
                                    <small class="text-muted">Bulan Ini</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-warning-subtle rounded-circle">
                                        <i class="bi bi-calendar-check text-warning fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-{{ $statistikBooking['booking_trend'] == 'up' ? 'success' : 'danger' }}-subtle text-{{ $statistikBooking['booking_trend'] == 'up' ? 'success' : 'danger' }}">
                                    <i class="bi bi-arrow-{{ $statistikBooking['booking_trend'] == 'up' ? 'up' : 'down' }} me-1"></i>
                                    {{ abs($statistikBooking['booking_percentage']) }}%
                                </span>
                                <small class="text-muted ms-2">vs bulan lalu</small>
                                <div class="mt-2">
                                    <small class="text-muted">DP: Rp {{ $statistikBooking['dp_booking_formatted'] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Penjualan Bulan Ini -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-success border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Penjualan</h6>
                                    <h4 class="fw-bold mb-0">{{ $statistikBooking['penjualan_bulan_ini'] }}</h4>
                                    <small class="text-muted">Bulan Ini</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-success-subtle rounded-circle">
                                        <i class="bi bi-cash-coin text-success fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-{{ $statistikBooking['penjualan_trend'] == 'up' ? 'success' : 'danger' }}-subtle text-{{ $statistikBooking['penjualan_trend'] == 'up' ? 'success' : 'danger' }}">
                                    <i class="bi bi-arrow-{{ $statistikBooking['penjualan_trend'] == 'up' ? 'up' : 'down' }} me-1"></i>
                                    {{ abs($statistikBooking['penjualan_percentage']) }}%
                                </span>
                                <small class="text-muted ms-2">vs bulan lalu</small>
                                <div class="mt-2">
                                    <small class="text-muted">Nilai: Rp {{ $statistikBooking['nilai_penjualan_formatted'] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer & Conversion -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-info border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Customer</h6>
                                    <h4 class="fw-bold mb-0">{{ $statistikCustomer['total'] }}</h4>
                                    <small class="text-muted">Total</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-info-subtle rounded-circle">
                                        <i class="bi bi-people text-info fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-primary-subtle text-primary">
                                    <i class="bi bi-graph-up-arrow me-1"></i>
                                    {{ $statistikBooking['conversion_rate'] }}% Conversion
                                </span>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Baru: {{ $statistikCustomer['baru_bulan_ini'] }} | 
                                        Repeat: {{ $statistikCustomer['repeat'] }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafik dan Top Projects -->
            <div class="row mb-4">
                <!-- Grafik Marketing -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-bar-chart me-2"></i>Trend Booking & Penjualan 6 Bulan Terakhir
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($chartData['labels']))
                                <canvas id="marketingChart" height="250"></canvas>
                            @else
                                <div class="text-center py-5">
                                    <i class="bi bi-graph-up display-4 text-muted"></i>
                                    <p class="text-muted mt-3">Belum ada data grafik</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Top Projects -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-trophy me-2"></i>Top 5 Projects
                            </h5>
                            <span class="badge bg-primary">Perform Terbaik</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach($topProjects as $index => $project)
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : ($index == 2 ? 'danger' : 'light')) }} rounded-circle d-flex align-items-center justify-content-center" 
                                                  style="width: 40px; height: 40px;">
                                                <strong>{{ $index + 1 }}</strong>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">{{ $project['nama'] }}</h6>
                                            <small class="text-muted">
                                                {{ $project['total_terjual'] }}/{{ $project['total_unit'] }} unit terjual
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-success fw-bold">{{ $project['penjualan_rate'] }}%</div>
                                            <small class="text-muted">Rp {{ $project['total_nilai_formatted'] }}</small>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div class="progress-bar bg-success" style="width: {{ $project['penjualan_rate'] }}%"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                        <!-- Detail Booking dan Penjualan -->
            <div class="row mb-4">
                <!-- Booking Terbaru -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-calendar-event me-2"></i>Booking Terbaru
                            </h5>
                            <a href="{{ route('laporan.bookings') }}" class="btn btn-sm btn-outline-primary">
                                Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="120">Tanggal</th>
                                            <th>Customer</th>
                                            <th>Project</th>
                                            <th>DP</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($bookingTerbaru as $booking)
                                        <tr>
                                            <td>
                                                <small class="text-muted">{{ $booking['tanggal'] }}</small>
                                                <br>
                                                <small><strong>{{ $booking['kode_booking'] }}</strong></small>
                                            </td>
                                            <td>
                                                {{ \Illuminate\Support\Str::limit($booking['customer'], 15) }}
                                            </td>
                                            <td>
                                                <small>{{ \Illuminate\Support\Str::limit($booking['project'], 12) }}</small>
                                            </td>
                                            <td>
                                                <small>Rp {{ $booking['dp'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge {{ $booking['status_badge'] }} badge-sm">
                                                    {{ ucfirst($booking['status']) }}
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="bi bi-calendar-x display-4 text-muted"></i>
                                                <p class="text-muted mt-2">Belum ada data booking</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Penjualan Terbaru -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-cash-stack me-2"></i>Penjualan Terbaru
                            </h5>
                            <a href="{{ route('laporan.penjualan') }}" class="btn btn-sm btn-outline-success">
                                Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="120">Tanggal</th>
                                            <th>Customer</th>
                                            <th>Project</th>
                                            <th>Harga</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($penjualanTerbaru as $penjualan)
                                        <tr>
                                            <td>
                                                <small class="text-muted">{{ $penjualan['tanggal'] }}</small>
                                                <br>
                                                <small><strong>{{ $penjualan['kode_penjualan'] }}</strong></small>
                                            </td>
                                            <td>
                                                {{ \Illuminate\Support\Str::limit($penjualan['customer'], 15) }}
                                            </td>
                                            <td>
                                                <small>{{ \Illuminate\Support\Str::limit($penjualan['project'], 12) }}</small>
                                            </td>
                                            <td>
                                                <small>Rp {{ $penjualan['harga'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge {{ $penjualan['status_badge'] }} badge-sm">
                                                    {{ ucfirst($penjualan['status']) }}
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="bi bi-cart-x display-4 text-muted"></i>
                                                <p class="text-muted mt-2">Belum ada data penjualan</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistik Detail -->
            <div class="row">
                <!-- Statistik Unit Detail -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-house me-2"></i>Detail Status Unit
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="border-start border-success border-3 ps-3">
                                        <small class="text-muted d-block">Tersedia</small>
                                        <h4 class="fw-bold text-success mb-0">{{ $statistikUnit['tersedia'] }}</h4>
                                        <small class="text-muted">{{ $statistikUnit['tersedia_percent'] }}%</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="border-start border-warning border-3 ps-3">
                                        <small class="text-muted d-block">Booking</small>
                                        <h4 class="fw-bold text-warning mb-0">{{ $statistikUnit['booking'] }}</h4>
                                        <small class="text-muted">{{ $statistikUnit['booking_percent'] }}%</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="border-start border-info border-3 ps-3">
                                        <small class="text-muted d-block">Terjual</small>
                                        <h4 class="fw-bold text-info mb-0">{{ $statistikUnit['terjual'] }}</h4>
                                        <small class="text-muted">{{ $statistikUnit['terjual_percent'] }}%</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="border-start border-secondary border-3 ps-3">
                                        <small class="text-muted d-block">Dalam Proses</small>
                                        <h4 class="fw-bold text-secondary mb-0">
                                            {{ $statistikUnit['bi_check'] + $statistikUnit['pemberkasan_bank'] + $statistikUnit['acc'] + $statistikUnit['akad'] + $statistikUnit['pencairan'] + $statistikUnit['bast'] }}
                                        </h4>
                                        <small class="text-muted">
                                            @php
                                                $proses = $statistikUnit['bi_check'] + $statistikUnit['pemberkasan_bank'] + $statistikUnit['acc'] + $statistikUnit['akad'] + $statistikUnit['pencairan'] + $statistikUnit['bast'];
                                                $prosesPercent = $statistikUnit['total'] > 0 ? round(($proses / $statistikUnit['total']) * 100, 1) : 0;
                                            @endphp
                                            {{ $prosesPercent }}%
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h6 class="text-muted mb-2">Nilai Unit</h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Tersedia</small>
                                    <small class="fw-bold text-success">Rp {{ $statistikUnit['nilai_tersedia_formatted'] }}</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Terjual</small>
                                    <small class="fw-bold text-info">Rp {{ $statistikUnit['nilai_terjual_formatted'] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistik Customer Detail -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-people me-2"></i>Detail Customer
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Chart Gender -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Jenis Kelamin</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-sm me-3">
                                                <span class="avatar-title bg-primary-subtle rounded-circle">
                                                    <i class="bi bi-gender-male text-primary"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="mb-0">{{ $statistikCustomer['laki_laki'] }}</h5>
                                                <small class="text-muted">Laki-laki</small>
                                            </div>
                                            <div class="ms-auto">
                                                <span class="badge bg-primary">{{ $statistikCustomer['persentase_laki'] }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-sm me-3">
                                                <span class="avatar-title bg-pink-subtle rounded-circle">
                                                    <i class="bi bi-gender-female text-pink"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="mb-0">{{ $statistikCustomer['perempuan'] }}</h5>
                                                <small class="text-muted">Perempuan</small>
                                            </div>
                                            <div class="ms-auto">
                                                <span class="badge bg-pink">{{ $statistikCustomer['persentase_perempuan'] }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $statistikCustomer['persentase_laki'] }}%"></div>
                                    <div class="progress-bar bg-pink" style="width: {{ $statistikCustomer['persentase_perempuan'] }}%"></div>
                                </div>
                            </div>

                            <!-- Customer Repeat -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Repeat Customer</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-arrow-repeat text-success fs-4"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h4 class="mb-0">{{ $statistikCustomer['repeat'] }}</h4>
                                        <small class="text-muted">Customer repeat purchase</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">{{ $statistikCustomer['persentase_repeat'] }}%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Top DP -->
                            @if($statistikCustomer['top_dp_customer'])
                            <div class="mt-4 pt-3 border-top">
                                <h6 class="text-muted mb-3">Top DP Customer</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-trophy text-warning fs-4"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">{{ $statistikCustomer['top_dp_customer']['nama'] }}</h6>
                                        <small class="text-muted">DP: Rp {{ $statistikCustomer['top_dp_customer']['dp'] }}</small>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-speedometer2 me-2"></i>Performance Metrics
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Conversion Rate -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Conversion Rate</h6>
                                <div class="text-center">
                                    <div class="position-relative d-inline-block">
                                        <div class="progress-circle" 
                                             data-value="{{ $statistikBooking['conversion_rate'] }}" 
                                             data-size="120" 
                                             data-thickness="8"
                                             data-color="{{ $statistikBooking['conversion_rate'] >= 50 ? 'success' : ($statistikBooking['conversion_rate'] >= 30 ? 'warning' : 'danger') }}"
                                             style="width: 120px; height: 120px; margin: 0 auto;">
                                        </div>
                                        <div class="position-absolute top-50 start-50 translate-middle">
                                            <h3 class="fw-bold mb-0">{{ $statistikBooking['conversion_rate'] }}%</h3>
                                            <small class="text-muted">Booking to Sale</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Average Values -->
                            <div class="row mt-4">
                                <div class="col-6">
                                    <div class="border-start border-warning border-3 ps-3">
                                        <small class="text-muted d-block">Avg. DP</small>
                                        <h4 class="fw-bold text-warning mb-0">
                                            Rp {{ number_format($statistikBooking['avg_dp'], 0, ',', '.') }}
                                        </h4>
                                        <small class="text-muted">per booking</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border-start border-success border-3 ps-3">
                                        <small class="text-muted d-block">Avg. Sale</small>
                                        <h4 class="fw-bold text-success mb-0">
                                            Rp {{ number_format($statistikBooking['avg_sale'], 0, ',', '.') }}
                                        </h4>
                                        <small class="text-muted">per unit</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="mt-4 pt-3 border-top">
                                <h6 class="text-muted mb-3">Quick Stats</h6>
                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-check text-primary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Booking</small>
                                                <small class="fw-bold">{{ $statistikBooking['booking_bulan_ini'] }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-cash-coin text-success me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Penjualan</small>
                                                <small class="fw-bold">{{ $statistikBooking['penjualan_bulan_ini'] }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-plus text-info me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Customer Baru</small>
                                                <small class="fw-bold">{{ $statistikCustomer['baru_bulan_ini'] }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-house text-secondary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Unit Tersedia</small>
                                                <small class="fw-bold">{{ $statistikUnit['tersedia'] }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                <i class="bi bi-lightning-charge me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <a href="{{ route('units.index') }}" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center py-3">
                                        <i class="bi bi-house fs-4 mb-2"></i>
                                        <span>Unit</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <a href="{{ route('units.details.index') }}" class="btn btn-outline-success w-100 d-flex flex-column align-items-center py-3">
                                        <i class="bi bi-plus-circle fs-4 mb-2"></i>
                                        <span>Unit</span>
                                    </a>
                                </div>

                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <a href="{{ route('customers.index') }}" class="btn btn-outline-warning w-100 d-flex flex-column align-items-center py-3">
                                        <i class="bi bi-people fs-4 mb-2"></i>
                                        <span>Customer</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Chart
            const chartData = @json($chartData);
            
            if (chartData.labels && chartData.labels.length > 0) {
                const ctx = document.getElementById('marketingChart').getContext('2d');
                const marketingChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [
                            {
                                label: 'Booking',
                                data: chartData.booking,
                                borderColor: '#ffc107',
                                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Penjualan',
                                data: chartData.penjualan,
                                borderColor: '#198754',
                                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
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
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.datasetIndex === 0) {
                                            label += context.parsed.y + ' booking';
                                        } else {
                                            label += context.parsed.y + ' penjualan';
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Jumlah'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Bulan'
                                }
                            }
                        }
                    }
                });
            }

            // Inisialisasi Progress Circle untuk Conversion Rate
            const conversionRate = {{ $statistikBooking['conversion_rate'] }};
            const progressCircles = document.querySelectorAll('.progress-circle');
            
            progressCircles.forEach(circle => {
                const value = parseFloat(circle.dataset.value);
                const size = parseInt(circle.dataset.size);
                const thickness = parseInt(circle.dataset.thickness);
                const color = circle.dataset.color;
                
                const radius = (size - thickness) / 2;
                const circumference = 2 * Math.PI * radius;
                const offset = circumference - (value / 100) * circumference;
                
                const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                svg.setAttribute('width', size);
                svg.setAttribute('height', size);
                svg.setAttribute('class', 'progress-circle-svg');
                
                const bgCircle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                bgCircle.setAttribute('cx', size / 2);
                bgCircle.setAttribute('cy', size / 2);
                bgCircle.setAttribute('r', radius);
                bgCircle.setAttribute('fill', 'none');
                bgCircle.setAttribute('stroke', '#e9ecef');
                bgCircle.setAttribute('stroke-width', thickness);
                
                const progressCircle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                progressCircle.setAttribute('cx', size / 2);
                progressCircle.setAttribute('cy', size / 2);
                progressCircle.setAttribute('r', radius);
                progressCircle.setAttribute('fill', 'none');
                progressCircle.setAttribute('stroke', getColor(color));
                progressCircle.setAttribute('stroke-width', thickness);
                progressCircle.setAttribute('stroke-linecap', 'round');
                progressCircle.setAttribute('stroke-dasharray', circumference);
                progressCircle.setAttribute('stroke-dashoffset', circumference);
                progressCircle.style.transition = 'stroke-dashoffset 1s ease-in-out';
                
                svg.appendChild(bgCircle);
                svg.appendChild(progressCircle);
                circle.appendChild(svg);
                
                // Trigger animation
                setTimeout(() => {
                    progressCircle.style.strokeDashoffset = offset;
                }, 100);
            });

            function getColor(colorName) {
                const colors = {
                    'success': '#198754',
                    'warning': '#ffc107',
                    'danger': '#dc3545',
                    'primary': '#0d6efd',
                    'info': '#0dcaf0'
                };
                return colors[colorName] || '#0d6efd';
            }

            // Auto refresh setiap 5 menit
            setInterval(() => {
                // Optional: Implement AJAX refresh jika diperlukan
                // console.log('Dashboard akan refresh...');
            }, 300000); // 5 menit
        });
    </script>
    <style>
        .progress-circle {
            position: relative;
            display: inline-block;
        }
        .progress-circle-svg {
            transform: rotate(-90deg);
        }
        .avatar-title {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .border-start {
            border-left-width: 3px !important;
        }
        .badge-sm {
            font-size: 0.75em;
            padding: 0.25em 0.5em;
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .list-group-item:hover {
            background-color: rgba(0,0,0,0.02);
        }
    </style>
    @endpush
</x-app-layout>