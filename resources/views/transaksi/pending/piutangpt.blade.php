<x-app-layout>
    <x-slot name="pagetitle">Daftar Piutang</x-slot>

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
        
        .card-piutang {
            border-color: #6f42c1;
        }
        .card-piutang .card-header {
            background-color: #6f42c1;
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
        .text-purple {
            color: #6f42c1 !important;
        }
        .text-info {
            color: #17a2b8 !important;
        }
        
        .overdue {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        .due-soon {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
    </style>

    <div class="print-header">
        <h4>Daftar Piutang</h4>
        <p>Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</p>
        <hr>
    </div>

    <div class="app-content-header no-print">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-credit-card text-purple"></i> Daftar Piutang</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-primary btn-sm" onclick="reloadTablePiutang()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-piutang card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0">Data Transaksi Keluar</h5>
                </div>
                <div class="card-body">
                    <table id="tblPiutang" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th width="30">No</th>
                                <th width="120">No. Nota</th>
                                <th width="90">Tgl. Nota</th>
                                <th width="100">Jatuh Tempo</th>
                                <th width="120">Hari</th>
                                <th>Nama Transaksi</th>
                                <th width="150">Customer/Retail</th>
                                <th width="120" class="text-end">Total Piutang</th>
                                <th width="120" class="text-end">Terbayar</th>
                                <th width="120" class="text-end">Sisa Piutang</th>
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
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="6"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="7" style="text-align: right;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="8" style="text-align: right;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="9" style="text-align: right;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="10" style="text-align: center;"></th>
                                <th><input type="text" class="filter-input" placeholder="Cari..." data-column="11" style="text-align: center;"></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <th colspan="7" class="text-end"><strong>TOTAL PIUTANG</strong></th>
                                <th id="totalJumlahPiutang" class="text-end"></th>
                                <th id="totalTerbayarPiutang" class="text-end"></th>
                                <th id="totalSisaPiutang" class="text-end fw-bold text-purple"></th>
                                <th></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Terima Pembayaran Piutang -->
    <div class="modal fade" id="bayarModalPiutang" tabindex="-1" aria-labelledby="bayarModalPiutangLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bayarModalPiutangLabel">Terima Pembayaran Piutang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bayarFormPiutang">
                        <input type="hidden" name="nota_id" id="nota_id_piutang">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="tanggal_bayar_piutang" class="form-label">Tanggal Terima <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_bayar_piutang" name="tanggal_bayar" required value="{{ date('Y-m-d') }}">
                        </div>
                        
                        <div class="mb-3">
                            <label for="idrek_piutang" class="form-label">Rekening <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="idrek_piutang" name="idrek" required>
                                <option value="">Pilih Rekening</option>
                                @foreach($rekenings as $rek)
                                    <option value="{{ $rek->idrek }}">{{ $rek->norek }} - {{ $rek->namarek }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="jumlah_bayar_piutang" class="form-label">Jumlah Terima <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="jumlah_bayar_piutang" name="jumlah_bayar" min="1" required>
                            <small class="text-muted" id="sisaTextPiutang">Sisa Piutang: Rp 0</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="keterangan_piutang" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan_piutang" name="keterangan" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-purple" id="simpanBayarPiutang">Terima Pembayaran</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Detail Nota Piutang -->
    <div class="modal fade" id="detailModalPiutang" tabindex="-1" aria-labelledby="detailModalPiutangLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalPiutangLabel">Detail Piutang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detailLoadingPiutang" class="text-center">
                        <div class="spinner-border text-purple" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="detailContentPiutang" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">No. Nota</th>
                                        <td id="detailNoNotaPiutang"></td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Nota</th>
                                        <td id="detailTanggalPiutang"></td>
                                    </tr>
                                    <tr>
                                        <th>Jatuh Tempo</th>
                                        <td id="detailTglTempoPiutang"></td>
                                    </tr>
                                    <tr>
                                        <th>Keterlambatan</th>
                                        <td id="detailKeterlambatanPiutang"></td>
                                    </tr>
                                    <tr>
                                        <th>Nama Transaksi</th>
                                        <td id="detailNamaTransaksiPiutang"></td>
                                    </tr>
                                    <tr>
                                        <th>Cashflow</th>
                                        <td id="detailCashflowPiutang"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">Customer/Retail</th>
                                        <td id="detailCustomerPiutang"></td>
                                    </tr>
                                    <tr>
                                        <th>Project</th>
                                        <td id="detailProjectPiutang"></td>
                                    </tr>
                                    <tr>
                                        <th>Total Piutang</th>
                                        <td id="detailTotalNotaPiutang" class="fw-bold"></td>
                                    </tr>
                                    <tr>
                                        <th>Terbayar</th>
                                        <td id="detailTerbayarPiutang"></td>
                                    </tr>
                                    <tr>
                                        <th>Sisa Piutang</th>
                                        <td id="detailSisaPiutang" class="fw-bold text-purple"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Riwayat Penerimaan Pembayaran</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="detailAngsuranPiutang">
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
                                        <td id="detailTotalTerbayarPiutang" class="text-end fw-bold"></td>
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
            let tablePiutang;
            let currentNotaIdPiutang = null;
            let currentSisaPiutang = 0;
            let globalTotalsPiutang = { total: 0, terbayar: 0, sisa: 0 };

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
                    case 'overdue':
                        badgeClass = 'bg-danger';
                        badgeText = 'LEWAT JATUH TEMPO';
                        break;
                }
                
                return `<span class="badge ${badgeClass} status-badge">${badgeText}</span>`;
            }

            function calculateDaysOverdue(tglTempo) {
                if (!tglTempo) return 0;
                const today = moment();
                const dueDate = moment(tglTempo);
                return dueDate.diff(today, 'days');
            }

            function getDueStatusClass(days) {
                if (days < 0) return 'overdue'; // Sudah lewat jatuh tempo
                if (days <= 3) return 'due-soon'; // Akan jatuh tempo dalam 3 hari
                return '';
            }

            function getDueStatusText(days) {
                if (days < 0) return `<span class="text-danger">${Math.abs(days)} hari terlambat</span>`;
                if (days === 0) return `<span class="text-warning">Hari ini jatuh tempo</span>`;
                if (days <= 3) return `<span class="text-warning">${days} hari lagi</span>`;
                return `<span class="text-success">${days} hari</span>`;
            }

            function reloadTablePiutang() {
                if (tablePiutang) {
                    tablePiutang.ajax.reload(null, false);
                }
            }

            function showDetailPiutang(notaId) {
                $('#detailLoadingPiutang').show();
                $('#detailContentPiutang').hide();
                
                $.ajax({
                    url: '/pending/show/' + notaId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            const summary = response.summary;
                            
                            $('#detailNoNotaPiutang').text(data.nota_no || '-');
                            $('#detailTanggalPiutang').text(formatDate(data.tanggal));
                            $('#detailTglTempoPiutang').text(formatDate(data.tgl_tempo));
                            
                            // Hitung keterlambatan
                            const daysOverdue = calculateDaysOverdue(data.tgl_tempo);
                            $('#detailKeterlambatanPiutang').html(getDueStatusText(daysOverdue));
                            
                            $('#detailNamaTransaksiPiutang').text(data.namatransaksi || '-');
                            $('#detailCashflowPiutang').text(data.cashflow === 'in' ? 'PEMASUKAN' : 'PENGELUARAN');
                            $('#detailCustomerPiutang').text(data.retail ? data.retail.namaretail : '-');
                            $('#detailProjectPiutang').text(data.project ? data.project.namaproject : '-');
                            $('#detailTotalNotaPiutang').text(formatRupiah(data.total));
                            $('#detailTerbayarPiutang').text(formatRupiah(summary.terbayar));
                            $('#detailSisaPiutang').text(formatRupiah(summary.sisa));
                            
                            const angsuranBody = $('#detailAngsuranPiutang tbody');
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
                                                <button class="btn btn-xs btn-danger hapus-angsuran-btn-piutang" 
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
                            
                            $('#detailTotalTerbayarPiutang').text(formatRupiah(summary.terbayar));
                            
                            $('#detailLoadingPiutang').hide();
                            $('#detailContentPiutang').show();
                            $('#detailModalPiutang').modal('show');
                        } else {
                            alert(response.message || 'Gagal mengambil detail piutang');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        alert('Terjadi kesalahan saat mengambil detail piutang');
                    }
                });
            }

            function openBayarModalPiutang(notaId) {
                currentNotaIdPiutang = notaId;
                
                $.ajax({
                    url: '/pending/show/' + notaId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const summary = response.summary;
                            currentSisaPiutang = parseRupiahToNumber(summary.sisa);
                            
                            $('#nota_id_piutang').val(notaId);
                            $('#tanggal_bayar_piutang').val(moment().format('YYYY-MM-DD'));
                            $('#sisaTextPiutang').text('Sisa Piutang: ' + formatRupiah(currentSisaPiutang));
                            $('#jumlah_bayar_piutang').val(currentSisaPiutang);
                            $('#jumlah_bayar_piutang').attr('max', currentSisaPiutang);
                            $('#keterangan_piutang').val('');
                            
                            $('#idrek_piutang').select2({
                                dropdownParent: $('#bayarModalPiutang')
                            });
                            
                            $('#bayarModalPiutang').modal('show');
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + xhr.responseJSON.message);
                    }
                });
            }

            $(document).ready(function () {
                $('#idrek_piutang').select2({
                    dropdownParent: $('#bayarModalPiutang')
                });

                // Tambah style untuk tombol purple
                $('<style>').text('.btn-purple { background-color: #6f42c1; border-color: #6f42c1; color: white; } .btn-purple:hover { background-color: #5a32a3; border-color: #5a32a3; }').appendTo('head');

                tablePiutang = $('#tblPiutang').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: true,
                    paging: true,
                    pageLength: 50,
                    lengthMenu: [25, 50, 100, 200],
                    ajax: {
                        url: "{{ route('company.pending.data.piutang') }}",
                        type: 'GET',
                        dataSrc: function(json) {
                            console.log('PIUTANG - Full response:', json);
                            
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
                                    
                                    // Tambahkan data untuk hari
                                    if (row.tgl_tempo) {
                                        row._days_until_due = calculateDaysOverdue(row.tgl_tempo);
                                    }
                                });
                            }
                            
                            globalTotalsPiutang = { total: totalJumlah, terbayar: totalTerbayar, sisa: totalSisa };
                            
                            $('#totalJumlahPiutang').text(formatRupiah(globalTotalsPiutang.total));
                            $('#totalTerbayarPiutang').text(formatRupiah(globalTotalsPiutang.terbayar));
                            $('#totalSisaPiutang').text(formatRupiah(globalTotalsPiutang.sisa));
                            
                            console.log('PIUTANG - Calculated totals:', globalTotalsPiutang);
                            
                            return json.data || [];
                        },
                        error: function(xhr, error, thrown) {
                            console.error('PIUTANG - AJAX Error:', {
                                xhr: xhr,
                                error: error,
                                thrown: thrown,
                                responseText: xhr.responseText
                            });
                            alert('Error loading data. Please check console for details.');
                        }
                    },
                    createdRow: function(row, data, dataIndex) {
                        // Tambahkan class untuk baris berdasarkan jatuh tempo
                        if (data._days_until_due !== undefined) {
                            $(row).addClass(getDueStatusClass(data._days_until_due));
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
                            data: 'tgl_tempo',
                            name: 'days_until_due',
                            className: 'text-center',
                            render: function(data, type, row) {
                                const days = row._days_until_due || calculateDaysOverdue(data);
                                return getDueStatusText(days);
                            }
                        },
                        { 
                            data: 'namatransaksi',
                            name: 'namatransaksi'
                        },
                        { 
                            data: 'retail.namaretail',
                            name: 'retail.namaretail',
                            defaultContent: '-',
                            render: function(data, type, row) {
                                return data || (row.retail && row.retail.namaretail) || '-';
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
                                        return `<span class="text-purple">${formatted}</span>`;
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
                            render: function(data, type, row) {
                                const days = row._days_until_due || 0;
                                let status = data || 'open';
                                
                                // Jika status open dan sudah lewat jatuh tempo
                                if ((status === 'open' || status === 'partial') && days < 0) {
                                    return getStatusBadge('overdue');
                                }
                                return getStatusBadge(status);
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
                                    console.error('PIUTANG - No ID found for row:', row);
                                    return '<span class="text-danger">Error</span>';
                                }
                                
                                return `
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-info btn-action" 
                                                onclick="showDetailPiutang(${notaId})"
                                                title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-success btn-action" 
                                                onclick="openBayarModalPiutang(${notaId})"
                                                title="Terima Pembayaran">
                                            <i class="bi bi-cash"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        }
                    ],
                    order: [[3, 'asc']], // Sort by jatuh tempo
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
                                        if (column === 11) { // Status column
                                            return $(node).text();
                                        }
                                        if (column === 4) { // Hari column
                                            // Remove HTML tags
                                            return $(node).text().replace(/<\/?[^>]+(>|$)/g, "");
                                        }
                                        if (column === 7 || column === 8 || column === 9) { // Amount columns
                                            return parseRupiahToNumber(data);
                                        }
                                        return data;
                                    }
                                }
                            },
                            title: 'Daftar Piutang',
                            messageTop: 'Tanggal Export: ' + moment().format('DD/MM/YYYY HH:mm'),
                            filename: function() {
                                return 'Piutang_' + moment().format('YYYYMMDD_HHmmss');
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="bi bi-printer"></i> Print',
                            className: 'btn btn-primary btn-sm',
                            title: '',
                            messageTop: function() {
                                return `<div class="print-header">
                                            <h4 style="text-align: center; margin-bottom: 10px;">Daftar Piutang</h4>
                                            <p style="text-align: center; margin-bottom: 15px;">
                                                Tanggal Cetak: ${moment().format('DD/MM/YYYY HH:mm')}
                                            </p>
                                            <p style="text-align: center; margin-bottom: 15px; font-size: 11pt;">
                                                <span style="background-color: rgba(220, 53, 69, 0.1); padding: 2px 5px; margin-right: 10px;">■ Lewat Jatuh Tempo</span>
                                                <span style="background-color: rgba(255, 193, 7, 0.1); padding: 2px 5px;">■ Jatuh Tempo Mendekati</span>
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
                                            <div>Total Piutang: ${formatRupiah(globalTotalsPiutang.total)}</div>
                                            <div>Total Terbayar: ${formatRupiah(globalTotalsPiutang.terbayar)}</div>
                                            <div>Total Sisa Piutang: ${formatRupiah(globalTotalsPiutang.sisa)}</div>
                                        </div>
                                        <div style="clear: both;"></div>
                                    </div>
                                `;
                                
                                $(win.document.body).append(totals);
                            }
                        }
                    ],
                    language: {
                        emptyTable: "Tidak ada data piutang",
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
                        console.log('PIUTANG - DataTable initialized successfully');
                        
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
                        console.log('PIUTANG - DataTable draw complete, rows:', this.api().rows().count());
                    }
                });

                $('#simpanBayarPiutang').click(function() {
                    const formData = $('#bayarFormPiutang').serialize();
                    
                    if (!$('#idrek_piutang').val()) {
                        alert('Pilih rekening terlebih dahulu');
                        return;
                    }
                    
                    const jumlah = parseFloat($('#jumlah_bayar_piutang').val());
                    if (jumlah > currentSisaPiutang) {
                        alert('Jumlah melebihi sisa piutang');
                        return;
                    }
                    
                    if (jumlah <= 0) {
                        alert('Jumlah harus lebih dari 0');
                        return;
                    }
                    
                    $.ajax({
                        url: '/pending/bayar/' + currentNotaIdPiutang,
                        type: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                $('#bayarModalPiutang').modal('hide');
                                tablePiutang.ajax.reload();
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

                $(document).on('click', '.hapus-angsuran-btn-piutang', function(e) {
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
                                    showDetailPiutang(currentNotaIdPiutang);
                                    tablePiutang.ajax.reload();
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