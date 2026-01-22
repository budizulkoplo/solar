<x-app-layout>
    <x-slot name="pagetitle">Management Stock - Toko</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Management Stock Barang</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body p-3">
                                            <h5 class="card-title mb-1">Total Barang</h5>
                                            <h3 class="mb-0" id="totalBarang">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body p-3">
                                            <h5 class="card-title mb-1">Stock Cukup</h5>
                                            <h3 class="mb-0" id="stockCukup">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body p-3">
                                            <h5 class="card-title mb-1">Stock Menipis</h5>
                                            <h3 class="mb-0" id="stockMenipis">0</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body p-3">
                                            <h5 class="card-title mb-1">Stock Habis</h5>
                                            <h3 class="mb-0" id="stockHabis">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <!-- <button class="btn btn-sm btn-primary" id="btnExportStock">
                            <i class="bi bi-download"></i> Export Excel
                        </button> -->
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbStock" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nama Barang</th>
                                <th>Deskripsi</th>
                                <th class="text-end">Harga Beli</th>
                                <th class="text-end">Harga Jual</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Masuk</th>
                                <th class="text-center">Keluar</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adjust Stock -->
    <div class="modal fade" id="modalAdjustStock" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmAdjustStock">
                    @csrf
                    <input type="hidden" name="barang_id" id="adjustBarangId">

                    <div class="modal-header">
                        <h6 class="modal-title">Adjustment Stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Barang</label>
                            <input type="text" class="form-control" id="adjustNamaBarang" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Stock Saat Ini</label>
                            <input type="text" class="form-control" id="adjustStockSekarang" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipe Adjustment *</label>
                            <select class="form-select" name="tipe" id="adjustTipe" required>
                                <option value="">-- Pilih Tipe --</option>
                                <option value="masuk">Stock Masuk (+)</option>
                                <option value="keluar">Stock Keluar (-)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jumlah *</label>
                            <input type="number" class="form-control" name="qty" id="adjustQty" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Keterangan *</label>
                            <textarea class="form-control" name="keterangan" id="adjustKeterangan" rows="2" required placeholder="Contoh: Koreksi stock, barang rusak, dll"></textarea>
                        </div>
                        
                        <div class="alert alert-info p-2">
                            <i class="bi bi-info-circle"></i>
                            <small>Stock setelah adjustment: <span id="adjustStockSetelah" class="fw-bold">0</span></small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnAdjustSubmit">
                            <span class="submit-text">Simpan</span>
                            <span class="loading-text" style="display:none;">
                                <i class="bi bi-hourglass-split"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal History Stock -->
    <div class="modal fade" id="modalHistoryStock" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">History Stock Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <h6 id="historyBarangName" class="mb-3"></h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped" id="tbHistory">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tipe</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Sebelum</th>
                                    <th class="text-center">Sesudah</th>
                                    <th>Keterangan</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data akan diisi oleh JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal View Barang -->
    <div class="modal fade" id="modalViewBarang" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Detail Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Nama Barang</th>
                            <td id="viewNamaBarang">-</td>
                        </tr>
                        <tr>
                            <th>Harga Beli</th>
                            <td id="viewHargaBeli">-</td>
                        </tr>
                        <tr>
                            <th>Harga Jual</th>
                            <td id="viewHargaJual">-</td>
                        </tr>
                        <tr>
                            <th>Stock</th>
                            <td id="viewStock">-</td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td id="viewDeskripsi">-</td>
                        </tr>
                        <tr>
                            <th>Dibuat</th>
                            <td id="viewCreatedAt">-</td>
                        </tr>
                        <tr>
                            <th>Diupdate</th>
                            <td id="viewUpdatedAt">-</td>
                        </tr>
                    </table>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Barang -->
    <div class="modal fade" id="modalEditBarang" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmEditBarang">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="editBarangId">

                    <div class="modal-header">
                        <h6 class="modal-title">Edit Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Barang *</label>
                            <input type="text" class="form-control" name="nama_barang" id="editNamaBarang" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Harga Beli *</label>
                            <input type="number"  class="form-control" name="harga_beli" id="editHargaBeli" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Harga Jual *</label>
                            <input type="number"  class="form-control" name="harga_jual" id="editHargaJual" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" id="editDeskripsi" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnEditSubmit">
                            <span class="submit-text">Simpan Perubahan</span>
                            <span class="loading-text" style="display:none;">
                                <i class="bi bi-hourglass-split"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
    <script>
    // Helper function untuk format number
    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(num);
    }
    
    // Helper function untuk route dengan parameter
    function getRouteUrl(routeName, params) {
        let url = '';
        
        switch(routeName) {
            case 'toko.barang.detail':
                url = "{{ route('toko.barang.detail', ['id' => ':id']) }}";
                url = url.replace(':id', params.id);
                break;
            case 'toko.barang.update':
                url = "{{ route('toko.barang.update', ['id' => ':id']) }}";
                url = url.replace(':id', params.id);
                break;
            case 'toko.stock.history':
                url = "{{ route('toko.stock.history', ['barangId' => ':id']) }}";
                url = url.replace(':id', params.id);
                break;
            default:
                console.error('Route tidak ditemukan:', routeName);
        }
        
        return url;
    }

    $(document).ready(function() {
        // DataTable stock
        let tbStock = $('#tbStock').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('toko.stock.data') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center' },
                { data: 'nama_barang', name: 'nama_barang' },
                { data: 'deskripsi', name: 'deskripsi' },
                { 
                    data: 'harga_beli', 
                    name: 'harga_beli', 
                    className: 'text-end'
                },
                { 
                    data: 'harga_jual', 
                    name: 'harga_jual', 
                    className: 'text-end'
                },
                {
                    data: 'stock_project',
                    name: 'stock_project',
                    className: 'text-center',
                    render: function (data) {
                        const stock = parseInt(
                            String(data).replace(/\D/g, ''),
                            10
                        ) || 0;

                        const color = stock > 10
                            ? 'success'
                            : stock > 0
                                ? 'warning'
                                : 'danger';

                        return `<span class="badge bg-${color}">${stock}</span>`;
                    }
                },
                { 
                    data: 'total_masuk', 
                    name: 'total_masuk', 
                    className: 'text-center'
                },
                { 
                    data: 'total_keluar', 
                    name: 'total_keluar', 
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
                // Hitung statistik setelah data selesai load
                calculateStatistics();
            }
        });

        function normalizeStock(value) {
            if (value === null || value === undefined) return 0;
            return parseInt(String(value).replace(/\D/g, ''), 10) || 0;
        }

        // Hitung statistik
        function calculateStatistics() {
            let totalBarang = 0;
            let stockCukup = 0;
            let stockMenipis = 0;
            let stockHabis = 0;

            let data = tbStock.rows().data();
            data.each(function (value) {
                totalBarang++;

                const stock = normalizeStock(value.stock_project);

                if (stock > 10) {
                    stockCukup++;
                } else if (stock > 0) {
                    stockMenipis++;
                } else {
                    stockHabis++;
                }
            });

            $('#totalBarang').text(totalBarang);
            $('#stockCukup').text(stockCukup);
            $('#stockMenipis').text(stockMenipis);
            $('#stockHabis').text(stockHabis);
        }

        // View Barang
        $(document).on('click', '.view-barang-btn', function() {
            let barangId = $(this).data('id');
            
            let detailUrl = getRouteUrl('toko.barang.detail', { id: barangId });

            $.get(detailUrl, function(res) {
                if (res.success) {
                    let barang = res.data.barang;
                    let stock = res.data.stock;
                    
                    $('#viewNamaBarang').text(barang.nama_barang);
                    $('#viewHargaBeli').text('Rp ' + formatNumber(barang.harga_beli));
                    $('#viewHargaJual').text('Rp ' + formatNumber(barang.harga_jual));
                    $('#viewStock').html('<span class="badge ' + (stock > 10 ? 'bg-success' : (stock > 0 ? 'bg-warning' : 'bg-danger')) + '">' + stock + '</span>');
                    $('#viewDeskripsi').text(barang.deskripsi || '-');
                    $('#viewCreatedAt').text(new Date(barang.created_at).toLocaleString('id-ID'));
                    $('#viewUpdatedAt').text(new Date(barang.updated_at).toLocaleString('id-ID'));
                    
                    $('#modalViewBarang').modal('show');
                }
            }).fail(function() {
                Swal.fire('Error', 'Gagal mengambil data barang', 'error');
            });
        });

        // Edit Barang
        $(document).on('click', '.edit-barang-btn', function() {
            let barangId = $(this).data('id');
            
            let detailUrl = getRouteUrl('toko.barang.detail', { id: barangId });

            $.get(detailUrl, function(res) {
                if (res.success) {
                    let barang = res.data.barang;
                    
                    $('#editBarangId').val(barang.idbarang);
                    $('#editNamaBarang').val(barang.nama_barang);
                    $('#editHargaBeli').val(barang.harga_beli);
                    $('#editHargaJual').val(barang.harga_jual);
                    $('#editDeskripsi').val(barang.deskripsi || '');
                    
                    $('#modalEditBarang').modal('show');
                }
            }).fail(function() {
                Swal.fire('Error', 'Gagal mengambil data barang', 'error');
            });
        });

        // Submit edit barang
        $('#frmEditBarang').submit(function(e) {
            e.preventDefault();
            
            let formData = $(this).serialize();
            let barangId = $('#editBarangId').val();
            
            $('#btnEditSubmit').prop('disabled', true);
            $('.submit-text').hide();
            $('.loading-text').show();

            $.ajax({
                url: getRouteUrl('toko.barang.update', { id: barangId }),
                type: 'PUT',
                data: formData,
                success: function(res) {
                    $('#btnEditSubmit').prop('disabled', false);
                    $('.submit-text').show();
                    $('.loading-text').hide();
                    
                    if (res.success) {
                        $('#modalEditBarang').modal('hide');
                        tbStock.ajax.reload();
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

        // Adjust Stock
        $(document).on('click', '.adjust-stock-btn', function() {
            let barangId = $(this).data('id');
            
            let detailUrl = getRouteUrl('toko.barang.detail', { id: barangId });

            $.get(detailUrl, function(res) {
                if (res.success) {
                    let barang = res.data.barang;
                    let stock = res.data.stock;
                    
                    $('#adjustBarangId').val(barang.idbarang);
                    $('#adjustNamaBarang').val(barang.nama_barang);
                    $('#adjustStockSekarang').val(stock);
                    $('#adjustStockSetelah').text(stock);
                    $('#adjustQty').val('');
                    $('#adjustKeterangan').val('');
                    
                    $('#modalAdjustStock').modal('show');
                }
            }).fail(function() {
                Swal.fire('Error', 'Gagal mengambil data barang', 'error');
            });
        });

        // Hitung stock setelah adjustment
        $(document).on('input', '#adjustTipe, #adjustQty', function() {
            let stockSekarang = parseInt($('#adjustStockSekarang').val()) || 0;
            let tipe = $('#adjustTipe').val();
            let qty = parseInt($('#adjustQty').val()) || 0;
            
            if (tipe === 'masuk') {
                $('#adjustStockSetelah').text(stockSekarang + qty);
            } else if (tipe === 'keluar') {
                let stockSetelah = stockSekarang - qty;
                if (stockSetelah < 0) stockSetelah = 0;
                $('#adjustStockSetelah').text(stockSetelah);
            }
        });

        // Submit adjust stock
        $('#frmAdjustStock').submit(function(e) {
            e.preventDefault();
            
            let formData = $(this).serialize();
            
            $('#btnAdjustSubmit').prop('disabled', true);
            $('.submit-text').hide();
            $('.loading-text').show();

            $.ajax({
                url: "{{ route('toko.adjust-stock') }}",
                type: 'POST',
                data: formData,
                success: function(res) {
                    $('#btnAdjustSubmit').prop('disabled', false);
                    $('.submit-text').show();
                    $('.loading-text').hide();
                    
                    if (res.success) {
                        $('#modalAdjustStock').modal('hide');
                        tbStock.ajax.reload();
                        Swal.fire('Berhasil!', res.message, 'success');
                    } else {
                        Swal.fire('Error!', res.message, 'error');
                    }
                },
                error: function(xhr) {
                    $('#btnAdjustSubmit').prop('disabled', false);
                    $('.submit-text').show();
                    $('.loading-text').hide();
                    
                    let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                    Swal.fire('Error!', errorMsg, 'error');
                }
            });
        });

        // View History Stock
        $(document).on('click', '.view-history-btn', function() {
            let barangId = $(this).data('id');
            let barangName = $(this).data('name');
            
            $('#historyBarangName').text('History: ' + barangName);
            $('#tbHistory tbody').html('<tr><td colspan="7" class="text-center">Loading...</td></tr>');
            
            let historyUrl = getRouteUrl('toko.stock.history', { id: barangId });
            
            $.get(historyUrl, function(res) {
                if (res.success && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(function(history) {
                        let tipeBadge = '';
                        let tipeText = '';
                        if (history.tipe === 'masuk') {
                            tipeBadge = 'bg-success';
                            tipeText = 'Masuk (+)';
                        } else if (history.tipe === 'keluar') {
                            tipeBadge = 'bg-danger';
                            tipeText = 'Keluar (-)';
                        } else {
                            tipeBadge = 'bg-info';
                            tipeText = 'Adjust';
                        }
                        
                        html += `
                            <tr>
                                <td>${new Date(history.created_at).toLocaleString('id-ID')}</td>
                                <td><span class="badge ${tipeBadge}">${tipeText}</span></td>
                                <td class="text-center">${history.qty}</td>
                                <td class="text-center">${history.qty_sebelum}</td>
                                <td class="text-center">${history.qty_sesudah}</td>
                                <td>${history.keterangan}</td>
                                <td>${history.user ? history.user.name : '-'}</td>
                            </tr>
                        `;
                    });
                    $('#tbHistory tbody').html(html);
                } else {
                    $('#tbHistory tbody').html('<tr><td colspan="7" class="text-center">Tidak ada data history</td></tr>');
                }
                
                $('#modalHistoryStock').modal('show');
            }).fail(function() {
                $('#tbHistory tbody').html('<tr><td colspan="7" class="text-center">Error loading data</td></tr>');
            });
        });
    });
    </script>
</x-slot>
</x-app-layout>