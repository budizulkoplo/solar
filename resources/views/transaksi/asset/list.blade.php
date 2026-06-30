<x-app-layout>
    <x-slot name="pagetitle">Daftar Asset Tetap</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Daftar Asset Tetap</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="btn-group">
                        <a href="{{ route('transaksi.asset.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali ke Transaksi
                        </a>
                        <button class="btn btn-primary" id="btnExportAsset">
                            <i class="bi bi-download"></i> Download Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
    <div class="container-fluid">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-0">Total Asset</h6>
                                <h4 class="mb-0" id="totalAssetCount">0</h4>
                            </div>
                            <div class="rounded-circle bg-primary p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-box-seam fs-4 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-0">Total Nilai Asset</h6>
                                <h4 class="mb-0" id="totalAssetValue">Rp 0</h4>
                            </div>
                            <div class="rounded-circle bg-success p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-currency-dollar fs-4 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-0">Nilai Buku</h6>
                                <h4 class="mb-0" id="totalBookValue">Rp 0</h4>
                            </div>
                            <div class="rounded-circle bg-info p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-journal-bookmark fs-4 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-0">Akumulasi Penyusutan</h6>
                                <h4 class="mb-0" id="totalDepreciation">Rp 0</h4>
                            </div>
                            <div class="rounded-circle bg-warning p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-graph-down fs-4 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Filters -->
            <div class="card mb-4" id="assetFilterCard">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-filter"></i> Filter
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="filterStatus">
                                <option value="">Semua Status</option>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non Aktif</option>
                                <option value="terjual">Terjual</option>
                                <option value="hilang">Hilang</option>
                                <option value="rusak">Rusak</option>
                                <option value="disewakan">Disewakan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Metode Penyusutan</label>
                            <select class="form-select" id="filterMetode">
                                <option value="">Semua Metode</option>
                                <option value="garis_lurus">Garis Lurus</option>
                                <option value="saldo_menurun">Saldo Menurun</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Dari</label>
                            <input type="date" class="form-control" id="filterDateFrom">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Sampai</label>
                            <input type="date" class="form-control" id="filterDateTo">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Periode Susut Awal</label>
                            <input type="month" class="form-control" id="filterPeriodeAwal" value="{{ date('Y-m') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Periode Susut Akhir</label>
                            <input type="month" class="form-control" id="filterPeriodeAkhir" value="{{ date('Y-m') }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-success w-100" id="btnGenerateFilteredDepreciation">
                                <i class="bi bi-calculator"></i> Susutkan dari Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table"></i> Daftar Asset
                    </h5>
                </div>
                <div class="card-body">
                    <table id="tbAssets" class="table table-sm table-striped w-100">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Kode Asset</th>
                                <th>Nama Asset</th>
                                <th class="text-center">Tanggal Beli</th>
                                <th class="text-center">Umur (bln)</th>
                                <th class="text-end">Harga Perolehan</th>
                                <th class="text-end">Nilai Buku</th>
                                <th class="text-end">Akum. Susut</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDisposeAsset" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Penghapusan Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="frmDisposeAsset">
                    @csrf
                    <input type="hidden" id="disposeAssetId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Asset</label>
                                <input type="text" class="form-control" id="disposeAssetName" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Penghapusan *</label>
                                <select class="form-select" name="tipe_penghapusan" id="disposeType" required>
                                    <option value="rusak">Rusak</option>
                                    <option value="terjual">Terjual</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control" name="tanggal" id="disposeDate" required>
                            </div>
                            <div class="col-md-6 dispose-sale-only" style="display:none;">
                                <label class="form-label">Pihak Pembeli</label>
                                <input type="text" class="form-control" name="pihak_terkait" id="disposeBuyer">
                            </div>
                            <div class="col-md-6 dispose-sale-only" style="display:none;">
                                <label class="form-label">Nilai Jual</label>
                                <input type="number" class="form-control" name="nilai" id="disposeValue" min="0" step="0.01">
                            </div>
                            <div class="col-md-6 dispose-sale-only" style="display:none;">
                                <label class="form-label">Kode Transaksi</label>
                                <select class="form-select select2-asset" name="idkodetransaksi" id="disposeKodeTransaksi">
                                    <option value="">-- Pilih Kode Transaksi --</option>
                                    @foreach($kodeTransaksi as $kt)
                                        <option value="{{ $kt->id }}">{{ $kt->kodetransaksi }} - {{ $kt->transaksi }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 dispose-sale-only" style="display:none;">
                                <label class="form-label">Rekening</label>
                                <select class="form-select select2-asset" name="idrek" id="disposeRekening">
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach($rekenings as $rek)
                                        <option value="{{ $rek->idrek }}">{{ $rek->norek }} - {{ $rek->namarek }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 dispose-sale-only" style="display:none;">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="paymen_method" id="disposePaymentMethod">
                                    <option value="cash">Cash</option>
                                    <option value="tempo">Tempo</option>
                                </select>
                            </div>
                            <div class="col-md-6 dispose-sale-only" id="disposeTempoContainer" style="display:none;">
                                <label class="form-label">Tanggal Tempo</label>
                                <input type="date" class="form-control" name="tgl_tempo" id="disposeTempoDate">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <textarea class="form-control" name="keterangan" id="disposeKeterangan" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Simpan Penghapusan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalRentAsset" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sewa Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="frmRentAsset">
                    @csrf
                    <input type="hidden" id="rentAssetId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Asset</label>
                                <input type="text" class="form-control" id="rentAssetName" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Nota *</label>
                                <input type="date" class="form-control" name="tanggal" id="rentDate" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Penyewa *</label>
                                <input type="text" class="form-control" name="penyewa" id="rentPenyewa" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mulai Sewa *</label>
                                <input type="date" class="form-control" name="tanggal_mulai" id="rentStartDate" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Selesai Sewa *</label>
                                <input type="date" class="form-control" name="tanggal_selesai" id="rentEndDate" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Transaksi *</label>
                                <select class="form-select select2-asset" name="idkodetransaksi" id="rentKodeTransaksi" required>
                                    <option value="">-- Pilih Kode Transaksi --</option>
                                    @foreach($kodeTransaksi as $kt)
                                        <option value="{{ $kt->id }}">{{ $kt->kodetransaksi }} - {{ $kt->transaksi }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nilai Sewa *</label>
                                <input type="number" class="form-control" name="nilai_sewa" id="rentValue" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select select2-asset" name="idrek" id="rentRekening" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach($rekenings as $rek)
                                        <option value="{{ $rek->idrek }}">{{ $rek->norek }} - {{ $rek->namarek }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method *</label>
                                <select class="form-select" name="paymen_method" id="rentPaymentMethod" required>
                                    <option value="cash">Cash</option>
                                    <option value="tempo">Tempo</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="rentTempoContainer" style="display:none;">
                                <label class="form-label">Tanggal Tempo</label>
                                <input type="date" class="form-control" name="tgl_tempo" id="rentTempoDate">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <textarea class="form-control" name="keterangan" id="rentKeterangan" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Sewa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Generate Penyusutan -->
    <div class="modal fade" id="modalGenerateDepreciation" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Penyusutan Bulanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="frmGenerateDepreciation">
                    @csrf
                    <input type="hidden" id="depreciationAssetId" name="asset_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Asset</label>
                            <input type="text" class="form-control" id="depreciationAssetName" readonly>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Periode Awal *</label>
                                <input type="month" class="form-control" name="periode_awal" id="periodeAwal"
                                       value="{{ date('Y-m') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Periode Akhir *</label>
                                <input type="month" class="form-control" name="periode_akhir" id="periodeAkhir"
                                       value="{{ date('Y-m') }}" required>
                            </div>
                            <div class="col-12">
                                <small class="text-muted">Pilih rentang bulan dan tahun untuk generate penyusutan.</small>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <span id="depreciationHelpText">Sistem akan generate penyusutan hanya untuk asset yang dipilih.</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-calculator"></i> Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail Asset -->
    <div class="modal fade" id="modalDetailAsset" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="assetDetailContent">
                    <!-- Konten akan diisi oleh JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Asset -->
    <div class="modal fade" id="modalEditAsset" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="frmEditAsset">
                    @csrf
                    <input type="hidden" name="id" id="editAssetId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kode Asset</label>
                                <input type="text" class="form-control" id="editKodeAsset" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Asset *</label>
                                <input type="text" class="form-control" name="nama_aset" id="editNamaAsset" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai Susut</label>
                                <input type="date" class="form-control" name="tanggal_mulai_susut" id="editTanggalSusut" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Umur Ekonomis (bulan) *</label>
                                <input type="number" class="form-control" name="umur_ekonomis" id="editUmurEkonomis" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nilai Residu</label>
                                <input type="number" class="form-control" name="nilai_residu" id="editNilaiResidu" min="0" step="1000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode Penyusutan</label>
                                <select class="form-select" name="metode_penyusutan" id="editMetodePenyusutan">
                                    <option value="garis_lurus">Garis Lurus</option>
                                    <option value="saldo_menurun">Saldo Menurun</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Persentase Susut (%)</label>
                                <input type="number" class="form-control" name="persentase_susut" id="editPersentaseSusut" min="0" max="100" step="0.1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="editStatus">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Non Aktif</option>
                                    <option value="terjual">Terjual</option>
                                    <option value="hilang">Hilang</option>
                                    <option value="rusak">Rusak</option>
                                    <option value="disewakan">Disewakan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lokasi</label>
                                <input type="text" class="form-control" name="lokasi" id="editLokasi">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">PIC</label>
                                <input type="text" class="form-control" name="pic" id="editPic">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <textarea class="form-control" name="keterangan" id="editKeterangan" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
            let tbAssets = null;
            const urlParams = new URLSearchParams(window.location.search);
            const notaIdFilter = urlParams.get('nota_id') || '';
            let today = new Date();
            let firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

            if (!notaIdFilter) {
                $('#filterDateFrom').val(firstDay.toISOString().split('T')[0]);
                $('#filterDateTo').val(today.toISOString().split('T')[0]);
            } else {
                $('#assetFilterCard').hide();
            }

            function initSelect2Asset() {
                if ($.fn.select2) {
                    $('.select2-asset').each(function() {
                        $(this).select2({
                            width: '100%',
                            dropdownParent: $(this).closest('.modal')
                        });
                    });
                }
            }

            function resetDisposeForm() {
                $('#frmDisposeAsset')[0].reset();
                $('#disposeAssetId').val('');
                $('#disposeDate').val(new Date().toISOString().split('T')[0]);
                $('#disposeTempoContainer').hide();
                $('.dispose-sale-only').hide();
                $('#disposeKodeTransaksi, #disposeRekening').val('').trigger('change');
            }

            function resetRentForm() {
                $('#frmRentAsset')[0].reset();
                $('#rentAssetId').val('');
                let today = new Date().toISOString().split('T')[0];
                $('#rentDate, #rentStartDate, #rentEndDate').val(today);
                $('#rentTempoContainer').hide();
                $('#rentKodeTransaksi, #rentRekening').val('').trigger('change');
            }

            function toggleDisposeType() {
                let isSale = $('#disposeType').val() === 'terjual';
                $('.dispose-sale-only').toggle(isSale);
                $('#disposeKodeTransaksi, #disposeRekening, #disposePaymentMethod, #disposeValue').prop('required', isSale);
                $('#disposeBuyer').prop('required', isSale);
            }
            
            // Format number
            function formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }

            let depreciationMode = 'single';
            
            // Parse number
            function parseNumber(str) {
                return parseFloat(str.replace(/[^\d.-]/g, '')) || 0;
            }
            
            // Initialize DataTable
            function initDataTable() {
                if (tbAssets) {
                    tbAssets.ajax.reload(null, true);
                    return;
                }
                
                tbAssets = $('#tbAssets').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    stateSave: true,
                    ajax: {
                        url: "{{ route('transaksi.asset.list.getdata') }}",
                        data: function(d) {
                            d.status = $('#filterStatus').val();
                            d.metode = $('#filterMetode').val();
                            d.date_from = notaIdFilter ? '' : $('#filterDateFrom').val();
                            d.date_to = notaIdFilter ? '' : $('#filterDateTo').val();
                            d.nota_id = notaIdFilter;
                        }
                    },
                    columns: [
                        { 
                            data: 'DT_RowIndex', 
                            orderable: false, 
                            searchable: false, 
                            className: 'text-center' 
                        },
                        { data: 'kode_aset', name: 'kode_aset' },
                        { data: 'nama_aset', name: 'nama_aset' },
                        { 
                            data: 'tanggal_pembelian', 
                            name: 'tanggal_pembelian'
                        },
                        { 
                            data: 'umur_ekonomis', 
                            name: 'umur_ekonomis',
                            className: 'text-center'
                        },
                        { 
                            data: 'harga_perolehan', 
                            name: 'harga_perolehan',
                            className: 'text-end',
                            render: function(data, type) {
                                if (type === 'display' || type === 'filter') {
                                    return 'Rp ' + formatNumber(data || 0);
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'nilai_buku', 
                            name: 'nilai_buku',
                            orderable: false,
                            searchable: false,
                            className: 'text-end',
                            render: function(data, type) {
                                if (type === 'display' || type === 'filter') {
                                    return 'Rp ' + formatNumber(data || 0);
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'akumulasi_susut', 
                            name: 'akumulasi_susut',
                            orderable: false,
                            searchable: false,
                            className: 'text-end',
                            render: function(data, type) {
                                if (type === 'display' || type === 'filter') {
                                    return 'Rp ' + formatNumber(data || 0);
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'status', 
                            name: 'status',
                            className: 'text-center'
                        },
                        { 
                            data: 'action', 
                            orderable: false, 
                            searchable: false, 
                            className: 'text-center'
                        }
                    ],
                    drawCallback: function(settings) {
                        // Update summary
                        updateSummary();
                    }
                });
            }
            
            // Update summary
            function updateSummary() {
                $.ajax({
                    url: "{{ route('transaksi.asset.list.getdata') }}",
                    data: {
                        summary_only: true,
                        status: $('#filterStatus').val(),
                        metode: $('#filterMetode').val(),
                        date_from: notaIdFilter ? '' : $('#filterDateFrom').val(),
                        date_to: notaIdFilter ? '' : $('#filterDateTo').val(),
                        nota_id: notaIdFilter
                    },
                    success: function(res) {
                        if (res.summary) {
                            $('#totalAssetCount').text(res.summary.total_assets || 0);
                            $('#totalAssetValue').text('Rp ' + formatNumber(res.summary.total_value || 0));
                            $('#totalBookValue').text('Rp ' + formatNumber(res.summary.total_book_value || 0));
                            $('#totalDepreciation').text('Rp ' + formatNumber(res.summary.total_depreciation || 0));
                        }
                    }
                });
            }
            
            // Apply filters
            $('#filterStatus, #filterMetode, #filterDateFrom, #filterDateTo').change(function() {
                initDataTable();
            });
            
            // Generate depreciation per asset
            $(document).on('click', '.generate-depreciation-asset', function() {
                depreciationMode = 'single';
                $('#depreciationAssetId').val($(this).data('id'));
                $('#depreciationAssetName').val($(this).data('name'));
                $('#depreciationHelpText').text('Sistem akan generate penyusutan hanya untuk asset yang dipilih.');
                $('#periodeAwal').val($('#filterPeriodeAwal').val() || '{{ date('Y-m') }}');
                $('#periodeAkhir').val($('#filterPeriodeAkhir').val() || '{{ date('Y-m') }}');
                $('#modalGenerateDepreciation .modal-title').text('Generate Penyusutan Bulanan');
                $('#modalGenerateDepreciation').modal('show');
            });

            $('#btnGenerateFilteredDepreciation').on('click', function() {
                depreciationMode = 'global';
                $('#depreciationAssetId').val('');
                $('#depreciationAssetName').val('Semua asset aktif sesuai filter saat ini');
                $('#depreciationHelpText').text('Sistem akan generate penyusutan untuk semua asset aktif yang sesuai filter daftar asset.');
                $('#periodeAwal').val($('#filterPeriodeAwal').val() || '{{ date('Y-m') }}');
                $('#periodeAkhir').val($('#filterPeriodeAkhir').val() || '{{ date('Y-m') }}');
                $('#modalGenerateDepreciation .modal-title').text('Generate Penyusutan dari Filter');
                $('#modalGenerateDepreciation').modal('show');
            });
            
            // Form generate depreciation
            $('#frmGenerateDepreciation').submit(function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Generate Penyusutan?',
                    text: depreciationMode === 'global'
                        ? "Penyusutan semua asset aktif dari filter akan dikalkulasi ulang sesuai rentang periode yang dipilih."
                        : "Penyusutan asset ini akan dikalkulasi ulang sesuai rentang periode yang dipilih.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Generate!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        const payload = {
                            _token: '{{ csrf_token() }}',
                            periode_awal: $('#periodeAwal').val(),
                            periode_akhir: $('#periodeAkhir').val()
                        };

                        if (depreciationMode === 'global') {
                            payload.global = 1;
                            payload.status = $('#filterStatus').val();
                            payload.metode = $('#filterMetode').val();
                            payload.date_from = notaIdFilter ? '' : $('#filterDateFrom').val();
                            payload.date_to = notaIdFilter ? '' : $('#filterDateTo').val();
                            payload.nota_id = notaIdFilter;
                        } else {
                            payload.asset_id = $('#depreciationAssetId').val();
                        }

                        return $.post("{{ route('transaksi.asset.generate.depreciation') }}", {
                            ...payload
                        }).then(response => {
                            if (!response.success) {
                                throw new Error(response.message);
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Error: ${error.responseJSON?.message || error}`
                            );
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire('Berhasil!', result.value.message, 'success');
                        $('#modalGenerateDepreciation').modal('hide');
                        initDataTable();
                    }
                });
            });
            
            // View asset detail
            $(document).on('click', '.view-asset-detail', function() {
                let assetId = $(this).data('id');
                
                $.get(`/transaksi/asset/${assetId}/detail`, function(res) {
                    if (res.success) {
                        let asset = res.asset;
                        let statusClass = {
                            aktif: 'bg-success',
                            nonaktif: 'bg-warning',
                            terjual: 'bg-info',
                            hilang: 'bg-danger',
                            rusak: 'bg-danger',
                            disewakan: 'bg-primary'
                        };
                        let html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Kode Asset</th>
                                            <td>${asset.kode_aset}</td>
                                        </tr>
                                        <tr>
                                            <th>Nama Asset</th>
                                            <td>${asset.nama_aset}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Pembelian</th>
                                            <td>${asset.tanggal_pembelian ? new Date(asset.tanggal_pembelian).toLocaleDateString('id-ID') : '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Mulai Susut</th>
                                            <td>${asset.tanggal_mulai_susut ?? '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>Harga Perolehan</th>
                                            <td>Rp ${formatNumber(asset.harga_perolehan)}</td>
                                        </tr>
                                        <tr>
                                            <th>Nilai Residu</th>
                                            <td>Rp ${formatNumber(asset.nilai_residu)}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Umur Ekonomis</th>
                                            <td>${asset.umur_ekonomis} bulan (${(asset.umur_ekonomis/12).toFixed(1)} tahun)</td>
                                        </tr>
                                        <tr>
                                            <th>Metode Penyusutan</th>
                                            <td>${asset.metode_penyusutan}</td>
                                        </tr>
                                        ${asset.persentase_susut ? `<tr>
                                            <th>Persentase Susut</th>
                                            <td>${asset.persentase_susut}%</td>
                                        </tr>` : ''}
                                        <tr>
                                            <th>Status</th>
                                            <td><span class="badge ${statusClass[asset.status] || 'bg-secondary'}">${asset.status}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Lokasi</th>
                                            <td>${asset.lokasi || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>PIC</th>
                                            <td>${asset.pic || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>Kode Transaksi</th>
                                            <td>${asset.kode_transaksi || '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <h6 class="mt-4">Perhitungan Penyusutan</h6>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="30%">Nilai Buku Sekarang</th>
                                    <td class="text-success fw-bold">Rp ${formatNumber(asset.nilai_buku)}</td>
                                </tr>
                                <tr>
                                    <th>Akumulasi Penyusutan</th>
                                    <td class="text-danger">Rp ${formatNumber(asset.harga_perolehan - asset.nilai_buku)}</td>
                                </tr>
                                <tr>
                                    <th>Penyusutan per Bulan</th>
                                    <td>Rp ${formatNumber(res.calculate_monthly_depreciation || 0)}</td>
                                </tr>
                            </table>
                        `;
                        
                        if (asset.depreciations && asset.depreciations.length > 0) {
                            html += `
                                <h6 class="mt-4">Riwayat Penyusutan</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Periode</th>
                                                <th>Bulan Ke</th>
                                                <th class="text-end">Penyusutan</th>
                                                <th class="text-end">Akumulasi</th>
                                                <th class="text-end">Nilai Buku</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${asset.depreciations.map(dep => `
                                                <tr>
                                                    <td>${dep.periode ? new Date(dep.periode).toLocaleDateString('id-ID', {month: 'long', year: 'numeric'}) : '-'}</td>
                                                    <td>${dep.bulan_ke}</td>
                                                    <td class="text-end">Rp ${formatNumber(dep.nilai_penyusutan)}</td>
                                                    <td class="text-end">Rp ${formatNumber(dep.akumulasi_penyusutan)}</td>
                                                    <td class="text-end">Rp ${formatNumber(dep.nilai_buku)}</td>
                                                    <td><span class="badge ${dep.status === 'terposting' ? 'bg-success' : 'bg-warning'}">${dep.status}</span></td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        }
                        
                        $('#assetDetailContent').html(html);
                        $('#modalDetailAsset').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error', 'Gagal mengambil data asset', 'error');
                });
            });
            
            // Edit asset
            $(document).on('click', '.edit-asset', function() {
                let assetId = $(this).data('id');
                
                $.get(`/transaksi/asset/${assetId}/edit`, function(res) {
                    if (res.success) {
                        let asset = res.asset;
                        
                        // Log untuk debug
                        console.log('Asset data:', asset);
                        
                        $('#editAssetId').val(asset.id);
                        $('#editKodeAsset').val(asset.kode_aset || '');
                        $('#editNamaAsset').val(asset.nama_aset || '');
                        
                        // Pastikan format tanggal benar (Y-m-d)
                        if (asset.tanggal_mulai_susut) {
                            // Jika sudah dalam format Y-m-d, langsung gunakan
                            $('#editTanggalSusut').val(asset.tanggal_mulai_susut);
                        } else {
                            $('#editTanggalSusut').val('');
                        }
                        
                        $('#editUmurEkonomis').val(asset.umur_ekonomis || '');
                        $('#editNilaiResidu').val(asset.nilai_residu || 0);
                        $('#editMetodePenyusutan').val(asset.metode_penyusutan || 'garis_lurus');
                        $('#editPersentaseSusut').val(asset.persentase_susut || '');
                        $('#editStatus').val(asset.status || 'aktif');
                        $('#editLokasi').val(asset.lokasi || '');
                        $('#editPic').val(asset.pic || '');
                        $('#editKeterangan').val(asset.keterangan || '');
                        
                        // Trigger change untuk metode penyusutan (untuk show/hide persentase)
                        $('#editMetodePenyusutan').trigger('change');
                        
                        $('#modalEditAsset').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    console.error('Error response:', xhr.responseJSON);
                    Swal.fire('Error', 'Gagal mengambil data asset: ' + (xhr.responseJSON?.message || 'Unknown error'), 'error');
                });
            });
            
            // Form edit asset
            $('#frmEditAsset').submit(function(e) {
                e.preventDefault();
                let assetId = $('#editAssetId').val();
                
                Swal.fire({
                    title: 'Update Asset?',
                    text: "Apakah Anda yakin ingin mengupdate data asset ini?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Update!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: `/transaksi/asset/${assetId}/update`,
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                _method: 'PUT',
                                nama_aset: $('#editNamaAsset').val(),
                                tanggal_mulai_susut: $('#editTanggalSusut').val(),
                                umur_ekonomis: $('#editUmurEkonomis').val(),
                                nilai_residu: $('#editNilaiResidu').val(),
                                metode_penyusutan: $('#editMetodePenyusutan').val(),
                                persentase_susut: $('#editPersentaseSusut').val(),
                                status: $('#editStatus').val(),
                                lokasi: $('#editLokasi').val(),
                                pic: $('#editPic').val(),
                                keterangan: $('#editKeterangan').val()
                            }
                        }).then(response => {
                            if (!response.success) {
                                throw new Error(response.message);
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Error: ${error.responseJSON?.message || error}`
                            );
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire('Berhasil!', result.value.message, 'success');
                        $('#modalEditAsset').modal('hide');
                        initDataTable();
                    }
                });
            });

            $(document).on('click', '.dispose-asset', function() {
                resetDisposeForm();
                $('#disposeAssetId').val($(this).data('id'));
                $('#disposeAssetName').val($(this).data('name'));
                toggleDisposeType();
                $('#modalDisposeAsset').modal('show');
            });

            $(document).on('click', '.rent-asset', function() {
                resetRentForm();
                $('#rentAssetId').val($(this).data('id'));
                $('#rentAssetName').val($(this).data('name'));
                $('#modalRentAsset').modal('show');
            });

            $('#disposeType').change(function() {
                toggleDisposeType();
            });

            $('#disposePaymentMethod').change(function() {
                let showTempo = $(this).val() === 'tempo';
                $('#disposeTempoContainer').toggle(showTempo);
                $('#disposeTempoDate').prop('required', showTempo);
            });

            $('#rentPaymentMethod').change(function() {
                let showTempo = $(this).val() === 'tempo';
                $('#rentTempoContainer').toggle(showTempo);
                $('#rentTempoDate').prop('required', showTempo);
            });

            $('#frmDisposeAsset').submit(function(e) {
                e.preventDefault();
                const assetId = $('#disposeAssetId').val();

                $.ajax({
                    url: `/transaksi/asset/${assetId}/dispose`,
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Berhasil!', res.message, 'success');
                            $('#modalDisposeAsset').modal('hide');
                            initDataTable();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Gagal menyimpan penghapusan asset', 'error');
                    }
                });
            });

            $('#frmRentAsset').submit(function(e) {
                e.preventDefault();
                const assetId = $('#rentAssetId').val();

                $.ajax({
                    url: `/transaksi/asset/${assetId}/rent`,
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Berhasil!', res.message, 'success');
                            $('#modalRentAsset').modal('hide');
                            initDataTable();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Gagal menyimpan transaksi sewa asset', 'error');
                    }
                });
            });
            
            // Delete asset
            $(document).on('click', '.delete-asset', function() {
                let assetId = $(this).data('id');
                let assetName = $(this).data('name');
                
                Swal.fire({
                    title: 'Hapus Asset?',
                    html: `Apakah Anda yakin ingin menghapus asset <strong>${assetName}</strong>?<br><br>
                        <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> 
                        Asset yang sudah memiliki penyusutan terposting tidak dapat dihapus!</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: `/transaksi/asset/${assetId}/destroy`,
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                _method: 'DELETE'
                            }
                        }).then(response => {
                            if (!response.success) {
                                throw new Error(response.message);
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Error: ${error.responseJSON?.message || error}`
                            );
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire('Berhasil!', result.value.message, 'success');
                        initDataTable();
                    }
                });
            });
            
            // Export asset
            $('#btnExportAsset').click(function() {
                let params = new URLSearchParams({
                    status: $('#filterStatus').val(),
                    metode: $('#filterMetode').val(),
                    date_from: notaIdFilter ? '' : $('#filterDateFrom').val(),
                    date_to: notaIdFilter ? '' : $('#filterDateTo').val(),
                    nota_id: notaIdFilter
                }).toString();
                
                window.open(`{{ route('transaksi.asset.export') }}?${params}`, '_blank');
            });
            
            // Reset filters
            $('#btnResetFilter').click(function() {
                $('#filterStatus').val('');
                $('#filterMetode').val('');
                $('#filterDateFrom').val('');
                $('#filterDateTo').val('');
                initDataTable();
            });
            
            // Initialize
            initSelect2Asset();
            resetDisposeForm();
            resetRentForm();
            initDataTable();
        });
        </script>
    </x-slot>
</x-app-layout>
