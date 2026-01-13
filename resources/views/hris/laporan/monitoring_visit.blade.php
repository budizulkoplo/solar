<x-app-layout>
    <x-slot name="pagetitle">Monitoring Presensi Visit Harian</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-binoculars"></i> Monitoring Presensi Visit</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <input type="date" id="tanggal" class="form-control form-control-sm w-auto" 
                               value="{{ date('Y-m-d') }}" onchange="reloadTable()">
                        <button class="btn btn-info btn-sm" onclick="setToday()">
                            <i class="bi bi-calendar-day"></i> Hari Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-body">
                    <table id="tblMonitoringVisit" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama / Unit Kerja</th>
                                <th>Visit Masuk</th>
                                <th>Visit Pulang</th>
                                <th>Durasi Visit</th>
                                <th>Keterangan</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                                <th>Foto</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Foto -->
    <div class="modal fade" id="fotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto Presensi Visit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalFotoImg" src="" alt="Foto Presensi" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    {{-- ========== CUSTOM SCRIPT ========== --}}
    <x-slot name="jscustom">
        <script>
            let tableMonitoringVisit;

            function reloadTable() {
                if (tableMonitoringVisit) tableMonitoringVisit.ajax.reload();
            }

            function setToday() {
                const today = new Date().toISOString().split('T')[0];
                $('#tanggal').val(today);
                reloadTable();
            }

            function viewFoto(fotoUrl) {
                $('#modalFotoImg').attr('src', fotoUrl);
                $('#fotoModal').modal('show');
            }

            $(document).ready(function () {
                tableMonitoringVisit = $('#tblMonitoringVisit').DataTable({
                    processing: true,
                    serverSide: false,
                    pageLength: 100,
                    lengthMenu: [25, 50, 100, 200, 500],
                    ajax: {
                        url: "{{ route('hris.laporan.monitoring_visit.data') }}",
                        data: function(d) {
                            d.tanggal = $('#tanggal').val();
                        },
                        dataSrc: "data"
                    },
                    columns: [
                        { 
                            data: null, 
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        { 
                            data: null,
                            render: function(data) {
                                return `
                                    <strong>${data.nama}</strong><br>
                                    <small class="text-muted">${data.unitkerja || '-'}</small><br>
                                    <small class="text-muted">NIK: ${data.nik}</small>
                                `;
                            }
                        },
                        { 
                            data: 'visit_masuk', 
                            className: 'text-center'
                        },
                        { 
                            data: 'visit_pulang', 
                            className: 'text-center'
                        },
                        { 
                            data: 'durasi_visit', 
                            className: 'text-center',
                            render: function(data) {
                                return data !== '-' ? `<span class="badge bg-info">${data}</span>` : '-';
                            }
                        },
                        
                        { 
                            data: 'keterangan_masuk',
                            render: function(data, type, row) {
                                const keterangan = row.keterangan_masuk || row.keterangan_pulang || '-';
                                return keterangan
                            }
                        },
                        { 
                            data: 'lokasi_masuk',
                            render: function(data, type, row) {
                                const lokasi = row.lokasi_masuk || row.lokasi_pulang || '-';
                                return lokasi.length > 30 ? lokasi.substring(0, 30) + '...' : lokasi;
                            }
                        },
                        {
                            data: 'status',
                            className: 'text-center',
                            render: function(data) {
                                let badgeClass = 'bg-secondary';
                                if (data === 'Complete') badgeClass = 'bg-success';
                                if (data === 'Belum Pulang') badgeClass = 'bg-warning';
                                if (data === 'Belum Presensi') badgeClass = 'bg-danger';
                                
                                return `<span class="badge ${badgeClass}">${data}</span>`;
                            }
                        },
                        {
                            data: null,
                            className: 'text-center',
                            render: function(data) {
                                let buttons = '';
                                
                                if (data.foto_masuk && data.foto_masuk !== '-') {
                                    buttons += `<button class="btn btn-outline-info btn-sm" onclick="viewFoto('${data.foto_masuk}')" title="Foto Masuk">M</button> `;
                                }
                                
                                if (data.foto_pulang && data.foto_pulang !== '-') {
                                    buttons += `<button class="btn btn-outline-warning btn-sm" onclick="viewFoto('${data.foto_pulang}')" title="Foto Pulang">P</button>`;
                                }
                                
                                return buttons || '-';
                            }
                        }
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
                        },
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="bi bi-file-pdf"></i> Export PDF',
                            className: 'btn btn-danger btn-sm',
                            exportOptions: {
                                columns: ':visible'
                            }
                        }
                    ]
                });
            });
        </script>

        <style>
            .badge.bg-success { background-color: #28a745 !important; }
            .badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
            .badge.bg-danger { background-color: #dc3545 !important; }
            .badge.bg-info { background-color: #17a2b8 !important; }
        </style>
    </x-slot>
</x-app-layout>