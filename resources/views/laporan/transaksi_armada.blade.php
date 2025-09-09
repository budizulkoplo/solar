<x-app-layout>
    <x-slot name="pagetitle">Laporan Transaksi Armada</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">Laporan Transaksi Armada</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <input type="date" id="tanggal" class="form-control form-control-sm w-auto"
                               value="{{ $tanggal }}" onchange="reloadTable()" />

                        <select id="project" class="form-select form-select-sm w-auto" onchange="reloadTable()">
                            <option value="all">Semua Project</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}" @if($project==$p->id) selected @endif>
                                    {{ $p->nama_project }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-body">
                    <table id="tbtransaksi" class="table table-sm table-striped table-bordered" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th>Jam</th>
                                <th>Project</th>
                                <th>Plat Nomor</th>
                                <th>Panjang</th>
                                <th>Lebar</th>
                                <th>Tinggi</th>
                                <th>Plus</th>
                                <th class="text-end">Volume (m³)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="7" class="text-end">Total</th>
                                <th class="text-end" id="total_volume">0</th>
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
            function reloadTable() {
                table.ajax.reload();
            }

            var table = $('#tbtransaksi').DataTable({
                ordering: true,
                order: [[0, 'asc']],
                pageLength: 50,
                responsive: true,
                processing: true,
                ajax: {
                    url: "{{ route('laporan.transaksi_armada.data') }}",
                    type: "GET",
                    data: function(d) {
                        d.tanggal = $('#tanggal').val();
                        d.project = $('#project').val();
                    },
                    dataSrc: "data"
                },
                columns: [
                    { data: "tgl_transaksi", render: function(data){ 
                        return moment(data).format("HH:mm"); 
                    }},
                    { data: "nama_project" },
                    { data: "nopol" },
                    { data: "panjang" },
                    { data: "lebar" },
                    { data: "tinggi" },
                    { data: "plus" },
                    { data: "volume_m3", className: "text-end" }
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
                        exportOptions: {
                            columns: ':visible'
                        }
                    }
                ],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();

                    // Hitung total volume
                    var total = api
                        .column(7, { page: 'all' })
                        .data()
                        .reduce(function (a, b) {
                            return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                        }, 0);

                    // Update footer
                    $(api.column(7).footer()).html(total.toFixed(2));
                }
            });

        </script>
    </x-slot>
</x-app-layout>
