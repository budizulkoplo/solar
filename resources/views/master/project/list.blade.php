<x-app-layout>
    <x-slot name="pagetitle">Projects</x-slot>
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Project</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalProject" id="btnadd">
                            <i class="bi bi-file-earmark-plus"></i> Tambah Project
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbprojects" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Project</th>
                                <th>Lokasi</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalProject" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmProject">
                    @csrf
                    <input type="hidden" name="id" id="project_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama Project</label>
                            <input type="text" class="form-control form-control-sm" name="nama_project" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Lokasi</label>
                            <input type="text" class="form-control form-control-sm" name="lokasi">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control form-control-sm" name="keterangan"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            var table = $('#tbprojects').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('projects.index') }}",
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nama_project' },
                    { data: 'lokasi' },
                    { data: 'keterangan' },
                    { data: 'aksi', orderable: false, searchable: false },
                ]
            });

            $('#btnadd').on('click', function() {
                $('#frmProject')[0].reset();
                $('#project_id').val('');
            });

            $('#frmProject').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('projects.store') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(res) {
                        $('#modalProject').modal('hide');
                        table.ajax.reload();
                    }
                });
            });

            $('#tbprojects').on('click', '.formcell', function() {
                var id = $(this).data('id');
                $.get("{{ url('projects') }}/" + id, function(project) {
                    $('#project_id').val(project.id);
                    $('input[name="nama_project"]').val(project.nama_project);
                    $('input[name="lokasi"]').val(project.lokasi);
                    $('textarea[name="keterangan"]').val(project.keterangan);
                    $('#modalProject').modal('show');
                });
            });

            $('#tbprojects').on('click', '.deleteProject', function() {
                if (confirm('Hapus project ini?')) {
                    $.ajax({
                        url: "{{ url('projects') }}/" + $(this).data('id'),
                        method: "DELETE",
                        data: {_token: "{{ csrf_token() }}"},
                        success: function() {
                            table.ajax.reload();
                        }
                    });
                }
            });
        </script>
    </x-slot>
</x-app-layout>
