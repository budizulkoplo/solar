<x-app-layout>
    <x-slot name="pagetitle">Laporan Project</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">Laporan Project</h3>
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
                    <table id="tbproject" class="table table-sm table-striped table-bordered" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th>Project</th>
                                <th>Jumlah Input</th>
                                <th>Total Volume (m³)</th>
                                <th>Rata² Volume per Armada (m³)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $row)
                                <tr>
                                    <td>{{ $row->nama_project }}</td>
                                    <td>{{ $row->jumlah_input }}</td>
                                    <td>{{ $row->total_volume }}</td>
                                    <td>{{ $row->rata_volume_armada }}</td>
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
                table.ajax.reload();
            }

            // Inisialisasi DataTable
            var table = $('#tbproject').DataTable({
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
                            columns: ':visible'
                        }
                    }
                ]
            });
        </script>
    </x-slot>
</x-app-layout>
