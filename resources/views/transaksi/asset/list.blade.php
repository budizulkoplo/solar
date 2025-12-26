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
                        <button class="btn btn-success" id="btnGenerateDepreciation">
                            <i class="bi bi-calculator"></i> Generate Penyusutan
                        </button>
                        <button class="btn btn-primary" id="btnExportAsset">
                            <i class="bi bi-download"></i> Export
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
            <div class="card mb-4">
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
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Periode (Tahun-Bulan) *</label>
                            <input type="month" class="form-control" name="periode" 
                                   value="{{ date('Y-m') }}" required>
                            <small class="text-muted">Pilih periode untuk generate penyusutan</small>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Sistem akan generate penyusutan untuk semua asset aktif
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
            
            // Format number
            function formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
            
            // Parse number
            function parseNumber(str) {
                return parseFloat(str.replace(/[^\d.-]/g, '')) || 0;
            }
            
            // Initialize DataTable
            function initDataTable() {
                if (tbAssets) {
                    tbAssets.destroy();
                }
                
                tbAssets = $('#tbAssets').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('transaksi.asset.list.getdata') }}",
                        data: function(d) {
                            d.status = $('#filterStatus').val();
                            d.metode = $('#filterMetode').val();
                            d.date_from = $('#filterDateFrom').val();
                            d.date_to = $('#filterDateTo').val();
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
                            name: 'tanggal_pembelian',
                            className: 'text-center',
                            render: function(data) {
                                return new Date(data).toLocaleDateString('id-ID');
                            }
                        },
                        { 
                            data: 'umur_ekonomis', 
                            name: 'umur_ekonomis',
                            className: 'text-center'
                        },
                        { 
                            data: 'harga_perolehan', 
                            name: 'harga_perolehan'
                           
                        },
                        { 
                            data: 'nilai_buku', 
                            name: 'nilai_buku'
                            
                        },
                        { 
                            data: 'akumulasi_susut', 
                            name: 'akumulasi_susut',
                            className: 'text-end',
                            render: function(data) {
                                return 'Rp ' + formatNumber(parseNumber(data));
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
                        date_from: $('#filterDateFrom').val(),
                        date_to: $('#filterDateTo').val()
                    },
                    success: function(res) {
                        if (res.summary) {
                            $('#totalAssetCount').text(res.summary.total_assets);
                            $('#totalAssetValue').text('Rp ' + formatNumber(res.summary.total_value));
                            $('#totalBookValue').text('Rp ' + formatNumber(res.summary.total_book_value));
                            $('#totalDepreciation').text('Rp ' + formatNumber(res.summary.total_depreciation));
                        }
                    }
                });
            }
            
            // Apply filters
            $('#filterStatus, #filterMetode, #filterDateFrom, #filterDateTo').change(function() {
                initDataTable();
            });
            
            // Generate depreciation
            $('#btnGenerateDepreciation').click(function() {
                $('#modalGenerateDepreciation').modal('show');
            });
            
            // Form generate depreciation
            $('#frmGenerateDepreciation').submit(function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Generate Penyusutan?',
                    text: "Apakah Anda yakin ingin generate penyusutan untuk periode ini?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Generate!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.post("{{ route('transaksi.asset.generate.depreciation') }}", {
                            _token: '{{ csrf_token() }}',
                            periode: $('input[name="periode"]').val()
                        }).then(response => {
                            if (!response.success) {
                                throw new Error(response.message);
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Error: ${error}`
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
                                            <td>${new Date(asset.tanggal_pembelian).toLocaleDateString('id-ID')}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Mulai Susut</th>
                                            <td>${new Date(asset.tanggal_mulai_susut).toLocaleDateString('id-ID')}</td>
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
                                            <td><span class="badge ${asset.status === 'aktif' ? 'bg-success' : 'bg-warning'}">${asset.status}</span></td>
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
                                    <td>Rp ${formatNumber(asset.calculate_monthly_depreciation || 0)}</td>
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
                                                    <td>${new Date(dep.periode).toLocaleDateString('id-ID', {month: 'long', year: 'numeric'})}</td>
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
                    }
                });
            });
            
            // Edit asset
            $(document).on('click', '.edit-asset', function() {
                let assetId = $(this).data('id');
                
                $.get(`/transaksi/asset/${assetId}/edit`, function(res) {
                    if (res.success) {
                        let asset = res.asset;
                        $('#editAssetId').val(asset.id);
                        $('#editKodeAsset').val(asset.kode_aset);
                        $('#editNamaAsset').val(asset.nama_aset);
                        $('#editTanggalSusut').val(asset.tanggal_mulai_susut);
                        $('#editUmurEkonomis').val(asset.umur_ekonomis);
                        $('#editNilaiResidu').val(asset.nilai_residu);
                        $('#editMetodePenyusutan').val(asset.metode_penyusutan);
                        $('#editPersentaseSusut').val(asset.persentase_susut || '');
                        $('#editStatus').val(asset.status);
                        $('#editLokasi').val(asset.lokasi || '');
                        $('#editPic').val(asset.pic || '');
                        $('#editKeterangan').val(asset.keterangan || '');
                        
                        $('#modalEditAsset').modal('show');
                    }
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
                                `Error: ${error}`
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
            
            // Export asset
            $('#btnExportAsset').click(function() {
                let params = new URLSearchParams({
                    status: $('#filterStatus').val(),
                    metode: $('#filterMetode').val(),
                    date_from: $('#filterDateFrom').val(),
                    date_to: $('#filterDateTo').val(),
                    _token: '{{ csrf_token() }}'
                }).toString();
                
                window.open(`/transaksi/asset/export?${params}`, '_blank');
            });
            
            // Initialize
            initDataTable();
            
            // Set default dates
            let today = new Date();
            let firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            $('#filterDateFrom').val(firstDay.toISOString().split('T')[0]);
            $('#filterDateTo').val(today.toISOString().split('T')[0]);
        });
        </script>
    </x-slot>
</x-app-layout>