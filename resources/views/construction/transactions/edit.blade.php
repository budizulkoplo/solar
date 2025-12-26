<x-app-layout>
    <x-slot name="pagetitle">Edit Transaksi - {{ $pekerjaan->nama_pekerjaan }}</x-slot>

    <div class="container-fluid py-2">
        <!-- Header dengan Breadcrumb -->
        <div class="row mb-3">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item">
                            <a href="{{ route('construction.transactions.index') }}">Pekerjaan Konstruksi</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('construction.transactions.detail', $pekerjaan->id) }}">
                                {{ $pekerjaan->nama_pekerjaan }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active">Edit Transaksi</li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Edit Transaksi Konstruksi</h4>
                        <small class="text-muted">{{ $pekerjaan->nama_pekerjaan }} - {{ $pekerjaan->project->namaproject ?? '-' }}</small>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('construction.transactions.detail', $pekerjaan->id) }}" 
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Anggaran -->
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted">Anggaran</small>
                        <h5 class="text-primary">Rp {{ number_format($pekerjaan->anggaran ?? 0, 0, ',', '.') }}</h5>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Sisa Anggaran</small>
                        <h5 class="text-success">Rp {{ number_format($sisaAnggaran + $nota->total, 0, ',', '.') }}</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="progress" style="height: 20px;">
                            @php
                                $percentage = $pekerjaan->anggaran > 0 ? (($pekerjaan->anggaran - ($sisaAnggaran + $nota->total)) / $pekerjaan->anggaran) * 100 : 0;
                                $progressClass = '';
                                if ($percentage >= 100) {
                                    $progressClass = 'bg-danger';
                                } elseif ($percentage >= 80) {
                                    $progressClass = 'bg-warning';
                                } else {
                                    $progressClass = 'bg-success';
                                }
                            @endphp
                            <div class="progress-bar {{ $progressClass }}" role="progressbar" 
                                 style="width: {{ min($percentage, 100) }}%">
                                {{ round($percentage, 1) }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Edit Transaksi -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="frmTransaction" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" value="{{ $nota->id }}">
                    <input type="hidden" name="idproject" value="{{ $pekerjaan->idproject }}">
                    <input type="hidden" name="old_rekening" id="oldRekening" value="{{ $nota->idrek }}">
                    <input type="hidden" name="old_grand_total" id="oldGrandTotal" value="{{ $nota->total }}">
                    
                    <div class="row g-3">
                        <!-- Info Pekerjaan -->
                        <div class="col-12">
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Pekerjaan:</strong> {{ $pekerjaan->nama_pekerjaan }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Lokasi:</strong> {{ $pekerjaan->lokasi ?? '-' }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Jenis:</strong> {{ $jenisPekerjaan[$pekerjaan->jenis_pekerjaan] ?? $pekerjaan->jenis_pekerjaan }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Header -->
                        <div class="col-md-4">
                            <label class="form-label">No Invoice *</label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" name="nota_no" 
                                       id="notaNo" value="{{ $nota->nota_no }}" required>
                                <div class="input-group-text">
                                    <input type="checkbox" id="chkManualNo" checked> Manual
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <label class="form-label">Nama Transaksi *</label>
                            <input type="text" class="form-control form-control-sm" name="namatransaksi" 
                                   id="namatransaksi" value="{{ $nota->namatransaksi }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal *</label>
                            <input type="date" class="form-control form-control-sm" name="tanggal" 
                                   id="tanggalNota" value="{{ $nota->tanggal }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Vendor</label>
                            <select class="form-select form-select-sm select2" name="vendor_id" id="vendorId" style="width:100%;">
                                <option value="">-- Pilih Vendor --</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" {{ $nota->vendor_id == $v->id ? 'selected' : '' }}>
                                        {{ $v->namavendor }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Payment Method *</label>
                            <select class="form-select form-select-sm" name="paymen_method" id="paymenMethod" required>
                                <option value="cash" {{ $nota->paymen_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="tempo" {{ $nota->paymen_method == 'tempo' ? 'selected' : '' }}>Tempo</option>
                            </select>
                        </div>

                        <!-- Rekening -->
                        <div class="col-md-6">
                            <label class="form-label">Rekening *</label>
                            <select class="form-select form-select-sm select2" name="idrek" id="idRekening" style="width:100%;" required>
                                <option value="">-- Pilih Rekening --</option>
                                @foreach($rekenings as $rek)
                                    <option value="{{ $rek->idrek }}" 
                                            data-saldo="{{ $rek->saldo }}"
                                            {{ $nota->idrek == $rek->idrek ? 'selected' : '' }}>
                                        {{ $rek->norek }} - {{ $rek->namarek }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tanggal Tempo -->
                        <div class="col-md-6" id="tglTempoContainer" style="{{ $nota->paymen_method == 'tempo' ? '' : 'display:none;' }}">
                            <label class="form-label">Tanggal Tempo {{ $nota->paymen_method == 'tempo' ? '*' : '' }}</label>
                            <input type="date" class="form-control form-control-sm" name="tgl_tempo" 
                                   id="tglTempo" value="{{ $nota->tgl_tempo }}" 
                                   {{ $nota->paymen_method == 'tempo' ? 'required' : '' }}>
                        </div>

                        <!-- Bukti Nota -->
                        <div class="col-12">
                            <label class="form-label">Bukti Nota</label>
                            <input type="file" class="form-control form-control-sm" name="bukti_nota" 
                                   id="buktiNota" accept=".jpg,.jpeg,.png,.pdf">
                            <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                            
                            @if($nota->bukti_nota)
                                <div class="mt-2">
                                    <small>File saat ini:</small>
                                    <div class="d-flex align-items-center mt-1">
                                        @php
                                            $fileExt = pathinfo($nota->bukti_nota, PATHINFO_EXTENSION);
                                            $isImage = in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png', 'gif']);
                                            $fileUrl = Storage::url($nota->bukti_nota);
                                        @endphp
                                        
                                        @if($isImage)
                                            <img src="{{ asset('storage/' . $nota->bukti_nota) }}" 
                                                 class="img-thumbnail me-2" style="max-height: 60px;">
                                        @else
                                            <i class="bi bi-file-earmark-pdf text-danger me-2" style="font-size: 24px;"></i>
                                        @endif
                                        
                                        <a href="{{ asset('storage/' . $nota->bukti_nota) }}" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Lihat File
                                        </a>
                                        
                                        <div class="form-check ms-3">
                                            <input class="form-check-input" type="checkbox" id="replaceFile">
                                            <label class="form-check-label small" for="replaceFile">
                                                Ganti file
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <div id="buktiPreview" class="mt-2" style="display:none;">
                                <img id="previewImage" src="#" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                            </div>
                        </div>

                        <hr class="my-2">

                        <!-- Saldo Info -->
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <i class="bi bi-wallet2"></i> 
                                        Saldo tersedia: <span id="availableBalance">Rp 0</span>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="text-primary">
                                            Sisa anggaran: <span id="sisaAnggaran">Rp {{ number_format($sisaAnggaran + $nota->total, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detail Transaksi -->
                        <div class="col-12">
                            <h6>Detail Transaksi</h6>
                            <table class="table table-sm table-bordered" id="tblDetail">
                                <thead>
                                    <tr>
                                        <th>Kode Transaksi *</th>
                                        <th>Deskripsi *</th>
                                        <th width="80">Qty</th>
                                        <th width="120">Nominal</th>
                                        <th width="120">Total</th>
                                        <th width="40">
                                            <button type="button" class="btn btn-sm btn-success" id="addRow">+</button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $index => $transaction)
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm select2 kode-transaksi" 
                                                    name="transactions[{{ $index }}][idkodetransaksi]" style="width:100%;" required>
                                                <option value="">-- Pilih Kode Transaksi --</option>
                                                @foreach($kodeTransaksi as $kt)
                                                    <option value="{{ $kt->id }}" 
                                                            {{ $transaction->idkodetransaksi == $kt->id ? 'selected' : '' }}>
                                                        {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="transactions[{{ $index }}][description]" 
                                                   value="{{ $transaction->description }}" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-end jml" 
                                                   name="transactions[{{ $index }}][jml]" 
                                                   value="{{ $transaction->jml }}" min="1">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-end nominal" 
                                                   name="transactions[{{ $index }}][nominal]" 
                                                   value="{{ $transaction->nominal }}" min="0">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm text-end total" 
                                                   name="transactions[{{ $index }}][total]" 
                                                   value="{{ $transaction->total }}" readonly>
                                        </td>
                                        <td>
                                            @if($index > 0)
                                                <button type="button" class="btn btn-sm btn-danger removeRow">x</button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-danger removeRow" disabled>x</button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td colspan="2"><input type="text" class="form-control form-control-sm text-end" 
                                                               id="subtotal" value="Rp {{ number_format($nota->subtotal ?? 0, 0, ',', '.') }}" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">PPN</span>
                                                <input type="number" class="form-control form-control-sm text-end" 
                                                       name="ppn" id="ppnAmount" placeholder="Nominal PPN" 
                                                       min="0" value="{{ $nota->ppn ?? 0 }}">
                                            </div>
                                        </td>
                                        <td class="text-end"><strong>PPN:</strong></td>
                                        <td colspan="2"><input type="text" class="form-control form-control-sm text-end" 
                                                               id="ppnDisplay" value="Rp {{ number_format($nota->ppn ?? 0, 0, ',', '.') }}" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm" name="diskon_type" id="diskonType">
                                                <option value="">-- Pilih Diskon --</option>
                                                <option value="persen" {{ $nota->diskon > 0 && $nota->subtotal > 0 && (($nota->diskon / $nota->subtotal) * 100) > 0 ? 'selected' : '' }}>
                                                    Diskon %
                                                </option>
                                                <option value="nominal" {{ $nota->diskon > 0 && $nota->subtotal > 0 && (($nota->diskon / $nota->subtotal) * 100) <= 0 ? 'selected' : '' }}>
                                                    Diskon Nominal
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            @php
                                                $diskonValue = 0;
                                                if ($nota->diskon > 0 && $nota->subtotal > 0) {
                                                    $persentase = ($nota->diskon / $nota->subtotal) * 100;
                                                    if ($persentase > 0) {
                                                        $diskonValue = round($persentase, 2);
                                                    } else {
                                                        $diskonValue = $nota->diskon;
                                                    }
                                                }
                                            @endphp
                                            <input type="number" class="form-control form-control-sm" 
                                                   id="diskonValue" placeholder="Nilai" 
                                                   value="{{ $diskonValue }}"
                                                   style="{{ $nota->diskon > 0 ? '' : 'display:none;' }}">
                                        </td>
                                        <td class="text-end"><strong>Diskon:</strong></td>
                                        <td colspan="2"><input type="text" class="form-control form-control-sm text-end" 
                                                               id="diskonDisplay" value="Rp {{ number_format($nota->diskon ?? 0, 0, ',', '.') }}" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                        <td colspan="2"><input type="text" class="form-control form-control-sm text-end fw-bold" 
                                                               id="grandTotal" value="Rp {{ number_format($nota->total ?? 0, 0, ',', '.') }}" readonly></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <!-- Warnings -->
                            <div id="saldoWarning" class="alert alert-danger mt-2 p-2" style="display:none;">
                                <i class="bi bi-exclamation-triangle-fill"></i> 
                                <strong>Peringatan:</strong> Grand Total melebihi saldo rekening!
                            </div>
                            
                            <div id="anggaranWarning" class="alert alert-danger mt-2 p-2" style="display:none;">
                                <i class="bi bi-exclamation-triangle-fill"></i> 
                                <strong>Peringatan:</strong> Grand Total melebihi sisa anggaran pekerjaan!
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('construction.transactions.detail', $pekerjaan->id) }}" 
                                   class="btn btn-secondary">Batal</a>
                                <div>
                                    <button type="button" class="btn btn-warning" id="btnUpdateStatus">
                                        <i class="bi bi-arrow-repeat"></i> Update Status
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                                        <span class="submit-text">Simpan Perubahan</span>
                                        <span class="loading-text" style="display:none;">
                                            <i class="bi bi-hourglass-split"></i> Menyimpan...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Update Status -->
    <div class="modal fade" id="modalUpdateStatus" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form id="frmUpdateStatus">
                    @csrf
                    <input type="hidden" name="nota_id" value="{{ $nota->id }}">
                    
                    <div class="modal-header">
                        <h6 class="modal-title">Update Status Transaksi</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select form-select-sm" name="status" required>
                                <option value="open" {{ $nota->status == 'open' ? 'selected' : '' }}>Open</option>
                                <option value="paid" {{ $nota->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="partial" {{ $nota->status == 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="cancel" {{ $nota->status == 'cancel' ? 'selected' : '' }}>Cancel</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function() {
                const sisaAnggaran = {{ $sisaAnggaran }} + {{ $nota->total }};
                let currentSaldo = 0;
                let rowIndex = {{ count($transactions) }};
                
                // Initialize select2
                function initializeSelect2() {
                    $('.select2').select2({ 
                        width: '100%'
                    });
                }

                // Format angka ke Rupiah
                function formatRupiah(angka) {
                    if (angka === null || angka === undefined || angka === '' || isNaN(angka)) {
                        return 'Rp 0';
                    }
                    
                    let num = parseFloat(angka);
                    if (isNaN(num)) {
                        return 'Rp 0';
                    }
                    
                    return 'Rp ' + new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(num);
                }

                // Parse nilai dari format Rupiah atau angka biasa
                function parseNumber(value) {
                    if (!value && value !== 0) return 0;
                    
                    if (typeof value === 'string') {
                        value = value.replace(/[^\d.-]/g, '');
                    }
                    
                    let num = parseFloat(value);
                    return isNaN(num) ? 0 : num;
                }

                // Toggle input manual nomor nota
                $('#chkManualNo').change(function() {
                    if ($(this).is(':checked')) {
                        $('#notaNo').prop('readonly', false);
                    } else {
                        $('#notaNo').prop('readonly', true);
                    }
                });

                // Tampilkan tanggal tempo jika payment method = tempo
                $('#paymenMethod').change(function() {
                    if ($(this).val() === 'tempo') {
                        $('#tglTempoContainer').show();
                        $('#tglTempo').prop('required', true);
                    } else {
                        $('#tglTempoContainer').hide();
                        $('#tglTempo').prop('required', false);
                    }
                });

                // Toggle preview bukti nota baru
                $('#buktiNota').change(function() {
                    const file = this.files[0];
                    if (file) {
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                $('#previewImage').attr('src', e.target.result);
                                $('#buktiPreview').show();
                            }
                            reader.readAsDataURL(file);
                        } else {
                            $('#buktiPreview').hide();
                        }
                    } else {
                        $('#buktiPreview').hide();
                    }
                });

                // Toggle replace file checkbox
                $('#replaceFile').change(function() {
                    if ($(this).is(':checked')) {
                        $('#buktiNota').prop('required', true);
                    } else {
                        $('#buktiNota').prop('required', false);
                        $('#buktiNota').val('');
                        $('#buktiPreview').hide();
                    }
                });

                // Ambil saldo rekening saat rekening dipilih
                $('#idRekening').change(function() {
                    let id = $(this).val();
                    let selectedOption = $(this).find('option:selected');
                    
                    if (id) {
                        currentSaldo = selectedOption.data('saldo') || 0;
                        updateSaldoDisplay();
                        checkCukup();
                        
                        // AJAX call untuk mendapatkan saldo terbaru
                        $.ajax({
                            url: '{{ route("construction.transactions.rekening.saldo", ["id" => "__id__"]) }}'.replace('__id__', id),
                            type: 'GET',
                            success: function(res) {
                                if (res.saldo !== undefined) {
                                    currentSaldo = res.saldo;
                                    updateSaldoDisplay();
                                    checkCukup();
                                }
                            }
                        });
                    } else {
                        currentSaldo = 0;
                        updateSaldoDisplay();
                        $('#saldoWarning').hide();
                    }
                });

                // Update tampilan saldo
                function updateSaldoDisplay() {
                    $('#availableBalance').text(formatRupiah(currentSaldo));
                }

                // Cek apakah saldo dan anggaran cukup
                function checkCukup() {
                    let grandTotal = parseNumber($('#grandTotal').val());
                    
                    // Cek saldo
                    if (currentSaldo > 0 && grandTotal > 0) {
                        if (grandTotal > currentSaldo) {
                            $('#saldoWarning').show();
                        } else {
                            $('#saldoWarning').hide();
                        }
                    } else {
                        $('#saldoWarning').hide();
                    }
                    
                    // Cek anggaran
                    if (grandTotal > sisaAnggaran) {
                        $('#anggaranWarning').show();
                    } else {
                        $('#anggaranWarning').hide();
                    }
                }

                // Hitung total per row
                $(document).on('input', '.jml, .nominal', function() {
                    let row = $(this).closest('tr');
                    let jml = parseNumber(row.find('.jml').val());
                    let nominal = parseNumber(row.find('.nominal').val());
                    let total = jml * nominal;
                    row.find('.total').val(total);
                    calculateSubtotal();
                });

                // Hitung input PPN
                $(document).on('input', '#ppnAmount', function() {
                    calculateGrandTotal();
                });

                // Handle Diskon selection
                $('#diskonType').change(function() {
                    let val = $(this).val();
                    if (val) {
                        $('#diskonValue').show();
                        if (!parseNumber($('#diskonValue').val())) {
                            $('#diskonValue').val('');
                        }
                    } else {
                        $('#diskonValue').hide().val('');
                        $('#diskonDisplay').val('Rp 0');
                    }
                    calculateGrandTotal();
                });

                // Handle Diskon value input
                $(document).on('input', '#diskonValue', function() {
                    calculateGrandTotal();
                });

                // Fungsi untuk menghitung subtotal
                function calculateSubtotal() {
                    let subtotal = 0;
                    $('.total').each(function() {
                        let val = parseNumber($(this).val());
                        subtotal += val;
                    });
                    
                    $('#subtotal').val(formatRupiah(subtotal));
                    calculateGrandTotal();
                }

                // Fungsi untuk menghitung grand total
                function calculateGrandTotal() {
                    let subtotal = parseNumber($('#subtotal').val());
                    let ppnAmount = parseNumber($('#ppnAmount').val());
                    $('#ppnDisplay').val(formatRupiah(ppnAmount));
                    
                    let diskonAmount = 0;
                    let diskonType = $('#diskonType').val();
                    let diskonValue = parseNumber($('#diskonValue').val());
                    
                    if (diskonType === 'persen' && diskonValue > 0) {
                        diskonAmount = subtotal * (diskonValue / 100);
                    } else if (diskonType === 'nominal' && diskonValue > 0) {
                        diskonAmount = diskonValue;
                    }
                    
                    $('#diskonDisplay').val(formatRupiah(diskonAmount));
                    
                    let grandTotal = (subtotal - diskonAmount) + ppnAmount;
                    if (grandTotal < 0) grandTotal = 0;
                    
                    $('#grandTotal').val(formatRupiah(grandTotal));
                    
                    // Cek saldo dan anggaran
                    checkCukup();
                }

                // Fungsi utama untuk menghitung semua
                function calculateTotals() {
                    calculateSubtotal();
                    calculateGrandTotal();
                }

                // Tambah row detail
                $('#addRow').click(function() {
                    let html = `<tr>
                        <td>
                            <select class="form-select form-select-sm select2 kode-transaksi" name="transactions[${rowIndex}][idkodetransaksi]" style="width:100%;" required>
                                <option value="">-- Pilih Kode Transaksi --</option>
                                @foreach($kodeTransaksi as $kt)
                                    <option value="{{ $kt->id }}">
                                        {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="transactions[${rowIndex}][description]" required></td>
                        <td><input type="number" class="form-control form-control-sm text-end jml" name="transactions[${rowIndex}][jml]" value="1" min="1"></td>
                        <td><input type="number" class="form-control form-control-sm text-end nominal" name="transactions[${rowIndex}][nominal]" value="0" min="0"></td>
                        <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[${rowIndex}][total]" value="0" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                    </tr>`;
                    $('#tblDetail tbody').append(html);
                    initializeSelect2();
                    rowIndex++;
                });

                // Hapus row detail
                $(document).on('click', '.removeRow', function() {
                    if ($('#tblDetail tbody tr').length > 1) {
                        $(this).closest('tr').remove();
                        calculateSubtotal();
                    } else {
                        Swal.fire('Peringatan', 'Minimal harus ada 1 item transaksi', 'warning');
                    }
                });

                // Submit form edit
                $('#frmTransaction').submit(function(e) {
                    e.preventDefault();
                    
                    // Validasi grand total
                    let grandTotal = parseNumber($('#grandTotal').val());
                    if (grandTotal <= 0) {
                        Swal.fire('Peringatan', 'Total transaksi harus lebih dari 0', 'warning');
                        return;
                    }

                    // Validasi anggaran
                    if (grandTotal > sisaAnggaran) {
                        Swal.fire({
                            title: 'Anggaran Tidak Cukup',
                            text: 'Total transaksi melebihi sisa anggaran pekerjaan. Lanjutkan?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Lanjutkan',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitForm();
                            }
                        });
                    } else {
                        submitForm();
                    }
                });

                function submitForm() {
                    let formData = new FormData($('#frmTransaction')[0]);
                    
                    // Tambahkan subtotal, ppn, diskon
                    let ppnAmount = parseNumber($('#ppnAmount').val());
                    let diskonAmount = parseNumber($('#diskonDisplay').val());
                    let subtotal = parseNumber($('#subtotal').val());
                    
                    formData.append('subtotal', subtotal);
                    
                    if (ppnAmount > 0) {
                        formData.append('ppn', ppnAmount);
                    } else {
                        formData.append('ppn', 0);
                    }
                    
                    if (diskonAmount > 0) {
                        formData.append('diskon', diskonAmount);
                    } else {
                        formData.append('diskon', 0);
                    }

                    // Tampilkan loading
                    $('#btnSubmit').prop('disabled', true);
                    $('.submit-text').hide();
                    $('.loading-text').show();

                    $.ajax({
                        url: '{{ route("construction.transactions.update", [$pekerjaan->id, $nota->id]) }}',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            $('#btnSubmit').prop('disabled', false);
                            $('.submit-text').show();
                            $('.loading-text').hide();
                            
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: res.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    window.location.href = '{{ route("construction.transactions.detail", $pekerjaan->id) }}';
                                });
                            } else {
                                Swal.fire('Error!', res.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            $('#btnSubmit').prop('disabled', false);
                            $('.submit-text').show();
                            $('.loading-text').hide();
                            
                            let errors = xhr.responseJSON?.errors;
                            let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data';
                            
                            if (errors) {
                                errorMsg = '';
                                $.each(errors, function(key, value) {
                                    errorMsg += value[0] + '\n';
                                });
                            }
                            
                            Swal.fire('Error!', errorMsg, 'error');
                        }
                    });
                }

                // Modal update status
                $('#btnUpdateStatus').click(function() {
                    $('#modalUpdateStatus').modal('show');
                });

                // Submit update status
                $('#frmUpdateStatus').submit(function(e) {
                    e.preventDefault();
                    
                    Swal.fire({
                        title: 'Update Status?',
                        text: 'Apakah Anda yakin ingin mengupdate status transaksi?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Update',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '{{ route("construction.transactions.status", [$pekerjaan->id, $nota->id]) }}',
                                type: 'PUT',
                                data: $(this).serialize(),
                                success: function(res) {
                                    if (res.success) {
                                        $('#modalUpdateStatus').modal('hide');
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Berhasil!',
                                            text: res.message,
                                            showConfirmButton: false,
                                            timer: 1500
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire('Error!', res.message, 'error');
                                    }
                                },
                                error: function(xhr) {
                                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                                }
                            });
                        }
                    });
                });

                // Initialize
                initializeSelect2();
                updateSaldoDisplay();
                
                // Get initial saldo
                let selectedRekening = $('#idRekening').val();
                if (selectedRekening) {
                    $.ajax({
                        url: '{{ route("construction.transactions.rekening.saldo", ["id" => "__id__"]) }}'.replace('__id__', selectedRekening),
                        type: 'GET',
                        success: function(res) {
                            if (res.saldo !== undefined) {
                                currentSaldo = res.saldo;
                                updateSaldoDisplay();
                                checkCukup();
                            }
                        }
                    });
                }

                // Hitung ulang setelah load
                setTimeout(() => {
                    calculateTotals();
                }, 500);
            });
        </script>
        
        <style>
            .progress-bar-animated {
                animation: progress-bar-stripes 1s linear infinite;
            }
            
            .select2-container--default .select2-selection--single {
                height: 31px;
                padding: 3px 12px;
            }
            
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 29px;
            }
        </style>
    </x-slot>
</x-app-layout>