<x-app-layout>
    <x-slot name="pagetitle">Tambah Pembayaran Penjualan</x-slot>

    <style>
        .progress {
            height: 20px;
        }
        .progress-bar {
            line-height: 20px;
        }
        .card-unit {
            border-left: 4px solid #0d6efd;
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
                        <i class="bi bi-plus-circle text-success me-2"></i>
                        Tambah Pembayaran Penjualan
                    </h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('penjualan-payment.index') }}">Pembayaran Penjualan</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('penjualan-payment.detail', $penjualan->id) }}">Detail</a>
                            </li>
                            <li class="breadcrumb-item active">Tambah Pembayaran</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-sm-6 text-end">
                    <a href="{{ route('penjualan-payment.detail', $penjualan->id) }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali ke Detail
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Info Unit & Customer -->
                <div class="col-md-12 mb-3">
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
                                            <th>No. Rumah</th>
                                            <td>
                                                <span class="no-rumah-badge">
                                                    {{ $penjualan->unitDetail->no_rumah ?? 'UR-' . $penjualan->unitDetail->id }}
                                                </span>
                                            </td>
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
                                            <td>{{ $penjualan->customer->nama_lengkap ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>No. HP</th>
                                            <td>{{ $penjualan->customer->no_hp ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>KTP</th>
                                            <td>{{ $penjualan->customer->no_ktp ?? '-' }}</td>
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
                                            <th>Status</th>
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

                <!-- Progress & Financial Info -->
                <div class="col-md-8 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Informasi Keuangan & Progress Pembayaran</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
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
                                    </table>
                                </div>
                                <div class="col-md-6">
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
                            
                            <!-- Riwayat Pembayaran -->
                            @if(count($penjualan->payments) > 0)
                                <div class="mt-3">
                                    <h6>Riwayat Pembayaran</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th width="50">#</th>
                                                    <th>Jenis</th>
                                                    <th>Metode</th>
                                                    <th>Tanggal</th>
                                                    <th class="text-end">Nominal</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
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
                                                        
                                                        $badgeStatus = [
                                                            'pending' => 'bg-warning',
                                                            'realized' => 'bg-success'
                                                        ];
                                                        
                                                        $methodBadge = $payment->metode_pembayaran == 'cash' ? 'bg-success' : 'bg-info';
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            {{ $jenis[$payment->jenis_payment] ?? '-' }}
                                                            @if($payment->termin_ke)
                                                                (Termin ke-{{ $payment->termin_ke }})
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $methodBadge }}">
                                                                {{ ucfirst($payment->metode_pembayaran) }}
                                                            </span>
                                                        </td>
                                                        <td>{{ \Carbon\Carbon::parse($payment->tanggal_payment)->format('d/m/Y') }}</td>
                                                        <td class="text-end">Rp {{ number_format($payment->nominal, 0, ',', '.') }}</td>
                                                        <td>
                                                            <span class="badge {{ $badgeStatus[$payment->status_payment] ?? 'bg-secondary' }}">
                                                                {{ ucfirst($payment->status_payment) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Belum ada riwayat pembayaran untuk unit ini.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Form Pembayaran -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Form Pembayaran Baru</h6>
                        </div>
                        <div class="card-body">
                            <form id="formPayment" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="penjualan_id" value="{{ $penjualan->id }}">
                                
                                <div class="mb-3">
                                    <label class="form-label">Jenis Pembayaran *</label>
                                    <select class="form-select" name="jenis_payment" id="jenis_payment" required 
                                            onchange="updateJenisPayment()">
                                        <option value="">-- Pilih Jenis --</option>
                                        @if($totalPayment == 0)
                                            <option value="dp_awal" selected>DP Awal</option>
                                        @endif
                                        <option value="termin_1">Termin 1</option>
                                        <option value="termin_2">Termin 2</option>
                                        <option value="termin_3">Termin 3</option>
                                        @if($sisaBelumDibayar < $penjualan->harga_jual * 0.1)
                                            <option value="lunas">Pelunasan</option>
                                        @endif
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="terminContainer" style="display: none;">
                                    <label class="form-label">Termin Ke</label>
                                    <input type="number" class="form-control" name="termin_ke" 
                                           id="termin_ke" min="1" placeholder="Masukkan termin ke-">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Metode Pembayaran *</label>
                                    <select class="form-select" name="metode_pembayaran" id="metode_pembayaran" required 
                                            onchange="toggleBankFields()">
                                        <option value="cash">Cash</option>
                                        <option value="transfer">Transfer Bank</option>
                                    </select>
                                </div>
                                
                                <!-- Fields untuk transfer bank -->
                                <div id="bankFields" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Bank *</label>
                                        <input type="text" class="form-control" name="bank" 
                                               placeholder="Nama bank (contoh: BNI, BCA, BRI)">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">No. Rekening</label>
                                        <input type="text" class="form-control" name="no_rekening" 
                                               placeholder="Masukkan nomor rekening">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nama Rekening</label>
                                        <input type="text" class="form-control" name="nama_rekening" 
                                               placeholder="Masukkan nama rekening">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pembayaran *</label>
                                    <input type="date" class="form-control" name="tanggal_payment" 
                                           value="{{ date('Y-m-d') }}" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nominal Pembayaran *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control numeric-input" 
                                               name="nominal" id="nominal" 
                                               value="{{ number_format($sisaBelumDibayar > $penjualan->dp_awal ? $penjualan->dp_awal : $sisaBelumDibayar, 0, ',', '.') }}" 
                                               required>
                                    </div>
                                    <small class="text-muted">
                                        Maksimal: Rp {{ number_format($sisaBelumDibayar, 0, ',', '.') }}
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control" name="keterangan" rows="3" 
                                              placeholder="Tambahkan keterangan..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Bukti Pembayaran (Opsional)</label>
                                    <input type="file" class="form-control" name="bukti_payment" 
                                           accept=".jpg,.jpeg,.png,.pdf">
                                    <small class="text-muted">Format: JPG, PNG, PDF. Maks: 8MB</small>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 
                                    @if($penjualan->metode_pembayaran == 'cash')
                                        Pembayaran cash akan langsung masuk ke status <strong>Realized</strong>.
                                    @else
                                        Pembayaran kredit akan masuk ke status <strong>Realized</strong> 
                                        dan mengurangi sisa pembayaran.
                                    @endif
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Simpan Pembayaran
                                    </button>
                                    <button type="button" class="btn btn-secondary" 
                                            onclick="window.history.back()">
                                        <i class="bi bi-x-circle"></i> Batal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            function updateJenisPayment() {
                const jenis = $('#jenis_payment').val();
                const terminContainer = $('#terminContainer');
                
                if (jenis.startsWith('termin_')) {
                    terminContainer.show();
                    // Auto set termin ke
                    const terminKe = jenis.replace('termin_', '');
                    $('#termin_ke').val(terminKe);
                } else {
                    terminContainer.hide();
                    $('#termin_ke').val('');
                }
            }
            
            function toggleBankFields() {
                const method = $('#metode_pembayaran').val();
                const bankFields = $('#bankFields');
                
                if (method === 'transfer') {
                    bankFields.show();
                    $('input[name="bank"]').prop('required', true);
                } else {
                    bankFields.hide();
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
            
            // Validasi nominal tidak melebihi sisa
            $('#nominal').on('blur', function() {
                const nominal = parseFloat($(this).val().replace(/\./g, ''));
                const sisaBelumDibayar = parseFloat("{{ $sisaBelumDibayar }}");
                
                if (nominal > sisaBelumDibayar) {
                    alert('Nominal tidak boleh melebihi sisa yang belum dibayar: Rp ' + 
                          sisaBelumDibayar.toLocaleString('id-ID'));
                    $(this).val(sisaBelumDibayar.toLocaleString('id-ID'));
                }
            });
            
            // Handle form submission
            $('#formPayment').submit(function(e) {
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
                    url: "{{ route('penjualan-payment.store') }}",
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
                updateJenisPayment();
                toggleBankFields();
                
                // Set nilai default untuk bank jika penjualan kredit
                @if($penjualan->metode_pembayaran == 'kredit')
                    $('#metode_pembayaran').val('transfer');
                    $('#bankFields').show();
                    $('input[name="bank"]').val("{{ $penjualan->bank_kredit }}");
                    $('input[name="bank"]').prop('required', true);
                @endif
            });
        </script>
    </x-slot>
</x-app-layout>