<x-app-layout>
    <x-slot name="pagetitle">Penjualan Agency</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Penjualan Agency</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Card untuk Unit Terjual -->
            <div class="card card-primary card-outline mb-4">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-house-check-fill"></i> Daftar Unit Terjual
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Filter -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Project</label>
                            <select class="form-select form-select-sm" id="filterProject">
                                <option value="">Semua Project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->namaproject }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-sm btn-primary d-block" id="btnFilter">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </div>

                    <table id="tbUnits" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Project</th>
                                <th>Unit</th>
                                <th>Customer</th>
                                <th>Kode Booking</th>
                                <th>Kode Penjualan</th>
                                <th class="text-end">Harga Jual</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!-- Card untuk Transaksi Agency yang Sudah Dibuat -->
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-receipt"></i> Transaksi Agency
                    </h4>
                </div>
                <div class="card-body">
                    <table id="tbTransactions" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nota No</th>
                                <th>Tanggal</th>
                                <th>Nama Transaksi</th>
                                <th>Unit</th>
                                <th>Customer</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi -->
    <div class="modal fade" id="modalConfirm" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Konfirmasi Penjualan Agency</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan membuat transaksi agency untuk:</p>
                    <table class="table table-sm">
                        <tr>
                            <th>Unit:</th>
                            <td id="confirmUnitName">-</td>
                        </tr>
                        <tr>
                            <th>Customer:</th>
                            <td id="confirmCustomer">-</td>
                        </tr>
                        <tr>
                            <th>Harga Jual:</th>
                            <td id="confirmHargaJual">-</td>
                        </tr>
                    </table>
                    <p class="text-muted small">Pastikan data di atas sudah benar sebelum melanjutkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="btnCreateTransaction" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Buat Transaksi
                    </a>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
            // DataTable untuk Unit Terjual
            let tbUnits = $('#tbUnits').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('agency-sales.get-data') }}",
                    data: function(d) {
                        d.project_id = $('#filterProject').val();
                    }
                },
                columns: [
                    { 
                        data: 'DT_RowIndex', 
                        name: 'DT_RowIndex', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    },
                    { 
                        data: 'unit.project.namaproject', 
                        name: 'unit.project.namaproject'
                    },
                    { 
                        data: 'unit.namaunit', 
                        name: 'unit.namaunit'
                    },
                    { 
                        data: 'customer.nama_lengkap', 
                        name: 'customer.nama_lengkap',
                        defaultContent: '-'
                    },
                    { 
                        data: 'booking.kode_booking', 
                        name: 'booking.kode_booking',
                        defaultContent: '-'
                    },
                    { 
                        data: 'penjualan.kode_penjualan', 
                        name: 'penjualan.kode_penjualan',
                        defaultContent: '-'
                    },
                    { 
                        data: 'penjualan.harga_jual', 
                        name: 'penjualan.harga_jual',
                        className: 'text-end'
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
                    { width: "15%", targets: 1 },
                    { width: "15%", targets: 2 },
                    { width: "15%", targets: 3 },
                    { width: "10%", targets: 4 },
                    { width: "10%", targets: 5 },
                    { width: "15%", targets: 6 },
                    { width: "10%", targets: 7 }
                ]
            });

            // DataTable untuk Transaksi Agency
            let tbTransactions = $('#tbTransactions').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('agency-sales.transactions.data') }}",
                columns: [
                    { 
                        data: 'DT_RowIndex', 
                        name: 'DT_RowIndex', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    },
                    { data: 'nota_no', name: 'nota_no' },
                    { 
                        data: 'tanggal', 
                        name: 'tanggal',
                        className: 'text-center'
                       
                    },
                    { data: 'namatransaksi', name: 'namatransaksi' },
                    { 
                        data: 'unit_info', 
                        name: 'unitDetail.unit.namaunit',
                        defaultContent: '-'
                    },
                    { 
                        data: 'customer_info', 
                        name: 'unitDetail.customer.nama_lengkap',
                        defaultContent: '-'
                    },
                   
                    { 
                        data: 'total', 
                        name: 'total',
                        className: 'text-end'
                        
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
                ]
            });

            // Filter unit terjual
            $('#btnFilter').click(function() {
                tbUnits.ajax.reload();
            });

            // Pilih unit untuk transaksi agency
            $(document).on('click', '.btn-select', function() {
                let unitDetailId = $(this).data('id');
                let unitName = $(this).data('unit-name');
                let customer = $(this).data('customer');
                let hargaJual = $(this).data('harga-jual');

                $('#confirmUnitName').text(unitName);
                $('#confirmCustomer').text(customer);
                $('#confirmHargaJual').text(hargaJual);

                let createUrl = "{{ route('agency-sales.create', ':id') }}";
                createUrl = createUrl.replace(':id', unitDetailId);
                $('#btnCreateTransaction').attr('href', createUrl);

                $('#modalConfirm').modal('show');
            });

            // Delete transaksi agency
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                let transactionId = $(this).data('id');
                
                // Debug: cek ID
                console.log('Transaction ID to delete:', transactionId);
                
                if (!transactionId) {
                    Swal.fire('Error', 'ID transaksi tidak ditemukan', 'error');
                    return;
                }
                
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Transaksi agency ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // PERBAIKAN 1: Gunakan template literal untuk URL
                        let deleteUrl = "{{ route('agency-sales.destroy', ':id') }}".replace(':id', transactionId);
                        
                        // PERBAIKAN 2: Atau langsung konstruksi URL
                        // let deleteUrl = `/agency-sales/${transactionId}`;
                        
                        console.log('Delete URL:', deleteUrl);
                        
                        $.ajax({
                            url: deleteUrl, // ← Gunakan URL yang sudah diperbaiki
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}',
                                _method: 'DELETE'
                            },
                            success: function(res) {
                                if (res.success) {
                                    tbTransactions.ajax.reload();
                                    Swal.fire('Berhasil!', 'Transaksi agency berhasil dihapus', 'success');
                                } else {
                                    Swal.fire('Error!', res.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus';
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                });
            });
        });
        </script>
    </x-slot>
</x-app-layout>