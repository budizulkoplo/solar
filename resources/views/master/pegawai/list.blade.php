<x-app-layout>
    <x-slot name="pagetitle">Pegawai</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Manajemen Pegawai</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <!-- <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPegawai" id="btnAdd">
                        <i class="bi bi-file-earmark-plus"></i> Tambah Pegawai
                    </button> -->
                </div>
                <div class="card-body">
                    <table id="tbpegawai" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Jabatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit -->
    <div class="modal fade" id="modalPegawai" tabindex="-1">
        <div class="modal-dialog">
            <form id="frmPegawai">
                @csrf
                <input type="hidden" name="fidusers" id="fidusers">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NIK</label>
                            <input type="text" class="form-control" name="nik" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Jabatan</label>
                            <input type="text" class="form-control" name="jabatan">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tanggal Masuk</label>
                            <input type="date" class="form-control" name="tanggal_masuk">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">No HP</label>
                            <input type="text" class="form-control" name="nohp">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" name="status" checked>
                            <label class="form-check-label">Aktif</label>
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
            let table = $('#tbpegawai').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('pegawai.getdata') }}",
                columns: [
                    {data: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'nip'},
                    {data: 'nik'},
                    {data: 'name'},
                    {data: 'email'},
                    {data: 'jabatan'},
                    {data: 'status'},
                    {data: 'aksi', orderable: false, searchable: false}
                ]
            });

            $('#frmPegawai').on('submit', function(e){
                e.preventDefault();
                $.ajax({
                    url: "{{ route('pegawai.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(res){
                        $('#modalPegawai').modal('hide');
                        table.ajax.reload();
                        $('#frmPegawai')[0].reset();
                    }
                });
            });

            // edit
            $('#tbpegawai').on('click', '.btn-edit', function(){
                let id = $(this).data('id');
                $.get("{{ url('pegawai') }}/"+id, function(res){
                    $('#fidusers').val(res.id);
                    $('input[name="name"]').val(res.name);
                    $('input[name="nik"]').val(res.nik);
                    $('input[name="email"]').val(res.email);
                    $('input[name="jabatan"]').val(res.jabatan);
                    $('input[name="tanggal_masuk"]').val(res.tanggal_masuk);
                    $('input[name="nohp"]').val(res.nohp);
                    $('textarea[name="alamat"]').val(res.alamat);
                    if(res.status === 'aktif'){
                        $('input[name="status"]').prop('checked', true);
                    } else {
                        $('input[name="status"]').prop('checked', false);
                    }
                    $('#modalPegawai').modal('show');
                });
            });
        </script>
    </x-slot>
</x-app-layout>
