<x-app-layout>
    <x-slot name="pagetitle">Transaksi Keluar - Project</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Nota Pengeluaran - Project</h3>
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
                                <h6 class="modal-title" id="modalNotaTitle">Form Nota Keluar</h5>
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

                                    {{-- Vendor --}}
                                    <div class="col-md-4">
                                        <label class="form-label">Vendor</label>
                                        <select class="form-select form-select-sm select2" name="vendor_id" id="vendorId" style="width:100%;">
                                            <option value="">-- Pilih Vendor --</option>
                                            @foreach(\App\Models\Vendor::whereNull('deleted_at')->get() as $v)
                                                <option value="{{ $v->id }}">{{ $v->namavendor }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Rekening --}}
                                    <div class="col-md-6">
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

                                    {{-- Bukti Nota --}}
                                    <div class="col-12">
                                        <label class="form-label">Bukti Nota <span class="text-danger" id="buktiRequired">*</span></label>
                                        <input type="file" class="form-control form-control-sm" name="bukti_nota" id="buktiNota" 
                                               accept=".jpg,.jpeg,.png,.pdf">
                                        <small class="text-muted">Format: JPG, PNG, PDF (Max: 8MB)</small>
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
                                            <td><input type="number"  class="form-control form-control-sm text-end nominal" name="transactions[0][nominal]" value="0" min="0" step="0.01"></td>
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
                                                    <input type="number"  class="form-control form-control-sm text-end" 
                                                           name="ppn" id="ppnAmount" placeholder="Nominal PPN" min="0" value="0" step="0.01">
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
                                                <input type="number"  class="form-control form-control-sm" 
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
                                
                                <!-- Warning jika saldo tidak cukup -->
                                <div id="saldoWarning" class="alert alert-warning mt-2 p-2" style="display:none;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> 
                                    <strong>Peringatan:</strong> Grand Total melebihi saldo rekening!
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
                            <h6 class="modal-title">Detail Nota</h5>
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
                                            <th>Vendor</th>
                                            <td id="viewVendor">-</td>
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
            /* Semua input readonly */
            input[readonly],
            textarea[readonly],
            select[readonly] {
                background-color: #f0f4ff !important;  /* biru muda */
                border-color: #6c8ae4 !important;
                color: #000 !important;
                cursor: not-allowed;
            }

            .modal-xxl {
                max-width: 80% !important;
            }
        </style>

        <script>
        $(document).ready(function() {
            // DataTable dengan perbaikan
            let tbNotas = $('#tbNotas').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.project.getdata', 'out') }}",
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
                        className: 'text-center'
                        
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

            // Generate nomor nota otomatis untuk OUT
            function generateNotaNo() {
                let projectId = "{{ session('active_project_id') }}";
                let tgl = $('#tanggalNota').val().replaceAll('-','');
                let urut = Math.floor(Math.random() * 90000) + 10000;
                return 'OUT-' + projectId + '-' + tgl + '-' + urut;
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
                
                // Jika sudah format Rupiah, hilangkan simbol
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
                $('#modalNotaTitle').text('Form Nota Keluar');
                $('#saldoWarning').hide();
                
                // Wajib bukti nota untuk form baru
                $('#buktiNota').prop('required', true);
                $('#buktiRequired').show();
                
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
                        <td><input type="number"  class="form-control form-control-sm text-end nominal" name="transactions[0][nominal]" value="0" min="0" step="0.01"></td>
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
                    checkSaldoCukup();
                    
                    let url = "{{ route('transaksi.project.rekening.saldo', ['id' => ':id']) }}";
                    url = url.replace(':id', id);
                    
                    $.get(url, function(res) {
                        currentSaldo = res.saldo || 0;
                        updateSaldoDisplay();
                        checkSaldoCukup();
                    }).fail(function(xhr) {
                        console.error('Error mengambil saldo:', xhr);
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

            // Cek apakah saldo cukup untuk grand total
            function checkSaldoCukup() {
                let grandTotal = parseNumber($('#grandTotal').val());
                
                if (currentSaldo > 0 && grandTotal > 0) {
                    if (grandTotal > currentSaldo) {
                        $('#saldoWarning').show();
                        $('#saldoInfo').addClass('text-danger').removeClass('text-primary');
                    } else {
                        $('#saldoWarning').hide();
                        $('#saldoInfo').addClass('text-primary').removeClass('text-danger');
                    }
                } else {
                    $('#saldoWarning').hide();
                    $('#saldoInfo').addClass('text-primary').removeClass('text-danger');
                }
            }

            // Hitung total per row
            $(document).on('input', '.jml, .nominal', function() {
                let row = $(this).closest('tr');
                let jml = parseNumber(row.find('.jml').val());
                let nominal = parseNumber(row.find('.nominal').val());
                let total = jml * nominal;
                row.find('.total').val(total);
                
                // Update subtotal saja (tidak menghitung grand total)
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
                
                // Setelah subtotal berubah, hitung grand total
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
                    // Diskon persen: hitung persentase dari (subtotal + ppn)
                    diskonAmount = subtotal * (diskonValue / 100);
                } else if (diskonType === 'nominal' && diskonValue > 0) {
                    // Diskon nominal: langsung pakai nilai
                    diskonAmount = diskonValue;
                }
                
                $('#diskonDisplay').val(parseNumber(diskonAmount));
                
                // Hitung Grand Total = subtotal + ppn - diskon
                let grandTotal = (subtotal-diskonAmount)+ppnAmount;
                
                // Pastikan grand total tidak negatif
                if (grandTotal < 0) grandTotal = 0;
                
                $('#grandTotal').val(parseNumber(grandTotal));
                
                // Cek saldo
                checkSaldoCukup();
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
                    <td><input type="number" class="form-control form-control-sm text-end nominal" name="transactions[${rowIndex}][nominal]" value="0" min="0" step="0.01"></td>
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
                    $('#viewVendor').text(nota.vendor ? nota.vendor.namavendor : '-');
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
                            // Untuk gambar, buat preview yang bisa diklik
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

        // Tambahkan event handler untuk preview gambar yang diklik
        $(document).on('click', '.bukti-preview-link', function(e) {
            e.preventDefault();
            let imageUrl = $(this).data('url');
            
            // Buat modal untuk preview gambar besar
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
            
            // Hapus modal sebelumnya jika ada
            $('#imagePreviewModal').remove();
            
            // Tambahkan modal baru ke body
            $('body').append(modalHtml);
            
            // Tampilkan modal
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
                        
                        if (nota.vendor_id) {
                            $('#vendorId').val(nota.vendor_id).trigger('change');
                        }
                        
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
                        
                        // Bukti nota tidak wajib untuk edit
                        $('#buktiNota').prop('required', false);
                        $('#buktiRequired').hide();
                        
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
                                        <td><input type="number"  class="form-control form-control-sm text-end nominal" name="transactions[${newRowIndex}][nominal]" value="${transaction.nominal || 0}" min="0" step="0.01"></td>
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
                            // Default ke nominal dulu
                            $('#diskonType').val('nominal');
                            $('#diskonValue').show().val(nota.diskon);
                        }
                        
                        calculateTotals();
                        rowIndex = newRowIndex;
                        
                        // Update modal title
                        $('#modalNotaTitle').text('Edit Nota Keluar');
                        
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

                // Cek saldo
                if (currentSaldo > 0 && grandTotal > currentSaldo) {
                    Swal.fire({
                        title: 'Saldo Tidak Cukup',
                        text: 'Saldo rekening tidak mencukupi untuk transaksi ini. Lanjutkan?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Lanjutkan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitFormData(formElement);
                        }
                    });
                } else {
                    submitFormData(formElement);
                }
            }

            function submitFormData(formElement) {
                let notaId = $('#idNota').val();
                let url = notaId ? "/transaksi/project/" + notaId + "/out" : "{{ route('transaksi.project.store', 'out') }}";
                
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

            let activeNominalInput = null;

    // Format angka ke Rupiah dengan pemisah ribuan
    function formatRupiah(angka, isInput = false) {
        if (angka === null || angka === undefined || angka === '') {
            return isInput ? '' : 'Rp 0';
        }
        
        let num = parseFloat(angka);
        if (isNaN(num)) {
            return isInput ? '' : 'Rp 0';
        }
        
        if (isInput) {
            // Untuk input field, hanya format angka tanpa "Rp"
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        } else {
            // Untuk display, tambahkan "Rp"
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        }
    }

    // Parse nilai dari format dengan pemisah ribuan
    function parseNumber(value) {
        if (!value && value !== 0) return 0;
        
        // Hapus semua karakter non-digit kecuali titik
        if (typeof value === 'string') {
            // Hapus "Rp", spasi, dan titik pemisah ribuan
            value = value.replace(/[^\d]/g, '');
        }
        
        let num = parseFloat(value);
        return isNaN(num) ? 0 : num;
    }

    // Reset form ke kondisi default
    function resetForm() {
        // ... kode reset sebelumnya (tetap sama)
        
        // Reset tracking input aktif
        activeNominalInput = null;
    }

    // Handle input di kolom nominal dengan auto-format
    $(document).on('focus', '.nominal', function() {
        // Hapus highlight dari input sebelumnya
        if (activeNominalInput) {
            activeNominalInput.removeClass('input-active');
        }
        
        // Set input aktif baru
        activeNominalInput = $(this);
        activeNominalInput.addClass('input-active');
        
        // Jika nilai 0, kosongkan untuk input baru
        let currentVal = parseNumber(activeNominalInput.val());
        if (currentVal === 0) {
            activeNominalInput.val('');
        } else {
            // Format dengan pemisah ribuan tanpa "Rp"
            activeNominalInput.val(formatRupiah(currentVal, true));
        }
    });

    $(document).on('blur', '.nominal', function() {
        // Format saat keluar dari input
        let value = $(this).val();
        let numericValue = parseNumber(value);
        
        // Jika kosong, set ke 0
        if (value === '') {
            $(this).val('0');
            numericValue = 0;
        } else {
            // Format dengan pemisah ribuan tanpa "Rp"
            $(this).val(formatRupiah(numericValue, true));
        }
        
        // Hapus styling aktif
        $(this).removeClass('input-active');
        
        // Trigger perhitungan
        $(this).trigger('input');
    });

    // Handle input real-time di kolom nominal
    $(document).on('input', '.nominal', function() {
        let input = $(this);
        let value = input.val();
        
        // Hapus semua karakter non-digit
        let digitsOnly = value.replace(/[^\d]/g, '');
        
        // Parse ke angka
        let numericValue = parseFloat(digitsOnly) || 0;
        
        // Update nilai (dalam format untuk kalkulasi)
        input.data('numeric-value', numericValue);
        
        // Format dengan pemisah ribuan saat mengetik
        if (value !== '') {
            input.val(formatRupiah(numericValue, true));
            
            // Tempatkan kursor di akhir setelah formatting
            setTimeout(function() {
                input[0].setSelectionRange(input.val().length, input.val().length);
            }, 0);
        }
        
        // Trigger perhitungan
        let row = input.closest('tr');
        let jml = parseNumber(row.find('.jml').val());
        let total = jml * numericValue;
        row.find('.total').val(total);
        
        calculateSubtotal();
    });

    // Handle input di kolom jumlah
    $(document).on('input', '.jml', function() {
        let row = $(this).closest('tr');
        let jml = parseNumber($(this).val());
        let nominal = parseNumber(row.find('.nominal').val());
        let total = jml * nominal;
        row.find('.total').val(total);
        
        calculateSubtotal();
    });

    // Hitung total per row
    function calculateRowTotal(row) {
        let jml = parseNumber(row.find('.jml').val());
        let nominal = parseNumber(row.find('.nominal').val());
        let total = jml * nominal;
        
        // Format display total
        row.find('.total').val(formatRupiah(total, true));
        
        return total;
    }

    // Hitung subtotal
    function calculateSubtotal() {
        let subtotal = 0;
        $('#tblDetail tbody tr').each(function() {
            let total = parseNumber($(this).find('.total').val());
            subtotal += total;
        });
        
        // Update display subtotal dengan format Rupiah
        $('#subtotal').val(formatRupiah(subtotal, true));
        
        // Setelah subtotal berubah, hitung grand total
        calculateGrandTotal();
    }

    // Hitung grand total
    function calculateGrandTotal() {
        let subtotal = parseNumber($('#subtotal').val());
        let ppnAmount = parseNumber($('#ppnAmount').val());
        let diskonType = $('#diskonType').val();
        let diskonValue = parseNumber($('#diskonValue').val());
        
        // Hitung Diskon
        let diskonAmount = 0;
        if (diskonType === 'persen' && diskonValue > 0) {
            diskonAmount = subtotal * (diskonValue / 100);
        } else if (diskonType === 'nominal' && diskonValue > 0) {
            diskonAmount = diskonValue;
        }
        
        // Update display PPN dan Diskon dengan format
        $('#ppnDisplay').val(formatRupiah(ppnAmount, true));
        $('#diskonDisplay').val(formatRupiah(diskonAmount, true));
        
        // Hitung Grand Total
        let grandTotal = (subtotal - diskonAmount) + ppnAmount;
        if (grandTotal < 0) grandTotal = 0;
        
        $('#grandTotal').val(formatRupiah(grandTotal, true));
        
        // Cek saldo
        checkSaldoCukup();
    }

    // Handle input PPN
    $(document).on('input', '#ppnAmount', function() {
        // Format saat input
        let value = $(this).val();
        let digitsOnly = value.replace(/[^\d]/g, '');
        let numericValue = parseFloat(digitsOnly) || 0;
        
        // Update nilai
        $(this).val(formatRupiah(numericValue, true));
        $(this).data('numeric-value', numericValue);
        
        calculateGrandTotal();
    });

    $(document).on('focus', '#ppnAmount', function() {
        let currentVal = parseNumber($(this).val());
        if (currentVal === 0) {
            $(this).val('');
        } else {
            $(this).val(formatRupiah(currentVal, true));
        }
    });

    $(document).on('blur', '#ppnAmount', function() {
        let value = $(this).val();
        let numericValue = parseNumber(value);
        
        if (value === '') {
            $(this).val('0');
            numericValue = 0;
        } else {
            $(this).val(formatRupiah(numericValue, true));
        }
        
        calculateGrandTotal();
    });

    // Handle input Diskon Value
    $(document).on('input', '#diskonValue', function() {
        // Format saat input
        let value = $(this).val();
        let digitsOnly = value.replace(/[^\d]/g, '');
        let numericValue = parseFloat(digitsOnly) || 0;
        
        // Update nilai
        $(this).val(formatRupiah(numericValue, true));
        $(this).data('numeric-value', numericValue);
        
        calculateGrandTotal();
    });

    $(document).on('focus', '#diskonValue', function() {
        let currentVal = parseNumber($(this).val());
        if (currentVal === 0) {
            $(this).val('');
        } else {
            $(this).val(formatRupiah(currentVal, true));
        }
    });

    $(document).on('blur', '#diskonValue', function() {
        let value = $(this).val();
        let numericValue = parseNumber(value);
        
        if (value === '') {
            $(this).val('0');
            numericValue = 0;
        } else {
            $(this).val(formatRupiah(numericValue, true));
        }
        
        calculateGrandTotal();
    });

    // Tampilkan modal view
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
                $('#viewVendor').text(nota.vendor ? nota.vendor.namavendor : '-');
                $('#viewUser').text(nota.namauser || '-');
                $('#viewPaymentMethod').text(nota.paymen_method === 'cash' ? 'Cash' : 'Tempo');
                $('#viewTglTempo').text(nota.tgl_tempo ? new Date(nota.tgl_tempo).toLocaleDateString('id-ID') : '-');
                $('#viewRekening').text(nota.rekening ? nota.rekening.norek + ' - ' + nota.rekening.namarek : '-');
                $('#viewTotal').text(formatRupiah(nota.total));
                $('#viewStatus').html(getStatusBadge(nota.status));
                
                // Format nilai dengan pemisah ribuan
                let subtotal = nota.subtotal || 0;
                let ppn = nota.ppn || 0;
                let diskon = nota.diskon || 0;
                
                $('#viewSubtotal').text(formatRupiah(subtotal));
                $('#viewPpn').text(formatRupiah(ppn));
                $('#viewDiskon').text(formatRupiah(diskon));
                $('#viewGrandTotal').text(formatRupiah(nota.total));
                
                // Isi detail transaksi dengan formatting
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
                
                // ... kode lainnya tetap sama
                
                $('#modalViewNota').modal('show');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
    });

    // Tambahkan CSS untuk styling input aktif
    $('head').append(`
        <style>
            .input-active {
                border-color: #007bff !important;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
                background-color: #fff !important;
                z-index: 1;
                position: relative;
            }
            
            .nominal, #ppnAmount, #diskonValue {
                text-align: right !important;
                font-family: 'Courier New', monospace;
            }
            
            /* Highlight row yang sedang aktif */
            .input-active-row {
                background-color: rgba(0, 123, 255, 0.05) !important;
            }
        </style>
    `);

    // Tambahkan event untuk highlight row saat input aktif
    $(document).on('focus', '.nominal, .jml', function() {
        $(this).closest('tr').addClass('input-active-row');
    });

    $(document).on('blur', '.nominal, .jml', function() {
        $(this).closest('tr').removeClass('input-active-row');
    });

    // Event saat submit form - konversi nilai sebelum submit
    $('#frmNota').submit(function(e) {
        e.preventDefault();
        
        // Konversi semua nilai formatted ke numeric sebelum submit
        $('.nominal').each(function() {
            let numericValue = parseNumber($(this).val());
            $(this).val(numericValue.toFixed(2));
        });
        
        $('#ppnAmount').each(function() {
            let numericValue = parseNumber($(this).val());
            $(this).val(numericValue.toFixed(2));
        });
        
        $('#diskonValue').each(function() {
            let numericValue = parseNumber($(this).val());
            $(this).val(numericValue.toFixed(2));
        });
        
        $('.total').each(function() {
            let numericValue = parseNumber($(this).val());
            $(this).val(numericValue.toFixed(2));
        });
        
        // Konversi juga nilai-nilai display
        $('#subtotal').val(parseNumber($('#subtotal').val()).toFixed(2));
        $('#grandTotal').val(parseNumber($('#grandTotal').val()).toFixed(2));
        
        // Lanjutkan dengan proses form submission
        processFormSubmission(this);
    });

    // Fungsi untuk menampilkan nominal dalam format saat edit
    function formatAllNumericsOnLoad() {
        $('.nominal').each(function() {
            let value = $(this).val();
            let numericValue = parseNumber(value);
            if (numericValue > 0) {
                $(this).val(formatRupiah(numericValue, true));
            }
        });
        
        $('.total').each(function() {
            let value = $(this).val();
            let numericValue = parseNumber(value);
            if (numericValue > 0) {
                $(this).val(formatRupiah(numericValue, true));
            }
        });
    }

    // Panggil formatting saat edit
    $(document).on('click', '.edit-btn', function() {
        setTimeout(function() {
            formatAllNumericsOnLoad();
        }, 500);
    });

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