<x-app-layout>
    <x-slot name="pagetitle">Transaksi Penjualan - Toko</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Penjualan Barang - Toko</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" id="btnTambahPenjualan">
                            <i class="bi bi-file-earmark-plus"></i> Transaksi Penjualan Baru
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbPenjualan" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nota No</th>
                                <th>Nama Trans.</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-center">Tipe</th>
                                <th class="text-end">Total</th>
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

    <!-- Modal Penjualan -->
    <div class="modal fade" id="modalPenjualan" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="frmPenjualan" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="idPenjualan">

                    <div class="modal-header">
                        <h6 class="modal-title">Form Penjualan Barang</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">No Invoice *</label>
                                <input type="text" class="form-control form-control-sm" name="nota_no" id="notaNo" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Transaksi *</label>
                                <input type="text" class="form-control form-control-sm" name="namatransaksi" id="namaTransaksi" value="Penjualan Barang" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalPenjualan" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Jenis Penjualan *</label>
                                <select class="form-select form-select-sm" name="jenis_penjualan" id="jenisPenjualan" required>
                                    <option value="toko">Toko (Umum)</option>
                                    <option value="project">Ke Project</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="projectTujuanContainer" style="display:none;">
                                <label class="form-label">Project Tujuan *</label>
                                <select class="form-select form-select-sm select2" name="project_tujuan_id" id="projectTujuanId" style="width:100%;">
                                    <option value="">-- Pilih Project --</option>
                                    @foreach(\App\Models\Project::where('id', '!=', session('active_project_id'))->get() as $project)
                                        <option value="{{ $project->id }}">{{ $project->namaproject }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select form-select-sm select2" name="idrek" id="idRekening" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach(\App\Models\Rekening::forProject(session('active_project_id'))->get() as $rek)
                                        <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                            {{ $rek->norek }} - {{ $rek->namarek }} (Saldo: Rp {{ number_format($rek->saldo, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="mt-1">
                                    <small class="text-muted">Saldo akan bertambah: <strong id="saldoInfo">Rp 0</strong></small>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Keterangan / Customer</label>
                                <input type="text" class="form-control form-control-sm" name="keterangan_customer" id="keteranganCustomer" placeholder="Nama customer / keterangan penjualan">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Bukti Nota</label>
                                <input type="file" class="form-control form-control-sm" name="bukti_nota" id="buktiNota" 
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                            </div>
                        </div>

                        <hr>

                        <h6>Detail Barang <small class="text-muted">(Stock akan otomatis dikurangi)</small></h6>
                        
                        <div class="alert alert-warning p-2 mb-2" id="stockWarning" style="display:none;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span id="warningMessage"></span>
                        </div>
                        
                        <table class="table table-sm table-bordered" id="tblDetailBarang">
                            <thead>
                                <tr>
                                    <th width="40%">Barang *</th>
                                    <th width="80">Stock</th>
                                    <th width="80">Qty</th>
                                    <th width="120">Harga Jual</th>
                                    <th width="120">Total</th>
                                    <th width="40">
                                        <button type="button" class="btn btn-sm btn-success" id="addBarangRow">+</button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select class="form-select form-select-sm barang-select" name="transactions[0][idbarang]" style="width:100%;" required>
                                            <option value="">-- Pilih Barang --</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <span class="stock-info badge bg-secondary">0</span>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[0][qty]" value="1" min="1"></td>
                                    <td><input type="number" class="form-control form-control-sm text-end harga-jual" name="transactions[0][harga_jual]" value="0" min="0" required></td>
                                    <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[0][total]" value="0" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-danger removeBarangRow">x</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                    <td><input type="text" class="form-control form-control-sm text-end fw-bold" id="grandTotal" value="Rp 0" readonly></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span class="submit-text">Simpan Penjualan</span>
                            <span class="loading-text" style="display:none;">
                                <i class="bi bi-hourglass-split"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal View Nota Penjualan -->
    <div class="modal fade" id="modalViewPenjualan" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="row g-0">
                    <!-- Kolom Kiri: Data Nota (80%) -->
                    <div class="col-md-9">
                        <div class="modal-header">
                            <h6 class="modal-title">Detail Nota Penjualan</h6>
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
                                            <th>Jenis Penjualan</th>
                                            <td id="viewJenisPenjualan">-</td>
                                        </tr>
                                        <tr>
                                            <th>Keterangan</th>
                                            <td id="viewKeterangan">-</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">User</th>
                                            <td id="viewUser">-</td>
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
                                        <tr>
                                            <th>Cashflow</th>
                                            <td id="viewCashflow">-</td>
                                        </tr>
                                        <tr>
                                            <th>Payment Method</th>
                                            <td id="viewPaymentMethod">-</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <h6>Detail Barang</h6>
                            <table class="table table-sm table-bordered" id="tblViewDetail">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Harga Jual</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data akan diisi oleh JavaScript -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end"><strong>Subtotal:</strong></td>
                                        <td colspan="2" class="text-end" id="viewSubtotal">-</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="text-end"><strong>Grand Total:</strong></td>
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

    <!-- Modal Edit Penjualan -->
    <div class="modal fade" id="modalEditPenjualan" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="row g-0">
                    <!-- Kolom Kiri: Form (80%) -->
                    <div class="col-md-9">
                        <form id="frmEditPenjualan" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="id" id="editIdPenjualan">
                            <input type="hidden" name="old_bukti_nota" id="editOldBuktiNota">

                            <div class="modal-header">
                                <h6 class="modal-title">Edit Nota Penjualan</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">No Invoice *</label>
                                        <input type="text" class="form-control form-control-sm" name="nota_no" id="editNotaNo" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nama Transaksi *</label>
                                        <input type="text" class="form-control form-control-sm" name="namatransaksi" id="editNamaTransaksi" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal *</label>
                                        <input type="date" class="form-control form-control-sm" name="tanggal" id="editTanggalPenjualan" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Jenis Penjualan *</label>
                                        <select class="form-select form-select-sm" name="jenis_penjualan" id="editJenisPenjualan" required>
                                            <option value="toko">Toko (Umum)</option>
                                            <option value="project">Ke Project</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6" id="editProjectTujuanContainer" style="display:none;">
                                        <label class="form-label">Project Tujuan *</label>
                                        <select class="form-select form-select-sm select2" name="project_tujuan_id" id="editProjectTujuanId" style="width:100%;">
                                            <option value="">-- Pilih Project --</option>
                                            @foreach(\App\Models\Project::where('id', '!=', session('active_project_id'))->get() as $project)
                                                <option value="{{ $project->id }}">{{ $project->namaproject }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Rekening *</label>
                                        <select class="form-select form-select-sm select2" name="idrek" id="editIdRekening" style="width:100%;" required>
                                            <option value="">-- Pilih Rekening --</option>
                                            @foreach(\App\Models\Rekening::forProject(session('active_project_id'))->get() as $rek)
                                                <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                                    {{ $rek->norek }} - {{ $rek->namarek }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="mt-1">
                                            <small class="text-muted">Saldo akan bertambah: <strong id="editSaldoInfo">Rp 0</strong></small>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Keterangan / Customer</label>
                                        <input type="text" class="form-control form-control-sm" name="keterangan_customer" id="editKeteranganCustomer" placeholder="Nama customer / keterangan penjualan">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Bukti Nota <span class="text-muted">(Optional)</span></label>
                                        <input type="file" class="form-control form-control-sm" name="bukti_nota" id="editBuktiNota" 
                                               accept=".jpg,.jpeg,.png,.pdf">
                                        <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                                        <div id="editBuktiPreview" class="mt-2" style="display:none;">
                                            <img id="editPreviewImage" src="#" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                                        </div>
                                        <div id="currentBukti" class="mt-2" style="display:none;">
                                            <small>Bukti saat ini: <a href="#" id="currentBuktiLink" target="_blank">Lihat</a></small>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <h6>Detail Barang</h6>
                                
                                <div class="alert alert-warning p-2 mb-2" id="editStockWarning" style="display:none;">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span id="editWarningMessage"></span>
                                </div>
                                
                                <table class="table table-sm table-bordered" id="tblEditDetailBarang">
                                    <thead>
                                        <tr>
                                            <th width="40%">Barang *</th>
                                            <th width="80">Stock</th>
                                            <th width="80">Qty</th>
                                            <th width="120">Harga Jual</th>
                                            <th width="120">Total</th>
                                            <th width="40">
                                                <button type="button" class="btn btn-sm btn-success" id="addEditBarangRow">+</button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data akan diisi oleh JavaScript -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                            <td><input type="text" class="form-control form-control-sm text-end fw-bold" id="editGrandTotal" value="Rp 0" readonly></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="btnEditSubmit">
                                    <span class="submit-text">Update Penjualan</span>
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
                            <div id="editLogContainer">
                                <p class="text-muted small">Tidak ada riwayat perubahan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
            // ====================
            // UTILITY FUNCTIONS
            // ====================
            
            // Format number untuk tampilan Rupiah
            function formatNumber(num) {
                if (!num || isNaN(num)) return '0';
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(num);
            }

            // Format Rupiah
            function formatRupiah(angka) {
                if (!angka || isNaN(angka)) return 'Rp 0';
                return 'Rp ' + formatNumber(angka);
            }

            // Format tanggal dari YYYY-MM-DD menjadi DD/MM/YYYY
            function formatTanggal(tanggal) {
                if (!tanggal) return '';
                const date = new Date(tanggal);
                return date.toLocaleDateString('id-ID');
            }

            // Helper function untuk status badge
            function getStatusBadge(status) {
                const badge = {
                    'open': 'bg-warning',
                    'paid': 'bg-success', 
                    'partial': 'bg-info',
                    'cancel': 'bg-danger'
                };
                const statusText = status.charAt(0).toUpperCase() + status.slice(1);
                return `<span class="badge ${badge[status] || 'bg-secondary'}">${statusText}</span>`;
            }

            // ====================
            // MAIN DATA TABLE
            // ====================
            let tbPenjualan = $('#tbPenjualan').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('toko.penjualan.data') }}",
                columns: [
                    { 
                        data: 'DT_RowIndex', 
                        name: 'DT_RowIndex', 
                        className: 'text-center' 
                    },
                    { 
                        data: 'nota_no', 
                        name: 'nota_no' 
                    },
                    { 
                        data: 'namatransaksi', 
                        name: 'namatransaksi' 
                    },
                    { 
                        data: 'tanggal', 
                        name: 'tanggal', 
                        className: 'text-center'
                    },
                    { 
                        data: 'type', 
                        name: 'type',
                        className: 'text-center',
                        render: function(data, type, row) {
                            let jenis = 'Toko';
                            if (data === 'project') {
                                jenis = 'Ke Project';
                            }
                            return '<span class="badge bg-success">' + jenis + '</span>';
                        }
                    },
                    { 
                        data: 'total', 
                        name: 'total', 
                        className: 'text-end'
                    },
                    { 
                        data: 'status', 
                        name: 'status', 
                        className: 'text-center',
                        render: function(data) {
                            return getStatusBadge(data);
                        }
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
                order: [[3, 'desc']]
            });

            // ====================
            // MODAL TAMBAH PENJUALAN
            // ====================
            
            // Set tanggal default
            $('#tanggalPenjualan').val(new Date().toISOString().split('T')[0]);

            // Generate nomor nota otomatis
            function generateNotaNo() {
                let tgl = $('#tanggalPenjualan').val().replaceAll('-','');
                let urut = Math.floor(Math.random() * 90000) + 10000;
                return 'JUAL-' + tgl + '-' + urut;
            }
            
            $('#tanggalPenjualan').change(function() {
                $('#notaNo').val(generateNotaNo());
            });
            
            $('#notaNo').val(generateNotaNo());

            // Initialize select2
            function initializeSelect2() {
                $('.select2').select2({ 
                    dropdownParent: $('#modalPenjualan'),
                    width: '100%'
                });
                
                // Initialize select2 untuk barang
                $('.barang-select').each(function(index) {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            dropdownParent: $('#modalPenjualan'),
                            width: '100%',
                            ajax: {
                                url: "{{ route('toko.barang.search') }}",
                                dataType: 'json',
                                delay: 250,
                                data: function(params) {
                                    return {
                                        search: params.term
                                    };
                                },
                                processResults: function(data) {
                                    return {
                                        results: data
                                    };
                                }
                            },
                            minimumInputLength: 1
                        });
                    }
                });
            }

            // Toggle project tujuan
            $('#jenisPenjualan').change(function() {
                if ($(this).val() === 'project') {
                    $('#projectTujuanContainer').show();
                    $('#projectTujuanId').prop('required', true);
                    $('#namaTransaksi').val('Penjualan Barang');
                } else {
                    $('#projectTujuanContainer').hide();
                    $('#projectTujuanId').prop('required', false);
                    $('#namaTransaksi').val('Penjualan Barang');
                }
            });

            // Update saldo info
            function updateSaldoInfo() {
                let selectedRekening = $('#idRekening').find(':selected');
                let saldo = selectedRekening.data('saldo') || 0;
                $('#saldoInfo').text('Rp ' + formatNumber(saldo));
            }

            // Load detail barang saat dipilih
            $(document).on('select2:select', '.barang-select', function(e) {
                let row = $(this).closest('tr');
                let barangId = e.params.data.id;
                
                if (barangId) {
                    let detailUrl = "{{ route('toko.barang.detail', ['id' => ':id']) }}";
                    detailUrl = detailUrl.replace(':id', barangId);
                    
                    $.get(detailUrl, function(res) {
                        if (res.success) {
                            let barang = res.data.barang;
                            let stock = res.data.stock;
                            
                            // Update stock info
                            let stockBadge = stock > 10 ? 'bg-success' : (stock > 0 ? 'bg-warning' : 'bg-danger');
                            row.find('.stock-info')
                                .text(stock)
                                .removeClass('bg-secondary bg-success bg-warning bg-danger')
                                .addClass(stockBadge);
                            
                            // Set harga jual
                            row.find('.harga-jual').val(barang.harga_jual);
                            
                            // Hitung total
                            calculateRowTotal(row);
                        }
                    }).fail(function() {
                        console.error('Gagal mengambil detail barang');
                    });
                }
            });

            // Check stock availability
            function checkStockAvailability() {
                let warnings = [];
                let isValid = true;
                
                $('#tblDetailBarang tbody tr').each(function() {
                    let row = $(this);
                    let barangId = row.find('.barang-select').val();
                    let stockElement = row.find('.stock-info');
                    let stock = parseInt(stockElement.text());
                    let qty = parseInt(row.find('.qty').val()) || 0;
                    
                    if (barangId && !isNaN(stock) && !isNaN(qty)) {
                        if (qty > stock) {
                            let barangName = row.find('.barang-select').select2('data')[0]?.text || 'Barang';
                            barangName = barangName.split('|')[0].trim();
                            warnings.push(`${barangName}: Qty ${qty} > Stock ${stock}`);
                            isValid = false;
                        }
                    }
                });
                
                if (warnings.length > 0) {
                    $('#warningMessage').text('Stock tidak cukup: ' + warnings.join(', '));
                    $('#stockWarning').show();
                } else {
                    $('#stockWarning').hide();
                }
                
                return isValid;
            }

            // Add row barang
            let barangRowIndex = 1;
            $('#addBarangRow').click(function() {
                let html = `
                    <tr>
                        <td>
                            <select class="form-select form-select-sm barang-select" name="transactions[${barangRowIndex}][idbarang]" style="width:100%;" required>
                                <option value="">-- Pilih Barang --</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <span class="stock-info badge bg-secondary">0</span>
                        </td>
                        <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[${barangRowIndex}][qty]" value="1" min="1"></td>
                        <td><input type="number" class="form-control form-control-sm text-end harga-jual" name="transactions[${barangRowIndex}][harga_jual]" value="0" min="0" required></td>
                        <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[${barangRowIndex}][total]" value="0" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeBarangRow">x</button></td>
                    </tr>
                `;
                $('#tblDetailBarang tbody').append(html);
                initializeSelect2();
                barangRowIndex++;
            });

            // Remove row barang
            $(document).on('click', '.removeBarangRow', function() {
                if ($('#tblDetailBarang tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    checkStockAvailability();
                    calculateGrandTotal();
                }
            });

            // Calculate row total
            function calculateRowTotal(row) {
                let qty = parseFloat(row.find('.qty').val()) || 0;
                let hargaJual = parseFloat(row.find('.harga-jual').val()) || 0;
                let total = qty * hargaJual;
                row.find('.total').val(formatRupiah(total));
                calculateGrandTotal();
                checkStockAvailability();
            }

            // Calculate grand total
            function calculateGrandTotal() {
                let grandTotal = 0;
                $('.total').each(function() {
                    let val = parseFloat($(this).val().replace(/[^\d]/g, '')) || 0;
                    grandTotal += val;
                });
                $('#grandTotal').val(formatRupiah(grandTotal));
            }

            // Event listeners untuk perhitungan
            $(document).on('input', '.qty, .harga-jual', function() {
                calculateRowTotal($(this).closest('tr'));
            });

            // Update saldo saat rekening berubah
            $('#idRekening').change(function() {
                updateSaldoInfo();
            });

            // Tombol tambah penjualan
            $('#btnTambahPenjualan').click(function() {
                resetForm();
                $('#modalPenjualan').modal('show');
            });

            // Reset form
            function resetForm() {
                $('#frmPenjualan')[0].reset();
                $('#idPenjualan').val('');
                $('#jenisPenjualan').val('toko').trigger('change');
                $('#projectTujuanContainer').hide();
                $('#stockWarning').hide();
                $('#keteranganCustomer').val('');
                $('#namaTransaksi').val('Penjualan Barang');
                
                $('#tblDetailBarang tbody').html(`
                    <tr>
                        <td>
                            <select class="form-select form-select-sm barang-select" name="transactions[0][idbarang]" style="width:100%;" required>
                                <option value="">-- Pilih Barang --</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <span class="stock-info badge bg-secondary">0</span>
                        </td>
                        <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[0][qty]" value="1" min="1"></td>
                        <td><input type="number" class="form-control form-control-sm text-end harga-jual" name="transactions[0][harga_jual]" value="0" min="0" required></td>
                        <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[0][total]" value="0" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeBarangRow">x</button></td>
                    </tr>
                `);
                $('#grandTotal').val('Rp 0');
                $('#tanggalPenjualan').val(new Date().toISOString().split('T')[0]);
                $('#notaNo').val(generateNotaNo());
                barangRowIndex = 1;
                initializeSelect2();
                updateSaldoInfo();
            }

            // Submit form tambah
            $('#frmPenjualan').submit(function(e) {
                e.preventDefault();
                
                // Validasi stock
                if (!checkStockAvailability()) {
                    Swal.fire('Peringatan', 'Ada barang yang stocknya tidak cukup. Silahkan periksa kembali.', 'warning');
                    return;
                }
                
                // Validasi minimal 1 barang
                let hasBarang = false;
                $('#tblDetailBarang tbody tr').each(function() {
                    if ($(this).find('.barang-select').val()) {
                        hasBarang = true;
                    }
                });
                
                if (!hasBarang) {
                    Swal.fire('Peringatan', 'Minimal harus ada 1 barang yang dipilih', 'warning');
                    return;
                }
                
                let formData = new FormData(this);
                
                // Tampilkan loading
                $('#btnSubmit').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();

                $.ajax({
                    url: "{{ route('toko.penjualan.store') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalPenjualan').modal('hide');
                            tbPenjualan.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            });

            // ====================
            // VIEW NOTA PENJUALAN
            // ====================
            
            // View Nota Penjualan
            $(document).on('click', '.view-btn', function() {
                let notaId = $(this).data('id');
                
                $.get("/toko/" + notaId, function(res) {
                    if (res.success) {
                        let nota = res.data;
                        
                        // Isi data header
                        $('#viewNotaNo').text(nota.nota_no);
                        $('#viewTanggal').text(formatTanggal(nota.tanggal));
                        $('#viewNamaTransaksi').text(nota.namatransaksi);
                        $('#viewProject').text(nota.project ? nota.project.namaproject : '-');
                        $('#viewJenisPenjualan').text(nota.type === 'project' ? 'Ke Project' : 'Toko');
                        $('#viewKeterangan').text(nota.keterangan_customer || '-');
                        $('#viewUser').text(nota.namauser || '-');
                        $('#viewRekening').text(nota.rekening ? nota.rekening.norek + ' - ' + nota.rekening.namarek : '-');
                        $('#viewTotal').text(formatRupiah(nota.total));
                        $('#viewStatus').html(getStatusBadge(nota.status));
                        $('#viewCashflow').text(nota.cashflow === 'out' ? 'Keluar' : 'Masuk');
                        $('#viewPaymentMethod').text(nota.paymen_method === 'cash' ? 'Cash' : 'Tempo');
                        
                        // Isi detail barang
                        let detailHtml = '';
                        if (nota.transactions && nota.transactions.length > 0) {
                            nota.transactions.forEach(function(transaction) {
                                detailHtml += `
                                    <tr>
                                        <td>${transaction.barang ? transaction.barang.nama_barang : transaction.description}</td>
                                        <td class="text-center">${transaction.jml}</td>
                                        <td class="text-end">${formatRupiah(transaction.nominal)}</td>
                                        <td class="text-end">${formatRupiah(transaction.total)}</td>
                                    </tr>
                                `;
                            });
                        }
                        $('#tblViewDetail tbody').html(detailHtml);
                        
                        // Hitung total
                        let subtotal = nota.subtotal || 0;
                        let total = nota.total || 0;
                        
                        $('#viewSubtotal').text(formatRupiah(subtotal));
                        $('#viewGrandTotal').text(formatRupiah(total));
                        
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
                                            <img src="${fileUrl}" class="img-thumbnail" style="max-height: 300px; cursor: pointer;" 
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
                        
                        $('#modalViewPenjualan').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error', 'Gagal memuat data nota', 'error');
                });
            });

            // Load update log untuk view modal
            function loadViewUpdateLog(notaId) {
                if (!notaId) {
                    $('#viewLogContainer').html('<p class="text-muted small">Tidak ada riwayat perubahan</p>');
                    return;
                }
                
                $.get(`${notaId}/logs`, function(res) {
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
                }).fail(function() {
                    $('#viewLogContainer').html('<p class="text-muted small">Error loading log</p>');
                });
            }

            // ====================
            // EDIT NOTA PENJUALAN
            // ====================
            
            // Edit Nota Penjualan
            $(document).on('click', '.edit-btn', function() {
                let notaId = $(this).data('id');
                
                $.get("/toko/" + notaId + "/edit", function(res) {
                    if (res.success) {
                        let nota = res.data;
                        
                        // Reset form edit
                        resetEditForm();
                        
                        // Isi data header
                        $('#editIdPenjualan').val(nota.id);
                        $('#editNotaNo').val(nota.nota_no);
                        $('#editNamaTransaksi').val(nota.namatransaksi);
                        $('#editTanggalPenjualan').val(nota.tanggal.split('T')[0]);
                        $('#editJenisPenjualan').val(nota.type).trigger('change');
                        $('#editKeteranganCustomer').val(nota.keterangan_customer || '');
                        
                        if (nota.idrek) {
                            $('#editIdRekening').val(nota.idrek).trigger('change');
                        }
                        
                        // Tampilkan project tujuan jika jenis penjualan = project
                        if (nota.type === 'project' && nota.project_tujuan_id) {
                            $('#editProjectTujuanContainer').show();
                            $('#editProjectTujuanId').val(nota.project_tujuan_id).trigger('change');
                        }
                        
                        // Isi detail barang
                        $('#tblEditDetailBarang tbody').empty();
                        let barangRowIndex = 0;
                        
                        if (nota.transactions && nota.transactions.length > 0) {
                            nota.transactions.forEach(function(transaction) {
                                let barangName = transaction.barang ? transaction.barang.nama_barang : transaction.description;
                                let barangId = transaction.idbarang;
                                
                                let html = `
                                    <tr data-index="${barangRowIndex}">
                                        <td>
                                            <select class="form-select form-select-sm edit-barang-select" name="transactions[${barangRowIndex}][idbarang]" style="width:100%;" required>
                                                <option value="">-- Pilih Barang --</option>
                                            </select>
                                            <input type="hidden" class="edit-barang-nama" value="${barangName}">
                                        </td>
                                        <td class="text-center">
                                            <span class="edit-stock-info badge bg-secondary">0</span>
                                        </td>
                                        <td><input type="number" class="form-control form-control-sm text-end edit-qty" name="transactions[${barangRowIndex}][qty]" value="${transaction.jml || 1}" min="1"></td>
                                        <td><input type="number" class="form-control form-control-sm text-end edit-harga-jual" name="transactions[${barangRowIndex}][harga_jual]" value="${transaction.nominal || 0}" min="0" required></td>
                                        <td><input type="text" class="form-control form-control-sm text-end edit-total" name="transactions[${barangRowIndex}][total]" value="${transaction.total || 0}" readonly></td>
                                        <td><button type="button" class="btn btn-sm btn-danger removeEditBarangRow">x</button></td>
                                    </tr>
                                `;
                                
                                $('#tblEditDetailBarang tbody').append(html);
                                
                                // Initialize select2 untuk row ini
                                let selectElement = $(`#tblEditDetailBarang tbody tr:eq(${barangRowIndex}) .edit-barang-select`);
                                initializeEditSelect2(selectElement, barangId, barangName);
                                
                                barangRowIndex++;
                            });
                        }
                        
                        editBarangRowIndex = barangRowIndex;
                        
                        // Tampilkan bukti nota saat ini
                        if (nota.bukti_nota) {
                            $('#editOldBuktiNota').val(nota.bukti_nota);
                            $('#currentBukti').show();
                            $('#currentBuktiLink').attr('href', '/storage/' + nota.bukti_nota).text('Lihat bukti nota');
                        }
                        
                        // Hitung grand total
                        calculateEditGrandTotal();
                        
                        // Load update log
                        loadEditUpdateLog(notaId);
                        
                        $('#modalEditPenjualan').modal('show');
                        
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error', 'Gagal memuat data untuk edit', 'error');
                });
            });

            // Initialize select2 untuk edit modal
            function initializeEditSelect2(selectElement, selectedId, selectedText) {
                selectElement.select2({
                    dropdownParent: $('#modalEditPenjualan'),
                    width: '100%',
                    ajax: {
                        url: "{{ route('toko.barang.search') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data
                            };
                        }
                    },
                    minimumInputLength: 1
                });
                
                // Set selected value jika ada
                if (selectedId) {
                    let option = new Option(selectedText, selectedId, true, true);
                    selectElement.append(option).trigger('change');
                    
                    // Load detail barang
                    setTimeout(() => {
                        loadEditBarangDetail(selectedId, selectElement.closest('tr'));
                    }, 100);
                }
            }

            // Load detail barang untuk edit
            function loadEditBarangDetail(barangId, row) {
                let detailUrl = "{{ route('toko.barang.detail', ['id' => ':id']) }}";
                detailUrl = detailUrl.replace(':id', barangId);
                
                $.get(detailUrl, function(res) {
                    if (res.success) {
                        let barang = res.data.barang;
                        let stock = res.data.stock;
                        
                        // Update stock info
                        let stockBadge = stock > 10 ? 'bg-success' : (stock > 0 ? 'bg-warning' : 'bg-danger');
                        row.find('.edit-stock-info')
                            .text(stock)
                            .removeClass('bg-secondary bg-success bg-warning bg-danger')
                            .addClass(stockBadge);
                        
                        // Set harga jual jika belum diisi
                        if (!row.find('.edit-harga-jual').val()) {
                            row.find('.edit-harga-jual').val(barang.harga_jual);
                        }
                        
                        // Hitung total
                        calculateEditRowTotal(row);
                    }
                }).fail(function() {
                    console.error('Gagal mengambil detail barang');
                });
            }

            // Reset form edit
            function resetEditForm() {
                $('#tblEditDetailBarang tbody').empty();
                $('#editStockWarning').hide();
                $('#editBuktiPreview').hide();
                $('#currentBukti').hide();
                $('#editGrandTotal').val('Rp 0');
                $('#editLogContainer').html('<p class="text-muted small">Tidak ada riwayat perubahan</p>');
                editBarangRowIndex = 0;
            }

            // Add row barang di edit modal
            let editBarangRowIndex = 0;
            $('#addEditBarangRow').click(function() {
                let html = `
                    <tr data-index="${editBarangRowIndex}">
                        <td>
                            <select class="form-select form-select-sm edit-barang-select" name="transactions[${editBarangRowIndex}][idbarang]" style="width:100%;" required>
                                <option value="">-- Pilih Barang --</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <span class="edit-stock-info badge bg-secondary">0</span>
                        </td>
                        <td><input type="number" class="form-control form-control-sm text-end edit-qty" name="transactions[${editBarangRowIndex}][qty]" value="1" min="1"></td>
                        <td><input type="number" class="form-control form-control-sm text-end edit-harga-jual" name="transactions[${editBarangRowIndex}][harga_jual]" value="0" min="0" required></td>
                        <td><input type="text" class="form-control form-control-sm text-end edit-total" name="transactions[${editBarangRowIndex}][total]" value="0" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeEditBarangRow">x</button></td>
                    </tr>
                `;
                $('#tblEditDetailBarang tbody').append(html);
                
                // Initialize select2
                let selectElement = $(`#tblEditDetailBarang tbody tr:last .edit-barang-select`);
                initializeEditSelect2(selectElement);
                
                editBarangRowIndex++;
            });

            // Remove row barang di edit modal
            $(document).on('click', '.removeEditBarangRow', function() {
                if ($('#tblEditDetailBarang tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    checkEditStockAvailability();
                    calculateEditGrandTotal();
                }
            });

            // Calculate row total di edit modal
            function calculateEditRowTotal(row) {
                let qty = parseFloat(row.find('.edit-qty').val()) || 0;
                let hargaJual = parseFloat(row.find('.edit-harga-jual').val()) || 0;
                let total = qty * hargaJual;
                row.find('.edit-total').val(formatRupiah(total));
                calculateEditGrandTotal();
                checkEditStockAvailability();
            }

            // Calculate grand total di edit modal
            function calculateEditGrandTotal() {
                let grandTotal = 0;
                $('.edit-total').each(function() {
                    let val = parseFloat($(this).val().replace(/[^\d]/g, '')) || 0;
                    grandTotal += val;
                });
                $('#editGrandTotal').val(formatRupiah(grandTotal));
            }

            // Check stock availability di edit modal
            function checkEditStockAvailability() {
                let warnings = [];
                let isValid = true;
                
                $('#tblEditDetailBarang tbody tr').each(function() {
                    let row = $(this);
                    let barangId = row.find('.edit-barang-select').val();
                    let stockElement = row.find('.edit-stock-info');
                    let stock = parseInt(stockElement.text());
                    let qty = parseInt(row.find('.edit-qty').val()) || 0;
                    
                    if (barangId && !isNaN(stock) && !isNaN(qty)) {
                        if (qty > stock) {
                            let barangName = row.find('.edit-barang-select').select2('data')[0]?.text || 'Barang';
                            barangName = barangName.split('|')[0].trim();
                            warnings.push(`${barangName}: Qty ${qty} > Stock ${stock}`);
                            isValid = false;
                        }
                    }
                });
                
                if (warnings.length > 0) {
                    $('#editWarningMessage').text('Stock tidak cukup: ' + warnings.join(', '));
                    $('#editStockWarning').show();
                } else {
                    $('#editStockWarning').hide();
                }
                
                return isValid;
            }

            // Event listeners untuk edit modal
            $(document).on('input', '.edit-qty, .edit-harga-jual', function() {
                calculateEditRowTotal($(this).closest('tr'));
            });

            $(document).on('select2:select', '.edit-barang-select', function(e) {
                let row = $(this).closest('tr');
                let barangId = e.params.data.id;
                
                if (barangId) {
                    loadEditBarangDetail(barangId, row);
                }
            });

            // Load update log untuk edit modal
            function loadEditUpdateLog(notaId) {
                if (!notaId) {
                    $('#editLogContainer').html('<p class="text-muted small">Tidak ada riwayat perubahan</p>');
                    return;
                }
                
                $.get(`${notaId}/logs`, function(res) {
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
                        $('#editLogContainer').html(logHtml);
                    } else {
                        $('#editLogContainer').html('<p class="text-muted small">Tidak ada riwayat perubahan</p>');
                    }
                }).fail(function() {
                    $('#editLogContainer').html('<p class="text-muted small">Error loading log</p>');
                });
            }

            // Submit form edit
            $('#frmEditPenjualan').submit(function(e) {
                e.preventDefault();
                
                // Validasi stock
                if (!checkEditStockAvailability()) {
                    Swal.fire('Peringatan', 'Ada barang yang stocknya tidak cukup. Silahkan periksa kembali.', 'warning');
                    return;
                }
                
                // Validasi minimal 1 barang
                let hasBarang = false;
                $('#tblEditDetailBarang tbody tr').each(function() {
                    if ($(this).find('.edit-barang-select').val()) {
                        hasBarang = true;
                    }
                });
                
                if (!hasBarang) {
                    Swal.fire('Peringatan', 'Minimal harus ada 1 barang yang dipilih', 'warning');
                    return;
                }
                
                let formData = new FormData(this);
                
                // Tampilkan loading
                $('#btnEditSubmit').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();

                $.ajax({
                    url: "/toko/" + $('#editIdPenjualan').val(),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btnEditSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalEditPenjualan').modal('hide');
                            tbPenjualan.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#btnEditSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            });

            // Toggle jenis penjualan di edit modal
            $('#editJenisPenjualan').change(function() {
                if ($(this).val() === 'project') {
                    $('#editProjectTujuanContainer').show();
                    $('#editProjectTujuanId').prop('required', true);
                } else {
                    $('#editProjectTujuanContainer').hide();
                    $('#editProjectTujuanId').prop('required', false);
                }
            });

            // Update saldo info di edit modal
            $('#editIdRekening').change(function() {
                let selectedRekening = $(this).find(':selected');
                let saldo = selectedRekening.data('saldo') || 0;
                $('#editSaldoInfo').text('Rp ' + formatNumber(saldo));
            });

            // Preview image untuk edit modal
            $('#editBuktiNota').change(function() {
                const file = this.files[0];
                if (file) {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#editPreviewImage').attr('src', e.target.result);
                            $('#editBuktiPreview').show();
                        }
                        reader.readAsDataURL(file);
                    } else {
                        $('#editBuktiPreview').hide();
                    }
                } else {
                    $('#editBuktiPreview').hide();
                }
            });

            // ====================
            // INITIALIZATION
            // ====================
            
            // Initialize saat pertama kali load
            initializeSelect2();
            updateSaldoInfo();
        });
        </script>
    </x-slot>
</x-app-layout>