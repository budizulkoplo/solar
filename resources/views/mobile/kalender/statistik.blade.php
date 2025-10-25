@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="/dashboard" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Statistik Absensi</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="row" style="margin-top:70px">
    <div class="col">
        {{-- Form Pilih Bulan --}}
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('mobile.kalender.statistik') }}">
                    <div class="row g-2 align-items-center">
                        <div class="col-8">
                            <input type="month" name="bulan" value="{{ $bulan ?? date('Y-m') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <ion-icon name="pie-chart-outline" class="me-1"></ion-icon>
                                Tampilkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($selectedEmployee)
            {{-- Ringkasan Statistik --}}
            <div class="row">
                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <p class="card-title mb-0">{{ $selectedEmployee->name }}</p>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                {{-- Hari Kerja --}}
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="card bg-light h-100">
                                        <div class="card-body py-2">
                                            <h6>Hari Kerja</h6>
                                            <h4 class="text-primary">{{ $totalWorkDays ?? 0 }}</h4>
                                            <small class="text-muted">hari</small>
                                        </div>
                                    </div>
                                </div>
                                {{-- Total Terlambat --}}
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="card bg-light h-100">
                                        <div class="card-body py-2">
                                            <h6>Total Terlambat</h6>
                                            <h4 class="text-warning">{{ $terlambatFormatted ?? '00:00' }}</h4>
                                            <small class="text-muted">jam:menit</small>
                                        </div>
                                    </div>
                                </div>
                                {{-- Total Lembur --}}
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="card bg-light h-100">
                                        <div class="card-body py-2">
                                            <h6>Total Lembur</h6>
                                            <h4 class="text-success">{{ $totalLemburFormatted ?? '00:00' }}</h4>
                                            <small class="text-muted">jam:menit</small>
                                        </div>
                                    </div>
                                </div>
                                {{-- Hari Cuti --}}
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="card bg-light h-100">
                                        <div class="card-body py-2">
                                            <h6>Hari Cuti</h6>
                                            <h4 class="text-info">{{ $jumlahCuti ?? 0 }}</h4>
                                            <small class="text-muted">hari</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Statistik Tambahan --}}
                            
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rata-rata Kehadiran --}}
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Rata-rata Kehadiran</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Masuk</span>
                                <span class="badge rounded-pill px-3
                                    {{ ($avgSelisihMasuk ?? 0) > 0 ? 'bg-danger' : (($avgSelisihMasuk ?? 0) < 0 ? 'bg-success' : 'bg-secondary') }}">
                                    @if(isset($avgSelisihMasuk) && $avgSelisihMasuk !== null)
                                        {{ abs(round($avgSelisihMasuk, 1)) }} menit
                                        {{ $avgSelisihMasuk > 0 ? 'terlambat' : ($avgSelisihMasuk < 0 ? 'lebih awal' : 'tepat waktu') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Pulang</span>
                                <span class="badge rounded-pill px-3
                                    {{ ($avgSelisihPulang ?? 0) < 0 ? 'bg-danger' : (($avgSelisihPulang ?? 0) > 0 ? 'bg-success' : 'bg-secondary') }}">
                                    @if(isset($avgSelisihPulang) && $avgSelisihPulang !== null)
                                        {{ abs(round($avgSelisihPulang, 1)) }} menit
                                        {{ $avgSelisihPulang > 0 ? 'lebih lambat' : ($avgSelisihPulang < 0 ? 'lebih awal' : 'tepat waktu') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="card-footer small text-muted">
                            Perhitungan berdasarkan {{ $countShiftDays ?? 0 }} hari dengan jadwal shift.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grafik --}}
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Kehadiran Bulanan</h5></div>
                        <div class="card-body">
                            <canvas id="chartKehadiran"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Tepat Waktu vs Terlambat</h5></div>
                        <div class="card-body">
                            <canvas id="chartTepatTerlambat"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-3">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Tren Keterlambatan Harian (Menit)</h5></div>
                        <div class="card-body">
                            <canvas id="chartKeterlambatanHarian"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detail Hari Kerja --}}
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Detail Hari Kerja</h5></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Masuk</th>
                                        <th>Pulang</th>
                                        <th>Lembur</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dataKalender ?? [] as $tgl => $item)
                                        @if(!empty($item['jam_masuk']) || !empty($item['jam_pulang']) || !empty($item['status_khusus']) || !empty($item['lembur_in']))
                                        <tr class="text-center">
                                            <td class="small">{{ \Carbon\Carbon::parse($tgl)->translatedFormat('d M') }}</td>
                                            <td class="small">{{ $item['jam_masuk'] ?? '-' }}</td>
                                            <td class="small">{{ $item['jam_pulang'] ?? '-' }}</td>
                                            <td class="small">
                                                @if(!empty($item['lembur_duration']))
                                                    <span class="badge bg-warning text-dark">
                                                        {{ $item['lembur_duration'] }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if(!empty($item['status_khusus']))
                                                    <span class="badge bg-info px-2 small">{{ $item['status_khusus'] }}</span>
                                                    @if(!empty($item['keterangan_izin']))
                                                        <br><small class="text-muted">{{ Str::limit($item['keterangan_izin'], 20) }}</small>
                                                    @endif
                                                @elseif(!empty($item['terlambat_jam']))
                                                    <span class="badge bg-success px-2 small">Masuk</span>
                                                    <br><small class="text-danger">+{{ $item['terlambat_jam'] }}</small>
                                                @else
                                                    <span class="badge bg-success px-2 small">Masuk</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        @else
            <div class="alert alert-info">
                <ion-icon name="information-circle-outline"></ion-icon>
                Silakan pilih pegawai untuk melihat statistik.
            </div>
        @endif
    </div>
</div>

@push('myscript')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pie Chart Kehadiran
    const pieData = {
        labels: ['Masuk', 'Izin', 'Sakit', 'Cuti', 'Tidak Hadir'],
        datasets: [{
            data: [
                {{ $totalWorkDays ?? 0 }},
                {{ $jumlahIzin ?? 0 }},
                {{ $jumlahSakit ?? 0 }},
                {{ $jumlahCuti ?? 0 }},
                {{ ($totalHariPeriode ?? 0) - (($totalWorkDays ?? 0) + ($jumlahIzin ?? 0) + ($jumlahSakit ?? 0) + ($jumlahCuti ?? 0)) }}
            ],
            backgroundColor: ['#198754', '#0dcaf0', '#6f42c1', '#20c997', '#dc3545'],
        }]
    };
    new Chart(document.getElementById('chartKehadiran'), { 
        type: 'pie', 
        data: pieData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Bar Chart Tepat Waktu vs Terlambat
    const barData = {
        labels: ['Tepat Waktu', 'Terlambat', 'Pulang Awal', 'Pulang Lambat'],
        datasets: [{
            label: 'Jumlah Hari',
            data: [
                {{ $jumlahTepatWaktu ?? 0 }},
                {{ $jumlahTerlambat ?? 0 }},
                {{ $jumlahPulangAwal ?? 0 }},
                {{ $jumlahPulangLambat ?? 0 }}
            ],
            backgroundColor: ['#0d6efd', '#dc3545', '#ffc107', '#198754']
        }]
    };
    new Chart(document.getElementById('chartTepatTerlambat'), {
        type: 'bar',
        data: barData,
        options: { 
            responsive: true,
            plugins: { 
                legend: { display: false } 
            }, 
            scales: { 
                y: { 
                    beginAtZero: true, 
                    stepSize: 1 
                } 
            } 
        }
    });

    // Line Chart Tren Keterlambatan
    const lineData = {
        labels: {!! json_encode($chartLabels ?? []) !!},
        datasets: [{
            label: 'Keterlambatan (menit)',
            data: {!! json_encode($chartValues ?? []) !!},
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.3,
            fill: true
        }]
    };
    new Chart(document.getElementById('chartKeterlambatanHarian'), { 
        type: 'line', 
        data: lineData, 
        options: { 
            responsive: true,
            scales: { 
                y: { 
                    beginAtZero: true 
                } 
            } 
        } 
    });
});
</script>
@endpush
@endsection