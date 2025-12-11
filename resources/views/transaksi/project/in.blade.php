<x-app-layout>
    <x-slot name="pagetitle">Transaksi Masuk - Project</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Nota Penerimaan - Project</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" id="btnTambahNota">
                            <i class="bi bi-file-earmark-plus"></i> Tambah Nota
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbNotas" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nota No</th>
                                <th>Nama Trans.</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Payment</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">User</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nota -->
    <div class="modal fade" id="modalNota" tabindex="-1">
        <div class="modal-dialog modal-xxl">
            <div class="modal-content">
                <div class="row g-0">
                    <!-- Kolom Kiri: Form (80%) -->
                    <div class="col-md-9">
                        <form id="frmNota" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id" id="idNota">
                            <input type="hidden" name="idproject" value="{{ session('active_project_id') }}">
                            <input type="hidden" name="old_rekening" id="oldRekening">
                            <input type="hidden" name="old_grand_total" id="oldGrandTotal">

                            <div class="modal-header">
                                <h6 class="modal-title" id="modalNotaTitle">Form Nota Masuk</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-2">
                                    {{-- No Invoice --}}
                                    <div class="col-md-4">
                                        <label class="form-label">No Invoice *</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-sm" name="nota_no" id="notaNo" required>
                                            <div class="input-group-text">
                                                <input type="checkbox" id="chkManualNo"> Manual
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nama Transaksi *</label>
                                        <input type="text" class="form-control form-control-sm" name="namatransaksi" id="namatransaksi" required>
                                    </div>
                                    {{-- Project --}}
                                    <div class="col-md-4">
                                        <label class="form-label">Project</label>
                                        <input type="text" class="form-control form-control-sm" value="{{ session('active_project_name') }}" disabled>
                                    </div>

                                    {{-- Payment Method --}}
                                    <div class="col-md-4">
                                        <label class="form-label">Payment Method *</label>
                                        <select class="form-select form-select-sm" name="paymen_method" id="paymenMethod" required>
                                            <option value="cash">Cash</option>
                                            <option value="tempo">Tempo</option>
                                        </select>
                                    </div>

                                    {{-- Tanggal --}}
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal *</label>
                                        <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalNota" required>
                                    </div>

                                    {{-- Rekening --}}
                                    <div class="col-md-4">
                                        <label class="form-label">Rekening *</label>
                                        <select class="form-select form-select-sm select2" name="idrek" id="idRekening" style="width:100%;" required>
                                            <option value="">-- Pilih Rekening --</option>
                                            @foreach(\App\Models\Rekening::forProject(session('active_project_id'))->get() as $rek)
                                                <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                                    {{ $rek->norek }} - {{ $rek->namarek }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Tanggal Tempo --}}
                                    <div class="col-md-6" id="tglTempoContainer" style="display:none;">
                                        <label class="form-label">Tanggal Tempo *</label>
                                        <input type="date" class="form-control form-control-sm" name="tgl_tempo" id="tglTempo">
                                    </div>

                                    {{-- Bukti Nota (Tidak Wajib) --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Bukti Nota <span class="text-muted">(Optional)</span></label>
                                        <input type="file" class="form-control form-control-sm" name="bukti_nota" id="buktiNota" 
                                               accept=".jpg,.jpeg,.png,.pdf">
                                        <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                                        <div id="buktiPreview" class="mt-2" style="display:none;">
                                            <img id="previewImage" src="#" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Detail Transaksi</h6>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="text-primary" id="saldoInfo">
                                            <i class="bi bi-wallet2"></i> 
                                            Saldo tersedia: <span id="availableBalance">Rp 0</span>
                                        </div>
                                    </div>
                                </div>
                                
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
                                                    @foreach(\App\Models\KodeTransaksi::all() as $kt)
                                                        <option value="{{ $kt->id }}" data-kode="{{ $kt->kodetransaksi }}">
                                                            {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" class="form-control form-control-sm" name="transactions[0][description]" required></td>
                                            <td><input type="number" class="form-control form-control-sm text-end jml" name="transactions[0][jml]" value="1" min="1" ></td>
                                            <td><input type="number" class="form-control form-control-sm text-end nominal" name="transactions[0][nominal]" value="0" min="0"></td>
                                            <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[0][total]" value="0" readonly></td>
                                            <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                            <td colspan="2"><input type="hidden" class="form-control form-control-sm text-end" id="subtotal" value="Rp 0" readonly>
                                            <input type="text" class="form-control form-control-sm text-end fw-bold" id="grandTotal" value="Rp 0" readonly></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                
                                <!-- Info untuk transaksi masuk -->
                                <div id="infoTransaksiMasuk" class="alert alert-info mt-2 p-2">
                                    <i class="bi bi-info-circle-fill"></i> 
                                    <strong>Informasi:</strong> Transaksi masuk akan menambah saldo rekening.
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <span class="submit-text">Simpan</span>
                                    <span class="loading-text" style="display:none;">
                                        <i class="bi bi-hourglass-split"></i> Menyimpan...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Kolom Kanan: Log Update (20%) -->
                    <div class="col-md-3 border-start">
                        <div class="modal-header border-bottom">
                            <h6 class="modal-title m-0"><i class="bi bi-clock-history"></i> Riwayat Update</h6>
                        </div>
                        <div class="modal-body p-2" style="height: calc(100vh - 150px); overflow-y: auto;">
                            <div id="updateLogContainer">
                                <p class="text-muted small">Tidak ada riwayat perubahan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal View Nota -->
    <div class="modal fade" id="modalViewNota" tabindex="-1">
        <div class="modal-dialog modal-xxl">
            <div class="modal-content">
                <div class="row g-0">
                    <!-- Kolom Kiri: Data Nota (90%) -->
                    <div class="col-md-9">
                        <div class="modal-header">
                            <h6 class="modal-title">Detail Nota</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">No Nota</th>
                                            <td id="viewNotaNo">-</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal</th>
                                            <td id="viewTanggal">-</td>
                                        </tr>
                                        <tr>
                                            <th>Nama Transaksi</th>
                                            <td id="viewNamaTransaksi">-</td>
                                        </tr>
                                        <tr>
                                            <th>Project</th>
                                            <td id="viewProject">-</td>
                                        </tr>
                                        <tr>
                                            <th>User</th>
                                            <td id="viewUser">-</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Payment Method</th>
                                            <td id="viewPaymentMethod">-</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Tempo</th>
                                            <td id="viewTglTempo">-</td>
                                        </tr>
                                        <tr>
                                            <th>Rekening</th>
                                            <td id="viewRekening">-</td>
                                        </tr>
                                        <tr>
                                            <th>Total</th>
                                            <td id="viewTotal">-</td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td id="viewStatus">-</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <h6>Detail Transaksi</h6>
                            <table class="table table-sm table-bordered" id="tblViewDetail">
                                <thead>
                                    <tr>
                                        <th>Kode Transaksi</th>
                                        <th>Deskripsi</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Nominal</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data akan diisi oleh JavaScript -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td colspan="2" class="text-end" id="viewSubtotal">-</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>PPN:</strong></td>
                                        <td colspan="2" class="text-end" id="viewPpn">-</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Diskon:</strong></td>
                                        <td colspan="2" class="text-end" id="viewDiskon">-</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                        <td colspan="2" class="text-end fw-bold" id="viewGrandTotal">-</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div id="viewBuktiNota" class="mt-3" style="display:none;">
                                <h6>Bukti Nota</h6>
                                <div id="buktiContainer">
                                    <!-- Preview bukti akan ditampilkan di sini -->
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                    
                    <!-- Kolom Kanan: Log Update (20%) -->
                    <div class="col-md-3 border-start">
                        <div class="modal-header border-bottom bg-light">
                            <h6 class="modal-title m-0"><i class="bi bi-clock-history"></i> Riwayat Perubahan</h6>
                        </div>
                        <div class="modal-body p-2" style="height: calc(100vh - 150px); overflow-y: auto;">
                            <div id="viewLogContainer">
                                <p class="text-muted small">Tidak ada riwayat perubahan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <style>
            .modal-xxl {
                max-width: 80% !important;
            }
            
            /* Hapus warna biru untuk input readonly */
            input[readonly],
            textarea[readonly],
            select[readonly] {
                background-color: #f8f9fa !important;
                cursor: not-allowed;
            }
        </style>

        <script>
        $(document).ready(function() {
            // DataTable
            let tbNotas = $('#tbNotas').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.project.getdata', 'in') }}",
                columns: [
                    { 
                        data: 'DT_RowIndex', 
                        name: 'DT_RowIndex', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    },
                    { data: 'nota_no', name: 'nota_no' },
                    { data: 'namatransaksi', name: 'namatransaksi' },
                    { 
                        data: 'tanggal', 
                        name: 'tanggal',
                        className: 'text-center',
                        render: function(data) {
                            if (data) {
                                return new Date(data).toLocaleDateString('id-ID');
                            }
                            return '-';
                        }
                    },
                    { 
                        data: 'total', 
                        name: 'total',
                        className: 'text-end',
                        render: function(data, type, row) {
                            if (type === 'display' || type === 'filter') {
                                let num = parseFloat(data);
                                if (!isNaN(num)) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(num);
                                }
                                return data;
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'paymen_method', 
                        name: 'paymen_method',
                        className: 'text-center',
                        render: function(data) {
                            if (data === 'cash') return 'Cash';
                            if (data === 'tempo') return 'Tempo';
                            return data;
                        }
                    },
                    { 
                        data: 'status', 
                        name: 'status',
                        className: 'text-center'
                    },
                    { 
                        data: 'namauser', 
                        name: 'namauser',
                        className: 'text-center'
                    },
                    { 
                        data: 'action', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                columnDefs: [
                    { width: "5%", targets: 0 },
                    { width: "12%", targets: 1 },
                    { width: "18%", targets: 2 },
                    { width: "8%", targets: 3 },
                    { width: "12%", targets: 4 },
                    { width: "8%", targets: 5 },
                    { width: "8%", targets: 6 },
                    { width: "10%", targets: 7 },
                    { width: "10%", targets: 8 }
                ]
            });

            // Generate nomor nota otomatis untuk IN
            function generateNotaNo() {
                let projectId = "{{ session('active_project_id') }}";
                let tgl = $('#tanggalNota').val().replaceAll('-','');
                let urut = Math.floor(Math.random() * 90000) + 10000;
                return 'IN-' + projectId + '-' + tgl + '-' + urut;
            }

            // Set tanggal default ke hari ini
            function setDefaultDate() {
                let today = new Date().toISOString().split('T')[0];
                $('#tanggalNota').val(today);
            }

            // Set nomor nota otomatis
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

            // Reset form ke kondisi default
            function resetForm() {
                $('#frmNota')[0].reset();
                $('#idNota').val('');
                $('#oldRekening').val('');
                $('#oldGrandTotal').val('');
                $('#buktiPreview').hide();
                $('#tglTempoContainer').hide();
                $('#tglTempo').prop('required', false);
                $('.select2').val(null).trigger('change');
                $('#modalNotaTitle').text('Form Nota Masuk');
                
                // Bukti nota TIDAK wajib untuk transaksi masuk
                $('#buktiNota').prop('required', false);
                
                // Reset detail transaksi ke 1 row
                $('#tblDetail tbody').html(`
                    <tr>
                        <td>
                            <select class="form-select form-select-sm select2 kode-transaksi" name="transactions[0][idkodetransaksi]" style="width:100%;" required>
                                <option value="">-- Pilih Kode Transaksi --</option>
                                @foreach(\App\Models\KodeTransaksi::all() as $kt)
                                    <option value="{{ $kt->id }}" data-kode="{{ $kt->kodetransaksi }}">
                                        {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="transactions[0][description]" required></td>
                        <td><input type="number" class="form-control form-control-sm text-end jml" name="transactions[0][jml]" value="1" min="1" ></td>
                        <td><input type="number" class="form-control form-control-sm text-end nominal" name="transactions[0][nominal]" value="0" min="0"></td>
                        <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[0][total]" value="0" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                    </tr>
                `);
                
                // Reset perhitungan
                $('#subtotal').val('Rp 0');
                $('#ppnAmount').val('0');
                $('#ppnDisplay').val('Rp 0');
                $('#diskonDisplay').val('Rp 0');
                $('#grandTotal').val('Rp 0');
                
                // Reset Diskon
                $('#diskonType').val('');
                $('#diskonValue').hide().val('');
                
                // Reset saldo
                currentSaldo = 0;
                oldRekening = null;
                oldGrandTotal = 0;
                
                // Update tampilan saldo
                updateSaldoDisplay();
                
                // Set default values
                setDefaultDate();
                setAutoNotaNo();
                
                // Re-initialize select2
                initializeSelect2();
                
                // Enable manual input checkbox
                $('#chkManualNo').prop('checked', false);
                $('#notaNo').prop('readonly', true);
                
                // Reset log
                $('#updateLogContainer').html('<p class="text-muted small">Tidak ada riwayat perubahan</p>');
                
                // Hitung ulang setelah reset
                calculateTotals();
            }

            // Initialize select2
            function initializeSelect2() {
                $('.select2').select2({ 
                    dropdownParent: $('#modalNota'),
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
            let currentSaldo = 0;
            let oldRekening = null;
            let oldGrandTotal = 0;
            
            $('#idRekening').change(function() {
                let id = $(this).val();
                let selectedOption = $(this).find('option:selected');
                
                if (id) {
                    if (!oldRekening && $('#idNota').val()) {
                        oldRekening = $('#oldRekening').val() || id;
                        $('#oldRekening').val(oldRekening);
                    }
                    
                    let grandTotal = parseNumber($('#grandTotal').val());
                    if (grandTotal > 0) {
                        oldGrandTotal = grandTotal;
                        $('#oldGrandTotal').val(oldGrandTotal);
                    }
                    
                    currentSaldo = selectedOption.data('saldo') || 0;
                    updateSaldoDisplay();
                    
                    let url = "{{ route('transaksi.project.rekening.saldo', ['id' => ':id']) }}";
                    url = url.replace(':id', id);
                    
                    $.get(url, function(res) {
                        currentSaldo = res.saldo || 0;
                        updateSaldoDisplay();
                    }).fail(function(xhr) {
                        console.error('Error mengambil saldo:', xhr);
                    });
                } else {
                    currentSaldo = 0;
                    updateSaldoDisplay();
                }
            });

            // Update tampilan saldo
            function updateSaldoDisplay() {
                $('#availableBalance').text(formatRupiah(currentSaldo));
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

            // Fungsi untuk menghitung subtotal saja (tanpa PPN dan diskon)
            function calculateSubtotal() {
                let subtotal = 0;
                $('.total').each(function() {
                    let val = parseNumber($(this).val());
                    subtotal += val;
                });
                
                $('#subtotal').val(parseNumber(subtotal));
                
                calculateGrandTotal();
            }

            // Fungsi untuk menghitung grand total (termasuk PPN dan diskon)
            function calculateGrandTotal() {
                // Ambil subtotal yang sudah dihitung
                let subtotal = parseNumber($('#subtotal').val());
                
                // Hitung PPN
                let ppnAmount = parseNumber($('#ppnAmount').val());
                $('#ppnDisplay').val(parseNumber(ppnAmount));
                
                // Hitung Diskon
                let diskonAmount = 0;
                let diskonType = $('#diskonType').val();
                let diskonValue = parseNumber($('#diskonValue').val());
                
                if (diskonType === 'persen' && diskonValue > 0) {
                    diskonAmount = subtotal * (diskonValue / 100);
                } else if (diskonType === 'nominal' && diskonValue > 0) {
                    diskonAmount = diskonValue;
                }
                
                $('#diskonDisplay').val(parseNumber(diskonAmount));
                
                // Hitung Grand Total = subtotal + ppn - diskon
                let grandTotal = (subtotal-diskonAmount)+ppnAmount;
                
                if (grandTotal < 0) grandTotal = 0;
                
                $('#grandTotal').val(parseNumber(grandTotal));
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
                            @foreach(\App\Models\KodeTransaksi::all() as $kt)
                                <option value="{{ $kt->id }}" data-kode="{{ $kt->kodetransaksi }}">
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

            // Load update log untuk nota tertentu
            function loadUpdateLog(notaId) {
                if (!notaId) {
                    $('#updateLogContainer').html('<p class="text-muted small">Tidak ada riwayat perubahan</p>');
                    return;
                }
                
                $.get(`/transaksi/project/${notaId}/logs`, function(res) {
                    if (res.success && res.data.length > 0) {
                        let logHtml = '';
                        res.data.forEach(function(log, index) {
                            let waktu = new Date(log.created_at).toLocaleString('id-ID');
                            let bgClass = index % 2 === 0 ? 'bg-light' : 'bg-white';
                            logHtml += `
                                <div class="mb-2 pb-2 border-bottom ${bgClass} p-2 rounded">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-clock"></i> ${waktu}
                                    </small>
                                    <p class="mb-0 small">${log.update_log}</p>
                                </div>
                            `;
                        });
                        $('#updateLogContainer').html(logHtml);
                    } else {
                        $('#updateLogContainer').html('<p class="text-muted small">Tidak ada riwayat perubahan</p>');
                    }
                }).fail(function() {
                    $('#updateLogContainer').html('<p class="text-muted small">Error loading log</p>');
                });
            }

            // Load update log untuk view modal
            function loadViewUpdateLog(notaId) {
                if (!notaId) {
                    $('#viewLogContainer').html('<p class="text-muted small">Tidak ada riwayat perubahan</p>');
                    return;
                }
                
                $.get(`/transaksi/project/${notaId}/logs`, function(res) {
                    if (res.success && res.data.length > 0) {
                        let logHtml = '';
                        res.data.forEach(function(log, index) {
                            let waktu = new Date(log.created_at).toLocaleString('id-ID');
                            let bgClass = index % 2 === 0 ? 'bg-light' : 'bg-white';
                            logHtml += `
                                <div class="mb-2 pb-2 border-bottom ${bgClass} p-2 rounded">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-clock"></i> ${waktu}
                                    </small>
                                    <p class="mb-0 small">${log.update_log}</p>
                                </div>
                            `;
                        });
                        $('#viewLogContainer').html(logHtml);
                    } else {
                        $('#viewLogContainer').html('<p class="text-muted small">Tidak ada riwayat perubahan</p>');
                    }
                });
            }

            // Tombol tambah nota - reset form dan buka modal
            $('#btnTambahNota').click(function() {
                resetForm();
                $('#modalNota').modal('show');
            });

            // View nota
            $(document).on('click', '.view-btn', function() {
                let notaId = $(this).data('id');
                
                $.get("/transaksi/project/" + notaId, function(res) {
                    if (res.success) {
                        let nota = res.data;
                        
                        // Isi data header
                        $('#viewNotaNo').text(nota.nota_no);
                        $('#viewTanggal').text(new Date(nota.tanggal).toLocaleDateString('id-ID'));
                        $('#viewNamaTransaksi').text(nota.namatransaksi);
                        $('#viewProject').text(nota.project ? nota.project.namaproject : '-');
                        $('#viewUser').text(nota.namauser || '-');
                        $('#viewPaymentMethod').text(nota.paymen_method === 'cash' ? 'Cash' : 'Tempo');
                        $('#viewTglTempo').text(nota.tgl_tempo ? new Date(nota.tgl_tempo).toLocaleDateString('id-ID') : '-');
                        $('#viewRekening').text(nota.rekening ? nota.rekening.norek + ' - ' + nota.rekening.namarek : '-');
                        $('#viewTotal').text(formatRupiah(nota.total));
                        $('#viewStatus').html(getStatusBadge(nota.status));
                        
                        let subtotal = nota.subtotal || 0;
                        let ppn = nota.ppn || 0;
                        let diskon = nota.diskon || 0;
                        
                        $('#viewSubtotal').text(formatRupiah(subtotal));
                        $('#viewPpn').text(formatRupiah(ppn));
                        $('#viewDiskon').text(formatRupiah(diskon));
                        $('#viewGrandTotal').text(formatRupiah(nota.total));
                        
                        // Isi detail transaksi
                        let detailHtml = '';
                        if (nota.transactions && nota.transactions.length > 0) {
                            nota.transactions.forEach(function(transaction) {
                                detailHtml += `
                                    <tr>
                                        <td>${transaction.kode_transaksi ? transaction.kode_transaksi.kodetransaksi : '-'}</td>
                                        <td>${transaction.description}</td>
                                        <td class="text-center">${transaction.jml}</td>
                                        <td class="text-end">${formatRupiah(transaction.nominal)}</td>
                                        <td class="text-end">${formatRupiah(transaction.total)}</td>
                                    </tr>
                                `;
                            });
                        }
                        $('#tblViewDetail tbody').html(detailHtml);
                        
                        // Load update log
                        loadViewUpdateLog(notaId);
                        
                        // Tampilkan bukti nota jika ada
                        $('#buktiContainer').empty();
                        $('#viewBuktiNota').hide();
                        if (nota.bukti_nota) {
                            let fileUrl = '/storage/' + nota.bukti_nota;
                            let fileExt = fileUrl.split('.').pop().toLowerCase();
                            
                            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                                $('#buktiContainer').html(`
                                    <div class="text-center">
                                        <a href="#" class="bukti-preview-link" data-url="${fileUrl}">
                                            <img src="${fileUrl}" class="img-thumbnail" style="max-height: 200px; cursor: pointer;" 
                                                alt="Bukti Nota" title="Klik untuk melihat lebih besar">
                                            <div class="small text-muted mt-1">Klik gambar untuk memperbesar</div>
                                        </a>
                                    </div>
                                `);
                            } else if (fileExt === 'pdf') {
                                $('#buktiContainer').html(`
                                    <div class="ratio ratio-16x9">
                                        <iframe src="${fileUrl}" class="border-0"></iframe>
                                    </div>
                                    <div class="mt-2">
                                        <a href="${fileUrl}" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="bi bi-download"></i> Buka PDF di Tab Baru
                                        </a>
                                    </div>
                                `);
                            } else {
                                $('#buktiContainer').html(`
                                    <a href="${fileUrl}" target="_blank" class="btn btn-primary btn-sm">
                                        <i class="bi bi-download"></i> Download File
                                    </a>
                                `);
                            }
                            $('#viewBuktiNota').show();
                        }
                        
                        $('#modalViewNota').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error', 'Gagal memuat data nota', 'error');
                });
            });

            // Preview gambar yang diklik
            $(document).on('click', '.bukti-preview-link', function(e) {
                e.preventDefault();
                let imageUrl = $(this).data('url');
                
                let modalHtml = `
                    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Preview Bukti Nota</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="${imageUrl}" class="img-fluid" style="max-height: 70vh;">
                                </div>
                                <div class="modal-footer">
                                    <a href="${imageUrl}" target="_blank" class="btn btn-primary">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#imagePreviewModal').remove();
                $('body').append(modalHtml);
                $('#imagePreviewModal').modal('show');
            });

            // Edit nota
            $(document).on('click', '.edit-btn', function() {
                let notaId = $(this).data('id');
                
                $.get("/transaksi/project/" + notaId + "/edit", function(res) {
                    if (res.success) {
                        let nota = res.data.nota;
                        let transactions = res.data.transactions;
                        
                        // Isi form dengan data existing
                        resetForm();
                        $('#idNota').val(nota.id);
                        $('#oldRekening').val(nota.idrek);
                        $('#notaNo').val(nota.nota_no);
                        $('#namatransaksi').val(nota.namatransaksi);
                        $('#paymenMethod').val(nota.paymen_method).trigger('change');
                        $('#tanggalNota').val(nota.tanggal);
                        
                        // Isi PPN
                        $('#ppnAmount').val(nota.ppn || 0);
                        
                        if (nota.idrek) {
                            oldRekening = nota.idrek;
                            $('#idRekening').val(nota.idrek).trigger('change');
                        }
                        
                        if (nota.paymen_method === 'tempo' && nota.tgl_tempo) {
                            $('#tglTempoContainer').show();
                            $('#tglTempo').val(nota.tgl_tempo).prop('required', true);
                        }
                        
                        // Enable manual input untuk edit
                        $('#chkManualNo').prop('checked', true);
                        $('#notaNo').prop('readonly', false);
                        
                        // Isi detail transaksi
                        $('#tblDetail tbody').empty();
                        let newRowIndex = 0;
                        
                        if (transactions && transactions.length > 0) {
                            transactions.forEach(function(transaction) {
                                let html = `
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm kode-transaksi" name="transactions[${newRowIndex}][idkodetransaksi]" required>
                                                <option value="">-- Pilih Kode Transaksi --</option>
                                `;
                                
                                @foreach(\App\Models\KodeTransaksi::all() as $kt)
                                    html += `<option value="{{ $kt->id }}" ${transaction.idkodetransaksi == {{ $kt->id }} ? 'selected' : '' }>{{ $kt->kodetransaksi }} - {{ $kt->transaksi }}</option>`;
                                @endforeach
                                
                                html += `
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm" name="transactions[${newRowIndex}][description]" value="${transaction.description || ''}" required></td>
                                        <td><input type="number" class="form-control form-control-sm text-end jml" name="transactions[${newRowIndex}][jml]" value="${transaction.jml || 1}" min="1" ></td>
                                        <td><input type="number" class="form-control form-control-sm text-end nominal" name="transactions[${newRowIndex}][nominal]" value="${transaction.nominal || 0}" min="0"></td>
                                        <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[${newRowIndex}][total]" value="${transaction.total || 0}" readonly></td>
                                        <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                                    </tr>
                                `;
                                
                                $('#tblDetail tbody').append(html);
                                newRowIndex++;
                            });
                        }
                        
                        // Load Diskon jika ada
                        if (nota.diskon > 0) {
                            $('#diskonType').val('nominal');
                            $('#diskonValue').show().val(nota.diskon);
                        }
                        
                        calculateTotals();
                        rowIndex = newRowIndex;
                        
                        // Update modal title
                        $('#modalNotaTitle').text('Edit Nota Masuk');
                        
                        // Load update log
                        loadUpdateLog(notaId);
                        
                        // Tampilkan modal
                        $('#modalNota').modal('show');
                        
                        // Initialize select2 setelah modal ditampilkan
                        setTimeout(() => {
                            initializeSelect2();
                        }, 300);
                        
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error', 'Gagal memuat data untuk edit: ' + (xhr.responseJSON?.message || 'Terjadi kesalahan'), 'error');
                });
            });

            // Delete nota
            $(document).on('click', '.delete-btn', function() {
                let notaId = $(this).data('id');
                
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Transaksi ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "/transaksi/project/" + notaId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    tbNotas.ajax.reload();
                                    Swal.fire('Berhasil!', res.message, 'success');
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

            // Submit form
            $('#frmNota').submit(function(e) {
                e.preventDefault();
                processFormSubmission(this);
            });

            function processFormSubmission(formElement) {
                // Validasi grand total
                let grandTotal = parseNumber($('#grandTotal').val());
                if (grandTotal <= 0) {
                    Swal.fire('Peringatan', 'Total transaksi harus lebih dari 0', 'warning');
                    return;
                }

                // Validasi tanggal tempo jika payment method = tempo
                if ($('#paymenMethod').val() === 'tempo' && !$('#tglTempo').val()) {
                    Swal.fire('Peringatan', 'Tanggal tempo harus diisi untuk payment method tempo', 'warning');
                    return;
                }

                submitFormData(formElement);
            }

            function submitFormData(formElement) {
                let notaId = $('#idNota').val();
                let url = notaId ? "/transaksi/project/" + notaId + "/in" : "{{ route('transaksi.project.store', 'in') }}";
                
                let formData = new FormData(formElement);
                if (notaId) {
                    formData.append('_method', 'PUT');
                }

                // Ambil nilai PPN dan Diskon
                let ppnAmount = parseNumber($('#ppnAmount').val());
                let diskonAmount = parseNumber($('#diskonDisplay').val());
                let subtotal = parseNumber($('#subtotal').val());
                
                // Tambahkan subtotal ke form data
                formData.append('subtotal', subtotal.toFixed(2));
                
                if (ppnAmount > 0) {
                    formData.append('ppn', ppnAmount.toFixed(2));
                    formData.append('ppn_kode', '3001');
                }
                
                if (diskonAmount > 0) {
                    formData.append('diskon', diskonAmount.toFixed(2));
                    formData.append('diskon_kode', '5001');
                }

                // Tampilkan loading
                $('#btnSubmit').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalNota').modal('hide');
                            tbNotas.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
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

            // Helper function untuk status badge
            function getStatusBadge(status) {
                const badge = {
                    'open': 'bg-warning',
                    'paid': 'bg-success', 
                    'partial': 'bg-info',
                    'cancel': 'bg-danger'
                };
                return `<span class="badge ${badge[status]}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
            }

            // Initialize select2 saat pertama kali load
            initializeSelect2();
            setDefaultDate();
            setAutoNotaNo();
            
            // Hitung awal
            calculateTotals();
        });
        </script>
    </x-slot>
</x-app-layout>