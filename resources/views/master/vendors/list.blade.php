<x-app-layout>
    <x-slot name="pagetitle">Vendors</x-slot>
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Vendor</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalVendor">
                            <i class="bi bi-file-earmark-plus"></i> Tambah Vendor
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbVendors" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Vendor</th>
                                <th>Jenis</th>
                                <th>NPWP</th>
                                <th>Rekening</th>
                                <th>Telp</th>
                                <th>Alamat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Vendor -->
    <div class="modal fade" id="modalVendor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmVendor">
                    @csrf
                    <input type="hidden" name="id" id="idVendor">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Vendor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama Vendor</label>
                            <input type="text" class="form-control form-control-sm" name="namavendor" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Jenis</label>
                            <select class="form-select form-select-sm" name="jenis" required>
                                <option value="pekerjaan">Pekerjaan</option>
                                <option value="material">Material</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NPWP</label>
                            <input type="text" class="form-control form-control-sm" name="npwp">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Rekening</label>
                            <input type="text" class="form-control form-control-sm" name="rekening">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Telp</label>
                            <input type="text" class="form-control form-control-sm" name="telp">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control form-control-sm" name="alamat"></textarea>
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
            let tbVendors = $('#tbVendors').DataTable({
                responsive: true,
                ajax: "{{ route('vendors.index') }}",
                columns: [
                    { data: 'DT_RowIndex' },
                    { data: 'namavendor' },
                    { 
                        data: 'jenis',
                        render: function(data, type, row) {
                            return `
                                <select class="form-select form-select-sm jenisSelect" data-id="${row.id}">
                                    <option value="pekerjaan" ${data==='pekerjaan'?'selected':''}>Pekerjaan</option>
                                    <option value="material" ${data==='material'?'selected':''}>Material</option>
                                </select>
                            `;
                        }
                    },
                    { data: 'npwp' },
                    { data: 'rekening' },
                    { data: 'telp' },
                    { data: 'alamat' },
                    { data: 'aksi', orderable:false, searchable:false }
                ]
            });

            // Submit form
            $('#frmVendor').submit(function(e){
                e.preventDefault();
                $.post("{{ route('vendors.store') }}", $(this).serialize(), function(){
                    $('#modalVendor').modal('hide');
                    tbVendors.ajax.reload();
                });
            });

            // Inline update jenis
            $('#tbVendors').on('change', '.jenisSelect', function(){
                let id = $(this).data('id');
                let val = $(this).val();
                $.post("{{ url('vendors') }}/"+id+"/update-jenis", {
                    _token: '{{ csrf_token() }}',
                    jenis: val
                }, function(){
                    tbVendors.ajax.reload(null,false);
                });
            });

            // Edit
            $('#tbVendors').on('click','.editVendor', function(){
                $.get('/vendors/'+$(this).data('id')+'/edit', function(d){
                    $('#idVendor').val(d.id);
                    $('input[name="namavendor"]').val(d.namavendor);
                    $('select[name="jenis"]').val(d.jenis);
                    $('input[name="npwp"]').val(d.npwp);
                    $('input[name="rekening"]').val(d.rekening);
                    $('input[name="telp"]').val(d.telp);
                    $('textarea[name="alamat"]').val(d.alamat);
                    $('#modalVendor').modal('show');
                });
            });

            // Delete
            $('#tbVendors').on('click','.deleteVendor', function(){
                if(confirm('Hapus vendor ini?')){
                    $.ajax({
                        url:'/vendors/'+$(this).data('id'),
                        type:'DELETE',
                        data:{_token:'{{ csrf_token() }}'},
                        success:function(){
                            tbVendors.ajax.reload();
                        }
                    });
                }
            });

            // Reset form saat modal dibuka
            $('#modalVendor').on('show.bs.modal', function(){
                $('#frmVendor')[0].reset();
                $('#idVendor').val('');
            });
        </script>
    </x-slot>
</x-app-layout>
