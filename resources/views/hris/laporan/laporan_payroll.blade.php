<x-app-layout>
    <x-slot name="pagetitle">Laporan Payroll</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-cash-stack"></i> Laporan Payroll</h3>
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
                    <table id="tblPayroll" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>NIP / Nama</th>
                                <th>Absen</th>
                                <th>Lembur</th>
                                <th>Terlambat</th>
                                <th>Cuti</th>
                                <th>Gaji Pokok</th>
                                <th>Pek. Tambahan</th>
                                <th>Masa Kerja</th>
                                <th>Komunikasi</th>
                                <th>Transportasi</th>
                                <th>Konsumsi</th>
                                <th>Tunj. Asuransi</th>
                                <th>Jabatan</th>
                                <th>Cicilan</th>
                                <th>Asuransi</th>
                                <th>Zakat</th>
                                <th>Total Diterima</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== CUSTOM SCRIPT ========== --}}
    <x-slot name="jscustom">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script> window.JSZip = JSZip; </script>

        <script>
            let table;

            function reloadTable() {
                if (table) table.ajax.reload();
            }

            $(document).ready(function () {
                table = $('#tblPayroll').DataTable({
                    processing: true,
                    pageLength: 100,
                    lengthMenu: [25, 50, 100, 200, 500],
                    ajax: {
                        url: "{{ route('hris.laporan.payroll.data') }}",
                        data: function(d) {
                            d.bulan = $('#bulan').val();
                            d.tahun = $('#tahun').val();
                        },
                        dataSrc: "data"
                    },
                    columns: [
                        { data: null, render: (data, type, row, meta) => meta.row + 1 },
                        { data: null, render: data => `
                            <small class="text-muted">${data.nip}<br></small>
                            <strong>${data.nama}</strong>
                        `},
                        { data: 'jmlabsen', className: 'text-center' },
                        { data: 'lembur', className: 'text-center', render: d => d || '-' },
                        { data: 'terlambat', className: 'text-center', render: d => d || '-' },
                        { data: 'cuti', className: 'text-center' },
                        { data: 'gajipokok', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'pek_tambahan', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'masakerja', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'komunikasi', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'transportasi', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'konsumsi', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'tunj_asuransi', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'jabatan', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'cicilan', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'asuransi', className: 'text-end', render: d => formatRupiah(d) },
                        { data: 'zakat', className: 'text-end', render: d => formatRupiah(d) },
                        {
                            data: null,
                            className: 'text-end fw-bold',
                            render: d => formatRupiah(
                                (parseFloat(d.gajipokok || 0)) +
                                (parseFloat(d.pek_tambahan || 0)) +
                                (parseFloat(d.masakerja || 0)) +
                                (parseFloat(d.komunikasi || 0)) +
                                (parseFloat(d.transportasi || 0)) +
                                (parseFloat(d.konsumsi || 0)) +
                                (parseFloat(d.tunj_asuransi || 0)) +
                                (parseFloat(d.jabatan || 0)) -
                                (parseFloat(d.cicilan || 0)) -
                                (parseFloat(d.asuransi || 0)) -
                                (parseFloat(d.zakat || 0))
                            )
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
                            exportOptions: { columns: ':visible' }
                        }
                    ],
                    ordering: false
                });
            });

            function formatRupiah(angka) {
                if (!angka || isNaN(angka)) return '-';
                return 'Rp ' + parseFloat(angka).toLocaleString('id-ID', { minimumFractionDigits: 0 });
            }
        </script>
    </x-slot>
</x-app-layout>
