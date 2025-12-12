<x-app-layout>
    <x-slot name="pagetitle">Laporan Cashflow PT</x-slot>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .card, .card-body {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10pt !important;
            }
            th, td {
                border: 1px solid #000 !important;
                padding: 3px 5px !important;
            }
            .dataTables_info, .dataTables_paginate, .dataTables_length, .dataTables_filter {
                display: none !important;
            }
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 15px;
            }
            .btn-view-detail {
                display: none !important;
            }
        }
        .filter-input {
            width: 100%;
            padding: 0.25rem;
            font-size: 0.875rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }
        .print-header {
            display: none;
        }
        .dataTables_wrapper .dt-buttons {
            margin-bottom: 10px;
        }
        .btn-view-detail {
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
            line-height: 1.2;
            margin-left: 5px;
        }
        .nota-no-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>

    <div class="print-header">
        <h4>Laporan Cashflow PT</h4>
        <p>Periode: <span id="printPeriod"></span></p>
        <p>Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</p>
        <hr>
    </div>

    <div class="app-content-header no-print">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-cash-coin"></i> Laporan Cashflow PT</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <input type="date" id="start_date" class="form-control form-control-sm w-auto" 
                               value="{{ $startDate }}" onchange="reloadTable()">
                        <span class="align-self-center">s/d</span>
                        <input type="date" id="end_date" class="form-control form-control-sm w-auto" 
                               value="{{ $endDate }}" onchange="reloadTable()">
                        <button class="btn btn-primary btn-sm" onclick="resetFilter()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-success card-outline">
                <div class="card-body">
                    <table id="tblCashflowPT" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th width="30">No</th>
                                <th width="200">No. Nota</th>
                                <th width="90">Tgl. Trans</th>
                                <th>Nama Transaksi</th>
                                <th width="120" class="text-end">Pemasukan</th>
                                <th width="120" class="text-end">Pengeluaran</th>
                                <th width="120" class="text-end">Saldo</th>
                                <th width="150">Vendor</th>
                                <th width="100">Rekening</th>
                                <th width="150">PT/Company</th>
                            </tr>
                            <tr class="filters no-print">
                                <th></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="1"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="2"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="3"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="4" style="text-align: right;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="5" style="text-align: right;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="6" style="text-align: right;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="7"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="8"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="9"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="table-info">
                            <tr>
                                <th colspan="4" class="text-end"><strong>TOTAL</strong></th>
                                <th id="totalPemasukan" class="text-end"></th>
                                <th id="totalPengeluaran" class="text-end"></th>
                                <th id="saldoAkhir" class="text-end"></th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Detail Transaksi PT -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Transaksi PT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detailLoading" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="detailContent" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">No. Nota</th>
                                        <td id="detailNoNota"></td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal</th>
                                        <td id="detailTanggal"></td>
                                    </tr>
                                    <tr>
                                        <th>Nama Transaksi</th>
                                        <td id="detailNamaTransaksi"></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td><span id="detailStatus" class="badge"></span></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Method</th>
                                        <td id="detailPaymentMethod"></td>
                                    </tr>
                                    <tr>
                                        <th>Cashflow</th>
                                        <td id="detailCashflow"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">Vendor</th>
                                        <td id="detailVendor"></td>
                                    </tr>
                                    <tr>
                                        <th>Rekening</th>
                                        <td id="detailRekening"></td>
                                    </tr>
                                    <tr>
                                        <th>PT/Company</th>
                                        <td id="detailCompany"></td>
                                    </tr>
                                    <tr>
                                        <th>Dibuat Oleh</th>
                                        <td id="detailCreatedBy"></td>
                                    </tr>
                                    <tr>
                                        <th>Dibuat Tanggal</th>
                                        <td id="detailCreatedAt"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Detail Item</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="detailItems">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Kode Transaksi</th>
                                        <th>Deskripsi</th>
                                        <th class="text-end">Nominal</th>
                                        <th class="text-end">Jumlah</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                                        <td id="detailSubtotal" colspan="3" class="text-end"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>PPN</strong></td>
                                        <td id="detailPpn" colspan="3" class="text-end"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Diskon</strong></td>
                                        <td id="detailDiskon" colspan="3" class="text-end"></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td colspan="3" class="text-end"><strong>GRAND TOTAL</strong></td>
                                        <td id="detailGrandTotal" colspan="3" class="text-end"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">History Pembayaran</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="detailPayments">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Rekening</th>
                                        <th class="text-end">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">History Perubahan</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="detailLogs">
                                <thead class="table-light">
                                    <tr>
                                        <th width="150">Tanggal</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
        <script> window.JSZip = JSZip; </script>

        <script>
            let table;
            let globalTotals = { pemasukan: 0, pengeluaran: 0, saldo_akhir: 0 };
            let originalData = [];

            function reloadTable() {
                if (table) {
                    updatePrintPeriod();
                    table.ajax.reload(null, false);
                }
            }

            function updatePrintPeriod() {
                const start = $('#start_date').val();
                const end = $('#end_date').val();
                const startFormatted = moment(start).format('DD/MM/YYYY');
                const endFormatted = moment(end).format('DD/MM/YYYY');
                $('#printPeriod').text(`${startFormatted} - ${endFormatted}`);
            }

            function resetFilter() {
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                
                $('#start_date').val(firstDay.toISOString().split('T')[0]);
                $('#end_date').val(lastDay.toISOString().split('T')[0]);
                
                if (table) {
                    table.columns().search('');
                    table.columns().every(function() {
                        const column = this;
                        const input = $(`input[data-column="${column.index()}"]`);
                        if (input.length) {
                            input.val('');
                        }
                    });
                    table.search('').draw();
                }
                
                reloadTable();
            }

            function formatRupiah(angka) {
                if (!angka || isNaN(angka) || parseFloat(angka) === 0) return '-';
                return 'Rp ' + parseFloat(angka).toLocaleString('id-ID', { 
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function showDetail(notaId) {
                console.log('Mencoba menampilkan detail nota PT dengan ID:', notaId);
                
                $('#detailLoading').show();
                $('#detailContent').hide();
                
                $.ajax({
                    url: "{{ route('transaksi.laporan.cashflow_pt.view_nota') }}",
                    method: 'GET',
                    data: { id: notaId },
                    success: function(response) {
                        console.log('Response dari server PT:', response);
                        
                        if (response.success) {
                            const data = response.data;
                            
                            // Isi data header
                            $('#detailNoNota').text(data.nota.nota_no || '-');
                            $('#detailTanggal').text(data.nota.tanggal || '-');
                            $('#detailNamaTransaksi').text(data.nota.namatransaksi || '-');
                            
                            // Status badge
                            let statusClass = 'bg-secondary';
                            let statusText = data.nota.status || 'unknown';
                            
                            switch(statusText.toLowerCase()) {
                                case 'paid': statusClass = 'bg-success'; break;
                                case 'open': statusClass = 'bg-warning'; break;
                                case 'partial': statusClass = 'bg-info'; break;
                                case 'cancel': statusClass = 'bg-danger'; break;
                            }
                            $('#detailStatus')
                                .text(statusText.toUpperCase())
                                .removeClass()
                                .addClass('badge ' + statusClass);
                            
                            $('#detailPaymentMethod').text(data.nota.paymen_method || '-');
                            $('#detailCashflow').text(data.nota.cashflow || '-');
                            $('#detailVendor').text(data.nota.vendor || '-');
                            $('#detailRekening').text(data.nota.rekening || '-');
                            $('#detailCompany').text(data.nota.company || '-'); // Untuk PT
                            $('#detailCreatedBy').text(data.nota.namauser || '-');
                            $('#detailCreatedAt').text(data.nota.created_at || '-');
                            
                            // Isi items
                            const itemsBody = $('#detailItems tbody');
                            itemsBody.empty();
                            
                            if (data.items && data.items.length > 0) {
                                data.items.forEach((item, index) => {
                                    itemsBody.append(`
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td>${item.kodetransaksi || '-'}<br><small>${item.namatransaksi || '-'}</small></td>
                                            <td>${item.description || '-'}</td>
                                            <td class="text-end">Rp ${item.nominal || '0'}</td>
                                            <td class="text-end">${item.jml || '0'}</td>
                                            <td class="text-end">Rp ${item.total || '0'}</td>
                                        </tr>
                                    `);
                                });
                            } else {
                                itemsBody.append('<tr><td colspan="6" class="text-center">Tidak ada item transaksi</td></tr>');
                            }
                            
                            // Isi footer
                            $('#detailSubtotal').text('Rp ' + (data.nota.subtotal || '0'));
                            $('#detailPpn').text('Rp ' + (data.nota.ppn || '0'));
                            $('#detailDiskon').text('Rp ' + (data.nota.diskon || '0'));
                            $('#detailGrandTotal').text('Rp ' + (data.nota.total || '0'));
                            
                            // Isi payments
                            const paymentsBody = $('#detailPayments tbody');
                            paymentsBody.empty();
                            if (data.payments && data.payments.length > 0) {
                                data.payments.forEach(payment => {
                                    paymentsBody.append(`
                                        <tr>
                                            <td>${payment.tanggal || '-'}</td>
                                            <td>${payment.rekening || '-'}</td>
                                            <td class="text-end">Rp ${payment.jumlah || '0'}</td>
                                        </tr>
                                    `);
                                });
                            } else {
                                paymentsBody.append('<tr><td colspan="3" class="text-center">Belum ada pembayaran</td></tr>');
                            }
                            
                            // Isi logs
                            const logsBody = $('#detailLogs tbody');
                            logsBody.empty();
                            if (data.logs && data.logs.length > 0) {
                                data.logs.forEach(log => {
                                    logsBody.append(`
                                        <tr>
                                            <td>${log.tanggal || '-'}</td>
                                            <td>${log.keterangan || '-'}</td>
                                        </tr>
                                    `);
                                });
                            } else {
                                logsBody.append('<tr><td colspan="2" class="text-center">Tidak ada history perubahan</td></tr>');
                            }
                            
                            $('#detailLoading').hide();
                            $('#detailContent').show();
                            $('#detailModal').modal('show');
                        } else {
                            alert('Gagal mengambil detail transaksi: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        alert('Terjadi kesalahan saat mengambil detail transaksi');
                    }
                });
            }

            $(document).ready(function () {
                // Update print period initially
                updatePrintPeriod();

                // Initialize DataTable
                table = $('#tblCashflowPT').DataTable({
                    processing: true,
                    serverSide: false,
                    searching: true,
                    paging: true,
                    pageLength: 50,
                    lengthMenu: [25, 50, 100, 200],
                    ajax: {
                        url: "{{ route('transaksi.laporan.cashflow_pt.data') }}",
                        data: function(d) {
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                        },
                        dataSrc: function(json) {
                            console.log('Data PT dari API:', json); // DEBUG
                            
                            // Store original data
                            originalData = json.data;
                            
                            // Store global totals
                            globalTotals = json.total || { pemasukan: 0, pengeluaran: 0, saldo_akhir: 0 };
                            
                            // Update footer with global totals
                            $('#totalPemasukan').text(formatRupiah(globalTotals.pemasukan));
                            $('#totalPengeluaran').text(formatRupiah(globalTotals.pengeluaran));
                            $('#saldoAkhir').text(formatRupiah(globalTotals.saldo_akhir));
                            
                            // Debug: lihat struktur data pertama
                            if (json.data && json.data.length > 0) {
                                console.log('Contoh data pertama PT:', json.data[0]);
                                console.log('Key yang tersedia:', Object.keys(json.data[0]));
                            }
                            
                            return json.data;
                        },
                        error: function(xhr, error, thrown) {
                            console.error('Error loading data PT:', error);
                            alert('Terjadi kesalahan saat memuat data. Silakan coba lagi.');
                        }
                    },
                    columns: [
                        { 
                            data: null, 
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            },
                            className: 'text-center',
                            orderable: false,
                            searchable: false
                        },
                        { 
                            data: 'nota_no',
                            className: 'text-center',
                            render: function(data, type, row) {
                                console.log('Row data PT untuk nota_no:', row);
                                
                                const id = row.id;
                                
                                if (!id) {
                                    console.error('ID tidak ditemukan untuk row PT:', row);
                                    return `<span>${data}</span> <small class="text-danger">(no ID)</small>`;
                                }
                                
                                return `
                                    <div class="nota-no-container">
                                        <span>${data}</span>
                                        <button class="btn btn-xs btn-info btn-view-detail" 
                                                onclick="showDetail(${id})"
                                                title="View Detail PT">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        },
                        { 
                            data: 'tanggal', 
                            className: 'text-center',
                            render: function(data) {
                                return data ? moment(data).format('DD/MM/YYYY') : '-';
                            }
                        },
                        { 
                            data: 'namatransaksi'
                        },
                        { 
                            data: 'pemasukan', 
                            className: 'text-end',
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        { 
                            data: 'pengeluaran', 
                            className: 'text-end',
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        { 
                            data: 'saldo', 
                            className: 'text-end fw-bold',
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        { 
                            data: 'namavendor', 
                            className: 'text-center'
                        },
                        { 
                            data: 'rekening', 
                            className: 'text-center'
                        },
                        { 
                            data: 'nama_company', 
                            className: 'text-center'
                        }
                    ],
                    dom: 
                        "<'row mb-2 no-print'<'col-md-6 d-flex align-items-center'B><'col-md-6 d-flex justify-content-end'f>>" +
                        "<'row mb-2 no-print'<'col-md-6'l><'col-md-6 text-end'i>>" +
                        "<'row'<'col-12'tr>>" +
                        "<'row mt-2 no-print'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                            className: 'btn btn-success btn-sm',
                            exportOptions: { 
                                columns: ':visible',
                                format: {
                                    body: function(data, row, column, node) {
                                        // Remove HTML tags and button for export
                                        if (column === 1) {
                                            const tempDiv = document.createElement('div');
                                            tempDiv.innerHTML = data;
                                            const span = tempDiv.querySelector('span');
                                            return span ? span.textContent : data;
                                        }
                                        if (column === 5 || column === 6 || column === 7) {
                                            const num = data.replace('Rp ', '').replace(/\./g, '').replace(',', '.');
                                            return isNaN(num) ? 0 : parseFloat(num);
                                        }
                                        return data;
                                    }
                                }
                            },
                            title: function() {
                                const start = $('#start_date').val();
                                const end = $('#end_date').val();
                                return `Laporan Cashflow PT ${moment(start).format('DD-MM-YYYY')} - ${moment(end).format('DD-MM-YYYY')}`;
                            },
                            messageTop: function() {
                                const start = $('#start_date').val();
                                const end = $('#end_date').val();
                                return `Periode: ${moment(start).format('DD/MM/YYYY')} - ${moment(end).format('DD/MM/YYYY')}\nTanggal Export: ${moment().format('DD/MM/YYYY HH:mm')}`;
                            },
                            filename: function() {
                                const start = $('#start_date').val();
                                const end = $('#end_date').val();
                                return `Cashflow_PT_${moment(start).format('YYYYMMDD')}_${moment(end).format('YYYYMMDD')}`;
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="bi bi-printer"></i> Print',
                            className: 'btn btn-primary btn-sm',
                            title: '',
                            messageTop: function() {
                                const start = $('#start_date').val();
                                const end = $('#end_date').val();
                                return `<div class="print-header">
                                            <h4 style="text-align: center; margin-bottom: 10px;">Laporan Cashflow PT</h4>
                                            <p style="text-align: center; margin-bottom: 5px;">
                                                Periode: ${moment(start).format('DD/MM/YYYY')} - ${moment(end).format('DD/MM/YYYY')}
                                            </p>
                                            <p style="text-align: center; margin-bottom: 15px;">
                                                Tanggal Cetak: ${moment().format('DD/MM/YYYY HH:mm')}
                                            </p>
                                        </div>`;
                            },
                            exportOptions: { 
                                columns: ':visible',
                                stripHtml: false,
                                format: {
                                    body: function(data, row, column, node) {
                                        // Remove button for print
                                        if (column === 1) {
                                            const tempDiv = document.createElement('div');
                                            tempDiv.innerHTML = data;
                                            const span = tempDiv.querySelector('span');
                                            return span ? span.textContent : data;
                                        }
                                        return data;
                                    }
                                }
                            },
                            customize: function(win) {
                                $(win.document.body).find('table').addClass('compact').css('font-size', '10pt');
                                $(win.document.body).css('font-size', '10pt');
                                
                                const totals = `
                                    <div style="margin-top: 20px; font-weight: bold; border-top: 2px solid #000; padding-top: 10px;">
                                        <div style="float: right; text-align: right;">
                                            <div>Total Pemasukan: ${formatRupiah(globalTotals.pemasukan)}</div>
                                            <div>Total Pengeluaran: ${formatRupiah(globalTotals.pengeluaran)}</div>
                                            <div>Saldo Akhir: ${formatRupiah(globalTotals.saldo_akhir)}</div>
                                        </div>
                                        <div style="clear: both;"></div>
                                    </div>
                                `;
                                
                                $(win.document.body).append(totals);
                                
                                $(win.document.body).find('table').css('margin', '0 auto');
                            }
                        }
                    ],
                    footerCallback: function(row, data, start, end, display) {
                        let totalPemasukan = 0;
                        let totalPengeluaran = 0;
                        
                        const api = this.api();
                        const rows = api.rows({ page: 'current' }).data();
                        
                        rows.each(function(row) {
                            totalPemasukan += parseFloat(row.pemasukan) || 0;
                            totalPengeluaran += parseFloat(row.pengeluaran) || 0;
                        });
                        
                        let lastSaldo = 0;
                        if (rows.length > 0) {
                            const lastRow = rows[rows.length - 1];
                            lastSaldo = parseFloat(lastRow.saldo) || 0;
                        }
                        
                        $('#totalPemasukan').text(formatRupiah(totalPemasukan));
                        $('#totalPengeluaran').text(formatRupiah(totalPengeluaran));
                        $('#saldoAkhir').text(formatRupiah(lastSaldo));
                    }
                });

                // Apply filter on header inputs
                $('#tblCashflowPT thead').on('keyup change', 'input.filter-input', function() {
                    const columnIndex = $(this).data('column');
                    const value = this.value;
                    
                    table.column(columnIndex)
                        .search(value)
                        .draw();
                });

                // Clear filter when clicking X in search input
                $('#tblCashflowPT thead').on('search', 'input.filter-input', function() {
                    if (this.value === '') {
                        const columnIndex = $(this).data('column');
                        table.column(columnIndex).search('').draw();
                    }
                });
            });
        </script>
    </x-slot>
</x-app-layout>