<x-app-layout>
    <x-slot name="pagetitle">Daftar Pending Pembayaran Perusahaan</x-slot>

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
            .btn-action {
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
        
        .btn-action {
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .select2-container {
            width: 100% !important;
        }
        
        .modal-dialog {
            max-width: 500px;
        }
        
        .card-company {
            border-color: #17a2b8;
        }
        .card-company .card-header {
            background-color: #17a2b8;
            color: #fff;
        }
        
        .text-danger {
            color: #dc3545 !important;
        }
        .text-warning {
            color: #ffc107 !important;
        }
        .text-success {
            color: #198754 !important;
        }
        .text-info {
            color: #17a2b8 !important;
        }
    </style>

    <div class="print-header">
        <h4>Daftar Pending Pembayaran Perusahaan</h4>
        <p>Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</p>
        <hr>
    </div>

    <div class="app-content-header no-print">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-building text-info"></i> Daftar Pending Pembayaran Perusahaan</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-primary btn-sm" onclick="reloadTableCompany()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-company card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0">Data Transaksi Masuk</h5>
                </div>
                <div class="card-body">
                    <table id="tblCompany" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th width="30">No</th>
                                <th width="120">No. Nota</th>
                                <th width="90">Tgl. Nota</th>
                                <th width="100">Jatuh Tempo</th>
                                <th>Nama Transaksi</th>
                                <th width="150">Company/PT</th>
                                <th width="120" class="text-end">Total</th>
                                <th width="120" class="text-end">Terbayar</th>
                                <th width="120" class="text-end">Sisa</th>
                                <th width="80" class="text-center">Angsuran</th>
                                <th width="120" class="text-center">Status</th>
                                <th width="100" class="text-center">Action</th>
                            </tr>
                            <tr class="filters no-print">
                                <th></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="1"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="2"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="3"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="4"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="5"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="6" style="text-align: right;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="7" style="text-align: right;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="8" style="text-align: right;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="9" style="text-align: center;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="10" style="text-align: center;"></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="table-info">
                            <tr>
                                <th colspan="6" class="text-end"><strong>TOTAL PENDING COMPANY</strong></th>
                                <th id="totalJumlahCompany" class="text-end"></th>
                                <th id="totalTerbayarCompany" class="text-end"></th>
                                <th id="totalSisaCompany" class="text-end fw-bold"></th>
                                <th></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Terima Pembayaran Company -->
    <div class="modal fade" id="bayarModalCompany" tabindex="-1" aria-labelledby="bayarModalCompanyLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bayarModalCompanyLabel">Terima Pembayaran dari Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bayarFormCompany">
                        <input type="hidden" name="nota_id" id="nota_id_company">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="tanggal_bayar_company" class="form-label">Tanggal Terima <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_bayar_company" name="tanggal_bayar" required value="{{ date('Y-m-d') }}">
                        </div>
                        
                        <div class="mb-3">
                            <label for="idrek_company" class="form-label">Rekening <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="idrek_company" name="idrek" required>
                                <option value="">Pilih Rekening</option>
                                @foreach($rekenings as $rek)
                                    <option value="{{ $rek->idrek }}">{{ $rek->norek }} - {{ $rek->namarek }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="jumlah_bayar_company" class="form-label">Jumlah Terima <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="jumlah_bayar_company" name="jumlah_bayar" min="1" required>
                            <small class="text-muted" id="sisaTextCompany">Sisa Pending: Rp 0</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="keterangan_company" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan_company" name="keterangan" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-info" id="simpanBayarCompany">Terima Pembayaran</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Detail Nota Company -->
    <div class="modal fade" id="detailModalCompany" tabindex="-1" aria-labelledby="detailModalCompanyLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalCompanyLabel">Detail Pending Pembayaran Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detailLoadingCompany" class="text-center">
                        <div class="spinner-border text-info" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="detailContentCompany" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">No. Nota</th>
                                        <td id="detailNoNotaCompany"></td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Nota</th>
                                        <td id="detailTanggalCompany"></td>
                                    </tr>
                                    <tr>
                                        <th>Jatuh Tempo</th>
                                        <td id="detailTglTempoCompany"></td>
                                    </tr>
                                    <tr>
                                        <th>Nama Transaksi</th>
                                        <td id="detailNamaTransaksiCompany"></td>
                                    </tr>
                                    <tr>
                                        <th>Cashflow</th>
                                        <td id="detailCashflowCompany"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">Company/PT</th>
                                        <td id="detailCompany"></td>
                                    </tr>
                                    <tr>
                                        <th>Total Pending</th>
                                        <td id="detailTotalNotaCompany" class="fw-bold"></td>
                                    </tr>
                                    <tr>
                                        <th>Terbayar</th>
                                        <td id="detailTerbayarCompany"></td>
                                    </tr>
                                    <tr>
                                        <th>Sisa Pending</th>
                                        <td id="detailSisaCompany" class="fw-bold text-info"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Riwayat Penerimaan Pembayaran</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="detailAngsuranCompany">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th width="100">Tanggal</th>
                                        <th>Rekening</th>
                                        <th class="text-end">Jumlah</th>
                                        <th>Keterangan</th>
                                        <th width="80" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>TOTAL TERBAYAR</strong></td>
                                        <td id="detailTotalTerbayarCompany" class="text-end fw-bold"></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
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
        <script>
            let tableCompany;
            let currentNotaIdCompany = null;
            let currentSisaCompany = 0;
            let globalTotalsCompany = { total: 0, terbayar: 0, sisa: 0 };

            function formatRupiah(angka) {
                if (!angka || isNaN(angka) || parseFloat(angka) === 0) return '-';
                return 'Rp ' + parseFloat(angka).toLocaleString('id-ID', { 
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function parseRupiahToNumber(rupiahString) {
                if (!rupiahString) return 0;
                const cleaned = rupiahString.toString()
                    .replace('Rp', '')
                    .replace(/\./g, '')
                    .replace(/\s/g, '')
                    .trim();
                return parseFloat(cleaned) || 0;
            }

            function formatDate(dateString) {
                if (!dateString) return '-';
                if (dateString.includes('/')) {
                    return dateString;
                }
                return dateString ? moment(dateString).format('DD/MM/YYYY') : '-';
            }

            function getStatusBadge(status) {
                let badgeClass = 'bg-secondary';
                let badgeText = status;
                
                switch(status) {
                    case 'paid':
                        badgeClass = 'bg-success';
                        badgeText = 'LUNAS';
                        break;
                    case 'open':
                        badgeClass = 'bg-warning';
                        badgeText = 'BELUM LUNAS';
                        break;
                    case 'partial':
                        badgeClass = 'bg-info';
                        badgeText = 'SEBAGIAN';
                        break;
                    case 'cancel':
                        badgeClass = 'bg-danger';
                        badgeText = 'BATAL';
                        break;
                }
                
                return `<span class="badge ${badgeClass} status-badge">${badgeText}</span>`;
            }

            function reloadTableCompany() {
                if (tableCompany) {
                    tableCompany.ajax.reload(null, false);
                }
            }

            function showDetailCompany(notaId) {
                $('#detailLoadingCompany').show();
                $('#detailContentCompany').hide();
                
                $.ajax({
                    url: '/pending/show/' + notaId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            const summary = response.summary;
                            
                            $('#detailNoNotaCompany').text(data.nota_no || '-');
                            $('#detailTanggalCompany').text(formatDate(data.tanggal));
                            $('#detailTglTempoCompany').text(formatDate(data.tgl_tempo));
                            $('#detailNamaTransaksiCompany').text(data.namatransaksi || '-');
                            $('#detailCashflowCompany').text(data.cashflow === 'in' ? 'PEMASUKAN' : 'PENGELUARAN');
                            // Perubahan di sini - gunakan companyUnit
                            $('#detailCompany').text(response.data.company_unit?.company_name ?? '-');
                            $('#detailProjectCompany').text(data.project ? data.project.namaproject : '-');
                            $('#detailTotalNotaCompany').text(formatRupiah(data.total));
                            $('#detailTerbayarCompany').text(formatRupiah(summary.terbayar));
                            $('#detailSisaCompany').text(formatRupiah(summary.sisa));
                            
                            const angsuranBody = $('#detailAngsuranCompany tbody');
                            angsuranBody.empty();
                            
                            if (data.angsuran && data.angsuran.length > 0) {
                                data.angsuran.forEach((item, index) => {
                                    const tanggal = formatDate(item.tanggal);
                                    const rekening = item.rekening ? item.rekening.norek + ' - ' + item.rekening.namarek : '-';
                                    const jumlah = formatRupiah(item.jumlah);
                                    
                                    angsuranBody.append(`
                                        <tr>
                                            <td class="text-center">${index + 1}</td>
                                            <td>${tanggal}</td>
                                            <td>${rekening}</td>
                                            <td class="text-end">${jumlah}</td>
                                            <td>${item.keterangan || '-'}</td>
                                            <td class="text-center">
                                                <button class="btn btn-xs btn-danger hapus-angsuran-btn-company" 
                                                        data-id="${item.id}"
                                                        title="Hapus Penerimaan">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `);
                                });
                            } else {
                                angsuranBody.append(`
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            Belum ada riwayat penerimaan pembayaran
                                        </td>
                                    </tr>
                                `);
                            }
                            
                            $('#detailTotalTerbayarCompany').text(formatRupiah(summary.terbayar));
                            
                            $('#detailLoadingCompany').hide();
                            $('#detailContentCompany').show();
                            $('#detailModalCompany').modal('show');
                        } else {
                            alert(response.message || 'Gagal mengambil detail pending pembayaran');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        alert('Terjadi kesalahan saat mengambil detail pending pembayaran');
                    }
                });
            }

            function openBayarModalCompany(notaId) {
                currentNotaIdCompany = notaId;
                
                $.ajax({
                    url: '/pending/show/' + notaId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const summary = response.summary;
                            currentSisaCompany = parseRupiahToNumber(summary.sisa);
                            
                            $('#nota_id_company').val(notaId);
                            $('#tanggal_bayar_company').val(moment().format('YYYY-MM-DD'));
                            $('#sisaTextCompany').text('Sisa Pending: ' + formatRupiah(currentSisaCompany));
                            $('#jumlah_bayar_company').val(currentSisaCompany);
                            $('#jumlah_bayar_company').attr('max', currentSisaCompany);
                            $('#keterangan_company').val('');
                            
                            $('#idrek_company').select2({
                                dropdownParent: $('#bayarModalCompany')
                            });
                            
                            $('#bayarModalCompany').modal('show');
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + xhr.responseJSON.message);
                    }
                });
            }

            $(document).ready(function () {
                $('#idrek_company').select2({
                    dropdownParent: $('#bayarModalCompany')
                });

                tableCompany = $('#tblCompany').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: true,
                    paging: true,
                    pageLength: 50,
                    lengthMenu: [25, 50, 100, 200],
                    ajax: {
                        url: "{{ route('company.pending.data.pembayaran') }}",
                        type: 'GET',
                        dataSrc: function(json) {
                            console.log('COMPANY - Full response:', json);
                            
                            let totalJumlah = 0;
                            let totalTerbayar = 0;
                            let totalSisa = 0;
                            
                            if (json.data && json.data.length > 0) {
                                json.data.forEach(function(row) {
                                    const total = parseRupiahToNumber(row.total);
                                    const terbayar = parseRupiahToNumber(row.terbayar);
                                    const sisa = parseRupiahToNumber(row.sisa);
                                    
                                    totalJumlah += total;
                                    totalTerbayar += terbayar;
                                    totalSisa += sisa;
                                    
                                    row._total_numeric = total;
                                    row._terbayar_numeric = terbayar;
                                    row._sisa_numeric = sisa;
                                });
                            }
                            
                            globalTotalsCompany = { total: totalJumlah, terbayar: totalTerbayar, sisa: totalSisa };
                            
                            $('#totalJumlahCompany').text(formatRupiah(globalTotalsCompany.total));
                            $('#totalTerbayarCompany').text(formatRupiah(globalTotalsCompany.terbayar));
                            $('#totalSisaCompany').text(formatRupiah(globalTotalsCompany.sisa));
                            
                            console.log('COMPANY - Calculated totals:', globalTotalsCompany);
                            
                            return json.data || [];
                        },
                        error: function(xhr, error, thrown) {
                            console.error('COMPANY - AJAX Error:', {
                                xhr: xhr,
                                error: error,
                                thrown: thrown,
                                responseText: xhr.responseText
                            });
                            alert('Error loading data. Please check console for details.');
                        }
                    },
                    columns: [
                        { 
                            data: null,
                            className: 'text-center',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        { 
                            data: 'nota_no',
                            name: 'nota_no',
                            className: 'text-center'
                        },
                        { 
                            data: 'tanggal',
                            name: 'tanggal',
                            className: 'text-center',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        { 
                            data: 'tgl_tempo',
                            name: 'tgl_tempo',
                            className: 'text-center',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        { 
                            data: 'namatransaksi',
                            name: 'namatransaksi'
                        },
                        { 
                            // PERUBAHAN PENTING: Gunakan company_unit bukan company
                            data: 'company_unit.company_name',
                            name: 'company_unit.company_name',
                            defaultContent: '-',
                            render: function(data, type, row) {
                                return data || (row.company_unit && row.company_unit.company_name) || '-';
                            }
                        },
                        { 
                            data: 'total',
                            name: 'total',
                            className: 'text-end',
                            type: 'num',
                            render: function(data, type, row) {
                                if (type === 'display' || type === 'filter') {
                                    return data || '-';
                                }
                                return row._total_numeric || parseRupiahToNumber(data) || 0;
                            }
                        },
                        { 
                            data: 'terbayar',
                            name: 'terbayar',
                            className: 'text-end',
                            type: 'num',
                            render: function(data, type, row) {
                                if (type === 'display' || type === 'filter') {
                                    return data || '-';
                                }
                                return row._terbayar_numeric || parseRupiahToNumber(data) || 0;
                            }
                        },
                        { 
                            data: 'sisa',
                            name: 'sisa',
                            className: 'text-end fw-bold',
                            type: 'num',
                            render: function(data, type, row) {
                                if (type === 'display' || type === 'filter') {
                                    const sisaValue = parseRupiahToNumber(data);
                                    const formatted = formatRupiah(sisaValue);
                                    
                                    if (sisaValue > 0) {
                                        return `<span class="text-info">${formatted}</span>`;
                                    } else if (sisaValue < 0) {
                                        return `<span class="text-danger">${formatted}</span>`;
                                    } else {
                                        return `<span class="text-success">${formatted}</span>`;
                                    }
                                }
                                return row._sisa_numeric || parseRupiahToNumber(data) || 0;
                            }
                        },
                        { 
                            data: 'angsuran_count',
                            name: 'angsuran_count',
                            className: 'text-center',
                            defaultContent: '0',
                            render: function(data) {
                                return data || '0';
                            }
                        },
                        { 
                            data: 'status',
                            name: 'status',
                            className: 'text-center',
                            render: function(data) {
                                return getStatusBadge(data || 'open');
                            }
                        },
                        { 
                            data: 'id',
                            name: 'id',
                            className: 'text-center',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                const notaId = data || row.id;
                                if (!notaId) {
                                    console.error('COMPANY - No ID found for row:', row);
                                    return '<span class="text-danger">Error</span>';
                                }
                                
                                return `
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-info btn-action" 
                                                onclick="showDetailCompany(${notaId})"
                                                title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-success btn-action" 
                                                onclick="openBayarModalCompany(${notaId})"
                                                title="Terima Pembayaran">
                                            <i class="bi bi-cash"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        }
                    ],
                    order: [[3, 'asc']],
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
                                        if (column === 10) {
                                            return $(node).text();
                                        }
                                        if (column === 6 || column === 7 || column === 8) {
                                            return parseRupiahToNumber(data);
                                        }
                                        return data;
                                    }
                                }
                            },
                            title: 'Daftar Pending Pembayaran Company',
                            messageTop: 'Tanggal Export: ' + moment().format('DD/MM/YYYY HH:mm'),
                            filename: function() {
                                return 'Pending_Pembayaran_Company_' + moment().format('YYYYMMDD_HHmmss');
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="bi bi-printer"></i> Print',
                            className: 'btn btn-primary btn-sm',
                            title: '',
                            messageTop: function() {
                                return `<div class="print-header">
                                            <h4 style="text-align: center; margin-bottom: 10px;">Daftar Pending Pembayaran Perusahaan</h4>
                                            <p style="text-align: center; margin-bottom: 15px;">
                                                Tanggal Cetak: ${moment().format('DD/MM/YYYY HH:mm')}
                                            </p>
                                        </div>`;
                            },
                            exportOptions: { 
                                columns: ':visible',
                                stripHtml: false
                            },
                            customize: function(win) {
                                $(win.document.body).find('table').addClass('compact').css('font-size', '10pt');
                                $(win.document.body).css('font-size', '10pt');
                                
                                const totals = `
                                    <div style="margin-top: 20px; font-weight: bold; border-top: 2px solid #000; padding-top: 10px;">
                                        <div style="float: right; text-align: right;">
                                            <div>Total Pending: ${formatRupiah(globalTotalsCompany.total)}</div>
                                            <div>Total Terbayar: ${formatRupiah(globalTotalsCompany.terbayar)}</div>
                                            <div>Total Sisa: ${formatRupiah(globalTotalsCompany.sisa)}</div>
                                        </div>
                                        <div style="clear: both;"></div>
                                    </div>
                                `;
                                
                                $(win.document.body).append(totals);
                            }
                        }
                    ],
                    language: {
                        emptyTable: "Tidak ada data pending pembayaran perusahaan",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        lengthMenu: "Tampilkan _MENU_ data",
                        loadingRecords: "Memuat...",
                        processing: "Memproses...",
                        search: "Cari:",
                        zeroRecords: "Tidak ditemukan data yang sesuai",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    },
                    initComplete: function() {
                        console.log('COMPANY - DataTable initialized successfully');
                        
                        this.api().columns().every(function() {
                            const column = this;
                            const input = $('input[data-column="' + column.index() + '"]');
                            
                            if (input.length) {
                                input.on('keyup change', function() {
                                    if (column.search() !== this.value) {
                                        column.search(this.value).draw();
                                    }
                                });
                            }
                        });
                    },
                    drawCallback: function() {
                        console.log('COMPANY - DataTable draw complete, rows:', this.api().rows().count());
                    }
                });

                $('#simpanBayarCompany').click(function() {
                    const formData = $('#bayarFormCompany').serialize();
                    
                    if (!$('#idrek_company').val()) {
                        alert('Pilih rekening terlebih dahulu');
                        return;
                    }
                    
                    const jumlah = parseFloat($('#jumlah_bayar_company').val());
                    if (jumlah > currentSisaCompany) {
                        alert('Jumlah melebihi sisa pending pembayaran');
                        return;
                    }
                    
                    if (jumlah <= 0) {
                        alert('Jumlah harus lebih dari 0');
                        return;
                    }
                    
                    $.ajax({
                        url: '/pending/bayar/' + currentNotaIdCompany,
                        type: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                $('#bayarModalCompany').modal('hide');
                                tableCompany.ajax.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                const errors = xhr.responseJSON.errors;
                                let errorMessage = '';
                                $.each(errors, function(key, value) {
                                    errorMessage += value[0] + '\n';
                                });
                                alert('Validasi Error:\n' + errorMessage);
                            } else {
                                alert('Error: ' + xhr.responseJSON.message);
                            }
                        }
                    });
                });

                $(document).on('click', '.hapus-angsuran-btn-company', function(e) {
                    e.stopPropagation();
                    const angsuranId = $(this).data('id');
                    
                    if (confirm('Apakah Anda yakin ingin menghapus penerimaan pembayaran ini?')) {
                        $.ajax({
                            url: '/pending/angsuran/' + angsuranId,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert(response.message);
                                    showDetailCompany(currentNotaIdCompany);
                                    tableCompany.ajax.reload();
                                } else {
                                    alert('Error: ' + response.message);
                                }
                            },
                            error: function(xhr) {
                                alert('Error: ' + xhr.responseJSON.message);
                            }
                        });
                    }
                });
            });
        </script>
    </x-slot>
</x-app-layout>