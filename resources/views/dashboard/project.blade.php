<x-app-layout>
    <x-slot name="pagetitle">Dashboard Project - {{ $projectInfo['nama'] }}</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
            <small class="text-sm text-muted">
                (Project: {{ $projectInfo['nama'] }} | PT: {{ $projectInfo['company'] }})
            </small>
        </h2>
    </x-slot>

    <div class="app-content">
        <div class="container-fluid my-4">
            <h2 class="mb-4">📊 Ringkasan Dashboard</h2>

            <!-- Info Project -->
            <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-1">
                        <i class="bi bi-building me-2"></i>{{ $projectInfo['nama'] }}
                        <span class="badge bg-primary ms-2">{{ $projectInfo['module'] }}</span>
                    </h5>
                    <small class="text-muted">
                        <i class="bi bi-bank me-1"></i>{{ $projectInfo['company'] }}
                    </small>
                </div>
                <div>
                    <small class="text-muted">
                        <i class="bi bi-calendar me-1"></i>
                        {{ \Carbon\Carbon::now()->format('d F Y') }}
                    </small>
                </div>
            </div>

            <!-- Ringkasan Statistik -->
            <div class="row mb-4">
                <!-- Transaksi Masuk -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-primary border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Transaksi Masuk</h6>
                                    <h4 class="fw-bold mb-0">Rp {{ $ringkasan['transaksi_in']['total'] }}</h4>
                                    <small class="text-muted">Bulan Ini</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-primary-subtle rounded-circle">
                                        <i class="bi bi-box-arrow-in-down text-primary fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-{{ $ringkasan['transaksi_in']['trend'] == 'up' ? 'success' : 'danger' }}-subtle text-{{ $ringkasan['transaksi_in']['trend'] == 'up' ? 'success' : 'danger' }}">
                                    <i class="bi bi-arrow-{{ $ringkasan['transaksi_in']['trend'] == 'up' ? 'up' : 'down' }} me-1"></i>
                                    {{ number_format(abs($ringkasan['transaksi_in']['percentage']), 1) }}%
                                </span>
                                <small class="text-muted ms-2">vs bulan lalu</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaksi Keluar -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-danger border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Transaksi Keluar</h6>
                                    <h4 class="fw-bold mb-0">Rp {{ $ringkasan['transaksi_out']['total'] }}</h4>
                                    <small class="text-muted">Bulan Ini</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-danger-subtle rounded-circle">
                                        <i class="bi bi-box-arrow-up text-danger fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-{{ $ringkasan['transaksi_out']['trend'] == 'down' ? 'success' : 'danger' }}-subtle text-{{ $ringkasan['transaksi_out']['trend'] == 'down' ? 'success' : 'danger' }}">
                                    <i class="bi bi-arrow-{{ $ringkasan['transaksi_out']['trend'] == 'down' ? 'down' : 'up' }} me-1"></i>
                                    {{ number_format(abs($ringkasan['transaksi_out']['percentage']), 1) }}%
                                </span>
                                <small class="text-muted ms-2">vs bulan lalu</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Cashflow -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-success border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Net Cashflow</h6>
                                    <h4 class="fw-bold mb-0 {{ $ringkasan['net_cashflow']['total_raw'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ $ringkasan['net_cashflow']['total'] }}
                                    </h4>
                                    <small class="text-muted">Bulan Ini</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-success-subtle rounded-circle">
                                        <i class="bi bi-graph-up-arrow text-success fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-{{ $ringkasan['net_cashflow']['trend'] == 'up' ? 'success' : 'danger' }}-subtle text-{{ $ringkasan['net_cashflow']['trend'] == 'up' ? 'success' : 'danger' }}">
                                    <i class="bi bi-arrow-{{ $ringkasan['net_cashflow']['trend'] == 'up' ? 'up' : 'down' }} me-1"></i>
                                    {{ number_format(abs($ringkasan['net_cashflow']['percentage']), 1) }}%
                                </span>
                                <small class="text-muted ms-2">vs bulan lalu</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Nota -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-warning border-5 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted fw-semibold">Nota Tertunda</h6>
                                    <h4 class="fw-bold mb-0">{{ $ringkasan['nota_open'] }}</h4>
                                    <small class="text-muted">Total Open</small>
                                </div>
                                <div class="avatar-sm">
                                    <span class="avatar-title bg-warning-subtle rounded-circle">
                                        <i class="bi bi-clock-history text-warning fs-4"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-info-subtle text-info">
                                    <i class="bi bi-receipt me-1"></i>
                                    {{ $ringkasan['nota_this_month'] }} nota bulan ini
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Saldo Rekening -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-wallet2 me-2"></i>Summary Saldo Rekening
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="border rounded p-3">
                                        <h2 class="text-primary fw-bold">Rp {{ $saldoRekening['total_saldo'] }}</h2>
                                        <p class="text-muted mb-1">Total Saldo</p>
                                        <small class="text-muted">{{ $saldoRekening['jumlah_rekening'] }} rekening</small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="border rounded p-3">
                                        <h2 class="text-success fw-bold">Rp {{ $saldoRekening['summary']['company']['total'] }}</h2>
                                        <p class="text-muted mb-1">Saldo Rekening PT</p>
                                        <small class="text-muted">{{ $saldoRekening['summary']['company']['count'] }} rekening PT</small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="border rounded p-3">
                                        <h2 class="text-info fw-bold">Rp {{ $saldoRekening['summary']['project']['total'] }}</h2>
                                        <p class="text-muted mb-1">Saldo Rekening Project</p>
                                        <small class="text-muted">{{ $saldoRekening['summary']['project']['count'] }} rekening project</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafik dan Saldo -->
            <div class="row mb-4">
                <!-- Grafik Transaksi Berdasarkan COA -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-bar-chart me-2"></i>Grafik Transaksi Berdasarkan COA
                                <small class="text-muted ms-2">(30 hari terakhir)</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(count($grafikTransaksi['labels']) > 0)
                                <div class="row">
                                    <div class="col-lg-8">
                                        <canvas id="coaChart" height="300"></canvas>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Kode</th>
                                                        <th>Nama</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($grafikTransaksi['detail'] as $detail)
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-{{ $detail['jenis'] == 'pendapatan' ? 'success' : ($detail['jenis'] == 'beban' ? 'danger' : 'secondary') }}-subtle text-{{ $detail['jenis'] == 'pendapatan' ? 'success' : ($detail['jenis'] == 'beban' ? 'danger' : 'secondary') }}">
                                                                {{ $detail['kode'] }}
                                                            </span>
                                                        </td>
                                                        <td>{{ Str::limit($detail['nama'], 20) }}</td>
                                                        <td class="text-end">Rp {{ $detail['total'] }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="border-top">
                                                        <td colspan="2" class="fw-bold">Total</td>
                                                        <td class="text-end fw-bold">
                                                            Rp {{ number_format(array_sum($grafikTransaksi['data']), 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="bi bi-pie-chart display-4 text-muted"></i>
                                    <p class="text-muted mt-3">Belum ada data transaksi</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Rincian Saldo Rekening -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-credit-card me-2"></i>Detail Saldo Rekening
                            </h5>
                            <span class="badge bg-primary">{{ $saldoRekening['jumlah_rekening'] }} rekening</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 300px;">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="sticky-top bg-light">
                                        <tr>
                                            <th>Rekening</th>
                                            <th class="text-end">Saldo</th>
                                            <th class="text-center">Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($saldoRekening['rekenings'] as $rekening)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ Str::limit($rekening['nama'], 20) }}</div>
                                                <small class="text-muted">{{ $rekening['norek'] }}</small>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold {{ $rekening['saldo_raw'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    Rp {{ $rekening['saldo'] }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $rekening['type_badge'] }}-subtle text-{{ $rekening['type_badge'] }}">
                                                    {{ $rekening['type_label'] }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="sticky-bottom bg-light">
                                        <tr>
                                            <td class="fw-bold">Total Saldo</td>
                                            <td class="text-end fw-bold {{ $saldoRekening['total_saldo_raw'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                Rp {{ $saldoRekening['total_saldo'] }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cashflow Detail per Rekening -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-cash-stack me-2"></i>Cashflow Detail per Rekening
                                <small class="text-muted ms-2">(7 hari terakhir)</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(count($cashflowDetail) > 0)
                                <div class="row">
                                    @foreach($cashflowDetail as $rekening)
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card border h-100">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    {{ Str::limit($rekening['namarek'], 15) }}
                                                    <small class="text-muted">({{ $rekening['norek'] }})</small>
                                                </h6>
                                                <span class="badge bg-{{ $rekening['net_cashflow_raw'] >= 0 ? 'success' : 'danger' }}">
                                                    {{ $rekening['net_cashflow_raw'] >= 0 ? '+' : '' }}Rp {{ $rekening['net_cashflow'] }}
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row text-center mb-3">
                                                    <div class="col-6">
                                                        <div class="text-success">
                                                            <div class="fs-5 fw-bold">Rp {{ $rekening['total_in'] }}</div>
                                                            <small>Pemasukan</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="text-danger">
                                                            <div class="fs-5 fw-bold">Rp {{ $rekening['total_out'] }}</div>
                                                            <small>Pengeluaran</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="progress mb-3" style="height: 10px;">
                                                    @php
                                                        $totalCashflow = $rekening['net_cashflow_raw'] + abs(min(0, $rekening['net_cashflow_raw']));
                                                        $positivePercent = $totalCashflow > 0 ? ($rekening['total_in'] / $totalCashflow * 100) : 0;
                                                        $negativePercent = $totalCashflow > 0 ? ($rekening['total_out'] / $totalCashflow * 100) : 0;
                                                    @endphp
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: {{ $positivePercent }}%" 
                                                         aria-valuenow="{{ $positivePercent }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                    <div class="progress-bar bg-danger" role="progressbar" 
                                                         style="width: {{ $negativePercent }}%" 
                                                         aria-valuenow="{{ $negativePercent }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                                
                                                <div class="accordion accordion-flush" id="accordion{{ $rekening['idrek'] }}">
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header">
                                                            <button class="accordion-button collapsed py-2" type="button" 
                                                                    data-bs-toggle="collapse" 
                                                                    data-bs-target="#flush{{ $rekening['idrek'] }}">
                                                                <small>
                                                                    <i class="bi bi-clock-history me-1"></i>
                                                                    {{ $rekening['transaksi_count'] }} transaksi terakhir
                                                                </small>
                                                            </button>
                                                        </h2>
                                                        <div id="flush{{ $rekening['idrek'] }}" 
                                                             class="accordion-collapse collapse" 
                                                             data-bs-parent="#accordion{{ $rekening['idrek'] }}">
                                                            <div class="accordion-body p-0">
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm table-hover mb-0">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Tanggal</th>
                                                                                <th>Tipe</th>
                                                                                <th class="text-end">In</th>
                                                                                <th class="text-end">Out</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($rekening['transaksi'] as $transaksi)
                                                                            <tr>
                                                                                <td>{{ $transaksi['tanggal'] }}</td>
                                                                                <td>
                                                                                    <span class="badge bg-{{ $transaksi['cashflow'] == 'in' ? 'success' : 'danger' }}-subtle text-{{ $transaksi['cashflow'] == 'in' ? 'success' : 'danger' }}">
                                                                                        {{ strtoupper($transaksi['cashflow']) }}
                                                                                    </span>
                                                                                </td>
                                                                                <td class="text-end text-success">
                                                                                    {{ $transaksi['total_in'] > 0 ? 'Rp ' . number_format($transaksi['total_in'], 0, ',', '.') : '-' }}
                                                                                </td>
                                                                                <td class="text-end text-danger">
                                                                                    {{ $transaksi['total_out'] > 0 ? 'Rp ' . number_format($transaksi['total_out'], 0, ',', '.') : '-' }}
                                                                                </td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="bi bi-currency-exchange display-4 text-muted"></i>
                                    <p class="text-muted mt-3">Belum ada data cashflow</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafik Time Series -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-graph-up me-2"></i>Trend Cashflow
                            </h5>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary active" data-period="daily">Harian</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-period="weekly">Mingguan</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-period="monthly">Bulanan</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="cashflowChart" height="100"></canvas>
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
                                    <a href="{{ route('transaksi.project.in') }}" class="text-decoration-none">
                                        <div class="card border border-success text-center h-100 hover-shadow">
                                            <div class="card-body">
                                                <i class="bi bi-plus-circle-fill text-success fs-1"></i>
                                                <h6 class="mt-2">Transaksi Masuk</h6>
                                                <small class="text-muted">Tambah transaksi masuk</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="{{ route('transaksi.project.out') }}" class="text-decoration-none">
                                        <div class="card border border-danger text-center h-100 hover-shadow">
                                            <div class="card-body">
                                                <i class="bi bi-dash-circle-fill text-danger fs-1"></i>
                                                <h6 class="mt-2">Transaksi Keluar</h6>
                                                <small class="text-muted">Tambah transaksi keluar</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="#" class="text-decoration-none">
                                        <div class="card border border-primary text-center h-100 hover-shadow">
                                            <div class="card-body">
                                                <i class="bi bi-eye-fill text-primary fs-1"></i>
                                                <h6 class="mt-2">Lihat Laporan</h6>
                                                <small class="text-muted">Laporan lengkap</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="{{ route('choose.project') }}" class="text-decoration-none">
                                        <div class="card border border-warning text-center h-100 hover-shadow">
                                            <div class="card-body">
                                                <i class="bi bi-arrow-left-right text-warning fs-1"></i>
                                                <h6 class="mt-2">Ganti Project</h6>
                                                <small class="text-muted">Pilih project lain</small>
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
                // Grafik Transaksi Berdasarkan COA
                @if(count($grafikTransaksi['labels']) > 0)
                const coaCtx = document.getElementById('coaChart').getContext('2d');
                const coaChart = new Chart(coaCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($grafikTransaksi['labels']),
                        datasets: [{
                            label: 'Total Transaksi',
                            data: @json($grafikTransaksi['data']),
                            backgroundColor: @json($grafikTransaksi['colors']),
                            borderColor: @json($grafikTransaksi['colors']),
                            borderWidth: 1,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += 'Rp ' + context.raw.toLocaleString('id-ID');
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        }
                    }
                });
                @endif

                // Grafik Time Series Cashflow
                const cashflowCtx = document.getElementById('cashflowChart').getContext('2d');
                let cashflowChart;
                
                // Load data untuk periode harian
                loadCashflowChart('daily');

                // Event listener untuk tombol periode
                document.querySelectorAll('[data-period]').forEach(button => {
                    button.addEventListener('click', function() {
                        const period = this.getAttribute('data-period');
                        
                        // Update active button
                        document.querySelectorAll('[data-period]').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');
                        
                        // Load chart dengan periode baru
                        loadCashflowChart(period);
                    });
                });

                function loadCashflowChart(period) {
                    fetch(`/dashboard/chart-data?type=${period}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                renderCashflowChart(data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading chart data:', error);
                        });
                }

                function renderCashflowChart(chartData) {
                    if (cashflowChart) {
                        cashflowChart.destroy();
                    }

                    cashflowChart = new Chart(cashflowCtx, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: [
                                {
                                    label: 'Pemasukan',
                                    data: chartData.in,
                                    borderColor: '#28a745',
                                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Pengeluaran',
                                    data: chartData.out,
                                    borderColor: '#dc3545',
                                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Net Cashflow',
                                    data: chartData.net,
                                    borderColor: '#6f42c1',
                                    backgroundColor: 'rgba(111, 66, 193, 0.1)',
                                    tension: 0.3,
                                    borderDash: [5, 5],
                                    fill: false
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
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
                                            label += 'Rp ' + context.raw.toLocaleString('id-ID');
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    display: true,
                                    title: {
                                        display: true,
                                        text: 'Periode'
                                    }
                                },
                                y: {
                                    display: true,
                                    title: {
                                        display: true,
                                        text: 'Nominal (Rp)'
                                    },
                                    ticks: {
                                        callback: function(value) {
                                            return 'Rp ' + value.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // Auto refresh setiap 60 detik
                setInterval(() => {
                    loadCashflowChart(document.querySelector('[data-period].active').getAttribute('data-period'));
                }, 60000);
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
            
            .accordion-button {
                font-size: 0.85rem;
                padding: 0.5rem 1rem;
            }
            
            .accordion-button:not(.collapsed) {
                background-color: rgba(var(--bs-primary-rgb), 0.1);
                color: var(--bs-primary);
            }
            
            .table-sm th, .table-sm td {
                padding: 0.5rem;
            }
            
            .border-5 {
                border-width: 5px !important;
            }
            
            .progress {
                overflow: visible;
            }
            
            .progress-bar {
                position: relative;
                overflow: visible;
            }
            
            @media (max-width: 768px) {
                .card-body canvas {
                    max-height: 250px;
                }
                
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