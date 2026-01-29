<x-app-layout>
    <x-slot name="pagetitle">Detail Pencairan Bank</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-eye text-primary me-2"></i>Detail Pencairan Bank</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <a href="{{ route('pencairan-bank.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Info Pencairan -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informasi Pencairan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Kode Pencairan</th>
                                            <td>{{ $pencairan->kode_pencairan }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Pencairan</th>
                                            <td>{{ \Carbon\Carbon::parse($pencairan->tanggal_pencairan)->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Jenis Pencairan</th>
                                            <td>
                                                @php
                                                    $jenis = [
                                                        'dp_awal' => 'DP Awal',
                                                        'termin_1' => 'Termin 1',
                                                        'termin_2' => 'Termin 2',
                                                        'termin_3' => 'Termin 3',
                                                        'lunas' => 'Pelunasan',
                                                        'lainnya' => 'Lainnya'
                                                    ];
                                                @endphp
                                                <span class="badge bg-info">
                                                    {{ $jenis[$pencairan->jenis_pencairan] ?? '-' }}
                                                    @if($pencairan->termin_ke)
                                                        (Termin ke-{{ $pencairan->termin_ke }})
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Nominal</th>
                                            <td class="fw-bold text-success">
                                                Rp {{ number_format($pencairan->nominal_pencairan, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                @php
                                                    $badge = [
                                                        'pending' => 'bg-warning',
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        'realized' => 'bg-info'
                                                    ];
                                                @endphp
                                                <span class="badge {{ $badge[$pencairan->status_pencairan] ?? 'bg-secondary' }}">
                                                    {{ ucfirst($pencairan->status_pencairan) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Bank</th>
                                            <td>{{ $pencairan->bank_kredit }}</td>
                                        </tr>
                                        <tr>
                                            <th>No. Rekening</th>
                                            <td>{{ $pencairan->no_rekening_bank ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Nama Rekening</th>
                                            <td>{{ $pencairan->nama_rekening ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Realisasi</th>
                                            <td>
                                                @if($pencairan->tanggal_realisasi)
                                                    {{ \Carbon\Carbon::parse($pencairan->tanggal_realisasi)->format('d/m/Y') }}
                                                @else
                                                    <span class="text-muted">Belum direalisasi</span>
                                                @endif
                                            </td>
                                        </tr>
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
                            
                            <!-- Keterangan -->
                            @if($pencairan->keterangan)
                                <div class="mt-3">
                                    <h6>Keterangan:</h6>
                                    <p class="border rounded p-2 bg-light">{{ $pencairan->keterangan }}</p>
                                </div>
                            @endif
                            
                            <!-- Bukti Pencairan -->
                            @if($pencairan->bukti_pencairan)
                                <div class="mt-3">
                                    <h6>Bukti Pencairan:</h6>
                                    @php
                                        $ext = pathinfo($pencairan->bukti_pencairan, PATHINFO_EXTENSION);
                                    @endphp
                                    @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif']))
                                        <img src="{{ Storage::url('public/bukti_pencairan/' . $pencairan->bukti_pencairan) }}" 
                                             class="img-fluid rounded border" style="max-height: 300px;">
                                    @elseif($ext == 'pdf')
                                        <div class="alert alert-info">
                                            <i class="bi bi-file-earmark-pdf"></i> 
                                            File PDF: {{ $pencairan->bukti_pencairan }}
                                            <br>
                                            <a href="{{ Storage::url('public/bukti_pencairan/' . $pencairan->bukti_pencairan) }}" 
                                               target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                <i class="bi bi-download"></i> Download PDF
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Info Penjualan & Progress -->
                <div class="col-md-4">
                    <!-- Info Penjualan -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Informasi Penjualan</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="50%">Unit</th>
                                    <td>{{ $pencairan->penjualan->unitDetail->unit->namaunit ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Customer</th>
                                    <td>{{ $pencairan->penjualan->customer->nama_lengkap ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Kode Penjualan</th>
                                    <td>{{ $pencairan->penjualan->kode_penjualan }}</td>
                                </tr>
                                <tr>
                                    <th>Harga Jual</th>
                                    <td class="fw-bold">Rp {{ number_format($pencairan->penjualan->harga_jual, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>DP Awal</th>
                                    <td>Rp {{ number_format($pencairan->penjualan->dp_awal, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Sisa Pembayaran</th>
                                    <td>Rp {{ number_format($pencairan->penjualan->sisa_pembayaran, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Progress Pencairan -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Progress Pencairan</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-2">
                                <div class="h3">{{ number_format($progress, 1) }}%</div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $progress }}%" 
                                         aria-valuenow="{{ $progress }}" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            <table class="table table-sm">
                                <tr>
                                    <th>Total Dicairkan</th>
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
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Riwayat Pencairan untuk Penjualan Ini -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Riwayat Pencairan untuk Penjualan Ini</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Kode</th>
                                            <th>Jenis</th>
                                            <th>Tanggal</th>
                                            <th class="text-end">Nominal</th>
                                            <th>Bank</th>
                                            <th>Status</th>
                                            <th>Tanggal Realisasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pencairan->penjualan->pencairanBank as $index => $pc)
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
                                            @endphp
                                            <tr class="{{ $pc->id == $pencairan->id ? 'table-primary' : '' }}">
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <a href="{{ route('pencairan-bank.show', $pc->id) }}" class="text-decoration-none">
                                                        {{ $pc->kode_pencairan }}
                                                    </a>
                                                </td>
                                                <td>
                                                    {{ $jenis[$pc->jenis_pencairan] ?? '-' }}
                                                    @if($pc->termin_ke)
                                                        (Termin ke-{{ $pc->termin_ke }})
                                                    @endif
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($pc->tanggal_pencairan)->format('d/m/Y') }}</td>
                                                <td class="text-end fw-bold">Rp {{ number_format($pc->nominal_pencairan, 0, ',', '.') }}</td>
                                                <td>{{ $pc->bank_kredit }}</td>
                                                <td>
                                                    <span class="badge {{ $badge[$pc->status_pencairan] ?? 'bg-secondary' }}">
                                                        {{ ucfirst($pc->status_pencairan) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($pc->tanggal_realisasi)
                                                        {{ \Carbon\Carbon::parse($pc->tanggal_realisasi)->format('d/m/Y') }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-success">
                                        <tr>
                                            <th colspan="4" class="text-end">TOTAL DICAIRKAN:</th>
                                            <th class="text-end">Rp {{ number_format($totalPencairan, 0, ',', '.') }}</th>
                                            <th colspan="3"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>