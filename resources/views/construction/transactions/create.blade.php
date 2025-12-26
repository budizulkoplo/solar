<x-app-layout>
    <x-slot name="pagetitle">Tambah Transaksi - {{ $pekerjaan->nama_pekerjaan }}</x-slot>

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
                        <li class="breadcrumb-item active">Tambah Transaksi</li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Tambah Transaksi Konstruksi</h4>
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
                        <h5 class="text-success">Rp {{ number_format($sisaAnggaran ?? 0, 0, ',', '.') }}</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="progress" style="height: 20px;">
                            @php
                                $percentage = $pekerjaan->anggaran > 0 ? (($pekerjaan->anggaran - $sisaAnggaran) / $pekerjaan->anggaran) * 100 : 0;
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

        <!-- Form Transaksi -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="frmTransaction" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="idproject" value="{{ $pekerjaan->idproject }}">
                    
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
                                <input type="text" class="form-control form-control-sm" name="nota_no" id="notaNo" required>
                                <div class="input-group-text">
                                    <input type="checkbox" id="chkManualNo"> Manual
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <label class="form-label">Nama Transaksi *</label>
                            <input type="text" class="form-control form-control-sm" name="namatransaksi" id="namatransaksi" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal *</label>
                            <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalNota" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Vendor</label>
                            <select class="form-select form-select-sm select2" name="vendor_id" id="vendorId" style="width:100%;">
                                <option value="">-- Pilih Vendor --</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}">{{ $v->namavendor }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Payment Method *</label>
                            <select class="form-select form-select-sm" name="paymen_method" id="paymenMethod" required>
                                <option value="cash">Cash</option>
                                <option value="tempo">Tempo</option>
                            </select>
                        </div>

                        <!-- Rekening -->
                        <div class="col-md-6">
                            <label class="form-label">Rekening *</label>
                            <select class="form-select form-select-sm select2" name="idrek" id="idRekening" style="width:100%;" required>
                                <option value="">-- Pilih Rekening --</option>
                                @foreach($rekenings as $rek)
                                    <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                        {{ $rek->norek }} - {{ $rek->namarek }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tanggal Tempo -->
                        <div class="col-md-6" id="tglTempoContainer" style="display:none;">
                            <label class="form-label">Tanggal Tempo *</label>
                            <input type="date" class="form-control form-control-sm" name="tgl_tempo" id="tglTempo">
                        </div>

                        <!-- Bukti Nota -->
                        <div class="col-12">
                            <label class="form-label">Bukti Nota</label>
                            <input type="file" class="form-control form-control-sm" name="bukti_nota" id="buktiNota" 
                                   accept=".jpg,.jpeg,.png,.pdf">
                            <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
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
                                            Sisa anggaran: <span id="sisaAnggaran">Rp {{ number_format($sisaAnggaran, 0, ',', '.') }}</span>
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
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm select2 kode-transaksi" name="transactions[0][idkodetransaksi]" style="width:100%;" required>
                                                <option value="">-- Pilih Kode Transaksi --</option>
                                                @foreach($kodeTransaksi as $kt)
                                                    <option value="{{ $kt->id }}">
                                                        {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm" name="transactions[0][description]" required></td>
                                        <td><input type="number" class="form-control form-control-sm text-end jml" name="transactions[0][jml]" value="1" min="1"></td>
                                        <td><input type="number" class="form-control form-control-sm text-end nominal" name="transactions[0][nominal]" value="0" min="0"></td>
                                        <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[0][total]" value="0" readonly></td>
                                        <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td colspan="2"><input type="text" class="form-control form-control-sm text-end" id="subtotal" value="Rp 0" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">PPN</span>
                                                <input type="number" class="form-control form-control-sm text-end" 
                                                       name="ppn" id="ppnAmount" placeholder="Nominal PPN" min="0" value="0">
                                            </div>
                                        </td>
                                        <td class="text-end"><strong>PPN:</strong></td>
                                        <td colspan="2"><input type="text" class="form-control form-control-sm text-end" 
                                                               id="ppnDisplay" value="Rp 0" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm" name="diskon_type" id="diskonType">
                                                <option value="">-- Pilih Diskon --</option>
                                                <option value="persen">Diskon %</option>
                                                <option value="nominal">Diskon Nominal</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" 
                                                   id="diskonValue" placeholder="Nilai" style="display:none;">
                                        </td>
                                        <td class="text-end"><strong>Diskon:</strong></td>
                                        <td colspan="2"><input type="text" class="form-control form-control-sm text-end" id="diskonDisplay" value="Rp 0" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                        <td colspan="2"><input type="text" class="form-control form-control-sm text-end fw-bold" id="grandTotal" value="Rp 0" readonly></td>
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
                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <span class="submit-text">Simpan Transaksi</span>
                                    <span class="loading-text" style="display:none;">
                                        <i class="bi bi-hourglass-split"></i> Menyimpan...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function() {
                const sisaAnggaran = {{ $sisaAnggaran }};
                let currentSaldo = 0;
                
                // Set tanggal default ke hari ini
                function setDefaultDate() {
                    let today = new Date().toISOString().split('T')[0];
                    $('#tanggalNota').val(today);
                }

                // Generate nomor nota otomatis
                function generateNotaNo() {
                    let tgl = $('#tanggalNota').val().replaceAll('-','');
                    let urut = Math.floor(Math.random() * 90000) + 10000;
                    return 'KON-' + {{ $pekerjaan->id }} + '-' + tgl + '-' + urut;
                }

                function setAutoNotaNo() {
                    if (!$('#chkManualNo').is(':checked')) {
                        $('#notaNo').val(generateNotaNo());
                    }
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

                // Initialize select2
                function initializeSelect2() {
                    $('.select2').select2({ 
                        width: '100%'
                    });
                }

                // Toggle input manual nomor nota
                $('#chkManualNo').change(function() {
                    if ($(this).is(':checked')) {
                        $('#notaNo').prop('readonly', false).val('');
                    } else {
                        $('#notaNo').prop('readonly', true);
                        setAutoNotaNo();
                    }
                });

                // Update nomor nota ketika tanggal berubah
                $('#tanggalNota').change(function() {
                    if (!$('#chkManualNo').is(':checked')) {
                        setAutoNotaNo();
                    }
                    $('#tglTempo').attr('min', $(this).val());
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

                // Preview image sebelum upload
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

                // Ambil saldo rekening saat rekening dipilih
                $('#idRekening').change(function() {
                    let id = $(this).val();
                    let selectedOption = $(this).find('option:selected');
                    
                    if (id) {
                        currentSaldo = selectedOption.data('saldo') || 0;
                        updateSaldoDisplay();
                        checkCukup();
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
                        $('#diskonValue').val('');
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
                    
                    $('#subtotal').val(subtotal);
                    calculateGrandTotal();
                }

                // Fungsi untuk menghitung grand total
                function calculateGrandTotal() {
                    let subtotal = parseNumber($('#subtotal').val());
                    let ppnAmount = parseNumber($('#ppnAmount').val());
                    $('#ppnDisplay').val(ppnAmount);
                    
                    let diskonAmount = 0;
                    let diskonType = $('#diskonType').val();
                    let diskonValue = parseNumber($('#diskonValue').val());
                    
                    if (diskonType === 'persen' && diskonValue > 0) {
                        diskonAmount = subtotal * (diskonValue / 100);
                    } else if (diskonType === 'nominal' && diskonValue > 0) {
                        diskonAmount = diskonValue;
                    }
                    
                    $('#diskonDisplay').val(diskonAmount);
                    
                    let grandTotal = (subtotal - diskonAmount) + ppnAmount;
                    if (grandTotal < 0) grandTotal = 0;
                    
                    $('#grandTotal').val(grandTotal);
                    
                    // Cek saldo dan anggaran
                    checkCukup();
                }

                // Fungsi utama untuk menghitung semua
                function calculateTotals() {
                    calculateSubtotal();
                    calculateGrandTotal();
                }

                // Tambah row detail
                let rowIndex = 1;
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

                // Submit form
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
                    
                    // Ambil nilai PPN dan Diskon
                    let ppnAmount = parseNumber($('#ppnAmount').val());
                    let diskonAmount = parseNumber($('#diskonDisplay').val());
                    let subtotal = parseNumber($('#subtotal').val());
                    
                    formData.append('subtotal', subtotal);
                    
                    if (ppnAmount > 0) {
                        formData.append('ppn', ppnAmount);
                    }
                    
                    if (diskonAmount > 0) {
                        formData.append('diskon', diskonAmount);
                    }

                    // Tampilkan loading
                    $('#btnSubmit').prop('disabled', true);
                    $('.submit-text').hide();
                    $('.loading-text').show();

                    $.ajax({
                        url: '{{ route("construction.transactions.store", $pekerjaan->id) }}',
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
                                    window.location.href = res.redirect_url;
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

                // Initialize
                initializeSelect2();
                setDefaultDate();
                setAutoNotaNo();
                calculateTotals();
            });
        </script>
        
        <style>
            .progress-bar-animated {
                animation: progress-bar-stripes 1s linear infinite;
            }
        </style>
    </x-slot>
</x-app-layout>