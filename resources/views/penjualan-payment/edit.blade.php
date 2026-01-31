<x-app-layout>
    <x-slot name="pagetitle">Edit Pembayaran Penjualan</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">
                        <i class="bi bi-pencil text-warning me-2"></i>
                        Edit Pembayaran Penjualan
                    </h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('penjualan-payment.index') }}">Pembayaran Penjualan</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('penjualan-payment.detail', $payment->penjualan_id) }}">Detail</a>
                            </li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-sm-6 text-end">
                    <a href="{{ route('penjualan-payment.detail', $payment->penjualan_id) }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Form Edit Pembayaran</h5>
                        </div>
                        <div class="card-body">
                            <form id="formEditPayment" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jenis Pembayaran *</label>
                                        <select class="form-select" name="jenis_payment" required>
                                            <option value="">-- Pilih Jenis --</option>
                                            <option value="dp_awal" {{ $payment->jenis_payment == 'dp_awal' ? 'selected' : '' }}>DP Awal</option>
                                            <option value="termin_1" {{ $payment->jenis_payment == 'termin_1' ? 'selected' : '' }}>Termin 1</option>
                                            <option value="termin_2" {{ $payment->jenis_payment == 'termin_2' ? 'selected' : '' }}>Termin 2</option>
                                            <option value="termin_3" {{ $payment->jenis_payment == 'termin_3' ? 'selected' : '' }}>Termin 3</option>
                                            <option value="lunas" {{ $payment->jenis_payment == 'lunas' ? 'selected' : '' }}>Pelunasan</option>
                                            <option value="lainnya" {{ $payment->jenis_payment == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Termin Ke (Opsional)</label>
                                        <input type="number" class="form-control" name="termin_ke" 
                                               min="1" value="{{ $payment->termin_ke }}" 
                                               placeholder="Masukkan termin ke-">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Metode Pembayaran *</label>
                                        <select class="form-select" name="metode_pembayaran" id="metode_pembayaran" required
                                                onchange="toggleBankFields()">
                                            <option value="cash" {{ $payment->metode_pembayaran == 'cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="transfer" {{ $payment->metode_pembayaran == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Pembayaran *</label>
                                        <input type="date" class="form-control" name="tanggal_payment" 
                                               value="{{ \Carbon\Carbon::parse($payment->tanggal_payment)->format('Y-m-d') }}" 
                                               required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nominal Pembayaran *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control numeric-input" 
                                                   name="nominal" 
                                                   value="{{ number_format($payment->nominal, 0, ',', '.') }}" 
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <!-- Fields untuk transfer bank -->
                                    <div class="col-md-6 mb-3" id="bankField" style="{{ $payment->metode_pembayaran == 'transfer' ? '' : 'display: none;' }}">
                                        <label class="form-label">Bank {{ $payment->metode_pembayaran == 'transfer' ? '*' : '' }}</label>
                                        <input type="text" class="form-control" name="bank" 
                                               value="{{ $payment->bank }}" 
                                               placeholder="Nama bank"
                                               {{ $payment->metode_pembayaran == 'transfer' ? 'required' : '' }}>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3" id="noRekField" style="{{ $payment->metode_pembayaran == 'transfer' ? '' : 'display: none;' }}">
                                        <label class="form-label">No. Rekening (Opsional)</label>
                                        <input type="text" class="form-control" name="no_rekening" 
                                               value="{{ $payment->no_rekening }}" 
                                               placeholder="Masukkan nomor rekening">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3" id="namaRekField" style="{{ $payment->metode_pembayaran == 'transfer' ? '' : 'display: none;' }}">
                                        <label class="form-label">Nama Rekening (Opsional)</label>
                                        <input type="text" class="form-control" name="nama_rekening" 
                                               value="{{ $payment->nama_rekening }}" 
                                               placeholder="Masukkan nama rekening">
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Keterangan (Opsional)</label>
                                        <textarea class="form-control" name="keterangan" rows="3" 
                                                  placeholder="Tambahkan keterangan...">{{ $payment->keterangan }}</textarea>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Bukti Pembayaran (Opsional)</label>
                                        <input type="file" class="form-control" name="bukti_payment" 
                                               accept=".jpg,.jpeg,.png,.pdf">
                                        <small class="text-muted">
                                            @if($payment->bukti_payment)
                                                File saat ini: {{ $payment->bukti_payment }}
                                            @else
                                                Belum ada file
                                            @endif
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> 
                                    <strong>Perhatian!</strong> Mengubah nominal pembayaran akan mempengaruhi sisa pembayaran penjualan.
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" 
                                            onclick="window.history.back()">Batal</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informasi Pembayaran</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Kode</th>
                                    <td>{{ $payment->kode_payment }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge {{ $payment->status_payment == 'realized' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($payment->status_payment) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Metode</th>
                                    <td>
                                        <span class="badge {{ $payment->metode_pembayaran == 'cash' ? 'bg-success' : 'bg-info' }}">
                                            {{ ucfirst($payment->metode_pembayaran) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Unit</th>
                                    <td>{{ $payment->penjualan->unitDetail->unit->namaunit ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Customer</th>
                                    <td>{{ $payment->penjualan->unitDetail->customer->nama_lengkap ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Sisa Pembayaran</th>
                                    <td class="fw-bold">
                                        Rp {{ number_format($payment->penjualan->sisa_pembayaran, 0, ',', '.') }}
                                    </td>
                                </tr>
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
    </div>

    <x-slot name="jscustom">
        <script>
            function toggleBankFields() {
                const method = $('#metode_pembayaran').val();
                const bankField = $('#bankField');
                const noRekField = $('#noRekField');
                const namaRekField = $('#namaRekField');
                
                if (method === 'transfer') {
                    bankField.show();
                    noRekField.show();
                    namaRekField.show();
                    $('input[name="bank"]').prop('required', true);
                } else {
                    bankField.hide();
                    noRekField.hide();
                    namaRekField.hide();
                    $('input[name="bank"]').prop('required', false);
                }
            }
            
            // Format numeric input
            $('.numeric-input').on('input', function() {
                let value = $(this).val().replace(/[^0-9]/g, '');
                if (value) {
                    value = parseInt(value).toLocaleString('id-ID');
                    $(this).val(value);
                }
            });
            
            // Handle form submission
            $('#formEditPayment').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Convert nominal back to number
                const nominal = formData.get('nominal').replace(/\./g, '');
                formData.set('nominal', nominal);
                
                // Show loading
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Memproses...');
                
                $.ajax({
                    url: "{{ route('penjualan-payment.update', $payment->id) }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            window.location.href = response.redirect;
                        } else {
                            alert(response.message);
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Terjadi kesalahan saat menyimpan data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert(errorMessage);
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // Initialize
            $(document).ready(function() {
                toggleBankFields();
            });
        </script>
    </x-slot>
</x-app-layout>