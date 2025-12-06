<x-app-layout>
    <x-slot name="pagetitle">Laporan Cashflow Project</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-cash-coin"></i> Laporan Cashflow Project</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <select id="bulan" class="form-select form-select-sm w-auto" onchange="reloadTable()">
                            @for($m=1;$m<=12;$m++)
                                <option value="{{ $m }}" {{ $m==$bulan?'selected':'' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>

                        <select id="tahun" class="form-select form-select-sm w-auto" onchange="reloadTable()">
                            @for($y=date('Y')-2;$y<=date('Y')+2;$y++)
                                <option value="{{ $y }}" {{ $y==$tahun?'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-success card-outline">
                <div class="card-body">
                    <table id="tblCashflowProject" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>ID Bayar</th>
                                <th>No. Nota</th>
                                <th>Tgl. Trans</th>
                                <th>Kategori</th>
                                <th>Nama Transaksi</th>
                                <th>Pemasukan</th>
                                <th>Pengeluaran</th>
                                <th>Saldo</th>
                                <th>Vendor</th>
                                <th>Rekening</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th colspan="6" class="text-end"><strong>TOTAL</strong></th>
                                <th id="totalPemasukan" class="text-end"></th>
                                <th id="totalPengeluaran" class="text-end"></th>
                                <th id="saldoAkhir" class="text-end"></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script> window.JSZip = JSZip; </script>

        <script>
            let table;

            function reloadTable() {
                if (table) table.ajax.reload();
            }

            function calculateTotals(data) {
                let totalPemasukan = 0;
                let totalPengeluaran = 0;
                let saldoAkhir = 0;
                let lastSaldo = 0;

                if (data.length > 0) {
                    lastSaldo = parseFloat(data[data.length - 1].saldo) || 0;
                }

                data.forEach(row => {
                    totalPemasukan += parseFloat(row.pemasukan) || 0;
                    totalPengeluaran += parseFloat(row.pengeluaran) || 0;
                });

                saldoAkhir = lastSaldo;

                $('#totalPemasukan').text(formatRupiah(totalPemasukan));
                $('#totalPengeluaran').text(formatRupiah(totalPengeluaran));
                $('#saldoAkhir').text(formatRupiah(saldoAkhir));
            }

            $(document).ready(function () {
                table = $('#tblCashflowProject').DataTable({
                    processing: true,
                    pageLength: 100,
                    lengthMenu: [25, 50, 100, 200, 500],
                    ajax: {
                        url: "{{ route('transaksi.laporan.cashflow_project.data') }}",
                        data: function(d) {
                            d.bulan = $('#bulan').val();
                            d.tahun = $('#tahun').val();
                        },
                        dataSrc: function(json) {
                            calculateTotals(json.data);
                            return json.data;
                        }
                    },
                    columns: [
                        { data: null, render: (data, type, row, meta) => meta.row + 1 },
                        { data: 'id_payment', className: 'text-center' },
                        { data: 'nota_no', className: 'text-center' },
                        { 
                            data: 'tanggal', 
                            className: 'text-center',
                            render: function(data) {
                                return data ? moment(data).format('DD/MM/YYYY') : '-';
                            }
                        },
                        { data: 'kategori', className: 'text-center' },
                        { data: 'namatransaksi' },
                        { data: 'pemasukan', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'pengeluaran', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'saldo', className: 'text-end fw-bold', render: d => formatRupiah(d) },
                        { data: 'namavendor', className: 'text-center' },
                        { data: 'rekening', className: 'text-center' }
                    ],
                    dom:
                        "<'row mb-2'<'col-md-6 d-flex align-items-center'B><'col-md-6 d-flex justify-content-end'f>>" +
                        "<'row mb-2'<'col-md-6'l><'col-md-6 text-end'i>>" +
                        "<'row'<'col-12'tr>>" +
                        "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                            className: 'btn btn-success btn-sm',
                            exportOptions: { columns: ':visible' },
                            title: 'Laporan Cashflow Project',
                            messageTop: function() {
                                return `Periode: ${$('#bulan option:selected').text()} ${$('#tahun').val()}`;
                            },
                            customize: function(xlsx) {
                                var sheet = xlsx.xl.worksheets['sheet1.xml'];
                                $('row c[r^="G"]', sheet).attr('s', '2'); // Pemasukan - format rupiah
                                $('row c[r^="H"]', sheet).attr('s', '2'); // Pengeluaran - format rupiah
                                $('row c[r^="I"]', sheet).attr('s', '2'); // Saldo - format rupiah
                            }
                        },
                        {
                            text: '<i class="bi bi-printer"></i> Print',
                            className: 'btn btn-primary btn-sm',
                            action: function(e, dt, node, config) {
                                window.print();
                            }
                        }
                    ],
                    ordering: false,
                    footerCallback: function(row, data, start, end, display) {
                        var api = this.api();
                        
                        // Hitung total
                        var totalPemasukan = api
                            .column(6, { page: 'current' })
                            .data()
                            .reduce(function(a, b) {
                                return parseFloat(a) + parseFloat(b);
                            }, 0);
                        
                        var totalPengeluaran = api
                            .column(7, { page: 'current' })
                            .data()
                            .reduce(function(a, b) {
                                return parseFloat(a) + parseFloat(b);
                            }, 0);
                        
                        // Ambil saldo terakhir
                        var lastSaldo = 0;
                        if (data.length > 0) {
                            lastSaldo = parseFloat(data[data.length - 1].saldo) || 0;
                        }
                        
                        // Update footer
                        $(api.column(6).footer()).html(
                            '<strong>' + formatRupiah(totalPemasukan) + '</strong>'
                        );
                        
                        $(api.column(7).footer()).html(
                            '<strong>' + formatRupiah(totalPengeluaran) + '</strong>'
                        );
                        
                        $(api.column(8).footer()).html(
                            '<strong>' + formatRupiah(lastSaldo) + '</strong>'
                        );
                    }
                });
            });

            function formatRupiah(angka) {
                if (!angka || isNaN(angka) || angka == 0) return '-';
                return 'Rp ' + parseFloat(angka).toLocaleString('id-ID', { 
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }
        </script>
    </x-slot>
</x-app-layout>