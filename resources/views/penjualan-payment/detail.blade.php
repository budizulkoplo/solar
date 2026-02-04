<x-app-layout>
    <x-slot name="pagetitle">Detail Pembayaran Penjualan</x-slot>

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
        
        .payment-method-badge {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">
                        <i class="bi bi-list text-primary me-2"></i>
                        Detail Pembayaran Penjualan
                    </h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('penjualan-payment.index') }}">Pembayaran Penjualan</a>
                            </li>
                            <li class="breadcrumb-item active">Detail</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('penjualan-payment.create-by-penjualan', $penjualan->id) }}" 
                           class="btn btn-success btn-sm {{ $sisaBelumDibayar <= 0 ? 'disabled' : '' }}"
                           {{ $sisaBelumDibayar <= 0 ? 'aria-disabled="true"' : '' }}>
                            <i class="bi bi-plus-circle"></i> Tambah Pembayaran
                        </a>
                        <a href="{{ route('penjualan-payment.index') }}" class="btn btn-secondary btn-sm">
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
                                            <th>Metode Pembayaran</th>
                                            <td>
                                                @if($penjualan->metode_pembayaran == 'cash')
                                                    <span class="badge bg-success">Cash</span>
                                                @else
                                                    <span class="badge bg-info">Kredit - {{ $penjualan->bank_kredit }}</span>
                                                @endif
                                            </td>
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
                                        @if($penjualan->metode_pembayaran == 'kredit')
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
                                        @endif
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Status Pembayaran</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="60%">Total Dibayar</th>
                                            <td class="text-end text-success fw-bold">
                                                Rp {{ number_format($totalPayment, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Sisa Belum Dibayar</th>
                                            <td class="text-end text-danger fw-bold">
                                                Rp {{ number_format($sisaBelumDibayar, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Progress</th>
                                            <td class="text-end fw-bold">
                                                {{ number_format($progress, 1) }}%
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Jumlah Pembayaran</th>
                                            <td class="text-end">
                                                {{ $penjualan->payments->count() }} kali
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td class="text-end">
                                                @if($totalPayment == 0)
                                                    <span class="badge bg-secondary">Belum Bayar</span>
                                                @elseif($sisaBelumDibayar <= 0)
                                                    <span class="badge bg-success">Lunas</span>
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
                                    <span>Progress Pembayaran</span>
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
                                <a href="{{ route('penjualan-payment.create-by-penjualan', $penjualan->id) }}" 
                                   class="btn btn-success {{ $sisaBelumDibayar <= 0 ? 'disabled' : '' }}"
                                   {{ $sisaBelumDibayar <= 0 ? 'aria-disabled="true"' : '' }}>
                                    <i class="bi bi-plus-circle"></i> Tambah Pembayaran Baru
                                </a>
                                
                                @if($penjualan->payments->count() > 0)
                                    <button type="button" class="btn btn-info" onclick="printRiwayat()">
                                        <i class="bi bi-printer"></i> Cetak Riwayat
                                    </button>
                                    
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalExport">
                                        <i class="bi bi-download"></i> Export Data
                                    </button>
                                @endif
                            </div>
                            
                            @if($sisaBelumDibayar > 0)
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Sisa yang dapat dibayar:</strong><br>
                                    Rp {{ number_format($sisaBelumDibayar, 0, ',', '.') }}
                                </div>
                            @else
                                <div class="alert alert-success mt-3">
                                    <i class="bi bi-check-circle"></i>
                                    <strong>Penjualan sudah lunas!</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Pembayaran -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-clock-history me-2"></i>
                                Riwayat Pembayaran
                                <span class="badge bg-primary float-end">{{ $penjualan->payments->count() }} pembayaran</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($penjualan->payments->count() > 0)
                                <!-- Timeline View -->
                                <div class="timeline">
                                    @foreach($penjualan->payments as $index => $payment)
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
                                                'realized' => 'bg-success'
                                            ];
                                            
                                            $methodBadge = $payment->metode_pembayaran == 'cash' ? 'bg-success' : 'bg-info';
                                            
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
                                                                <i class="bi {{ $icon[$payment->jenis_payment] ?? 'bi-currency-exchange' }} fs-4 text-primary"></i><br>
                                                                <small class="text-muted">
                                                                    {{ $jenis[$payment->jenis_payment] ?? '-' }}
                                                                    @if($payment->termin_ke)
                                                                        (Termin ke-{{ $payment->termin_ke }})
                                                                    @endif
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small class="text-muted">Kode</small>
                                                            <p class="mb-0 fw-bold">{{ $payment->kode_payment }}</p>
                                                            <small class="text-muted">
                                                                {{ \Carbon\Carbon::parse($payment->tanggal_payment)->format('d/m/Y') }}
                                                            </small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small class="text-muted">Metode</small>
                                                            <p class="mb-0">
                                                                <span class="badge {{ $methodBadge }}">
                                                                    {{ ucfirst($payment->metode_pembayaran) }}
                                                                </span>
                                                            </p>
                                                            @if($payment->bank)
                                                                <small class="text-muted">{{ $payment->bank }}</small>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small class="text-muted">Nominal</small>
                                                            <p class="mb-0 fw-bold text-success">
                                                                Rp {{ number_format($payment->nominal, 0, ',', '.') }}
                                                            </p>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <small class="text-muted">Status</small>
                                                            <p class="mb-0">
                                                                <span class="badge {{ $badge[$payment->status_payment] ?? 'bg-secondary' }}">
                                                                    {{ ucfirst($payment->status_payment) }}
                                                                </span>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <div class="btn-group btn-group-sm float-end">
                                                                <!-- View Details Button -->
                                                                <button type="button" class="btn btn-info btn-sm" 
                                                                        data-bs-toggle="modal" data-bs-target="#modalDetail{{ $payment->id }}">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                                
                                                                <!-- Edit Button -->
                                                                <a href="{{ route('penjualan-payment.edit', $payment->id) }}" 
                                                                   class="btn btn-warning btn-sm">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                                
                                                                <!-- Delete Button -->
                                                                <button type="button" class="btn btn-danger btn-sm" 
                                                                        onclick="deletePayment({{ $payment->id }})">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    @if($payment->keterangan)
                                                        <div class="mt-2">
                                                            <small class="text-muted">Keterangan:</small>
                                                            <p class="mb-0 small">{{ $payment->keterangan }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- Modal for Detail -->
                                            <div class="modal fade" id="modalDetail{{ $payment->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Detail Pembayaran</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <table class="table table-sm">
                                                                <tr>
                                                                    <th width="40%">Kode Pembayaran</th>
                                                                    <td>{{ $payment->kode_payment }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Jenis</th>
                                                                    <td>
                                                                        {{ $jenis[$payment->jenis_payment] ?? '-' }}
                                                                        @if($payment->termin_ke)
                                                                            (Termin ke-{{ $payment->termin_ke }})
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Metode Pembayaran</th>
                                                                    <td>
                                                                        <span class="badge {{ $methodBadge }}">
                                                                            {{ ucfirst($payment->metode_pembayaran) }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Tanggal Pembayaran</th>
                                                                    <td>{{ \Carbon\Carbon::parse($payment->tanggal_payment)->format('d/m/Y') }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Nominal</th>
                                                                    <td class="fw-bold">Rp {{ number_format($payment->nominal, 0, ',', '.') }}</td>
                                                                </tr>
                                                                @if($payment->bank)
                                                                    <tr>
                                                                        <th>Bank</th>
                                                                        <td>{{ $payment->bank }}</td>
                                                                    </tr>
                                                                @endif
                                                                @if($payment->no_rekening)
                                                                    <tr>
                                                                        <th>No. Rekening</th>
                                                                        <td>{{ $payment->no_rekening }}</td>
                                                                    </tr>
                                                                @endif
                                                                @if($payment->nama_rekening)
                                                                    <tr>
                                                                        <th>Nama Rekening</th>
                                                                        <td>{{ $payment->nama_rekening }}</td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <th>Status</th>
                                                                    <td>
                                                                        <span class="badge {{ $badge[$payment->status_payment] ?? 'bg-secondary' }}">
                                                                            {{ ucfirst($payment->status_payment) }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                                @if($payment->keterangan)
                                                                    <tr>
                                                                        <th>Keterangan</th>
                                                                        <td>{{ $payment->keterangan }}</td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <th>Dibuat Oleh</th>
                                                                    <td>{{ $payment->creator->name ?? '-' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Dibuat Pada</th>
                                                                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
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
                                        <h6>Ringkasan Pembayaran</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Metode</th>
                                                        <th>Jumlah</th>
                                                        <th class="text-end">Total Nominal</th>
                                                        <th>Persentase</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $summary = [
                                                            'cash' => ['count' => 0, 'total' => 0],
                                                            'transfer' => ['count' => 0, 'total' => 0]
                                                        ];
                                                        
                                                        foreach($penjualan->payments as $payment) {
                                                            $summary[$payment->metode_pembayaran]['count']++;
                                                            $summary[$payment->metode_pembayaran]['total'] += $payment->nominal;
                                                        }
                                                    @endphp
                                                    
                                                    @foreach($summary as $method => $data)
                                                        @if($data['count'] > 0)
                                                            <tr>
                                                                <td>
                                                                    @if($method == 'cash')
                                                                        <span class="badge bg-success">Cash</span>
                                                                    @else
                                                                        <span class="badge bg-info">Transfer</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $data['count'] }} pembayaran</td>
                                                                <td class="text-end fw-bold">
                                                                    Rp {{ number_format($data['total'], 0, ',', '.') }}
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $percentage = $totalPayment > 0 ? ($data['total'] / $totalPayment) * 100 : 0;
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
                                                        <th>{{ $penjualan->payments->count() }} pembayaran</th>
                                                        <th class="text-end">Rp {{ number_format($totalPayment, 0, ',', '.') }}</th>
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
                                    <h5 class="mt-2">Belum ada riwayat pembayaran</h5>
                                    <p class="text-muted">Mulai dengan menambahkan pembayaran baru untuk unit ini.</p>
                                    <a href="{{ route('penjualan-payment.create-by-penjualan', $penjualan->id) }}" 
                                       class="btn btn-success">
                                        <i class="bi bi-plus-circle"></i> Tambah Pembayaran Pertama
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
                    <h5 class="modal-title">Export Data Pembayaran</h5>
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

    <x-slot name="jscustom">
        <script>
            function deletePayment(id) {
                if (confirm('Apakah Anda yakin ingin menghapus pembayaran ini?')) {
                    $.ajax({
                        url: "{{ url('penjualan-payment') }}/" + id,
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
                window.open("{{ url('penjualan-payment/export-excel') }}/" + penjualanId, '_blank');
                $('#modalExport').modal('hide');
            }

            function exportPDF() {
                const penjualanId = {{ $penjualan->id }};
                window.open("{{ url('penjualan-payment/export-pdf') }}/" + penjualanId, '_blank');
                $('#modalExport').modal('hide');
            }
        </script>
    </x-slot>
</x-app-layout>