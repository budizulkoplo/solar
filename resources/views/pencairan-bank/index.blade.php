<x-app-layout>
    <x-slot name="pagetitle">Pencairan Bank Penjualan Unit</x-slot>

    <style>
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .btn-action {
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        .card-pencairan {
            border-color: #0d6efd;
        }
        
        .card-pencairan .card-header {
            background-color: #0d6efd;
            color: white;
        }
        
        .filter-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .progress-thin {
            height: 8px;
        }
        
        .financial-card {
            border-left: 4px solid #0d6efd;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

    <div class="app-content-header no-print">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-bank text-primary me-2"></i>Pencairan Bank Penjualan Unit</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-primary btn-sm" onclick="reloadTable()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <a href="{{ route('pencairan-bank.index') }}" class="btn btn-info btn-sm">
                            <i class="bi bi-file-earmark-text"></i> Laporan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Summary Cards -->
            <div class="row mb-3 no-print">
                <div class="col-md-3">
                    <div class="card financial-card">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-house fs-4 text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0 text-muted">Unit Terjual (Kredit)</h6>
                                    <h4 class="mb-0">{{ $totalPenjualan ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card financial-card">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-cash-coin fs-4 text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0 text-muted">Total Dicairkan</h6>
                                    <h4 class="mb-0">Rp {{ number_format($totalDicairkan ?? 0, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card financial-card">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-currency-exchange fs-4 text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0 text-muted">Nilai Penjualan</h6>
                                    <h4 class="mb-0">Rp {{ number_format($totalNilai ?? 0, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card financial-card">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-percent fs-4 text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0 text-muted">Progress Pencairan</h6>
                                    <h4 class="mb-0">{{ number_format($persentaseDicairkan ?? 0, 1) }}%</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar Overall -->
            <div class="card mb-3 no-print">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Progress Pencairan Semua Unit</small>
                        <small class="text-muted">{{ number_format($persentaseDicairkan ?? 0, 1) }}%</small>
                    </div>
                    <div class="progress progress-thin">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: {{ $persentaseDicairkan ?? 0 }}%" 
                             aria-valuenow="{{ $persentaseDicairkan ?? 0 }}" 
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card filter-card mb-3 no-print">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small mb-1">Bank</label>
                            <select class="form-select form-select-sm" id="filterBank" onchange="applyFilter()">
                                <option value="">Semua Bank</option>
                                <option value="BNI">BNI</option>
                                <option value="BCA">BCA</option>
                                <option value="BRI">BRI</option>
                                <option value="Mandiri">Mandiri</option>
                                <option value="Danamon">Danamon</option>
                                <option value="CIMB">CIMB</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small mb-1">Status Pencairan</label>
                            <select class="form-select form-select-sm" id="filterStatus" onchange="applyFilter()">
                                <option value="all">Semua Status</option>
                                <option value="belum">Belum Dicairkan</option>
                                <option value="proses">Dalam Proses</option>
                                <option value="lunas">Lunas Dicairkan</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-8">
                            <label class="form-label small mb-1">Pencarian</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="searchInput" 
                                       placeholder="Cari unit/customer/kode..." 
                                       onkeyup="applyFilter()">
                                <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <label class="form-label small mb-1">&nbsp;</label>
                            <button type="button" onclick="resetFilter()" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table -->
            <div class="card card-pencairan card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-house-check text-success"></i> Daftar Unit Terjual untuk Pencairan
                    </h5>
                </div>
                <div class="card-body">
                    <table id="tblPenjualan" class="table table-sm table-bordered table-striped" style="width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th width="50">No</th>
                                <th width="150">Unit</th>
                                <th width="150">Customer</th>
                                <th width="140">Info Penjualan</th>
                                <th width="160">Info Keuangan</th>
                                <th width="120">Progress</th>
                                <th width="80">Status</th>
                                <th width="80" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Quick Info -->
    <div class="modal fade" id="modalQuickInfo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pencairan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="quickInfoContent">
                    <!-- Content akan diisi via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            let tablePenjualan;

            function formatRupiah(angka) {
                if (!angka || isNaN(angka)) return '-';
                return 'Rp ' + parseFloat(angka).toLocaleString('id-ID');
            }

            function reloadTable() {
                if (tablePenjualan) {
                    tablePenjualan.ajax.reload(null, false);
                }
            }

            function applyFilter() {
                if (tablePenjualan) {
                    const status = $('#filterStatus').val();
                    const bank = $('#filterBank').val();
                    const search = $('#searchInput').val();
                    
                    tablePenjualan.ajax.url("{{ route('pencairan-bank.index') }}?status_filter=" + status + 
                        "&bank_filter=" + bank + "&search=" + encodeURIComponent(search)).load();
                }
            }

            function clearSearch() {
                $('#searchInput').val('');
                applyFilter();
            }

            function resetFilter() {
                $('#filterStatus').val('all');
                $('#filterBank').val('');
                $('#searchInput').val('');
                applyFilter();
            }

            function showQuickInfo(penjualanId) {
                $.ajax({
                    url: "{{ url('pencairan-bank/quick-info') }}/" + penjualanId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            $('#quickInfoContent').html(response.html);
                            $('#modalQuickInfo').modal('show');
                        }
                    }
                });
            }

            $(document).ready(function() {
                // Initialize DataTable
                tablePenjualan = $('#tblPenjualan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('pencairan-bank.index') }}",
                        type: 'GET',
                        data: function(d) {
                            d.status_filter = $('#filterStatus').val();
                            d.bank_filter = $('#filterBank').val();
                            d.search = $('#searchInput').val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'unit_info', name: 'unitDetail.unit.namaunit' },
                        { data: 'customer_info', name: 'customer.nama_lengkap' },
                        { data: 'penjualan_info', name: 'kode_penjualan', orderable: false },
                        { data: 'financial_info', name: 'harga_jual', orderable: false },
                        { data: 'progress_info', name: 'harga_jual', orderable: false },
                        { data: 'status_info', name: 'status_info', orderable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false }
                    ],
                    order: [[0, 'asc']],
                    dom: "<'row mb-2'<'col-md-6 d-flex align-items-center'B><'col-md-6 d-flex justify-content-end'f>>" +
                         "<'row'<'col-12'tr>>" +
                         "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
                    buttons: [
                        {
                            extend: 'excel',
                            text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                            className: 'btn btn-success btn-sm',
                            title: 'Daftar Unit untuk Pencairan Bank',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="bi bi-printer"></i> Print',
                            className: 'btn btn-primary btn-sm',
                            title: 'Daftar Unit untuk Pencairan Bank',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            }
                        }
                    ],
                    language: {
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Tidak ditemukan data yang sesuai",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    },
                    drawCallback: function() {
                        // Update total setelah data dimuat
                        updateFooterTotals();
                    }
                });
                
                // Search dengan delay
                let searchTimeout;
                $('#searchInput').on('keyup', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        applyFilter();
                    }, 500);
                });
            });
            
            function updateFooterTotals() {
                // Implementasi jika perlu menampilkan total di footer
            }
        </script>
    </x-slot>
</x-app-layout>