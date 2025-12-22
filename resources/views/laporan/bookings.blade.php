<x-app-layout>
    <x-slot name="pagetitle">Laporan Bookings</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Laporan Bookings</h3>
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
                            <label class="form-label">Status Booking</label>
                            <select class="form-select form-select-sm" name="status" id="status">
                                <option value="">Semua Status</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                @endforeach
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
            <div class="row mb-3 align-items-stretch" id="statsCards">

                <!-- Total Bookings -->
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card border-0 bg-light shadow-sm h-100">
                        <div class="card-body py-2 d-flex align-items-center">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div>
                                    <h6 class="mb-0">Total Bookings</h6>
                                    <h4 class="mb-0" id="totalBookings">0</h4>
                                </div>
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:42px; height:42px;">
                                    <i class="bi bi-calendar-check fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total DP -->
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card border-0 bg-success bg-opacity-10 shadow-sm h-100">
                        <div class="card-body py-2 d-flex align-items-center">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div>
                                    <h6 class="mb-0">Total DP</h6>
                                    <h4 class="mb-0 text-success" id="totalDp">Rp 0</h4>
                                </div>
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:42px; height:42px;">
                                    <i class="bi bi-cash-coin fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active -->
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card border-0 bg-info bg-opacity-10 shadow-sm h-100">
                        <div class="card-body py-2 d-flex align-items-center">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div>
                                    <h6 class="mb-0">Active</h6>
                                    <h4 class="mb-0 text-info" id="activeBookings">0</h4>
                                </div>
                                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:42px; height:42px;">
                                    <i class="bi bi-check-circle fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Canceled -->
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card border-0 bg-danger bg-opacity-10 shadow-sm h-100">
                        <div class="card-body py-2 d-flex align-items-center">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div>
                                    <h6 class="mb-0">Canceled</h6>
                                    <h4 class="mb-0 text-danger" id="canceledBookings">0</h4>
                                </div>
                                <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:42px; height:42px;">
                                    <i class="bi bi-x-circle fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>


            <!-- Data Table -->
            <div class="card card-info card-outline">
                <div class="card-header pt-1 pb-1">
                    <h6 class="mb-0">Data Bookings</h6>
                </div>
                <div class="card-body">
                    <table id="tbBookings" class="table table-sm table-hover w-100" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th width="3%">No</th>
                                <th>Kode Booking</th>
                                <th>Project</th>
                                <th>Unit</th>
                                <th>Customer</th>
                                <th>NIK</th>
                                <th>No. HP</th>
                                <th>Tanggal Booking</th>
                                <th>DP Awal</th>
                                <th>Metode Bayar</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Dibuat Oleh</th>
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
                let tbBookings = $('#tbBookings').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('laporan.bookings') }}",
                        type: "GET",
                        data: function(d) {
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                            d.project_id = $('#project_id').val();
                            d.status = $('#status').val();
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
                        { data: 'kode_booking', name: 'kode_booking' },
                        { data: 'project_name', name: 'project_name' },
                        { data: 'unit_name', name: 'unit_name' },
                        { data: 'customer_name', name: 'customer_name' },
                        { data: 'customer_nik', name: 'customer_nik' },
                        { data: 'customer_hp', name: 'customer_hp' },
                        { 
                            data: 'tanggal_booking_formatted', 
                            name: 'tanggal_booking',
                            className: 'text-center'
                        },
                        { 
                            data: 'dp_formatted', 
                            name: 'dp_awal',
                            className: 'text-end'
                        },
                        { data: 'metode_pembayaran_dp', name: 'metode_pembayaran_dp' },
                        { 
                            data: 'tanggal_jatuh_tempo_formatted', 
                            name: 'tanggal_jatuh_tempo',
                            className: 'text-center'
                        },
                        { 
                            data: 'status_badge', 
                            name: 'status_booking',
                            className: 'text-center'
                        },
                        { data: 'created_by_name', name: 'created_by' },
                        { 
                            data: 'keterangan', 
                            name: 'keterangan',
                            render: function(data) {
                                return data ? data.substring(0, 50) + (data.length > 50 ? '...' : '') : '-';
                            }
                        }
                    ],
                    order: [[7, 'desc']], // Order by tanggal_booking desc
                    language: {
                        emptyTable: "Tidak ada data bookings",
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
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]]
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
                            const data = response.data.bookings;
                            $('#totalBookings').text(data.total);
                            $('#totalDp').text('Rp ' + parseInt(data.total_dp).toLocaleString('id-ID'));
                            $('#activeBookings').text(data.active);
                            $('#canceledBookings').text(data.canceled);
                        }
                    });
                }
                
                // Initial load statistics
                loadStatistics();
                
                // Filter form submit
                $('#filterForm').submit(function(e) {
                    e.preventDefault();
                    tbBookings.ajax.reload();
                    loadStatistics();
                });
                
                // Export PDF
                $('#btnExportPDF').click(function() {
                    const params = {
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        project_id: $('#project_id').val(),
                        status: $('#status').val()
                    };
                    
                    const queryString = $.param(params);
                    window.open("{{ route('laporan.export.bookings.pdf') }}?" + queryString, '_blank');
                });
                
                // Reset filter
                $('#btnResetFilter').click(function() {
                    $('#filterForm')[0].reset();
                    $('#start_date').val(firstDay.toISOString().split('T')[0]);
                    $('#end_date').val(lastDay.toISOString().split('T')[0]);
                    tbBookings.ajax.reload();
                    loadStatistics();
                });
            });
        </script>
    </x-slot>
</x-app-layout>