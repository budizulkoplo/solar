{{-- resources/views/construction/report.blade.php --}}
<x-app-layout>
    <x-slot name="pagetitle">Laporan Pekerjaan Konstruksi</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Laporan Pekerjaan Konstruksi</h3>
                <div>
                    <a href="{{ route('pekerjaan-konstruksi.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <button class="btn btn-primary btn-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Filter -->
            <div class="card card-primary card-outline mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filter Laporan</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('pekerjaan-konstruksi.report') }}" method="GET">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label">Project</label>
                                <select class="form-select form-select-sm" name="project_id">
                                    <option value="">Semua Project</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" 
                                            {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                            {{ $project->namaproject }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Pekerjaan</label>
                                <select class="form-select form-select-sm" name="jenis_pekerjaan">
                                    <option value="">Semua Jenis</option>
                                    @foreach($jenisPekerjaan as $key => $value)
                                        <option value="{{ $key }}" 
                                            {{ request('jenis_pekerjaan') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select form-select-sm" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="planning" {{ request('status') == 'planning' ? 'selected' : '' }}>Planning</option>
                                    <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Sedang Berjalan</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                                    <option value="canceled" {{ request('status') == 'canceled' ? 'selected' : '' }}>Dibatalkan</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control form-control-sm" 
                                       name="start_date" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control form-control-sm" 
                                       name="end_date" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-12 mt-3">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <a href="{{ route('pekerjaan-konstruksi.report') }}" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-x-circle"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistik -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Pekerjaan</h6>
                            <h3 class="mb-0">{{ $totalPekerjaan }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Anggaran</h6>
                            <h3 class="mb-0">Rp {{ number_format($totalAnggaran, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Planning</h6>
                            <h3 class="mb-0">{{ $planning }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title">Ongoing</h6>
                            <h3 class="mb-0">{{ $ongoing }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Selesai</h6>
                            <h3 class="mb-0">{{ $completed }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Laporan -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Detail Pekerjaan Konstruksi</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Project</th>
                                    <th>Nama Pekerjaan</th>
                                    <th>Jenis</th>
                                    <th>Lokasi</th>
                                    <th>Volume</th>
                                    <th class="text-end">Anggaran</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Tanggal Buat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pekerjaan as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->project->namaproject ?? '-' }}</td>
                                        <td>{{ $item->nama_pekerjaan }}</td>
                                        <td>
                                            @php
                                                $badge = [
                                                    'irigasi' => 'info',
                                                    'renovasi' => 'warning',
                                                    'jalan' => 'success',
                                                    'bangunan' => 'primary',
                                                    'jembatan' => 'secondary',
                                                    'drainase' => 'dark',
                                                    'lainnya' => 'light'
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $badge[$item->jenis_pekerjaan] ?? 'light' }}">
                                                {{ $jenisPekerjaan[$item->jenis_pekerjaan] ?? $item->jenis_pekerjaan }}
                                            </span>
                                        </td>
                                        <td>{{ $item->lokasi ?? '-' }}</td>
                                        <td>
                                            @if($item->volume && $item->satuan)
                                                {{ $item->volume }} {{ $item->satuan }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">Rp {{ number_format($item->anggaran, 0, ',', '.') }}</td>
                                        <td>
                                            @if($item->tanggal_mulai && $item->tanggal_selesai)
                                                {{ date('d/m/Y', strtotime($item->tanggal_mulai)) }} - 
                                                {{ date('d/m/Y', strtotime($item->tanggal_selesai)) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $status = [
                                                    'planning' => 'secondary',
                                                    'ongoing' => 'warning',
                                                    'completed' => 'success',
                                                    'canceled' => 'danger'
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $status[$item->status] ?? 'light' }}">
                                                {{ ucfirst($item->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($item->status === 'completed')
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" style="width: 100%">100%</div>
                                                </div>
                                            @elseif($item->status === 'ongoing' && $item->tanggal_mulai && $item->tanggal_selesai)
                                                @php
                                                    $progress = $item->getProgressAttribute();
                                                @endphp
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-warning" style="width: {{ $progress }}%">
                                                        {{ round($progress, 0) }}%
                                                    </div>
                                                </div>
                                            @else
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" style="width: 0%">0%</div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ date('d/m/Y', strtotime($item->created_at)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="6" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>Rp {{ number_format($totalAnggaran, 0, ',', '.') }}</strong></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>