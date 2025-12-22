<x-app-layout>
    <x-slot name="pagetitle">Laporan Neraca Saldo</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-file-earmark-text"></i> Laporan Neraca Saldo</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <input type="date" id="start_date" class="form-control form-control-sm w-auto" value="{{ $startDate }}">
                        
                        <span class="align-self-center">s/d</span>
                        
                        <input type="date" id="end_date" class="form-control form-control-sm w-auto" value="{{ $endDate }}">
                        
                        <select id="module" class="form-select form-select-sm w-auto">
                            <option value="project" {{ $module == 'project' ? 'selected' : '' }}>Project</option>
                            <option value="company" {{ $module == 'company' ? 'selected' : '' }}>PT/Company</option>
                        </select>
                        
                        <button type="button" id="btnFilter" class="btn btn-primary btn-sm" onclick="reloadTable()">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        
                        <button type="button" id="btnReset" class="btn btn-secondary btn-sm" onclick="resetFilter()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <div class="card-tools">
                        <div class="btn-group" role="group">
                            <button type="button" id="exportExcel" class="btn btn-success btn-sm">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </button>
                            <button type="button" id="printReport" class="btn btn-warning btn-sm">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="summaryInfo" class="alert alert-info mb-3 d-none">
                        <!-- Summary info will be loaded here -->
                    </div>
                    
                    <table id="tblNeracaSaldo" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Kode Akun</th>
                                <th>Nama Akun</th>
                                <th width="20%" class="text-end">Debit (Rp)</th>
                                <th width="20%" class="text-end">Kredit (Rp)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot id="tblFooter" class="d-none">
                            <!-- Footer will be loaded via AJAX -->
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== CUSTOM SCRIPT ========== --}}
    <x-slot name="jscustom">
        <script>
            let table;
            let summaryData = {};

            function resetFilter() {
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                
                $('#start_date').val(formatDate(firstDay));
                $('#end_date').val(formatDate(lastDay));
                $('#module').val('{{ $module }}');
                
                reloadTable();
            }

            function formatDate(date) {
                const d = new Date(date);
                const month = (d.getMonth() + 1).toString().padStart(2, '0');
                const day = d.getDate().toString().padStart(2, '0');
                const year = d.getFullYear();
                return `${year}-${month}-${day}`;
            }

            function reloadTable() {
                if (table) table.ajax.reload();
            }

            function formatRupiah(angka) {
                if (!angka || isNaN(angka)) return '-';
                return 'Rp ' + parseFloat(angka).toLocaleString('id-ID', { minimumFractionDigits: 0 });
            }

            $(document).ready(function () {
                // Initialize DataTable
                table = $('#tblNeracaSaldo').DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: 50,
                    lengthMenu: [25, 50, 100, 200],
                    ajax: {
                        url: "{{ route('laporan.neraca-saldo.data') }}",
                        data: function(d) {
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                            d.module = $('#module').val();
                        },
                        dataSrc: function(json) {
                            if (json.success) {
                                // Store summary data
                                summaryData = json.summary;
                                summaryData.period = json.period;
                                
                                // Update summary info
                                updateSummaryInfo();
                                
                                return json.data;
                            } else {
                                console.error('Error loading data:', json.message);
                                return [];
                            }
                        }
                    },
                    columns: [
                        {
                            data: null,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            },
                            className: 'text-center'
                        },
                        {
                            data: 'kode',
                            render: function(data, type, row) {
                                return `<span class="badge bg-secondary">${data}</span>`;
                            }
                        },
                        {
                            data: 'nama_akun',
                            render: function(data, type, row) {
                                let typeBadge = '';
                                if (row.is_rekening) {
                                    typeBadge = '<span class="badge bg-info ms-1">Kas/Bank</span>';
                                } else if (row.jenis === 'pendapatan') {
                                    typeBadge = '<span class="badge bg-success ms-1">Pendapatan</span>';
                                } else if (row.jenis === 'beban') {
                                    typeBadge = '<span class="badge bg-danger ms-1">Beban</span>';
                                } else if (row.jenis === 'aset') {
                                    typeBadge = '<span class="badge bg-primary ms-1">Aset</span>';
                                }
                                return `${data} ${typeBadge}`;
                            }
                        },
                        {
                            data: 'debit_raw',
                            className: 'text-end',
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        {
                            data: 'kredit_raw',
                            className: 'text-end',
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        }
                    ],
                    order: [[1, 'asc']],
                    dom:
                        "<'row mb-2'<'col-md-6 d-flex align-items-center'B><'col-md-6 d-flex justify-content-end'f>>" +
                        "<'row mb-2'<'col-md-6'l><'col-md-6 text-end'i>>" +
                        "<'row'<'col-12'tr>>" +
                        "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
                    buttons: [
                        {
                            text: '<i class="bi bi-arrow-clockwise"></i> Refresh',
                            className: 'btn btn-secondary btn-sm',
                            action: function() {
                                reloadTable();
                            }
                        }
                    ],
                    drawCallback: function(settings) {
                        // Update footer after table is drawn
                        updateTableFooter();
                    }
                });

                // Export to Excel
                $('#exportExcel').on('click', function() {
                    const startDate = $('#start_date').val();
                    const endDate = $('#end_date').val();
                    const module = $('#module').val();
                    
                    const url = '{{ route("laporan.neraca-saldo.export-excel") }}?' + 
                                `start_date=${startDate}&end_date=${endDate}&module=${module}`;
                    
                    window.location.href = url;
                });

                // Print Report
                $('#printReport').on('click', function() {
                    const startDate = $('#start_date').val();
                    const endDate = $('#end_date').val();
                    const module = $('#module').val();
                    
                    const url = '{{ route("laporan.neraca-saldo.print") }}?' + 
                                `start_date=${startDate}&end_date=${endDate}&module=${module}`;
                    
                    window.open(url, '_blank');
                });

                // Update summary info
                function updateSummaryInfo() {
                    const summaryHtml = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-1">
                                    <i class="bi bi-calendar me-1"></i>
                                    Periode: ${formatDisplayDate(summaryData.period.start)} - ${formatDisplayDate(summaryData.period.end)}
                                </h6>
                                <small class="text-muted">
                                    <i class="bi bi-box me-1"></i>
                                    Module: ${summaryData.period.module === 'project' ? 'Project' : 'PT/Company'}
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="badge ${summaryData.balance ? 'bg-success' : 'bg-danger'}">
                                    <i class="bi ${summaryData.balance ? 'bi-check-circle' : 'bi-x-circle'} me-1"></i>
                                    ${summaryData.balance ? 'SEIMBANG' : 'TIDAK SEIMBANG'}
                                </span>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        Total Akun: ${summaryData.total_accounts}
                                        ${summaryData.total_projects ? ` | Total Project: ${summaryData.total_projects}` : ''}
                                    </small>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('#summaryInfo').html(summaryHtml).removeClass('d-none');
                }

                // Update table footer
                function updateTableFooter() {
                    if (summaryData.total_debit_raw !== undefined) {
                        const footerHtml = `
                            <tr class="table-active fw-bold">
                                <td colspan="3" class="text-end">TOTAL</td>
                                <td class="text-end">${formatRupiah(summaryData.total_debit_raw)}</td>
                                <td class="text-end">${formatRupiah(summaryData.total_kredit_raw)}</td>
                            </tr>
                            ${!summaryData.balance ? `
                            <tr class="table-warning">
                                <td colspan="3" class="text-end">SELISIH</td>
                                <td colspan="2" class="text-center text-danger fw-bold">
                                    ${formatRupiah(Math.abs(summaryData.total_debit_raw - summaryData.total_kredit_raw))}
                                </td>
                            </tr>
                            ` : ''}
                        `;
                        
                        $('#tblFooter').html(footerHtml).removeClass('d-none');
                    }
                }

                function formatDisplayDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                }

                // Auto submit on Enter key in filter inputs
                $('#start_date, #end_date, #module').on('keypress', function(e) {
                    if (e.which === 13) {
                        reloadTable();
                    }
                });

                // Initial load
                reloadTable();
            });
        </script>

        <style>
            .dataTables_wrapper .dataTables_filter input {
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 4px 8px;
            }
            
            .dataTables_wrapper .dataTables_length select {
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 4px 8px;
            }
            
            .table-sm th, .table-sm td {
                padding: 0.5rem;
            }
            
            .table tbody tr:hover {
                background-color: rgba(0, 0, 0, 0.02);
            }
            
            #tblNeracaSaldo tbody tr td:nth-child(4),
            #tblNeracaSaldo tbody tr td:nth-child(5) {
                font-family: 'Courier New', monospace;
                font-weight: 500;
            }
            
            #summaryInfo {
                padding: 0.75rem;
                margin-bottom: 1rem;
            }
            
            @media (max-width: 768px) {
                .app-content-header .col-sm-6 {
                    margin-bottom: 1rem;
                }
                
                .app-content-header .text-end {
                    justify-content: flex-start !important;
                }
                
                .app-content-header .d-flex {
                    flex-wrap: wrap;
                    gap: 0.5rem;
                }
                
                .app-content-header .w-auto {
                    width: 100% !important;
                }
            }
        </style>
    </x-slot>
</x-app-layout>