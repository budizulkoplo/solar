<x-app-layout>
    <x-slot name="pagetitle">Pengajuan Izin</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Manajemen Pengajuan Izin</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-header d-flex justify-content-start align-items-center gap-2 flex-wrap">
                    <select id="filterBulan" class="form-select form-select-sm" style="width:auto;">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ sprintf('%02d', $i) }}" {{ $i == date('m') ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                            </option>
                        @endfor
                    </select>

                    <select id="filterTahun" class="form-select form-select-sm" style="width:auto;">
                        @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <button id="btnFilter" class="btn btn-sm btn-secondary">
                        <i class="bi bi-filter"></i> Tampilkan
                    </button>

                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalIzin" id="btnAdd">
                        <i class="bi bi-file-earmark-plus"></i> Tambah Izin
                    </button>
                </div>
                <div class="card-body">
                    <table id="tbizin" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Tanggal Izin</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Approved</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit -->
    <div class="modal fade" id="modalIzin" tabindex="-1">
        <div class="modal-dialog">
            <form id="frmIzin">
                @csrf
                <input type="hidden" name="fidizin" id="fidizin">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Pengajuan Izin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Pegawai (NIK - Nama)</label>
                            <select class="form-control select2" name="nik" id="nik" required style="width: 100%;"></select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tanggal Izin</label>
                            <input type="date" class="form-control" name="tgl_izin" id="tgl_izin" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="i">Izin</option>
                                <option value="c">Cuti</option>
                                <option value="s">Sakit</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="keterangan" required></textarea>
                        </div>
                        <div class="mb-2 approval-section" style="display:none;">
                            <label class="form-label">Approval</label>
                            <select name="approved" id="approved" class="form-control">
                                <option value="0">Belum Disetujui</option>
                                <option value="1">Disetujui</option>
                                <option value="2">Tidak Disetujui</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            function getFilterParams() {
                return {
                    bulan: $('#filterBulan').val(),
                    tahun: $('#filterTahun').val(),
                };
            }

            // DataTable
            let table = $('#tbizin').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('hris.pengajuanizin.data') }}",
                    data: function (d) {
                        d.bulan = $('#filterBulan').val();
                        d.tahun = $('#filterTahun').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nik' },
                    { data: 'pegawai' },
                    { data: 'tgl_izin' },
                    { data: 'status_text' },
                    { data: 'keterangan' },
                    { data: 'approved_text' },
                    { data: 'aksi', orderable: false, searchable: false },
                ]
            });

            // Tombol filter
            $('#btnFilter').on('click', function () {
                table.ajax.reload();
            });

            // select2 pegawai
           $('.select2').select2({
                ajax: {
                    url: "{{ route('hris.pengajuanizin.select2pegawai') }}",
                    dataType: 'json',
                    delay: 250,
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    }
                },
                placeholder: 'Pilih Pegawai',
                allowClear: true,
                dropdownParent: $('#modalIzin') // 👈 penting untuk modal!
            });

            // tambah baru
            $('#btnAdd').on('click', function(){
                $('#fidizin').val('');
                $('#frmIzin')[0].reset();
                $('#nik').val(null).trigger('change');
                $('.approval-section').hide();
                $('#modalIzin .modal-title').text('Form Pengajuan Izin');
            });

            // submit form
            $('#frmIzin').on('submit', function(e){
                e.preventDefault();
                $.ajax({
                    url: "{{ route('hris.pengajuanizin.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(res){
                        $('#modalIzin').modal('hide');
                        table.ajax.reload();
                        $('#frmIzin')[0].reset();
                        $('#nik').val(null).trigger('change');
                    },
                    error: function(err){
                        alert('Terjadi kesalahan saat menyimpan data.');
                    }
                });
            });

            // edit data
            $('#tbizin').on('click', '.btn-edit', function(){
                let id = $(this).data('id');
                $.get("{{ url('hris/pengajuan-izin/show') }}/" + id, function(res){
                    $('#fidizin').val(res.id);
                    $('#tgl_izin').val(res.tgl_izin);
                    $('#status').val(res.status);
                    $('#keterangan').val(res.keterangan);
                    $('#approved').val(res.approved ?? 0);

                    let pegawaiText = res.nik + ' - ' + (res.user?.name ?? '');
                    let newOption = new Option(pegawaiText, res.nik, true, true);
                    $('#nik').append(newOption).trigger('change');

                    $('.approval-section').show();
                    $('#modalIzin .modal-title').text('Edit Pengajuan Izin');
                    $('#modalIzin').modal('show');
                });
            });

            // hapus data
            $('#tbizin').on('click', '.btn-hapus', function(){
                if(confirm('Yakin ingin menghapus data ini?')){
                    let id = $(this).data('id');
                    $.ajax({
                        url: "{{ url('hris/pengajuan-izin') }}/" + id,
                        method: "DELETE",
                        data: {_token: "{{ csrf_token() }}"},
                        success: function(res){
                            table.ajax.reload();
                        }
                    });
                }
            });
        </script>
    </x-slot>
</x-app-layout>
