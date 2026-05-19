<x-app-layout>
    <x-slot name="pagetitle">Transaksi Asset Tetap</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Transaksi Asset Tetap</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="btn-group">
                        <button class="btn btn-primary" id="btnTambahAsset">
                            <i class="bi bi-plus-circle"></i> Transaksi Asset Baru
                        </button>
                        <a href="{{ route('transaksi.asset.list') }}" class="btn btn-info">
                            <i class="bi bi-box-seam"></i> Daftar Asset
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-card-checklist"></i> Daftar Transaksi Asset
                    </h5>
                </div>
                <div class="card-body">
                    <table id="tbAssetTrans" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nota No</th>
                                <th>Nama Asset</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-end">Nilai Asset</th>
                                <th class="text-center">Status Asset</th>
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

    <!-- Modal Detail Asset -->
    <div class="modal fade" id="modalDetailAsset" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="assetDetailContent">
                        <!-- Konten akan diisi oleh JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Transaksi -->
    <div class="modal fade" id="modalDetailTransaksi" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="transaksiDetailContent">
                        <!-- Konten akan diisi oleh JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Transaksi -->
    <div class="modal fade" id="modalEditTransaksi" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Transaksi Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="frmEditTransaksi">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">No Nota *</label>
                                <input type="text" class="form-control" name="nota_no" id="editNotaNo" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Transaksi *</label>
                                <input type="text" class="form-control" name="namatransaksi" id="editNamaTransaksi" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control" name="tanggal" id="editTanggal" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor *</label>
                                <select class="form-select" name="vendor_id" id="editVendor" required>
                                    <option value="">Pilih Vendor</option>
                                    @foreach($vendors ?? [] as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->namavendor }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select" name="idrek" id="editRekening" required>
                                    <option value="">Pilih Rekening</option>
                                    @foreach($rekenings ?? [] as $rekening)
                                        <option value="{{ $rekening->idrek }}">{{ $rekening->namarek }} - Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode Pembayaran *</label>
                                <select class="form-select" name="paymen_method" id="editPaymentMethod" required>
                                    <option value="cash">Cash</option>
                                    <option value="tempo">Tempo</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="editTempoField" style="display: none;">
                                <label class="form-label">Tanggal Tempo</label>
                                <input type="date" class="form-control" name="tgl_tempo" id="editTglTempo">
                            </div>
                        </div>

                        <h6 class="mt-4">Detail Transaksi</h6>
                        <table class="table table-sm table-bordered" id="editTransactionsTable">
                            <thead>
                                <tr>
                                    <th>Kode Transaksi</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Nominal</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody id="editTransactionsBody">
                                <!-- Akan diisi JavaScript -->
                            </tbody>
                        </table>

                        <div class="row mt-3">
                            <div class="col-md-4 offset-md-8">
                                <table class="table table-sm">
                                    <tr>
                                        <th>Subtotal</th>
                                        <td class="text-end">
                                            <input type="number" class="form-control form-control-sm text-end" name="subtotal" id="editSubtotal" readonly>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>PPN</th>
                                        <td class="text-end">
                                            <input type="number" class="form-control form-control-sm text-end" name="ppn" id="editPpn" value="0">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Diskon</th>
                                        <td class="text-end">
                                            <input type="number" class="form-control form-control-sm text-end" name="diskon" id="editDiskon" value="0">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Total</th>
                                        <td class="text-end">
                                            <input type="number" class="form-control form-control-sm text-end fw-bold" name="total" id="editTotal" readonly>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
            // Format number
            function formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
            
            // Parse number
            function parseNumber(str) {
                return parseFloat(str.replace(/[^\d.-]/g, '')) || 0;
            }

            // DataTable
            let tbAssetTrans = $('#tbAssetTrans').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.asset.getdata') }}",
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
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
                        className: 'text-end'
                    },
                    { data: 'aset_status', name: 'aset_status', className: 'text-center' },
                    { data: 'status', name: 'status', className: 'text-center' },
                    { data: 'namauser', name: 'namauser', className: 'text-center' },
                    { data: 'action', orderable: false, searchable: false, className: 'text-center' }
                ]
            });

            // Tambah transaksi asset baru
            $('#btnTambahAsset').click(function() {
                window.location.href = "{{ route('transaksi.asset.create') }}";
            });

            // View detail transaksi
            $(document).on('click', '.view-btn', function() {
                let notaId = $(this).data('id');
                
                $.get(`/transaksi/asset/${notaId}/assets`, function(res) {
                    if (res.success) {
                        let html = `
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="30%">No Nota</th>
                                    <td>${res.nota.nota_no}</td>
                                </tr>
                                <tr>
                                    <th>Nama Transaksi</th>
                                    <td>${res.nota.namatransaksi}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal</th>
                                    <td>${new Date(res.nota.tanggal).toLocaleDateString('id-ID')}</td>
                                </tr>
                                <tr>
                                    <th>Vendor</th>
                                    <td>${res.nota.vendor ? res.nota.vendor.namavendor : '-'}</td>
                                </tr>
                                <tr>
                                    <th>Metode Pembayaran</th>
                                    <td>${res.nota.paymen_method}</td>
                                </tr>
                                <tr>
                                    <th>Total Pembelian</th>
                                    <td class="fw-bold text-success">Rp ${formatNumber(res.nota.total)}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><span class="badge ${res.nota.status === 'paid' ? 'bg-success' : 'bg-warning'}">${res.nota.status}</span></td>
                                </tr>
                            </table>
                            
                            <h6 class="mt-4">Daftar Asset yang Dibeli</h6>
                        `;
                        
                        if (res.assets && res.assets.length > 0) {
                            res.assets.forEach(function(asset, index) {
                                html += `
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <strong>${index + 1}. ${asset.nama_aset}</strong>
                                            <span class="float-end badge bg-primary">${asset.kode_aset}</span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <th width="40%">Harga Perolehan</th>
                                                            <td>Rp ${formatNumber(asset.harga_perolehan)}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Nilai Residu</th>
                                                            <td>Rp ${formatNumber(asset.nilai_residu)}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Umur Ekonomis</th>
                                                            <td>${asset.umur_ekonomis} bulan</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <th width="40%">Metode</th>
                                                            <td>${asset.metode_penyusutan}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Status</th>
                                                            <td><span class="badge ${asset.status === 'aktif' ? 'bg-success' : 'bg-warning'}">${asset.status}</span></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Lokasi/PIC</th>
                                                            <td>${asset.lokasi || '-'} / ${asset.pic || '-'}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            html += '<p class="text-muted">Belum ada asset yang digenerate dari transaksi ini</p>';
                        }
                        
                        $('#transaksiDetailContent').html(html);
                        $('#modalDetailTransaksi').modal('show');
                    }
                });
            });

            // Edit transaksi
            $(document).on('click', '.edit-btn', function() {
                let notaId = $(this).data('id');
                
                $.get(`/transaksi/asset/${notaId}/edit-transaksi`, function(res) {
                    if (res.success) {
                        let nota = res.nota;
                        $('#editId').val(nota.id);
                        $('#editNotaNo').val(nota.nota_no);
                        $('#editNamaTransaksi').val(nota.namatransaksi);
                        $('#editTanggal').val(nota.tanggal.split(' ')[0]);
                        $('#editVendor').val(nota.vendor_id);
                        $('#editRekening').val(nota.idrek);
                        $('#editPaymentMethod').val(nota.paymen_method);
                        
                        if (nota.paymen_method === 'tempo') {
                            $('#editTempoField').show();
                            $('#editTglTempo').val(nota.tgl_tempo ? nota.tgl_tempo.split(' ')[0] : '');
                        } else {
                            $('#editTempoField').hide();
                        }
                        
                        // Load transactions
                        let transactionsHtml = '';
                        let subtotal = 0;
                        
                        nota.transactions.forEach(function(trans) {
                            if (trans.kode_transaksi && !trans.kode_transaksi.startsWith('3') && !trans.kode_transaksi.startsWith('5')) {
                                let total = trans.nominal * trans.jml;
                                subtotal += total;
                                transactionsHtml += `
                                    <tr>
                                        <td>${trans.kode_transaksi} - ${trans.kode_transaksi?.nama || ''}</td>
                                        <td>${trans.description}</td>
                                        <td class="text-end">Rp ${formatNumber(trans.nominal)}</td>
                                        <td class="text-center">${trans.jml}</td>
                                        <td class="text-end">Rp ${formatNumber(total)}</td>
                                    </tr>
                                `;
                            }
                        });
                        
                        $('#editTransactionsBody').html(transactionsHtml);
                        $('#editSubtotal').val(subtotal);
                        $('#editPpn').val(nota.ppn || 0);
                        $('#editDiskon').val(nota.diskon || 0);
                        $('#editTotal').val(nota.total);
                        
                        $('#modalEditTransaksi').modal('show');
                    }
                });
            });

            // Toggle tempo field
            $('#editPaymentMethod').change(function() {
                if ($(this).val() === 'tempo') {
                    $('#editTempoField').show();
                } else {
                    $('#editTempoField').hide();
                    $('#editTglTempo').val('');
                }
            });

            // Calculate total on edit
            function calculateEditTotal() {
                let subtotal = parseFloat($('#editSubtotal').val()) || 0;
                let ppn = parseFloat($('#editPpn').val()) || 0;
                let diskon = parseFloat($('#editDiskon').val()) || 0;
                let total = subtotal + ppn - diskon;
                $('#editTotal').val(total);
            }

            $('#editPpn, #editDiskon').on('input', calculateEditTotal);

            // Submit edit form
            $('#frmEditTransaksi').submit(function(e) {
                e.preventDefault();
                let notaId = $('#editId').val();
                
                Swal.fire({
                    title: 'Update Transaksi?',
                    text: "Apakah Anda yakin ingin mengupdate transaksi ini?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Update!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: `/transaksi/asset/${notaId}/update-transaksi`,
                            type: 'POST',
                            data: $(this).serialize()
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
                        $('#modalEditTransaksi').modal('hide');
                        tbAssetTrans.ajax.reload();
                    }
                });
            });

            // Delete transaksi
            $(document).on('click', '.delete-btn', function() {
                let notaId = $(this).data('id');
                
                Swal.fire({
                    title: 'Hapus Transaksi?',
                    html: `Apakah Anda yakin ingin menghapus transaksi ini?<br>
                           <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> 
                           Semua data aset yang terkait juga akan terhapus!</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: `/transaksi/asset/${notaId}/destroy-transaksi`,
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
                        tbAssetTrans.ajax.reload();
                    }
                });
            });

            // Generate aset dari transaksi existing
            $(document).on('click', '.generate-asset-btn', function() {
                let notaId = $(this).data('id');
                
                Swal.fire({
                    title: 'Generate Asset?',
                    text: "Apakah Anda yakin ingin mengenerate aset dari transaksi ini?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Generate!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(`/transaksi/asset/${notaId}/generate`, {
                            _token: '{{ csrf_token() }}'
                        }, function(res) {
                            if (res.success) {
                                tbAssetTrans.ajax.reload();
                                Swal.fire('Berhasil!', res.message, 'success');
                            } else {
                                Swal.fire('Error!', res.message, 'error');
                            }
                        }).fail(function(xhr) {
                            Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                        });
                    }
                });
            });

            // View detail asset
            $(document).on('click', '.view-asset-btn', function() {
                let notaId = $(this).data('id');
                window.location.href = "{{ route('transaksi.asset.list') }}?nota_id=" + encodeURIComponent(notaId);
            });
        });
        </script>
    </x-slot>
</x-app-layout>
