<x-app-layout>
    <x-slot name="pagetitle">Kelompok Jam</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Manajemen Kelompok Jam</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKelompok" id="btnAdd">
                        <i class="bi bi-file-earmark-plus"></i> Tambah Kelompok Jam
                    </button>
                </div>
                <div class="card-body">
                    <table id="tbKelompok" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Shift</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit -->
    <div class="modal fade" id="modalKelompok" tabindex="-1">
        <div class="modal-dialog">
            <form id="frmKelompok">
                @csrf
                <input type="hidden" name="fid" id="fid">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Kelompok Jam</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Shift</label>
                            <input type="text" class="form-control" name="shift" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Jam Masuk</label>
                            <input type="time" class="form-control" name="jammasuk" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Jam Pulang</label>
                            <input type="time" class="form-control" name="jampulang" required>
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            $(function(){
                let table = $('#tbKelompok').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('master.kelompokjam.data') }}",
                    columns: [
                        {data: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'shift'},
                        {data: 'jammasuk'},
                        {data: 'jampulang'},
                        {data: 'aksi', orderable: false, searchable: false},
                    ]
                });

                $('#btnAdd').on('click', function(){
                    $('#frmKelompok')[0].reset();
                    $('#fid').val('');
                });

                $('#frmKelompok').on('submit', function(e){
                    e.preventDefault();
                    $.ajax({
                        url: "{{ route('master.kelompokjam.store') }}",
                        type: "POST",
                        data: $(this).serialize(),
                        success: function(res){
                            if(res.success){
                                $('#modalKelompok').modal('hide');
                                table.ajax.reload();
                                Swal.fire({icon:'success', title:'Sukses', text:res.message, timer:1500, showConfirmButton:false});
                            } else {
                                Swal.fire({icon:'error', title:'Gagal', text:res.message});
                            }
                        },
                        error: function(){
                            Swal.fire({icon:'error', title:'Gagal', text:'Terjadi kesalahan server.'});
                        }
                    });
                });

                $('#tbKelompok').on('click', '.btn-edit', function(){
                    let id = $(this).data('id');
                    $.get("{{ url('master/kelompokjam') }}/"+id, function(res){
                        $('#fid').val(res.id);
                        $('input[name="shift"]').val(res.shift);
                        $('input[name="jammasuk"]').val(res.jammasuk);
                        $('input[name="jampulang"]').val(res.jampulang);
                        $('#modalKelompok').modal('show');
                    });
                });

                $('#tbKelompok').on('click', '.btn-hapus', function(){
                    let id = $(this).data('id');
                    Swal.fire({
                        title: 'Hapus data?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Hapus',
                        cancelButtonText: 'Batal'
                    }).then((result)=>{
                        if(result.isConfirmed){
                            $.ajax({
                                url: "{{ url('master/kelompokjam') }}/"+id,
                                type: 'DELETE',
                                data: {_token: "{{ csrf_token() }}"},
                                success: function(res){
                                    table.ajax.reload();
                                    Swal.fire({icon:'success', title:'Sukses', text:res.message, timer:1500, showConfirmButton:false});
                                }
                            });
                        }
                    });
                });
            });
        </script>
    </x-slot>
</x-app-layout>
