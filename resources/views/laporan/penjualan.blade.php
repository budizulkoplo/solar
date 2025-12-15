<x-app-layout>
    <x-slot name="pagetitle">Laporan Penjualan</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Laporan Penjualan</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Filter Card -->
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <h6 class="mb-0">Filter Laporan</h6>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control form-control-sm" name="start_date" id="start_date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control form-control-sm" name="end_date" id="end_date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Project</label>
                            <select class="form-select form-select-sm" name="project_id" id="project_id">
                                <option value="">Semua Project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->namaproject }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Metode Pembayaran</label>
                            <select class="form-select form-select-sm" name="metode_pembayaran" id="metode_pembayaran">
                                <option value="">Semua Metode</option>
                                <option value="cash">Cash</option>
                                <option value="kredit">Kredit</option>
                            </select>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <button type="button" class="btn btn-sm btn-success" id="btnExportPDF">
                                    <i class="bi bi-file-pdf"></i> Export PDF
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" id="btnResetFilter">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4" id="statsCards">
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card border-0 bg-primary bg-opacity-10 shadow-sm">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Penjualan</h6>
                                    <h4 class="mb-0" id="totalPenjualan">0</h4>
                                </div>
                                <div class="bg-primary rounded-circle p-2">
                                    <i class="bi bi-cart-check text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card border-0 bg-success bg-opacity-10 shadow-sm">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total Harga Jual</h6>
                                    <h4 class="mb-0 text-success" id="totalHargaJual">Rp 0</h4>
                                </div>
                                <div class="bg-success rounded-circle p-2">
                                    <i class="bi bi-currency-dollar text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card border-0 bg-info bg-opacity-10 shadow-sm">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Total DP</h6>
                                    <h4 class="mb-0 text-info" id="totalDp">Rp 0</h4>
                                </div>
                                <div class="bg-info rounded-circle p-2">
                                    <i class="bi bi-cash-coin text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card border-0 bg-warning bg-opacity-10 shadow-sm">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Cash vs Kredit</h6>
                                    <h6 class="mb-0"><span id="cashSales">0</span> / <span id="creditSales">0</span></h6>
                                </div>
                                <div class="bg-warning rounded-circle p-2">
                                    <i class="bi bi-pie-chart text-dark"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card card-info card-outline">
                <div class="card-header pt-1 pb-1">
                    <h6 class="mb-0">Data Penjualan</h6>
                </div>
                <div class="card-body">
                    <table id="tbPenjualan" class="table table-sm table-hover w-100" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th width="3%">No</th>
                                <th>Kode Penjualan</th>
                                <th>Project</th>
                                <th>Unit</th>
                                <th>Customer</th>
                                <th>NIK</th>
                                <th>Tanggal Akad</th>
                                <th>Harga Jual</th>
                                <th>DP Awal</th>
                                <th>Metode Bayar</th>
                                <th>Info Kredit</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function() {
                // Set default dates (this month)
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                
                $('#start_date').val(firstDay.toISOString().split('T')[0]);
                $('#end_date').val(lastDay.toISOString().split('T')[0]);
                
                // Initialize DataTable
                let tbPenjualan = $('#tbPenjualan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('laporan.penjualan') }}",
                        type: "GET",
                        data: function(d) {
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                            d.project_id = $('#project_id').val();
                            d.metode_pembayaran = $('#metode_pembayaran').val();
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTables error:', error, thrown);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Gagal memuat data penjualan: ' + error
                            });
                        }
                    },
                    columns: [
                        { 
                            data: null,
                            name: 'no',
                            className: 'text-center',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        { data: 'kode_penjualan', name: 'kode_penjualan' },
                        { data: 'project_name', name: 'project_name' },
                        { data: 'unit_name', name: 'unit_name' },
                        { data: 'customer_name', name: 'customer_name' },
                        { data: 'customer_nik', name: 'customer_nik' },
                        { 
                            data: 'tanggal_akad_formatted', 
                            name: 'tanggal_akad',
                            className: 'text-center'
                        },
                        { 
                            data: 'harga_jual_formatted', 
                            name: 'harga_jual',
                            className: 'text-end'
                        },
                        { 
                            data: 'dp_awal_formatted', 
                            name: 'dp_awal',
                            className: 'text-end'
                        },
                        { 
                            data: 'metode_badge', 
                            name: 'metode_pembayaran',
                            className: 'text-center'
                        },
                        { 
                            data: 'kredit_info', 
                            name: 'kredit_info',
                            className: 'text-center'
                        },
                        { 
                            data: 'keterangan', 
                            name: 'keterangan',
                            render: function(data) {
                                return data ? data.substring(0, 50) + (data.length > 50 ? '...' : '') : '-';
                            }
                        }
                    ],
                    order: [[6, 'desc']], // Order by tanggal_akad desc
                    language: {
                        emptyTable: "Tidak ada data penjualan",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Tidak ditemukan data yang cocok",
                        loadingRecords: "Memuat...",
                        processing: "Memproses...",
                        paginate: {
                            first: "Awal",
                            last: "Akhir",
                            next: "›",
                            previous: "‹"
                        }
                    },
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                    drawCallback: function(settings) {
                        console.log('DataTables drawCallback - Total records:', settings.json?.recordsTotal);
                        console.log('DataTables drawCallback - Filtered records:', settings.json?.recordsFiltered);
                    }
                });
                
                // Load statistics
                function loadStatistics() {
                    const params = {
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        project_id: $('#project_id').val()
                    };
                    
                    $.get("{{ route('laporan.statistics') }}", params, function(response) {
                        if (response.success) {
                            const data = response.data.penjualan;
                            $('#totalPenjualan').text(data.total);
                            $('#totalHargaJual').text('Rp ' + parseInt(data.total_harga_jual).toLocaleString('id-ID'));
                            $('#totalDp').text('Rp ' + parseInt(data.total_dp).toLocaleString('id-ID'));
                            $('#cashSales').text(data.cash);
                            $('#creditSales').text(data.credit);
                        }
                    }).fail(function(xhr) {
                        console.error('Error loading statistics:', xhr);
                    });
                }
                
                // Initial load statistics
                loadStatistics();
                
                // Filter form submit
                $('#filterForm').submit(function(e) {
                    e.preventDefault();
                    tbPenjualan.ajax.reload();
                    loadStatistics();
                });
                
                // Export PDF
                $('#btnExportPDF').click(function() {
                    const params = {
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        project_id: $('#project_id').val(),
                        metode_pembayaran: $('#metode_pembayaran').val()
                    };
                    
                    const queryString = $.param(params);
                    window.open("{{ route('laporan.export.penjualan.pdf') }}?" + queryString, '_blank');
                });
                
                // Reset filter
                $('#btnResetFilter').click(function() {
                    $('#filterForm')[0].reset();
                    $('#start_date').val(firstDay.toISOString().split('T')[0]);
                    $('#end_date').val(lastDay.toISOString().split('T')[0]);
                    tbPenjualan.ajax.reload();
                    loadStatistics();
                });
            });
        </script>
    </x-slot>
</x-app-layout>