<x-app-layout>
    <x-slot name="pagetitle">Laporan Rekap Absensi</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-clipboard-data"></i> Laporan Rekap Absensi</h3>
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
            <div class="card card-info card-outline">
                <div class="card-body">
                    <table id="tblRekap" class="table table-sm table-bordered table-striped" style="width: 100%; font-size: small;">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama / Unit Kerja</th>
                                <th>Jml Absensi</th>
                                <th>Lembur (Jam:Menit)</th>
                                <th>Terlambat (Jam:Menit)</th>
                                <th>Cuti</th>
                                <th>Total</th>
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
                table = $('#tblRekap').DataTable({
                    processing: true,
                    pageLength: 100, // ✅ Default tampil 100 data
                    lengthMenu: [25, 50, 100, 200, 500],
                    ajax: {
                        url: "{{ route('hris.laporan.rekap_absensi.data') }}",
                        data: function(d) {
                            d.bulan = $('#bulan').val();
                            d.tahun = $('#tahun').val();
                        },
                        dataSrc: "data"
                    },
                    columns: [
                        { data: null, render: (data, type, row, meta) => meta.row + 1 },
                        { 
                            data: null, 
                            render: (data) => `
                                <strong>${data.nama}</strong><br>
                                <small class="text-muted">${data.unitkerja || '-'}</small>
                            `
                        },
                        { data: 'jml_absensi', className: 'text-center' },
                        { data: 'lembur', className: 'text-center' },
                        { data: 'terlambat', className: 'text-center' },
                        { data: 'cuti', className: 'text-center' },
                        { data: 'total', className: 'text-center fw-bold' },
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
                        },
                        {
                            text: '<i class="bi bi-save"></i> Export to Payroll',
                            className: 'btn btn-warning btn-sm',
                            action: function () {
                                let bulan = $('#bulan').val();
                                let tahun = $('#tahun').val();

                                Swal.fire({
                                    title: 'Export ke Payroll?',
                                    text: "Data periode " + bulan + "-" + tahun + " akan dimasukkan ke tabel payroll.",
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonText: 'Ya, Export',
                                    cancelButtonText: 'Batal'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        $.post("{{ route('hris.laporan.rekap_absensi.export_payroll') }}", {
                                            _token: '{{ csrf_token() }}',
                                            bulan: bulan,
                                            tahun: tahun
                                        })
                                        .done((res) => {
                                            Swal.fire('Berhasil!', res.message, 'success');
                                        })
                                        .fail((xhr) => {
                                            Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                                        });
                                    }
                                });
                            }
                        }
                    ],
                    ordering: false
                });
            });
        </script>

    </x-slot>
</x-app-layout>
