<x-app-layout>
    <x-slot name="pagetitle">Units</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Units</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalUnit">
                            <i class="bi bi-file-earmark-plus"></i> Tambah Unit
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbUnits" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Unit</th>
                                <th>Project</th>
                                <th>Jenis</th>
                                <th>Blok</th>
                                <th>Luas Tanah</th>
                                <th>Luas Bangunan</th>
                                <th>Harga Dasar</th>
                                <th>Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Unit -->
    <div class="modal fade" id="modalUnit" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="frmUnit">
                    @csrf
                    <input type="hidden" name="id" id="idUnit">

                    <div class="modal-header">
                        <h5 class="modal-title">Form Unit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Project</label>
                                <select class="form-select form-select-sm" name="idproject" required>
                                    <option value="">-- Pilih Project --</option>
                                    @foreach(\App\Models\Project::all() as $proj)
                                        <option value="{{ $proj->id }}">{{ $proj->namaproject }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Unit</label>
                                <select class="form-select form-select-sm" name="idjenis" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    @foreach(\App\Models\JenisUnit::all() as $jns)
                                        <option value="{{ $jns->id }}">{{ $jns->jenisunit }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Unit</label>
                                <input type="text" class="form-control form-control-sm" name="namaunit" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Blok</label>
                                <input type="text" class="form-control form-control-sm" name="blok">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Luas Tanah</label>
                                <input type="text" class="form-control form-control-sm" name="luastanah">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Luas Bangunan</label>
                                <input type="text" class="form-control form-control-sm" name="luasbangunan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga Dasar</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="hargadasar" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Unit</label>
                                <input type="number" class="form-control form-control-sm" name="jumlah" min="1" required>
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
            let tbUnits = $('#tbUnits').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('units.getdata') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                    { data: 'namaunit', name: 'namaunit' },
                    { data: 'project', name: 'project' },
                    { data: 'jenisunit', name: 'jenisunit' },
                    { data: 'blok', name: 'blok' },
                    { data: 'luastanah', name: 'luastanah' },
                    { data: 'luasbangunan', name: 'luasbangunan' },
                    { 
                        data: 'hargadasar',
                        render: function(data){
                            return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR'}).format(data);
                        }
                    },
                    { data: 'jumlah', name: 'jumlah' },
                    { data: 'action', orderable:false, searchable:false }
                ]
            });

            // Submit form (Add / Update)
            $('#frmUnit').submit(function(e){
                e.preventDefault();
                $.post("{{ route('units.store') }}", $(this).serialize(), function(){
                    $('#modalUnit').modal('hide');
                    tbUnits.ajax.reload();
                }).fail(function(xhr){
                    alert('Error: ' + xhr.responseJSON.message);
                });
            });

            // Edit
            $('#tbUnits').on('click','.editUnit', function(){
                $.get('/units/'+$(this).data('id')+'/edit', function(d){
                    $('#idUnit').val(d.id);
                    $('select[name="idproject"]').val(d.idproject);
                    $('select[name="idjenis"]').val(d.idjenis);
                    $('input[name="namaunit"]').val(d.namaunit);
                    $('input[name="blok"]').val(d.blok);
                    $('input[name="luastanah"]').val(d.luastanah);
                    $('input[name="luasbangunan"]').val(d.luasbangunan);
                    $('input[name="hargadasar"]').val(d.hargadasar);
                    $('input[name="jumlah"]').val(d.jumlah);
                    $('#modalUnit').modal('show');
                });
            });

            // Delete
            $('#tbUnits').on('click','.deleteUnit', function(){
                if(confirm('Hapus unit ini?')){
                    $.ajax({
                        url:'/units/'+$(this).data('id'),
                        type:'DELETE',
                        data:{_token:'{{ csrf_token() }}'},
                        success:function(){
                            tbUnits.ajax.reload();
                        },
                        error:function(xhr){
                            alert('Error: ' + xhr.responseJSON.message);
                        }
                    });
                }
            });

            // Reset form saat modal dibuka
            $('#modalUnit').on('show.bs.modal', function(){
                $('#frmUnit')[0].reset();
                $('#idUnit').val('');
                $('select[name="idproject"]').val('');
                $('select[name="idjenis"]').val('');
            });
        </script>
    </x-slot>
</x-app-layout>
