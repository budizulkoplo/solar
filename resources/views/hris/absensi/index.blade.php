<x-app-layout>
    <x-slot name="pagetitle">Laporan Absensi</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Laporan Absensi Pegawai</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <label for="bulan" class="form-label me-2">Periode Bulan:</label>
                            <input type="month" id="bulan" name="bulan" 
                                   class="form-control form-control-sm d-inline-block"
                                   style="width: auto;" value="{{ $bulan }}">
                        </div>
                    </div>
                    <button id="btnFilter" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel"></i> Tampilkan
                    </button>
                </div>

                <div class="card-body table-responsive">
                    <table id="tbabsensi" class="table table-bordered table-striped table-sm nowrap" width="100%">
                        <thead>
                            <tr id="thead-row">
                                <th class="bg-light">No</th>
                                <th class="bg-light">Nama / Jabatan</th>
                                {{-- Kolom tanggal akan dibuat dinamis --}}
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Data akan diisi oleh DataTable --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
    <script>
    $(document).ready(function () {
        let currentData = [];

        // Format waktu untuk menentukan status
        function getStatusColor(jamIn, jamOut) {
            if (jamIn === '-' && jamOut === '-') return '';
            
            const jamMasukNormal = '08:00:00';
            if (jamIn !== '-' && jamIn > jamMasukNormal) return 'bg-warning';
            
            return 'bg-success text-white';
        }

        // Custom HTML renderer untuk DataTable
        function renderAbsensiData(data, type, row) {
    if (!data || data === '-') return '';

    // Ganti <br> literal dengan <br> HTML
    const htmlData = data.replace(/<br>/g, '<br>');
    
    const parts = data.split('<br>');
    const jamIn = parts[0] && parts[0] !== '-' ? parts[0] : '-';
    const jamOut = parts[1] && parts[1] !== '-' ? parts[1] : '-';
    const statusClass = getStatusColor(jamIn, jamOut);

    return `<div class="${statusClass} p-1 rounded" style="font-size:0.75em;">
                ${htmlData}
            </div>`;
}

        function loadAbsensiTable(bulan) {
            $.ajax({
                url: "{{ route('hris.absensi.getdata') }}",
                data: { bulan: bulan },
                beforeSend: function() {
                    if ($.fn.DataTable.isDataTable('#tbabsensi')) {
                        $('#tbabsensi').DataTable().destroy();
                    }
                    $('#tbabsensi tbody').empty();
                    $('#thead-row').empty().append(
                        '<th class="bg-light">No</th><th class="bg-light">Nama / Jabatan</th>'
                    );
                },
                success: function(res) {
                    currentData = res.data || [];

                    // Reset header
                    $('#thead-row').empty();
                    $('#thead-row').append(
                        '<th class="bg-light">No</th><th class="bg-light">Nama / Jabatan</th>'
                    );

                    if (currentData.length === 0) {
                        $('#tbabsensi').DataTable({
                            data: [],
                            columns: [
                                { data: 'no', className: 'text-center' },
                                { data: 'name', defaultContent: '-' }
                            ],
                            language: {
                                emptyTable: "Tidak ada data absensi untuk periode yang dipilih"
                            },
                            paging: false,
                            searching: false,
                            info: false
                        });
                        return;
                    }

                    // Ambil kolom tanggal
                    let firstRow = currentData[0];
                    let tanggalCols = Object.keys(firstRow).filter(k =>
                        !['id','user_id','nik','nip','name','jabatan'].includes(k)
                    );

                    // Urutkan tanggal: 26->31 lalu 01->25
                    let tanggal26_31 = tanggalCols.filter(n => parseInt(n,10) >= 26)
                        .sort((a,b) => parseInt(a) - parseInt(b));
                    let tanggal01_25 = tanggalCols.filter(n => parseInt(n,10) <= 25)
                        .sort((a,b) => parseInt(a) - parseInt(b));
                    let sortedCols = [...tanggal26_31, ...tanggal01_25];

                    // Buat header tanggal
                    sortedCols.forEach(col => {
                        $('#thead-row').append(
                            `<th class="text-center bg-light">${col}</th>`
                        );
                    });

                    // Columns definition
                    let columns = [
                        {
                            data: null,
                            render: function(d, type, row, meta) {
                                return meta.row + 1;
                            },
                            className: 'text-center align-middle',
                            width: '50px',
                            orderable: false
                        },
                        {
                            data: null,
                            render: function(d) {
                                return `<div>
                                            <div class="fw-semibold">${d.name}</div>
                                            <div class="text-muted" style="font-size:0.85em;">${d.jabatan || '-'}</div>
                                        </div>`;
                            },
                            className: 'align-middle',
                            orderable: false
                        }
                    ];

                    // Kolom tanggal dengan custom renderer
                    sortedCols.forEach(col => {
                        columns.push({
                            data: col,
                            className: 'text-center align-middle',
                            width: '80px',
                            orderable: false,
                            render: renderAbsensiData
                        });
                    });

                    // Initialize DataTable
                    $('#tbabsensi').DataTable({
                        data: currentData,
                        columns: columns,
                        scrollX: true,
                        ordering: false,
                        paging: false,
                        searching: true,
                        info: true,
                        language: {
                            search: "Cari:",
                            zeroRecords: "Tidak ada data yang sesuai",
                            info: "Menampilkan _TOTAL_ pegawai",
                            infoEmpty: "Menampilkan 0 pegawai"
                        },
                        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>tip'
                    });
                },
                error: function(xhr) {
                    console.error('Error loading data:', xhr);
                    alert('Terjadi kesalahan saat memuat data absensi');
                }
            });
        }

        // Filter button
        $('#btnFilter').click(function () {
            const bulan = $('#bulan').val();
            if (!bulan) {
                alert('Pilih bulan terlebih dahulu');
                return;
            }
            loadAbsensiTable(bulan);
        });

        // Load pertama
        loadAbsensiTable($('#bulan').val());
    });
    </script>

    <style>
    .bg-success {
        background-color: #d4edda !important;
        color: #155724 !important;
    }
    .bg-warning {
        background-color: #fff3cd !important;
        color: #856404 !important;
    }
    .table th {
        font-size: 0.8em;
        padding: 4px 2px;
    }
    .table td {
        padding: 2px;
    }
    </style>
    </x-slot>
</x-app-layout>