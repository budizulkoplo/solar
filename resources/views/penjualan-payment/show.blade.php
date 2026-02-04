<x-app-layout>
    <x-slot name="pagetitle">Detail Pembayaran</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-eye text-primary me-2"></i>Detail Pembayaran</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <a href="{{ route('penjualan-payment.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Info Pembayaran -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informasi Pembayaran</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Kode Pembayaran</th>
                                            <td>{{ $payment->kode_payment }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Pembayaran</th>
                                            <td>{{ \Carbon\Carbon::parse($payment->tanggal_payment)->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Jenis Pembayaran</th>
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
                                                    {{ $jenis[$payment->jenis_payment] ?? '-' }}
                                                    @if($payment->termin_ke)
                                                        (Termin ke-{{ $payment->termin_ke }})
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Metode Pembayaran</th>
                                            <td>
                                                @if($payment->metode_pembayaran == 'cash')
                                                    <span class="badge bg-success">Cash</span>
                                                @else
                                                    <span class="badge bg-info">Transfer Bank</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Nominal</th>
                                            <td class="fw-bold text-success">
                                                Rp {{ number_format($payment->nominal, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                @php
                                                    $badge = [
                                                        'pending' => 'bg-warning',
                                                        'realized' => 'bg-success'
                                                    ];
                                                @endphp
                                                <span class="badge {{ $badge[$payment->status_payment] ?? 'bg-secondary' }}">
                                                    {{ ucfirst($payment->status_payment) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        @if($payment->bank)
                                            <tr>
                                                <th width="40%">Bank</th>
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
                                            <th>Dibuat Oleh</th>
                                            <td>{{ $payment->creator->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Dibuat Pada</th>
                                            <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Diupdate Pada</th>
                                            <td>{{ $payment->updated_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Keterangan -->
                            @if($payment->keterangan)
                                <div class="mt-3">
                                    <h6>Keterangan:</h6>
                                    <p class="border rounded p-2 bg-light">{{ $payment->keterangan }}</p>
                                </div>
                            @endif
                            
                            <!-- Bukti Pembayaran -->
                            @if($payment->bukti_payment)
                                <div class="mt-3">
                                    <h6>Bukti Pembayaran:</h6>
                                    @php
                                        $ext = pathinfo($payment->bukti_payment, PATHINFO_EXTENSION);
                                    @endphp
                                    @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif']))
                                        <img src="{{ Storage::url('public/bukti_payment/' . $payment->bukti_payment) }}" 
                                             class="img-fluid rounded border" style="max-height: 300px;">
                                    @elseif($ext == 'pdf')
                                        <div class="alert alert-info">
                                            <i class="bi bi-file-earmark-pdf"></i> 
                                            File PDF: {{ $payment->bukti_payment }}
                                            <br>
                                            <a href="{{ Storage::url('public/bukti_payment/' . $payment->bukti_payment) }}" 
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
                                    <td>{{ $payment->penjualan->unitDetail->unit->namaunit ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Customer</th>
                                    <td>{{ $payment->penjualan->unitDetail->customer->nama_lengkap ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Kode Penjualan</th>
                                    <td>{{ $payment->penjualan->kode_penjualan }}</td>
                                </tr>
                                <tr>
                                    <th>Metode Pembayaran</th>
                                    <td>
                                        @if($payment->penjualan->metode_pembayaran == 'cash')
                                            <span class="badge bg-success">Cash</span>
                                        @else
                                            <span class="badge bg-info">Kredit - {{ $payment->penjualan->bank_kredit }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Harga Jual</th>
                                    <td class="fw-bold">Rp {{ number_format($payment->penjualan->harga_jual, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>DP Awal</th>
                                    <td>Rp {{ number_format($payment->penjualan->dp_awal, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Sisa Pembayaran</th>
                                    <td>Rp {{ number_format($payment->penjualan->sisa_pembayaran, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('penjualan-payment.edit', $payment->id) }}" 
                                   class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Edit Pembayaran
                                </a>
                                <a href="{{ route('penjualan-payment.detail', $payment->penjualan_id) }}" 
                                   class="btn btn-info">
                                    <i class="bi bi-list"></i> Lihat Semua Pembayaran
                                </a>
                                <button type="button" class="btn btn-danger" onclick="deletePayment({{ $payment->id }})">
                                    <i class="bi bi-trash"></i> Hapus Pembayaran
                                </button>
                            </div>
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
                                window.location.href = "{{ route('penjualan-payment.detail', $payment->penjualan_id) }}";
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
        </script>
    </x-slot>
</x-app-layout>