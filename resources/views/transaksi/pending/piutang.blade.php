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
            border-color: #dc3545;
        }
        .card-piutang .card-header {
            background-color: #dc3545;
            color: white;
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
                    <h3 class="mb-0"><i class="bi bi-clock-history text-danger"></i> Daftar Piutang</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-primary btn-sm" onclick="reloadTable()">
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
                    <h5 class="card-title mb-0 text-white">Data Transaksi Keluar</h5>
                </div>
                <div class="card-body">
                    <table id="tblPiutang" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th width="30">No</th>
                                <th width="120">No. Nota</th>
                                <th width="90">Tgl. Nota</th>
                                <th width="100">Jatuh Tempo</th>
                                <th>Nama Transaksi</th>
                                <th width="150">Vendor</th>
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
                        <tfoot class="table-danger">
                            <tr>
                                <th colspan="6" class="text-end"><strong>TOTAL PIUTANG</strong></th>
                                <th id="totalJumlah" class="text-end"></th>
                                <th id="totalTerbayar" class="text-end"></th>
                                <th id="totalSisa" class="text-end fw-bold"></th>
                                <th></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Bayar Angsuran -->
    <div class="modal fade" id="bayarModal" tabindex="-1" aria-labelledby="bayarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bayarModalLabel">Bayar Piutang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bayarForm">
                        <input type="hidden" name="nota_id" id="nota_id">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="tanggal_bayar" class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_bayar" name="tanggal_bayar" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="idrek" class="form-label">Rekening <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="idrek" name="idrek" required>
                                <option value="">Pilih Rekening</option>
                                @foreach($rekenings as $rek)
                                    <option value="{{ $rek->idrek }}">{{ $rek->norek }} - {{ $rek->namarek }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="jumlah_bayar" class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="jumlah_bayar" name="jumlah_bayar" min="1" required>
                            <small class="text-muted" id="sisaText">Sisa Piutang: Rp 0</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="simpanBayar">Bayar Piutang</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Detail Nota -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Piutang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detailLoading" class="text-center">
                        <div class="spinner-border text-danger" role="status">
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
                                        <th>Tanggal Nota</th>
                                        <td id="detailTanggal"></td>
                                    </tr>
                                    <tr>
                                        <th>Jatuh Tempo</th>
                                        <td id="detailTglTempo"></td>
                                    </tr>
                                    <tr>
                                        <th>Nama Transaksi</th>
                                        <td id="detailNamaTransaksi"></td>
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
                                        <th>Project</th>
                                        <td id="detailProject"></td>
                                    </tr>
                                    <tr>
                                        <th>Total Piutang</th>
                                        <td id="detailTotalNota" class="fw-bold"></td>
                                    </tr>
                                    <tr>
                                        <th>Terbayar</th>
                                        <td id="detailTerbayar"></td>
                                    </tr>
                                    <tr>
                                        <th>Sisa Piutang</th>
                                        <td id="detailSisa" class="fw-bold text-danger"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Riwayat Pembayaran Piutang</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="detailAngsuran">
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
                                        <td id="detailTotalTerbayar" class="text-end fw-bold"></td>
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        
        <script>
            let tablePiutang;
            let currentNotaId = null;
            let currentSisa = 0;
            let globalTotals = { total: 0, terbayar: 0, sisa: 0 };

            function formatRupiah(angka) {
                if (!angka || isNaN(angka) || parseFloat(angka) === 0) return '-';
                return 'Rp ' + parseFloat(angka).toLocaleString('id-ID', { 
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function formatDate(dateString) {
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

            function reloadTable() {
                if (tablePiutang) {
                    tablePiutang.ajax.reload(null, false);
                }
            }

            function showDetailPiutang(notaId) {
                $('#detailLoading').show();
                $('#detailContent').hide();
                
                $.ajax({
                    url: '/pending/show/' + notaId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            const summary = response.summary;
                            
                            // Isi data header
                            $('#detailNoNota').text(data.nota_no || '-');
                            $('#detailTanggal').text(formatDate(data.tanggal));
                            $('#detailTglTempo').text(formatDate(data.tgl_tempo));
                            $('#detailNamaTransaksi').text(data.namatransaksi || '-');
                            $('#detailCashflow').text(data.cashflow === 'in' ? 'PEMASUKAN' : 'PENGELUARAN');
                            $('#detailVendor').text(data.vendor ? data.vendor.namavendor : '-');
                            $('#detailProject').text(data.project ? data.project.namaproject : '-');
                            $('#detailTotalNota').text(formatRupiah(data.total));
                            $('#detailTerbayar').text(formatRupiah(summary.terbayar));
                            $('#detailSisa').text(formatRupiah(summary.sisa));
                            
                            // Isi riwayat angsuran
                            const angsuranBody = $('#detailAngsuran tbody');
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
                                                <button class="btn btn-xs btn-danger hapus-angsuran-btn" 
                                                        data-id="${item.id}"
                                                        title="Hapus Angsuran">
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
                                            Belum ada riwayat pembayaran piutang
                                        </td>
                                    </tr>
                                `);
                            }
                            
                            // Update total terbayar
                            $('#detailTotalTerbayar').text(formatRupiah(summary.terbayar));
                            
                            $('#detailLoading').hide();
                            $('#detailContent').show();
                            $('#detailModal').modal('show');
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
                currentNotaId = notaId;
                
                $.ajax({
                    url: '/pending/show/' + notaId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const summary = response.summary;
                            currentSisa = summary.sisa;
                            
                            $('#nota_id').val(notaId);
                            $('#tanggal_bayar').val(moment().format('YYYY-MM-DD'));
                            $('#sisaText').text('Sisa Piutang: ' + formatRupiah(currentSisa));
                            $('#jumlah_bayar').val(currentSisa);
                            $('#jumlah_bayar').attr('max', currentSisa);
                            $('#keterangan').val('');
                            
                            // Initialize Select2
                            $('#idrek').select2({
                                dropdownParent: $('#bayarModal')
                            });
                            
                            $('#bayarModal').modal('show');
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + xhr.responseJSON.message);
                    }
                });
            }

            $(document).ready(function () {
                // Initialize Select2
                $('#idrek').select2({
                    dropdownParent: $('#bayarModal')
                });

                // Initialize DataTable for Piutang
                tablePiutang = $('#tblPiutang').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: true,
                    paging: true,
                    pageLength: 50,
                    lengthMenu: [25, 50, 100, 200],
                    ajax: {
                        url: "{{ route('pending.data.piutang') }}",
                        type: 'GET',
                        dataSrc: function(json) {
                            console.log('Data piutang:', json);
                            
                            // Calculate global totals
                            let totalJumlah = 0;
                            let totalTerbayar = 0;
                            let totalSisa = 0;
                            
                            if (json.data) {
                                json.data.forEach(function(row) {
                                    totalJumlah += parseFloat(row.total) || 0;
                                    totalTerbayar += parseFloat(row.terbayar) || 0;
                                    totalSisa += parseFloat(row.sisa) || 0;
                                });
                            }
                            
                            globalTotals = { total: totalJumlah, terbayar: totalTerbayar, sisa: totalSisa };
                            
                            // Update footer totals
                            $('#totalJumlah').text(formatRupiah(globalTotals.total));
                            $('#totalTerbayar').text(formatRupiah(globalTotals.terbayar));
                            $('#totalSisa').text(formatRupiah(globalTotals.sisa));
                            
                            return json.data || [];
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
                            data: 'vendor.namavendor',
                            name: 'vendor.namavendor',
                            defaultContent: '-'
                        },
                        { 
                            data: 'total',
                            name: 'total',
                            className: 'text-end',
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        { 
                            data: 'terbayar',
                            name: 'terbayar',
                            className: 'text-end',
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        { 
                            data: 'sisa',
                            name: 'sisa',
                            className: 'text-end fw-bold',
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        { 
                            data: 'angsuran_count',
                            name: 'angsuran_count',
                            className: 'text-center',
                            defaultContent: '0'
                        },
                        { 
                            data: 'status',
                            name: 'status',
                            className: 'text-center',
                            render: function(data) {
                                return getStatusBadge(data);
                            }
                        },
                        { 
                            data: 'id',
                            name: 'id',
                            className: 'text-center',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                return `
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-info btn-action" 
                                                onclick="showDetailPiutang(${data})"
                                                title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-success btn-action" 
                                                onclick="openBayarModalPiutang(${data})"
                                                title="Bayar Piutang">
                                            <i class="bi bi-cash"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        }
                    ],
                    order: [[3, 'asc']], // Sort by jatuh tempo
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
                        // Apply filter on header inputs
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
                    }
                });

                // Handle form submission for payment
                $('#simpanBayar').click(function() {
                    const formData = $('#bayarForm').serialize();
                    
                    if (!$('#idrek').val()) {
                        alert('Pilih rekening terlebih dahulu');
                        return;
                    }
                    
                    const jumlah = parseFloat($('#jumlah_bayar').val());
                    if (jumlah > currentSisa) {
                        alert('Jumlah melebihi sisa piutang');
                        return;
                    }
                    
                    if (jumlah <= 0) {
                        alert('Jumlah harus lebih dari 0');
                        return;
                    }
                    
                    $.ajax({
                        url: '/pending/bayar/' + currentNotaId,
                        type: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                $('#bayarModal').modal('hide');
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

                // Handle delete angsuran
                $(document).on('click', '.hapus-angsuran-btn', function(e) {
                    e.stopPropagation();
                    const angsuranId = $(this).data('id');
                    
                    if (confirm('Apakah Anda yakin ingin menghapus pembayaran piutang ini?')) {
                        $.ajax({
                            url: '/pending/angsuran/' + angsuranId,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert(response.message);
                                    showDetailPiutang(currentNotaId); // Reload detail modal
                                    tablePiutang.ajax.reload(); // Reload table
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