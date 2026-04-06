<x-app-layout>
    <x-slot name="pagetitle">Transaksi Pembelian - Toko</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Pembelian Barang - Toko</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" id="btnTambahPembelian">
                            <i class="bi bi-file-earmark-plus"></i> Transaksi Pembelian Baru
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbPembelian" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nota No</th>
                                <th>Nama Trans.</th>
                                <th class="text-center">Tanggal</th>
                                <th>Vendor</th>
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

    <div class="modal fade" id="modalPembelian" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="frmPembelian" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title">Form Pembelian Barang</h6>
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
                                <input type="text" class="form-control form-control-sm" name="namatransaksi" id="namaTransaksi" value="Pembelian Barang" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalPembelian" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor</label>
                                <select class="form-select form-select-sm select2-modal" name="vendor_id" id="vendorId" style="width:100%;">
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach(\App\Models\Vendor::whereNull('deleted_at')->orderBy('namavendor')->get() as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->namavendor }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Metode Pembayaran *</label>
                                <select class="form-select form-select-sm" name="paymen_method" id="paymentMethod" required>
                                    <option value="cash">Cash</option>
                                    <option value="tempo">Tempo</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-none" id="tanggalTempoContainer">
                                <label class="form-label">Tanggal Tempo *</label>
                                <input type="date" class="form-control form-control-sm" name="tgl_tempo" id="tanggalTempo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select form-select-sm select2-modal" name="idrek" id="idRekening" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach(\App\Models\Rekening::forProject(session('active_project_id'))->get() as $rek)
                                        <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                            {{ $rek->norek }} - {{ $rek->namarek }} (Saldo: Rp {{ number_format($rek->saldo, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="mt-1">
                                    <small class="text-muted">Saldo rekening: <strong id="saldoInfo">Rp 0</strong></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bukti Nota</label>
                                <input type="file" class="form-control form-control-sm" name="bukti_nota" id="buktiNota" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Format: JPG, PNG, PDF. Maks. 2MB.</small>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Detail Item Pembelian</h6>
                            <button type="button" class="btn btn-sm btn-success" id="addBarangRow">+ Tambah Item</button>
                        </div>
                        <table class="table table-sm table-bordered" id="tblDetailBarang">
                            <thead>
                                <tr>
                                    <th width="25%">Kode Transaksi *</th>
                                    <th>Keterangan / Nama Transaksi *</th>
                                    <th width="90">Qty</th>
                                    <th width="130">Nominal</th>
                                    <th width="140">Total</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
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
                            <span class="submit-text">Simpan Pembelian</span>
                            <span class="loading-text d-none"><i class="bi bi-hourglass-split"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalViewPembelian" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Detail Nota Pembelian</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th width="40%">No Nota</th><td id="viewNotaNo">-</td></tr>
                                <tr><th>Tanggal</th><td id="viewTanggal">-</td></tr>
                                <tr><th>Nama Transaksi</th><td id="viewNamaTransaksi">-</td></tr>
                                <tr><th>Project</th><td id="viewProject">-</td></tr>
                                <tr><th>Vendor</th><td id="viewVendor">-</td></tr>
                                <tr><th>Bukti Nota</th><td id="viewBuktiNota">-</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th width="40%">User</th><td id="viewUser">-</td></tr>
                                <tr><th>Rekening</th><td id="viewRekening">-</td></tr>
                                <tr><th>Total</th><td id="viewTotal">-</td></tr>
                                <tr><th>Status</th><td id="viewStatus">-</td></tr>
                                <tr><th>Cashflow</th><td id="viewCashflow">-</td></tr>
                                <tr><th>Payment Method</th><td id="viewPaymentMethod">-</td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Detail Item</h6>
                        <a href="#" target="_blank" class="btn btn-sm btn-outline-primary" id="btnViewInvoicePdf">
                            <i class="bi bi-file-earmark-pdf"></i> Invoice PDF
                        </a>
                    </div>
                    <table class="table table-sm table-bordered" id="tblViewDetail">
                        <thead>
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Keterangan</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end" id="viewSubtotal">-</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                <td class="text-end fw-bold" id="viewGrandTotal">-</td>
                            </tr>
                        </tfoot>
                    </table>

                    <hr>
                    <h6>Riwayat Perubahan</h6>
                    <div id="viewLogContainer">
                        <p class="text-muted small mb-0">Tidak ada riwayat perubahan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditPembelian" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="frmEditPembelian" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editIdPembelian">
                    <div class="modal-header">
                        <h6 class="modal-title">Edit Nota Pembelian</h6>
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
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="editTanggalPembelian" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor</label>
                                <select class="form-select form-select-sm select2-edit" name="vendor_id" id="editVendorId" style="width:100%;">
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach(\App\Models\Vendor::whereNull('deleted_at')->orderBy('namavendor')->get() as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->namavendor }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Metode Pembayaran *</label>
                                <select class="form-select form-select-sm" name="paymen_method" id="editPaymentMethod" required>
                                    <option value="cash">Cash</option>
                                    <option value="tempo">Tempo</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-none" id="editTanggalTempoContainer">
                                <label class="form-label">Tanggal Tempo *</label>
                                <input type="date" class="form-control form-control-sm" name="tgl_tempo" id="editTanggalTempo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select form-select-sm select2-edit" name="idrek" id="editIdRekening" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach(\App\Models\Rekening::forProject(session('active_project_id'))->get() as $rek)
                                        <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                            {{ $rek->norek }} - {{ $rek->namarek }} (Saldo: Rp {{ number_format($rek->saldo, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="mt-1">
                                    <small class="text-muted">Saldo rekening: <strong id="editSaldoInfo">Rp 0</strong></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bukti Nota Baru</label>
                                <input type="file" class="form-control form-control-sm" name="bukti_nota" id="editBuktiNota" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted d-block">Kosongkan jika tidak ingin mengganti file.</small>
                                <small id="editBuktiNotaInfo" class="d-block mt-1"></small>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Detail Item Pembelian</h6>
                            <button type="button" class="btn btn-sm btn-success" id="addEditBarangRow">+ Tambah Item</button>
                        </div>
                        <table class="table table-sm table-bordered" id="tblEditDetailBarang">
                            <thead>
                                <tr>
                                    <th width="25%">Kode Transaksi *</th>
                                    <th>Keterangan / Nama Transaksi *</th>
                                    <th width="90">Qty</th>
                                    <th width="130">Nominal</th>
                                    <th width="140">Total</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                    <td><input type="text" class="form-control form-control-sm text-end fw-bold" id="editGrandTotal" value="Rp 0" readonly></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>

                        <hr>
                        <h6>Riwayat Perubahan</h6>
                        <div id="editLogContainer">
                            <p class="text-muted small mb-0">Tidak ada riwayat perubahan</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="btnEditSubmit">
                            <span class="submit-text">Update Pembelian</span>
                            <span class="loading-text d-none"><i class="bi bi-hourglass-split"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(function() {
                const kodeTransaksiOptions = @json($kodeTransaksi->map(function ($item) {
                    return ['id' => $item->id, 'label' => $item->kodetransaksi . ' - ' . $item->transaksi];
                })->values());

                let createRowIndex = 0;
                let editRowIndex = 0;

                function formatNumber(num) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num || 0); }
                function formatRupiah(num) { return 'Rp ' + formatNumber(num || 0); }
                function formatTanggal(date) { return date ? new Date(date).toLocaleDateString('id-ID') : '-'; }
                function statusBadge(status) {
                    const map = { open: 'bg-warning', paid: 'bg-success', partial: 'bg-info', cancel: 'bg-danger' };
                    return `<span class="badge ${map[status] || 'bg-secondary'}">${status}</span>`;
                }
                function setLoading(selector, loading) {
                    $(selector).prop('disabled', loading);
                    $(selector).find('.submit-text').toggleClass('d-none', loading);
                    $(selector).find('.loading-text').toggleClass('d-none', !loading);
                }
                function setSaldo(selectSelector, labelSelector) {
                    const saldo = $(selectSelector).find(':selected').data('saldo') || 0;
                    $(labelSelector).text(formatRupiah(saldo));
                }
                function toggleCreateTempo() {
                    const isTempo = $('#paymentMethod').val() === 'tempo';
                    $('#tanggalTempoContainer').toggleClass('d-none', !isTempo);
                    $('#tanggalTempo').prop('required', isTempo);
                    if (!isTempo) $('#tanggalTempo').val('');
                }
                function toggleEditTempo() {
                    const isTempo = $('#editPaymentMethod').val() === 'tempo';
                    $('#editTanggalTempoContainer').toggleClass('d-none', !isTempo);
                    $('#editTanggalTempo').prop('required', isTempo);
                    if (!isTempo) $('#editTanggalTempo').val('');
                }
                function generateNotaNo() {
                    const date = ($('#tanggalPembelian').val() || new Date().toISOString().split('T')[0]).replaceAll('-', '');
                    return `BLI-${date}-${Math.floor(Math.random() * 90000) + 10000}`;
                }
                function kodeTransaksiSelect(name, selectedId = '', mode = 'create') {
                    let options = '<option value="">-- Pilih Kode Transaksi --</option>';
                    kodeTransaksiOptions.forEach(item => {
                        options += `<option value="${item.id}" ${String(selectedId) === String(item.id) ? 'selected' : ''}>${item.label}</option>`;
                    });
                    const selectClass = mode === 'create' ? 'kode-transaksi-select' : 'edit-kode-transaksi-select';
                    return `<select class="form-select form-select-sm ${selectClass}" name="${name}" required style="width:100%;">${options}</select>`;
                }
                function initKodeSelect2(container, mode = 'create') {
                    const selector = mode === 'create' ? '.kode-transaksi-select' : '.edit-kode-transaksi-select';
                    const dropdownParent = mode === 'create' ? $('#modalPembelian') : $('#modalEditPembelian');
                    $(container).find(selector).each(function() {
                        if ($(this).data('select2')) $(this).select2('destroy');
                        $(this).select2({ dropdownParent, width: '100%', placeholder: '-- Pilih Kode Transaksi --', allowClear: true });
                    });
                }
                function buildRow(index, mode = 'create', data = {}) {
                    const qty = data.jml || data.qty || 1;
                    const harga = data.nominal || data.harga_beli || 0;
                    const total = data.total || (qty * harga);
                    return `
                        <tr>
                            <td>${kodeTransaksiSelect(`transactions[${index}][idkodetransaksi]`, data.idkodetransaksi || '', mode)}</td>
                            <td><input type="text" class="form-control form-control-sm description" name="transactions[${index}][description]" value="${data.description || ''}" required></td>
                            <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[${index}][qty]" value="${qty}" min="0.01" step="0.01"></td>
                            <td><input type="number" class="form-control form-control-sm text-end harga-beli" name="transactions[${index}][harga_beli]" value="${harga}" min="0"></td>
                            <td><input type="text" class="form-control form-control-sm text-end total" value="${formatRupiah(total)}" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                        </tr>
                    `;
                }
                function recalcTable(tableSelector, totalSelector) {
                    let grandTotal = 0;
                    $(`${tableSelector} tbody tr`).each(function() {
                        const qty = parseFloat($(this).find('.qty').val()) || 0;
                        const harga = parseFloat($(this).find('.harga-beli').val()) || 0;
                        const total = qty * harga;
                        $(this).find('.total').val(formatRupiah(total));
                        grandTotal += total;
                    });
                    $(totalSelector).val(formatRupiah(grandTotal));
                }
                function buildBuktiLink(path) {
                    if (!path) return '-';
                    return `<a href="{{ asset('storage') }}/${path}" target="_blank">Lihat File</a>`;
                }
                function loadLogs(id, targetSelector) {
                    $.get(`/toko/${id}/logs`, function(res) {
                        if (res.success && res.data.length) {
                            let html = '';
                            res.data.forEach(function(log) {
                                html += `<div class="border rounded p-2 mb-2"><small class="text-muted d-block mb-1">${new Date(log.created_at).toLocaleString('id-ID')}</small><div class="small">${log.update_log}</div></div>`;
                            });
                            $(targetSelector).html(html);
                        } else {
                            $(targetSelector).html('<p class="text-muted small mb-0">Tidak ada riwayat perubahan</p>');
                        }
                    }).fail(function() {
                        $(targetSelector).html('<p class="text-muted small mb-0">Gagal memuat riwayat perubahan</p>');
                    });
                }
                function resetCreateForm() {
                    $('#frmPembelian')[0].reset();
                    $('#tanggalPembelian').val(new Date().toISOString().split('T')[0]);
                    $('#notaNo').val(generateNotaNo());
                    $('#namaTransaksi').val('Pembelian Barang');
                    createRowIndex = 0;
                    $('#tblDetailBarang tbody').html(buildRow(0, 'create'));
                    setTimeout(() => initKodeSelect2('#tblDetailBarang', 'create'), 50);
                    recalcTable('#tblDetailBarang', '#grandTotal');
                    toggleCreateTempo();
                    setSaldo('#idRekening', '#saldoInfo');
                }
                function resetEditForm() {
                    $('#frmEditPembelian')[0].reset();
                    $('#tblEditDetailBarang tbody').empty();
                    $('#editBuktiNotaInfo').html('');
                    $('#editLogContainer').html('<p class="text-muted small mb-0">Tidak ada riwayat perubahan</p>');
                    editRowIndex = 0;
                    recalcTable('#tblEditDetailBarang', '#editGrandTotal');
                    toggleEditTempo();
                    setSaldo('#editIdRekening', '#editSaldoInfo');
                }

                const tbPembelian = $('#tbPembelian').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('toko.pembelian.data') }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center' },
                        { data: 'nota_no', name: 'nota_no' },
                        { data: 'namatransaksi', name: 'namatransaksi' },
                        { data: 'tanggal', name: 'tanggal', className: 'text-center' },
                        { data: 'vendor.namavendor', name: 'vendor.namavendor', render: data => data || '-' },
                        { data: 'total', name: 'total', className: 'text-end' },
                        { data: 'status', name: 'status', className: 'text-center' },
                        { data: 'namauser', name: 'namauser', className: 'text-center' },
                        {
                            data: 'action', orderable: false, searchable: false, className: 'text-center',
                            render: function(data, type, row) {
                                return `<div class="btn-group">
                                    <button class="btn btn-sm btn-info view-btn" data-id="${row.id}"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}"><i class="bi bi-trash"></i></button>
                                </div>`;
                            }
                        }
                    ],
                    order: [[3, 'desc']]
                });

                $('.select2-modal').select2({ dropdownParent: $('#modalPembelian'), width: '100%' });
                $('.select2-edit').select2({ dropdownParent: $('#modalEditPembelian'), width: '100%' });

                $('#btnTambahPembelian').on('click', function() { resetCreateForm(); $('#modalPembelian').modal('show'); });
                $('#tanggalPembelian').on('change', function() { $('#notaNo').val(generateNotaNo()); });
                $('#paymentMethod').on('change', toggleCreateTempo);
                $('#editPaymentMethod').on('change', toggleEditTempo);
                $('#idRekening').on('change', function() { setSaldo('#idRekening', '#saldoInfo'); });
                $('#editIdRekening').on('change', function() { setSaldo('#editIdRekening', '#editSaldoInfo'); });

                $('#addBarangRow').on('click', function() {
                    createRowIndex++;
                    $('#tblDetailBarang tbody').append(buildRow(createRowIndex, 'create'));
                    initKodeSelect2('#tblDetailBarang tbody tr:last', 'create');
                });
                $('#addEditBarangRow').on('click', function() {
                    editRowIndex++;
                    $('#tblEditDetailBarang tbody').append(buildRow(editRowIndex, 'edit'));
                    initKodeSelect2('#tblEditDetailBarang tbody tr:last', 'edit');
                });
                $(document).on('click', '.removeRow', function() {
                    const table = $(this).closest('table');
                    if (table.find('tbody tr').length > 1) {
                        $(this).closest('tr').remove();
                        recalcTable(table.attr('id') === 'tblEditDetailBarang' ? '#tblEditDetailBarang' : '#tblDetailBarang', table.attr('id') === 'tblEditDetailBarang' ? '#editGrandTotal' : '#grandTotal');
                    }
                });
                $(document).on('input', '.qty, .harga-beli', function() {
                    if ($(this).closest('#tblEditDetailBarang').length) recalcTable('#tblEditDetailBarang', '#editGrandTotal');
                    else recalcTable('#tblDetailBarang', '#grandTotal');
                });

                $(document).on('click', '.delete-btn', function() {
                    const id = $(this).data('id');
                    Swal.fire({
                        title: 'Konfirmasi Hapus',
                        text: 'Apakah Anda yakin ingin menghapus transaksi ini?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (!result.isConfirmed) return;
                        $.ajax({
                            url: `/toko/${id}`,
                            type: 'DELETE',
                            data: { _token: "{{ csrf_token() }}" },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Berhasil!', res.message, 'success');
                                    tbPembelian.ajax.reload();
                                } else {
                                    Swal.fire('Error!', res.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                            }
                        });
                    });
                });

                $('#frmPembelian').on('submit', function(e) {
                    e.preventDefault();
                    setLoading('#btnSubmit', true);
                    $.ajax({
                        url: "{{ route('toko.pembelian.store') }}",
                        type: 'POST',
                        data: new FormData(this),
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            setLoading('#btnSubmit', false);
                            if (res.success) {
                                $('#modalPembelian').modal('hide');
                                tbPembelian.ajax.reload();
                                if (res.invoice_url) window.open(res.invoice_url, '_blank');
                                Swal.fire('Berhasil!', res.message, 'success');
                            } else {
                                Swal.fire('Error!', res.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            setLoading('#btnSubmit', false);
                            Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                        }
                    });
                });

                $(document).on('click', '.view-btn', function() {
                    const id = $(this).data('id');
                    $.get(`/toko/${id}`, function(res) {
                        if (!res.success) return Swal.fire('Error', res.message, 'error');
                        const nota = res.data;
                        $('#viewNotaNo').text(nota.nota_no || '-');
                        $('#viewTanggal').text(formatTanggal(nota.tanggal));
                        $('#viewNamaTransaksi').text(nota.namatransaksi || '-');
                        $('#viewProject').text(nota.project?.namaproject || '-');
                        $('#viewVendor').text(nota.vendor?.namavendor || '-');
                        $('#viewBuktiNota').html(buildBuktiLink(nota.bukti_nota));
                        $('#viewUser').text(nota.namauser || '-');
                        $('#viewRekening').text(nota.rekening ? `${nota.rekening.norek} - ${nota.rekening.namarek}` : '-');
                        $('#viewTotal').text(formatRupiah(nota.total));
                        $('#viewStatus').html(statusBadge(nota.status));
                        $('#viewCashflow').text(nota.cashflow === 'in' ? 'Masuk' : 'Keluar');
                        $('#viewPaymentMethod').text(nota.paymen_method === 'tempo' ? `Tempo${nota.tgl_tempo ? ' - ' + formatTanggal(nota.tgl_tempo) : ''}` : 'Cash');
                        $('#btnViewInvoicePdf').attr('href', `/toko/${nota.id}/invoice`);

                        let html = '';
                        (nota.transactions || []).forEach(function(item) {
                            const kode = item.kode_transaksi ? `(${item.kode_transaksi.kodetransaksi}) ${item.kode_transaksi.transaksi}` : '-';
                            html += `<tr>
                                <td>${kode}</td>
                                <td>${item.description || '-'}</td>
                                <td class="text-center">${item.jml || 0}</td>
                                <td class="text-end">${formatRupiah(item.nominal)}</td>
                                <td class="text-end">${formatRupiah(item.total)}</td>
                            </tr>`;
                        });
                        $('#tblViewDetail tbody').html(html || '<tr><td colspan="5" class="text-center text-muted">Tidak ada item</td></tr>');
                        $('#viewSubtotal').text(formatRupiah(nota.subtotal));
                        $('#viewGrandTotal').text(formatRupiah(nota.total));
                        loadLogs(id, '#viewLogContainer');
                        $('#modalViewPembelian').modal('show');
                    }).fail(function() {
                        Swal.fire('Error', 'Gagal memuat data nota', 'error');
                    });
                });

                $(document).on('click', '.edit-btn', function() {
                    const id = $(this).data('id');
                    $.get(`/toko/${id}/edit`, function(res) {
                        if (!res.success) return Swal.fire('Error', res.message, 'error');
                        const nota = res.data;
                        resetEditForm();
                        $('#editIdPembelian').val(nota.id);
                        $('#editNotaNo').val(nota.nota_no);
                        $('#editNamaTransaksi').val(nota.namatransaksi);
                        $('#editTanggalPembelian').val(String(nota.tanggal).substring(0, 10));
                        $('#editVendorId').val(nota.vendor_id).trigger('change');
                        $('#editPaymentMethod').val(nota.paymen_method || 'cash').trigger('change');
                        $('#editTanggalTempo').val(nota.tgl_tempo ? String(nota.tgl_tempo).substring(0, 10) : '');
                        $('#editIdRekening').val(nota.idrek).trigger('change');
                        $('#editBuktiNotaInfo').html(nota.bukti_nota ? `File saat ini: ${buildBuktiLink(nota.bukti_nota)}` : 'Belum ada file bukti nota.');

                        let html = '';
                        (nota.transactions || []).forEach(function(item, index) {
                            html += buildRow(index, 'edit', item);
                            editRowIndex = index;
                        });
                        $('#tblEditDetailBarang tbody').html(html || buildRow(0, 'edit'));
                        setTimeout(() => initKodeSelect2('#tblEditDetailBarang', 'edit'), 50);
                        recalcTable('#tblEditDetailBarang', '#editGrandTotal');
                        loadLogs(id, '#editLogContainer');
                        $('#modalEditPembelian').modal('show');
                    }).fail(function() {
                        Swal.fire('Error', 'Gagal memuat data untuk edit', 'error');
                    });
                });

                $('#frmEditPembelian').on('submit', function(e) {
                    e.preventDefault();
                    const id = $('#editIdPembelian').val();
                    setLoading('#btnEditSubmit', true);
                    $.ajax({
                        url: `/toko/${id}`,
                        type: 'POST',
                        data: new FormData(this),
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            setLoading('#btnEditSubmit', false);
                            if (res.success) {
                                $('#modalEditPembelian').modal('hide');
                                tbPembelian.ajax.reload();
                                if (res.invoice_url) window.open(res.invoice_url, '_blank');
                                Swal.fire('Berhasil!', res.message, 'success');
                            } else {
                                Swal.fire('Error!', res.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            setLoading('#btnEditSubmit', false);
                            Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                        }
                    });
                });

                $('#modalPembelian').on('shown.bs.modal', function() { initKodeSelect2('#tblDetailBarang', 'create'); });
                $('#modalEditPembelian').on('shown.bs.modal', function() { initKodeSelect2('#tblEditDetailBarang', 'edit'); });

                resetCreateForm();
            });
        </script>
    </x-slot>
</x-app-layout>
