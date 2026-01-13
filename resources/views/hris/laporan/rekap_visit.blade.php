<x-app-layout>
    <x-slot name="pagetitle">Laporan Rekap Presensi Visit</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-clipboard-data"></i> Laporan Rekap Presensi Visit</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <select id="bulan" class="form-select form-select-sm w-auto" onchange="reloadTable()">
                            @for($m=1;$m<=12;$m++)
                                <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}" {{ $m==$bulan?'selected':'' }}>
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
            <div class="card card-info card-outline">
                <div class="card-body">
                    <table id="tblRekapVisit" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama / Unit Kerja</th>
                                <th>Total Hari Visit</th>
                               
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Presensi Visit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
            let tableVisit;

            function reloadTable() {
                if (tableVisit) tableVisit.ajax.reload();
            }

            function showDetail(nik, nama) {
                const bulan = $('#bulan').val();
                const tahun = $('#tahun').val();
                
                // Show loading
                $('#detailContent').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                `);
                
                $('#detailModal').modal('show');

                $.ajax({
                    url: "{{ route('hris.laporan.detail_visit') }}",
                    type: "GET",
                    data: {
                        nik: nik,
                        bulan: bulan,
                        tahun: tahun
                    },
                    success: function(response) {
                        if (response.success) {
                            let html = `
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6>Data Pegawai</h6>
                                        <table class="table table-sm table-bordered">
                                            <tr><th width="40%">NIK</th><td>${response.pegawai.nik}</td></tr>
                                            <tr><th>Nama</th><td>${response.pegawai.nama}</td></tr>
                                            <tr><th>Unit Kerja</th><td>${response.pegawai.unitkerja}</td></tr>
                                            <tr><th>Jabatan</th><td>${response.pegawai.jabatan || '-'}</td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Periode: ${response.periode.range}</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <div class="card bg-info text-white">
                                                    <div class="card-body p-2">
                                                        <h6 class="card-title mb-1">Total Hari Visit</h6>
                                                        <h4 class="mb-0">${response.statistik.total_hari_visit}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-2">
                                                <div class="card bg-warning text-white">
                                                    <div class="card-body p-2">
                                                        <h6 class="card-title mb-1">Rata-rata Jam/Hari</h6>
                                                        <h4 class="mb-0">${response.statistik.rata_rata_jam_per_hari}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <div class="card bg-primary text-white">
                                                    <div class="card-body p-2">
                                                        <h6 class="card-title mb-1">Persentase Hadir</h6>
                                                        <h4 class="mb-0">${response.statistik.persentase_hadir}%</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;

                            if (response.detail_presensi.length > 0) {
                                html += `
                                    <h6>Detail Presensi Harian</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Hari</th>
                                                    <th>Visit Masuk</th>
                                                    <th>Visit Pulang</th>
                                                    <th>Durasi Visit</th>
                                                    
                                                    <th>Keterangan</th>
                                                    <th>Lokasi</th>
                                                    <th>Foto</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                `;

                                response.detail_presensi.forEach(item => {
                                    html += `
                                        <tr>
                                            <td>${item.tanggal}</td>
                                            <td>${item.hari}</td>
                                            <td>${item.visit_masuk}</td>
                                            <td>${item.visit_pulang}</td>
                                            <td>${item.durasi_visit}</td>
                                            
                                            <td>${item.keterangan}</td>
                                            <td>${item.lokasi}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                    `;
                                    
                                    if (item.foto_masuk) {
                                        html += `<button class="btn btn-outline-info btn-sm" onclick="viewFoto('${item.foto_masuk}')" title="Foto Masuk">M</button>`;
                                    }
                                    
                                    if (item.foto_pulang) {
                                        html += `<button class="btn btn-outline-warning btn-sm" onclick="viewFoto('${item.foto_pulang}')" title="Foto Pulang">P</button>`;
                                    }
                                    
                                   
                                    
                                    
                                    html += `
                                                </div>
                                            </td>
                                        </tr>
                                    `;
                                });

                                html += `</tbody></table></div>`;
                            } else {
                                html += `
                                    <div class="alert alert-info text-center">
                                        <i class="bi bi-info-circle"></i> Tidak ada data presensi visit untuk periode ini.
                                    </div>
                                `;
                            }

                            $('#detailContent').html(html);
                        } else {
                            $('#detailContent').html(`
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> ${response.message}
                                </div>
                            `);
                        }
                    },
                    error: function() {
                        $('#detailContent').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Terjadi kesalahan saat memuat data.
                            </div>
                        `);
                    }
                });
            }

            function viewFoto(fotoUrl) {
                $('#modalFotoImg').attr('src', fotoUrl);
                $('#fotoModal').modal('show');
            }

            $(document).ready(function () {
                tableVisit = $('#tblRekapVisit').DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: 100,
                    lengthMenu: [25, 50, 100, 200, 500],
                    ajax: {
                        url: "{{ route('hris.laporan.rekap_visit.data') }}",
                        data: function(d) {
                            d.bulan = $('#bulan').val();
                            d.tahun = $('#tahun').val();
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
                                    <small class="text-muted">${data.unitkerja || '-'}</small>
                                `;
                            }
                        },
                        { 
                            data: 'total_hari_visit', 
                            className: 'text-center',
                            render: function(data) {
                                return `<span class="badge bg-primary">${data} hari</span>`;
                            }
                        },
          
                        {
                            data: null,
                            className: 'text-center',
                            render: function(data) {
                                return `
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info" onclick="showDetail('${data.nik}', '${data.nama}')" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <a href="{{ route('hris.laporan.export_visit_excel') }}?nik=${data.nik}&bulan=${$('#bulan').val()}&tahun=${$('#tahun').val()}" 
                                           class="btn btn-success" title="Export Excel">
                                            <i class="bi bi-file-excel"></i>
                                        </a>
                                    </div>
                                `;
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
                            text: '<i class="bi bi-file-earmark-excel"></i> Export All to Excel',
                            className: 'btn btn-success btn-sm',
                            action: function(e, dt, node, config) {
                                const bulan = $('#bulan').val();
                                const tahun = $('#tahun').val();
                                window.location.href = "{{ route('hris.laporan.export_visit_excel') }}?bulan=" + bulan + "&tahun=" + tahun;
                            }
                        }
                    ],
                    ordering: false
                });
            });
        </script>

        <style>
            .card.bg-info, .card.bg-success, .card.bg-warning, .card.bg-primary {
                transition: transform 0.2s;
            }
            .card.bg-info:hover, .card.bg-success:hover, .card.bg-warning:hover, .card.bg-primary:hover {
                transform: translateY(-2px);
            }
            .btn-group-sm .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        </style>
    </x-slot>
</x-app-layout>