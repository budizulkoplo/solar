<x-app-layout>
    <x-slot name="pagetitle">Laporan Vendor</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">Laporan Vendor</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <input type="date" id="start_date" class="form-control form-control-sm w-auto"
                            value="{{ $start }}" onchange="reloadTable()" />
                        <input type="date" id="end_date" class="form-control form-control-sm w-auto"
                            value="{{ $end }}" onchange="reloadTable()" />
                        <button class="btn btn-primary btn-sm" onclick="reloadTable()">Filter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-body">
                    <table id="tbvendor" class="table table-sm table-striped table-bordered" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th>Vendor</th>
                                <th>Jumlah Input</th>
                                <th>Total Volume (m³)</th>
                                <th>Rata² Volume per Armada (m³)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $row)
                                <tr>
                                    <td>{{ $row->nama_vendor }}</td>
                                    <td class="text-end">{{ $row->jumlah_input }}</td>
                                    <td class="text-end">{{ number_format($row->total_volume / 1000000, 2) }} m³</td>
                                    <td class="text-end">{{ number_format($row->rata_volume_armada / 1000000, 2) }} m³</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <!-- JSZip untuk export Excel -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script> window.JSZip = JSZip; </script>

        <script>
            function reloadTable() {
                let start = $('#start_date').val();
                let end = $('#end_date').val();
                window.location.href = `?start_date=${start}&end_date=${end}`;
            }

            var table = $('#tbvendor').DataTable({
                ordering: true,
                order: [[0, 'asc']],
                pageLength: 50,
                responsive: true,
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
                            columns: ':visible',
                            format: {
                                body: function (data, row, column, node) {
                                    // Hilangkan " m³" biar Excel tetap angka
                                    return typeof data === 'string' ? data.replace(" m³", "") : data;
                                }
                            }
                        }
                    }
                ]
            });
        </script>
    </x-slot>
</x-app-layout>
