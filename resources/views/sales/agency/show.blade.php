{{-- resources/views/sales/agency/show.blade.php --}}
<x-app-layout>
    <x-slot name="pagetitle">Detail Transaksi Agency</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Detail Transaksi Agency</h3>
                <div>
                    <a href="{{ route('agency-sales.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <a href="{{ route('agency-sales.edit', $nota->id) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Informasi Nota -->
            <div class="card card-primary mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-receipt"></i> Informasi Nota
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">No Nota</th>
                                    <td>: {{ $nota->nota_no }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal</th>
                                    <td>: {{ date('d/m/Y', strtotime($nota->tanggal)) }}</td>
                                </tr>
                                <tr>
                                    <th>Nama Transaksi</th>
                                    <td>: {{ $nota->namatransaksi }}</td>
                                </tr>
                                <tr>
                                    <th>Project</th>
                                    <td>: {{ $nota->project->namaproject ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Agency</th>
                                    <td>: {{ $nota->vendor->namavendor ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Payment Method</th>
                                    <td>: {{ strtoupper($nota->paymen_method) }}</td>
                                </tr>
                                @if($nota->paymen_method == 'tempo' && $nota->tgl_tempo)
                                <tr>
                                    <th>Tanggal Tempo</th>
                                    <td>: {{ date('d/m/Y', strtotime($nota->tgl_tempo)) }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Rekening</th>
                                    <td>: {{ $nota->rekening->norek ?? '-' }} - {{ $nota->rekening->namarek ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        : 
                                        @php
                                            $badge = [
                                                'open' => 'warning',
                                                'paid' => 'success',
                                                'partial' => 'info',
                                                'cancel' => 'danger'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $badge[$nota->status] ?? 'secondary' }}">
                                            {{ strtoupper($nota->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Subtotal</th>
                                    <td>: Rp {{ number_format($nota->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @if($nota->ppn > 0)
                                <tr>
                                    <th>PPN</th>
                                    <td>: Rp {{ number_format($nota->ppn, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                @if($nota->diskon > 0)
                                <tr>
                                    <th>Diskon</th>
                                    <td>: Rp {{ number_format($nota->diskon, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th><strong>Total</strong></th>
                                    <td>: <strong>Rp {{ number_format($nota->total, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Dibuat Oleh</th>
                                    <td>: {{ $nota->namauser }} - {{ $nota->nip }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Unit -->
            <div class="card card-info mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-house"></i> Informasi Unit
                    </h5>
                </div>
                <div class="card-body">
                    @if($nota->unitDetail)
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Unit</label>
                                <input type="text" class="form-control form-control-sm" 
                                       value="{{ $nota->unitDetail->unit->namaunit ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Customer</label>
                                <input type="text" class="form-control form-control-sm" 
                                       value="{{ $nota->unitDetail->customer->nama_lengkap ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Jual Unit</label>
                                <input type="text" class="form-control form-control-sm" 
                                       value="Rp {{ number_format($nota->unitDetail->penjualan->harga_jual ?? 0, 0, ',', '.') }}" readonly>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Informasi unit tidak tersedia
                        </div>
                    @endif
                </div>
            </div>

            <!-- Detail Transaksi -->
            <div class="card card-success mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check"></i> Detail Transaksi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Kode Transaksi</th>
                                    <th>Deskripsi</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Nominal</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @forelse($nota->transactions as $transaction)
                                    <tr>
                                        <td class="text-center">{{ $no++ }}</td>
                                        <td>
                                            {{ $transaction->kodeTransaksi->kodetransaksi ?? '-' }}
                                            @if($transaction->kodeTransaksi)
                                                <br><small class="text-muted">{{ $transaction->kodeTransaksi->transaksi }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->description }}</td>
                                        <td class="text-center">{{ number_format($transaction->jml, 0) }}</td>
                                        <td class="text-end">Rp {{ number_format($transaction->nominal, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Tidak ada detail transaksi</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td colspan="2" class="text-end">
                                        <strong>Rp {{ number_format($nota->subtotal, 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                                @if($nota->ppn > 0)
                                <tr>
                                    <td colspan="4" class="text-end"><strong>PPN:</strong></td>
                                    <td colspan="2" class="text-end">
                                        <strong>Rp {{ number_format($nota->ppn, 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                                @endif
                                @if($nota->diskon > 0)
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Diskon:</strong></td>
                                    <td colspan="2" class="text-end">
                                        <strong>Rp {{ number_format($nota->diskon, 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                    <td colspan="2" class="text-end">
                                        <strong class="text-primary">Rp {{ number_format($nota->total, 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bukti Nota -->
            @if($nota->bukti_nota)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark"></i> Bukti Nota
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $fileExtension = pathinfo($nota->bukti_nota, PATHINFO_EXTENSION);
                        $fileUrl = Storage::url($nota->bukti_nota);
                    @endphp
                    
                    @if(in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif']))
                        <div class="text-center">
                            <img src="{{ asset($fileUrl) }}" alt="Bukti Nota" 
                                 class="img-fluid rounded border" style="max-height: 400px;">
                            <div class="mt-2">
                                <a href="{{ asset($fileUrl) }}" target="_blank" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-download"></i> Download Gambar
                                </a>
                            </div>
                        </div>
                    @elseif(strtolower($fileExtension) == 'pdf')
                        <div class="alert alert-info">
                            <i class="bi bi-file-pdf"></i> File PDF
                            <a href="{{ asset($fileUrl) }}" target="_blank" 
                               class="btn btn-sm btn-primary ms-2">
                                <i class="bi bi-eye"></i> Lihat PDF
                            </a>
                            <a href="{{ asset($fileUrl) }}" download 
                               class="btn btn-sm btn-success">
                                <i class="bi bi-download"></i> Download PDF
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-file-earmark"></i> File Bukti
                            <a href="{{ asset($fileUrl) }}" download 
                               class="btn btn-sm btn-primary ms-2">
                                <i class="bi bi-download"></i> Download File
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Riwayat Pembayaran -->
            @if($nota->payments && $nota->payments->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cash-stack"></i> Riwayat Pembayaran
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Tanggal</th>
                                    <th>Rekening</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @foreach($nota->payments as $payment)
                                    <tr>
                                        <td class="text-center">{{ $no++ }}</td>
                                        <td>{{ date('d/m/Y', strtotime($payment->tanggal)) }}</td>
                                        <td>
                                            {{ $payment->rekening->norek ?? '-' }} - 
                                            {{ $payment->rekening->namarek ?? '-' }}
                                        </td>
                                        <td class="text-end">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total Dibayar:</strong></td>
                                    <td class="text-end">
                                        <strong>Rp {{ number_format($nota->payments->sum('jumlah'), 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Riwayat Update -->
            @if($nota->updateLogs && $nota->updateLogs->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history"></i> Riwayat Perubahan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($nota->updateLogs as $log)
                            <div class="timeline-item mb-3">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> 
                                            {{ date('d/m/Y H:i:s', strtotime($log->created_at)) }}
                                        </small>
                                    </div>
                                    <p class="mb-0">{{ $log->update_log }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <x-slot name="jscustom">
        <style>
            .timeline {
                position: relative;
                padding-left: 30px;
            }
            .timeline-item {
                position: relative;
            }
            .timeline-marker {
                position: absolute;
                left: -30px;
                top: 0;
                width: 12px;
                height: 12px;
                background-color: #0d6efd;
                border-radius: 50%;
            }
            .timeline-content {
                padding-bottom: 10px;
                border-left: 2px solid #dee2e6;
                padding-left: 15px;
            }
        </style>
    </x-slot>
</x-app-layout>