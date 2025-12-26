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
                        <button class="btn btn-success" id="btnGenerateDepreciation">
                            <i class="bi bi-calculator"></i> Generate Penyusutan
                        </button>
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
                <div class="modal-body">
                    <div id="assetDetailContent">
                        <!-- Konten akan diisi oleh JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
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
                        name: 'tanggal'
                        
                    },
                    { 
                        data: 'total', 
                        name: 'total'
                        
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
                
                $.get(`/transaksi/asset/${notaId}/assets`, function(res) {
                    if (res.success) {
                        let html = `
                            <h6>Informasi Nota</h6>
                            <table class="table table-sm table-bordered mb-4">
                                <tr>
                                    <th width="30%">No Nota</th>
                                    <td>${res.nota.nota_no}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Pembelian</th>
                                    <td>${new Date(res.nota.tanggal).toLocaleDateString('id-ID')}</td>
                                </tr>
                                <tr>
                                    <th>Total Pembelian</th>
                                    <td>Rp ${new Intl.NumberFormat('id-ID').format(res.nota.total)}</td>
                                </tr>
                            </table>
                            
                            <h6>Daftar Asset</h6>
                        `;
                        
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
                                                        <td>Rp ${new Intl.NumberFormat('id-ID').format(asset.harga_perolehan)}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Nilai Residu</th>
                                                        <td>Rp ${new Intl.NumberFormat('id-ID').format(asset.nilai_residu)}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Umur Ekonomis</th>
                                                        <td>${asset.umur_ekonomis} bulan (${(asset.umur_ekonomis/12).toFixed(1)} tahun)</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Metode Penyusutan</th>
                                                        <td>${asset.metode_penyusutan}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <table class="table table-sm">
    
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
                                                </table>
                                            </div>
                                        </div>
                                        
                                        ${asset.depreciations && asset.depreciations.length > 0 ? `
                                            <h6 class="mt-3">Riwayat Penyusutan</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Periode</th>
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
                                                                <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(dep.nilai_penyusutan)}</td>
                                                                <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(dep.akumulasi_penyusutan)}</td>
                                                                <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(dep.nilai_buku)}</td>
                                                                <td><span class="badge ${dep.status === 'terposting' ? 'bg-success' : 'bg-warning'}">${dep.status}</span></td>
                                                            </tr>
                                                        `).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        ` : '<p class="text-muted">Belum ada penyusutan</p>'}
                                    </div>
                                </div>
                            `;
                        });
                        
                        $('#assetDetailContent').html(html);
                        $('#modalDetailAsset').modal('show');
                    }
                });
            });

            // Generate penyusutan
            $('#btnGenerateDepreciation').click(function() {
                $('#modalGenerateDepreciation').modal('show');
            });

            // Form generate penyusutan
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
                    }
                });
            });
        });
        </script>
    </x-slot>
</x-app-layout>