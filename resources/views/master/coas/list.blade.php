<x-app-layout>
    <x-slot name="pagetitle">Chart of Accounts</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master COA</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCoa">
                            <i class="bi bi-file-earmark-plus"></i> Tambah COA
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbCoa" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Akun</th>
                                <th>Type</th>
                                <th>Parent</th>
                                <th>Level</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal COA -->
    <div class="modal fade" id="modalCoa" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="frmCoa">
                    @csrf
                    <input type="hidden" name="id" id="idCoa">

                    <div class="modal-header">
                        <h5 class="modal-title">Form COA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">Kode</label>
                                <input type="text" class="form-control form-control-sm" name="code" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Akun</label>
                                <input type="text" class="form-control form-control-sm" name="name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type</label>
                                <select class="form-select form-select-sm" name="type" required>
                                    <option value="">-- Pilih Type --</option>
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="equity">Equity</option>
                                    <option value="revenue">Revenue</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Parent</label>
                                <select class="form-select form-select-sm" name="parent_id">
                                    <option value="">-- Tidak Ada --</option>
                                    @foreach(\App\Models\Coa::all() as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Level</label>
                                <input type="number" class="form-control form-control-sm" name="level" min="1" required>
                            </div>
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
            let tbCoa = $('#tbCoa').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('coas.getdata') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                    { data: 'code', name: 'code' },
                    { data: 'name', name: 'name' },
                    { data: 'type', name: 'type' },
                    { data: 'parent', name: 'parent' },
                    { data: 'level', name: 'level' },
                    { data: 'action', orderable:false, searchable:false }
                ]
            });

            // Submit form (Add / Update)
            $('#frmCoa').submit(function(e){
                e.preventDefault();
                let id = $('#idCoa').val();
                let url = id ? '/coas/'+id : "{{ route('coas.store') }}";
                let method = id ? 'PUT' : 'POST';
                $.ajax({
                    url: url,
                    type: method,
                    data: $(this).serialize(),
                    success: function(){
                        $('#modalCoa').modal('hide');
                        tbCoa.ajax.reload();
                    },
                    error: function(xhr){
                        alert('Error: ' + xhr.responseJSON.message);
                    }
                });
            });

            // Edit
            $('#tbCoa').on('click','.editCoa', function(){
                $.get('/coas/'+$(this).data('id'), function(d){
                    $('#idCoa').val(d.id);
                    $('input[name="code"]').val(d.code);
                    $('input[name="name"]').val(d.name);
                    $('select[name="type"]').val(d.type);
                    $('select[name="parent_id"]').val(d.parent_id);
                    $('input[name="level"]').val(d.level);
                    $('#modalCoa').modal('show');
                });
            });

            // Delete
            $('#tbCoa').on('click','.deleteCoa', function(){
                if(confirm('Hapus akun ini?')){
                    $.ajax({
                        url:'/coas/'+$(this).data('id'),
                        type:'DELETE',
                        data:{_token:'{{ csrf_token() }}'},
                        success:function(){
                            tbCoa.ajax.reload();
                        },
                        error:function(xhr){
                            alert('Error: ' + xhr.responseJSON.message);
                        }
                    });
                }
            });

            // Reset form saat modal dibuka
            $('#modalCoa').on('show.bs.modal', function(){
                $('#frmCoa')[0].reset();
                $('#idCoa').val('');
                $('select[name="parent_id"]').val('');
            });
        </script>
    </x-slot>
</x-app-layout>
