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
                                
                                <input type="hidden" name="old_rekening" id="oldRekening" value="{{ $payment->idrek }}">
                                <input type="hidden" name="old_grand_total" id="oldGrandTotal" value="{{ $payment->nominal }}">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jenis Pembayaran *</label>
                                        <select class="form-select" name="jenis_payment" required>
                                            <option value="">-- Pilih Jenis --</option>
                                            <option value="dp_awal" {{ $payment->jenis_payment == 'dp_awal' ? 'selected' : '' }}>DP Awal</option>
                                            <option value="dp_uang_muka" {{ $payment->jenis_payment == 'dp_uang_muka' ? 'selected' : '' }}>DP Uang Muka</option>
                                            <option value="termin_1" {{ $payment->jenis_payment == 'termin_1' ? 'selected' : '' }}>Termin 1</option>
                                            <option value="termin_2" {{ $payment->jenis_payment == 'termin_2' ? 'selected' : '' }}>Termin 2</option>
                                            <option value="termin_3" {{ $payment->jenis_payment == 'termin_3' ? 'selected' : '' }}>Termin 3</option>
                                            <option value="retensi" {{ $payment->jenis_payment == 'retensi' ? 'selected' : '' }}>Point Retensi</option>
                                            <option value="sbum" {{ $payment->jenis_payment == 'sbum' ? 'selected' : '' }}>SBUM</option>
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
                                                onchange="toggleRekeningFields()">
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
                                    
                                    <!-- Rekening (Select2) -->
                                    <div class="col-md-6 mb-3" id="rekeningContainer" style="{{ $payment->metode_pembayaran == 'transfer' ? '' : 'display: none;' }}">
                                        <label class="form-label">Rekening Tujuan *</label>
                                        <select class="form-select select2" name="idrek" id="idrek" style="width:100%;"
                                                {{ $payment->metode_pembayaran == 'transfer' ? 'required' : '' }}>
                                            <option value="">-- Pilih Rekening --</option>
                                            @foreach($rekenings as $rek)
                                                <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}" 
                                                        data-norek="{{ $rek->norek }}" data-namarek="{{ $rek->namarek }}" 
                                                        data-bank="{{ $rek->namabank ?? $rek->nama }}"
                                                        {{ $payment->idrek == $rek->idrek ? 'selected' : '' }}>
                                                    {{ $rek->norek }} - {{ $rek->namarek }} ({{ $rek->namabank ?? $rek->nama }}) - Saldo: Rp {{ number_format($rek->saldo, 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <!-- Info Saldo Rekening -->
                                    <div class="col-md-6 mb-3" id="saldoInfoContainer" style="{{ $payment->metode_pembayaran == 'transfer' ? '' : 'display: none;' }}">
                                        <div class="alert alert-info p-2 mb-0">
                                            <i class="bi bi-wallet2"></i> 
                                            Saldo rekening tersedia: <strong id="availableBalance">Rp {{ number_format($payment->idrek ? ($rekenings->firstWhere('idrek', $payment->idrek)->saldo ?? 0) : 0, 0, ',', '.') }}</strong>
                                        </div>
                                    </div>
                                    
                                    <!-- Fields manual untuk rekening (opsional) -->
                                    <div class="col-md-6 mb-3" id="noRekContainer" style="{{ $payment->metode_pembayaran == 'transfer' ? '' : 'display: none;' }}">
                                        <label class="form-label">No. Rekening (Opsional)</label>
                                        <input type="text" class="form-control" name="no_rekening" id="no_rekening" 
                                               value="{{ $payment->no_rekening }}" 
                                               placeholder="Kosongkan untuk menggunakan norek dari rekening">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3" id="namaRekContainer" style="{{ $payment->metode_pembayaran == 'transfer' ? '' : 'display: none;' }}">
                                        <label class="form-label">Nama Rekening (Opsional)</label>
                                        <input type="text" class="form-control" name="nama_rekening" id="nama_rekening" 
                                               value="{{ $payment->nama_rekening }}" 
                                               placeholder="Kosongkan untuk menggunakan nama dari rekening">
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
                                    <strong>Perhatian!</strong> Mengubah nominal atau rekening akan mempengaruhi saldo rekening. 
                                    Perubahan akan melakukan rollback saldo lama dan menambah ke rekening baru.
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
                                        Rp {{ number_format(max(0, $payment->penjualan->harga_jual - $payment->penjualan->payments->where('status_payment', 'realized')->sum('nominal')), 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Rekening</th>
                                    <td>{{ $payment->bank }} - {{ $payment->no_rekening }} ({{ $payment->nama_rekening }})</td>
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
        <!-- Select2 -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        
        <!-- AutoNumeric JS -->
        <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

        <script>
            // Format Rupiah function
            function formatRupiah(angka) {
                if (!angka && angka !== 0) return 'Rp 0';
                let num = parseFloat(angka) || 0;
                return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function parseNumber(value) {
                if (!value && value !== 0) return 0;
                
                try {
                    if (value && value.jquery) {
                        value = value.val();
                    }
                    
                    if (typeof value === 'string') {
                        value = value.replace(/[^\d,-]/g, '');
                        value = value.replace(',', '.');
                    }
                    
                    let num = parseFloat(value);
                    return !isNaN(num) ? num : 0;
                } catch (e) {
                    return 0;
                }
            }
            
            function toggleRekeningFields() {
                const method = $('#metode_pembayaran').val();
                const rekeningContainer = $('#rekeningContainer');
                const saldoInfoContainer = $('#saldoInfoContainer');
                const noRekContainer = $('#noRekContainer');
                const namaRekContainer = $('#namaRekContainer');
                
                if (method === 'transfer') {
                    rekeningContainer.show();
                    saldoInfoContainer.show();
                    noRekContainer.show();
                    namaRekContainer.show();
                    $('#idrek').prop('required', true);
                } else {
                    rekeningContainer.hide();
                    saldoInfoContainer.hide();
                    noRekContainer.hide();
                    namaRekContainer.hide();
                    $('#idrek').prop('required', false);
                }
            }
            
            // Update saldo saat rekening dipilih
            $('#idrek').change(function() {
                const selected = $(this).find('option:selected');
                const saldo = selected.data('saldo') || 0;
                const norek = selected.data('norek') || '';
                const namarek = selected.data('namarek') || '';
                
                $('#availableBalance').text(formatRupiah(saldo));
                
                // Isi field manual dengan data dari rekening
                $('#no_rekening').val(norek);
                $('#nama_rekening').val(namarek);
            });
            
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
                // Initialize Select2
                $('.select2').select2({
                    width: '100%',
                    placeholder: '-- Pilih Rekening --'
                });
                
                toggleRekeningFields();
                
                // Set nilai awal saldo
                const selected = $('#idrek').find('option:selected');
                const saldo = selected.data('saldo') || 0;
                $('#availableBalance').text(formatRupiah(saldo));
            });
        </script>
    </x-slot>
</x-app-layout>
