<x-app-layout>
    <x-slot name="pagetitle">Detail Pencairan Bank</x-slot>

    <style>
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .btn-action {
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        .card-unit {
            border-left: 4px solid #0d6efd;
        }
        
        .progress {
            height: 20px;
        }
        
        .progress-bar {
            line-height: 20px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #0d6efd;
        }
    </style>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">
                        <i class="bi bi-list text-primary me-2"></i>
                        Detail Pencairan Bank
                    </h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('pencairan-bank.index') }}">Pencairan Bank</a>
                            </li>
                            <li class="breadcrumb-item active">Detail</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('pencairan-bank.create-by-penjualan', $penjualan->id) }}" 
                           class="btn btn-success btn-sm {{ $sisaBelumDicairkan <= 0 ? 'disabled' : '' }}"
                           {{ $sisaBelumDicairkan <= 0 ? 'aria-disabled="true"' : '' }}>
                            <i class="bi bi-plus-circle"></i> Tambah Pencairan
                        </a>
                        <a href="{{ route('pencairan-bank.index') }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Info Unit & Penjualan -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card card-unit">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h6 class="mb-2">Informasi Unit</h6>
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <th width="40%">Unit</th>
                                            <td>{{ $penjualan->unitDetail->unit->namaunit ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Type</th>
                                            <td>{{ $penjualan->unitDetail->unit->type ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Project</th>
                                            <td>{{ $penjualan->unitDetail->unit->project->namaproject ?? '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="mb-2">Informasi Customer</h6>
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <th width="40%">Nama</th>
                                            <td>{{ $penjualan->unitDetail->customer->nama_lengkap ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>No. HP</th>
                                            <td>{{ $penjualan->unitDetail->customer->no_hp ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>KTP</th>
                                            <td>{{ $penjualan->unitDetail->customer->no_ktp ?? '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="mb-2">Informasi Penjualan</h6>
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <th width="40%">Kode</th>
                                            <td>{{ $penjualan->kode_penjualan }}</td>
                                        </tr>
                                        <tr>
                                            <th>Bank</th>
                                            <td>{{ $penjualan->bank_kredit }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Akad</th>
                                            <td>{{ $penjualan->tanggal_akad ? \Carbon\Carbon::parse($penjualan->tanggal_akad)->format('d/m/Y') : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status Penjualan</th>
                                            <td>
                                                @php
                                                    $badgePenjualan = [
                                                        'process' => 'bg-warning',
                                                        'selesai' => 'bg-success',
                                                        'lunas' => 'bg-info'
                                                    ];
                                                @endphp
                                                <span class="badge {{ $badgePenjualan[$penjualan->status_penjualan] ?? 'bg-secondary' }}">
                                                    {{ ucfirst($penjualan->status_penjualan) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress & Financial Summary -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Ringkasan Keuangan & Progress</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6>Informasi Harga</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="60%">Harga Jual</th>
                                            <td class="text-end fw-bold">
                                                Rp {{ number_format($penjualan->harga_jual, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>DP Awal</th>
                                            <td class="text-end">
                                                Rp {{ number_format($penjualan->dp_awal, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Sisa Pembayaran</th>
                                            <td class="text-end">
                                                Rp {{ number_format($penjualan->sisa_pembayaran, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tenor Kredit</th>
                                            <td class="text-end">
                                                {{ $penjualan->tenor_kredit }} bulan
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Cicilan per Bulan</th>
                                            <td class="text-end">
                                                Rp {{ number_format($penjualan->cicilan_bulanan, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Status Pencairan</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="60%">Total Dicairkan</th>
                                            <td class="text-end text-success fw-bold">
                                                Rp {{ number_format($totalPencairan, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Sisa Belum Dicairkan</th>
                                            <td class="text-end text-danger fw-bold">
                                                Rp {{ number_format($sisaBelumDicairkan, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Progress</th>
                                            <td class="text-end fw-bold">
                                                {{ number_format($progress, 1) }}%
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Jumlah Pencairan</th>
                                            <td class="text-end">
                                                {{ $penjualan->pencairanBank->count() }} kali
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td class="text-end">
                                                @if($totalPencairan == 0)
                                                    <span class="badge bg-secondary">Belum Dicairkan</span>
                                                @elseif($sisaBelumDicairkan <= 0)
                                                    <span class="badge bg-success">Lunas Dicairkan</span>
                                                @else
                                                    <span class="badge bg-warning">Dalam Proses</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Progress Pencairan</span>
                                    <span>{{ number_format($progress, 1) }}%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $progress }}%" 
                                         aria-valuenow="{{ $progress }}" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        {{ number_format($progress, 1) }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('pencairan-bank.create-by-penjualan', $penjualan->id) }}" 
                                   class="btn btn-success {{ $sisaBelumDicairkan <= 0 ? 'disabled' : '' }}"
                                   {{ $sisaBelumDicairkan <= 0 ? 'aria-disabled="true"' : '' }}>
                                    <i class="bi bi-plus-circle"></i> Tambah Pencairan Baru
                                </a>
                                
                                @if($penjualan->pencairanBank->count() > 0)
                                    <button type="button" class="btn btn-info" onclick="printRiwayat()">
                                        <i class="bi bi-printer"></i> Cetak Riwayat
                                    </button>
                                    
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalExport">
                                        <i class="bi bi-download"></i> Export Data
                                    </button>
                                @endif
                                

                            </div>
                            
                            @if($sisaBelumDicairkan > 0)
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Sisa yang dapat dicairkan:</strong><br>
                                    Rp {{ number_format($sisaBelumDicairkan, 0, ',', '.') }}
                                </div>
                            @else
                                <div class="alert alert-success mt-3">
                                    <i class="bi bi-check-circle"></i>
                                    <strong>Penjualan sudah lunas dicairkan!</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Pencairan -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-clock-history me-2"></i>
                                Riwayat Pencairan
                                <span class="badge bg-primary float-end">{{ $penjualan->pencairanBank->count() }} pencairan</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($penjualan->pencairanBank->count() > 0)
                                <!-- Timeline View -->
                                <div class="timeline">
                                    @foreach($penjualan->pencairanBank as $index => $pencairan)
                                        @php
                                            $jenis = [
                                                'dp_awal' => 'DP Awal',
                                                'termin_1' => 'Termin 1',
                                                'termin_2' => 'Termin 2',
                                                'termin_3' => 'Termin 3',
                                                'lunas' => 'Pelunasan',
                                                'lainnya' => 'Lainnya'
                                            ];
                                            
                                            $badge = [
                                                'pending' => 'bg-warning',
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'realized' => 'bg-info'
                                            ];
                                            
                                            $icon = [
                                                'dp_awal' => 'bi-cash',
                                                'termin_1' => 'bi-1-circle',
                                                'termin_2' => 'bi-2-circle',
                                                'termin_3' => 'bi-3-circle',
                                                'lunas' => 'bi-check-circle',
                                                'lainnya' => 'bi-currency-exchange'
                                            ];
                                        @endphp
                                        
                                        <div class="timeline-item">
                                            <div class="card mb-2">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-2">
                                                            <div class="text-center">
                                                                <i class="bi {{ $icon[$pencairan->jenis_pencairan] ?? 'bi-currency-exchange' }} fs-4 text-primary"></i><br>
                                                                <small class="text-muted">
                                                                    {{ $jenis[$pencairan->jenis_pencairan] ?? '-' }}
                                                                    @if($pencairan->termin_ke)
                                                                        (Termin ke-{{ $pencairan->termin_ke }})
                                                                    @endif
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small class="text-muted">Kode</small>
                                                            <p class="mb-0 fw-bold">{{ $pencairan->kode_pencairan }}</p>
                                                            <small class="text-muted">
                                                                {{ \Carbon\Carbon::parse($pencairan->tanggal_pencairan)->format('d/m/Y') }}
                                                            </small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small class="text-muted">Nominal</small>
                                                            <p class="mb-0 fw-bold text-success">
                                                                Rp {{ number_format($pencairan->nominal_pencairan, 0, ',', '.') }}
                                                            </p>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small class="text-muted">Status</small>
                                                            <p class="mb-0">
                                                                <span class="badge {{ $badge[$pencairan->status_pencairan] ?? 'bg-secondary' }}">
                                                                    {{ ucfirst($pencairan->status_pencairan) }}
                                                                </span>
                                                            </p>
                                                            @if($pencairan->tanggal_realisasi)
                                                                <small class="text-muted">
                                                                    Realisasi: {{ \Carbon\Carbon::parse($pencairan->tanggal_realisasi)->format('d/m/Y') }}
                                                                </small>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="btn-group btn-group-sm float-end">
                                                                <!-- View Details Button -->
                                                                <button type="button" class="btn btn-info btn-sm" 
                                                                        data-bs-toggle="modal" data-bs-target="#modalDetail{{ $pencairan->id }}">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                                
                                                                <!-- Action Buttons based on status -->
                                                                @if($pencairan->status_pencairan == 'pending')
                                                                    <button type="button" class="btn btn-success btn-sm" 
                                                                            onclick="approvePencairan({{ $pencairan->id }})">
                                                                        <i class="bi bi-check"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                                            onclick="rejectPencairan({{ $pencairan->id }})">
                                                                        <i class="bi bi-x"></i>
                                                                    </button>
                                                                    <a href="{{ route('pencairan-bank.edit', $pencairan->id) }}" 
                                                                       class="btn btn-warning btn-sm">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </a>
                                                                @elseif($pencairan->status_pencairan == 'approved')
                                                                    <button type="button" class="btn btn-primary btn-sm" 
                                                                            onclick="realisasiPencairan({{ $pencairan->id }})">
                                                                        <i class="bi bi-cash-coin"></i>
                                                                    </button>
                                                                @endif
                                                                
                                                                @if(in_array($pencairan->status_pencairan, ['pending', 'rejected']))
                                                                    <button type="button" class="btn btn-secondary btn-sm" 
                                                                            onclick="deletePencairan({{ $pencairan->id }})">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    @if($pencairan->keterangan)
                                                        <div class="mt-2">
                                                            <small class="text-muted">Keterangan:</small>
                                                            <p class="mb-0 small">{{ $pencairan->keterangan }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- Modal for Detail -->
                                            <div class="modal fade" id="modalDetail{{ $pencairan->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Detail Pencairan</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <table class="table table-sm">
                                                                <tr>
                                                                    <th width="40%">Kode Pencairan</th>
                                                                    <td>{{ $pencairan->kode_pencairan }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Jenis</th>
                                                                    <td>
                                                                        {{ $jenis[$pencairan->jenis_pencairan] ?? '-' }}
                                                                        @if($pencairan->termin_ke)
                                                                            (Termin ke-{{ $pencairan->termin_ke }})
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Tanggal Pencairan</th>
                                                                    <td>{{ \Carbon\Carbon::parse($pencairan->tanggal_pencairan)->format('d/m/Y') }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Nominal</th>
                                                                    <td class="fw-bold">Rp {{ number_format($pencairan->nominal_pencairan, 0, ',', '.') }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Bank</th>
                                                                    <td>{{ $pencairan->bank_kredit }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Status</th>
                                                                    <td>
                                                                        <span class="badge {{ $badge[$pencairan->status_pencairan] ?? 'bg-secondary' }}">
                                                                            {{ ucfirst($pencairan->status_pencairan) }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                                @if($pencairan->no_rekening_bank)
                                                                    <tr>
                                                                        <th>No. Rekening</th>
                                                                        <td>{{ $pencairan->no_rekening_bank }}</td>
                                                                    </tr>
                                                                @endif
                                                                @if($pencairan->nama_rekening)
                                                                    <tr>
                                                                        <th>Nama Rekening</th>
                                                                        <td>{{ $pencairan->nama_rekening }}</td>
                                                                    </tr>
                                                                @endif
                                                                @if($pencairan->tanggal_realisasi)
                                                                    <tr>
                                                                        <th>Tanggal Realisasi</th>
                                                                        <td>{{ \Carbon\Carbon::parse($pencairan->tanggal_realisasi)->format('d/m/Y') }}</td>
                                                                    </tr>
                                                                @endif
                                                                @if($pencairan->keterangan)
                                                                    <tr>
                                                                        <th>Keterangan</th>
                                                                        <td>{{ $pencairan->keterangan }}</td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <th>Dibuat Oleh</th>
                                                                    <td>{{ $pencairan->creator->name ?? '-' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Dibuat Pada</th>
                                                                    <td>{{ $pencairan->created_at->format('d/m/Y H:i') }}</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <!-- Summary Table -->
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <h6>Ringkasan Pencairan</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Status</th>
                                                        <th class="text-end">Jumlah</th>
                                                        <th class="text-end">Total Nominal</th>
                                                        <th>Persentase</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $summary = [];
                                                        foreach(['pending', 'approved', 'realized', 'rejected'] as $status) {
                                                            $items = $penjualan->pencairanBank->where('status_pencairan', $status);
                                                            $summary[$status] = [
                                                                'count' => $items->count(),
                                                                'total' => $items->sum('nominal_pencairan')
                                                            ];
                                                        }
                                                    @endphp
                                                    
                                                    @foreach($summary as $status => $data)
                                                        @if($data['count'] > 0)
                                                            <tr>
                                                                <td>
                                                                    <span class="badge {{ $badge[$status] ?? 'bg-secondary' }}">
                                                                        {{ ucfirst($status) }}
                                                                    </span>
                                                                </td>
                                                                <td class="text-end">{{ $data['count'] }} pencairan</td>
                                                                <td class="text-end fw-bold">
                                                                    Rp {{ number_format($data['total'], 0, ',', '.') }}
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $percentage = $totalPencairan > 0 ? ($data['total'] / $totalPencairan) * 100 : 0;
                                                                    @endphp
                                                                    <div class="progress" style="height: 5px;">
                                                                        <div class="progress-bar" role="progressbar" 
                                                                             style="width: {{ $percentage }}%"
                                                                             aria-valuenow="{{ $percentage }}" 
                                                                             aria-valuemin="0" aria-valuemax="100"></div>
                                                                    </div>
                                                                    <small class="text-muted">{{ number_format($percentage, 1) }}%</small>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-success">
                                                    <tr>
                                                        <th>TOTAL</th>
                                                        <th class="text-end">{{ $penjualan->pencairanBank->count() }} pencairan</th>
                                                        <th class="text-end">Rp {{ number_format($totalPencairan, 0, ',', '.') }}</th>
                                                        <th>100%</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 text-muted"></i>
                                    <h5 class="mt-2">Belum ada riwayat pencairan</h5>
                                    <p class="text-muted">Mulai dengan menambahkan pencairan baru untuk unit ini.</p>
                                    <a href="{{ route('pencairan-bank.create-by-penjualan', $penjualan->id) }}" 
                                       class="btn btn-success">
                                        <i class="bi bi-plus-circle"></i> Tambah Pencairan Pertama
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Export -->
    <div class="modal fade" id="modalExport" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Data Pencairan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Format</label>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" onclick="exportExcel()">
                                <i class="bi bi-file-earmark-excel"></i> Export ke Excel
                            </button>
                            <button type="button" class="btn btn-danger" onclick="exportPDF()">
                                <i class="bi bi-file-earmark-pdf"></i> Export ke PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Realisasi -->
    <div class="modal fade" id="modalRealisasi" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Realisasi Pencairan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRealisasi">
                    <div class="modal-body">
                        <input type="hidden" id="realisasi_id">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Realisasi *</label>
                            <input type="date" class="form-control" id="tanggal_realisasi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan Realisasi</label>
                            <textarea class="form-control" id="keterangan_realisasi" rows="3" placeholder="Tambahkan keterangan realisasi..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Realisasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            let selectedPencairanId = null;

            function formatRupiah(angka) {
                if (!angka || isNaN(angka)) return '-';
                return 'Rp ' + parseFloat(angka).toLocaleString('id-ID');
            }

            function approvePencairan(id) {
                if (confirm('Apakah Anda yakin ingin approve pencairan ini?')) {
                    $.ajax({
                        url: "{{ url('pencairan-bank/pencairan') }}/" + id + "/approve",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                location.reload();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr) {
                            alert(
                                xhr.responseJSON?.message ??
                                'Terjadi kesalahan server'
                            );
                        }
                    });
                }
            }

            function rejectPencairan(id) {
                if (confirm('Apakah Anda yakin ingin reject pencairan ini?')) {
                    $.ajax({
                        url: "{{ url('pencairan-bank') }}/" + id + "/reject",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            _method: 'PUT'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                location.reload();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr) {
                            alert('Terjadi kesalahan: ' + xhr.responseJSON?.message || 'Server error');
                        }
                    });
                }
            }

            function realisasiPencairan(id) {
                selectedPencairanId = id;
                $('#realisasi_id').val(id);
                $('#tanggal_realisasi').val(new Date().toISOString().split('T')[0]);
                $('#keterangan_realisasi').val('');
                $('#modalRealisasi').modal('show');
            }

            function deletePencairan(id) {
                if (confirm('Apakah Anda yakin ingin menghapus pencairan ini?')) {
                    $.ajax({
                        url: "{{ url('pencairan-bank') }}/" + id,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                location.reload();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr) {
                            alert('Terjadi kesalahan: ' + xhr.responseJSON?.message || 'Server error');
                        }
                    });
                }
            }

            function printRiwayat() {
                window.print();
            }

            function exportExcel() {
                const penjualanId = {{ $penjualan->id }};
                window.open("{{ url('pencairan-bank/export-excel') }}/" + penjualanId, '_blank');
                $('#modalExport').modal('hide');
            }

            function exportPDF() {
                const penjualanId = {{ $penjualan->id }};
                window.open("{{ url('pencairan-bank/export-pdf') }}/" + penjualanId, '_blank');
                $('#modalExport').modal('hide');
            }

            $(document).ready(function() {
                $('#formRealisasi').submit(function(e) {
                    e.preventDefault();

                    const id = $('#realisasi_id').val();

                    $.ajax({
                        url: "{{ url('pencairan-bank/pencairan') }}/" + id + "/realisasi",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            tanggal_realisasi: $('#tanggal_realisasi').val(),
                            keterangan_realisasi: $('#keterangan_realisasi').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                $('#modalRealisasi').modal('hide');
                                location.reload();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Terjadi kesalahan server';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            alert(msg);
                        }
                    });
                });
            });

        </script>
    </x-slot>
</x-app-layout>