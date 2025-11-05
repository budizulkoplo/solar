<x-app-layout>
    <x-slot name="pagetitle">Laporan Monitoring Presensi</x-slot>

    {{-- ================= HEADER ================= --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-person-badge"></i> Laporan Monitoring Presensi</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <input type="date" id="tanggal" class="form-control form-control-sm w-auto" value="{{ date('Y-m-d') }}">
                        <button class="btn btn-primary btn-sm" onclick="reloadTable()">
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= CONTENT ================= --}}
    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-success card-outline">
                <div class="card-body">
                    <table id="tblMonitoring" class="table table-sm table-bordered table-striped w-100" style="font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>NIP / Nama</th>
                                <th>Unit Kerja</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Foto Masuk</th>
                                <th>Foto Pulang</th>
                                <th>Lokasi Masuk</th>
                                <th>Lokasi Pulang</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= CUSTOM SCRIPT ================= --}}
    <x-slot name="jscustom">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script> window.JSZip = JSZip; </script>

        <script>
            let table;

            function reloadTable() {
                if (table) table.ajax.reload();
            }

            $(document).ready(function () {
                table = $('#tblMonitoring').DataTable({
                    processing: true,
                    pageLength: 50,
                    lengthMenu: [25, 50, 100, 200],
                    ajax: {
                        url: "{{ route('hris.laporan.monitoring_presensi.data') }}",
                        data: function(d) {
                            d.tanggal = $('#tanggal').val();
                        },
                        dataSrc: "data"
                    },
                    columns: [
                        { data: null, render: (data, type, row, meta) => meta.row + 1 },
                        { data: null, render: d => `
                            <small class="text-muted">${d.nip}<br></small>
                            <strong>${d.name}</strong>
                        ` },
                        { data: 'namaunit', render: d => d || '-', className: 'text-left' },
                        { data: 'jam_masuk', render: d => d || '-', className: 'text-center' },
                        { data: 'jam_pulang', render: d => d || '-', className: 'text-center' },
                        { data: 'foto_masuk', className: 'text-center', render: d => d ? `
                            <img src="{{ asset('storage/uploads/absensi') }}/${d}" class="img-thumbnail" width="50">
                        ` : '-' },
                        { data: 'foto_pulang', className: 'text-center', render: d => d ? `
                            <img src="{{ asset('storage/uploads/absensi') }}/${d}" class="img-thumbnail" width="50">
                        ` : '-' },
                        { data: 'lokasi_masuk', className: 'text-center', render: d => d ? `
                            <a href="https://maps.google.com/?q=${d}" target="_blank" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-geo-alt"></i> Lihat
                            </a>
                        ` : '-' },
                        { data: 'lokasi_pulang', className: 'text-center', render: d => d ? `
                            <a href="https://maps.google.com/?q=${d}" target="_blank" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-geo-alt"></i> Lihat
                            </a>
                        ` : '-' },
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
                            exportOptions: { columns: ':visible' }
                        }
                    ],
                    ordering: false
                });
            });
        </script>
    </x-slot>
</x-app-layout>
