<x-app-layout>
    <x-slot name="pagetitle">Kode Transaksi</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Kode Transaksi</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" id="btnTambah">
                            <i class="bi bi-plus"></i> Tambah
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbData" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Transaksi</th>
                                <th>Nama Transaksi</th>
                                <th>COA</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit --}}
    <div class="modal fade" id="modalData">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmData">
                    @csrf
                    <input type="hidden" name="id" id="idData">

                    <div class="modal-header">
                        <h5 class="modal-title">Form Kode Transaksi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <label>Kode Transaksi</label>
                        <input type="text" class="form-control form-control-sm" name="kodetransaksi" required>

                        <label class="mt-2">Nama Transaksi</label>
                        <input type="text" class="form-control form-control-sm" name="transaksi" required>

                        <label class="mt-2">COA</label>
                        <select class="form-select form-select-sm" name="idcoa" required>
                            <option value="">-- Pilih COA --</option>
                            @foreach($coa as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            let tb = $('#tbData').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('kodetransaksi.data') }}",
                columns: [
                    { data: 'DT_RowIndex', className: 'text-center' }, // No
                    { data: 'kodetransaksi', className: 'text-center' }, // Kode Transaksi
                    { data: 'transaksi' }, // Nama Transaksi
                    { 
                        data: 'idcoa',
                        render: function(data, type, row){
                            let options = `<option value="">--Pilih COA--</option>`;
                            @foreach($coa as $c)
                                options += `<option value="{{ $c->id }}" ${data == {{ $c->id }} ? 'selected' : ''}>{{ $c->name }}</option>`;
                            @endforeach
                            return `<select class="form-select form-select-sm select-coa" data-id="${row.id}">${options}</select>`;
                        },
                        orderable: false,
                        searchable: false
                    },
                    { 
                        data: 'action', 
                        orderable:false, 
                        searchable:false,
                        className: 'text-center', // tombol aksi rata tengah
                        render: function(data, type, row){
                            return `
                                <button class="btn btn-sm btn-warning editData" data-id="${row.id}">Edit</button>
                                <button class="btn btn-sm btn-danger deleteData" data-id="${row.id}">Hapus</button>
                            `;
                        }
                    }
                ]
            });

            // Update COA langsung dari table
            $('#tbData').on('change', '.select-coa', function(){
                let id = $(this).data('id');
                let idcoa = $(this).val();
                $.ajax({
                    url: '/kodetransaksi/'+id+'/update-coa',
                    type: 'PATCH',
                    data: { idcoa: idcoa, _token: '{{ csrf_token() }}' },
                    success: function(res){
                        if(res.success) tb.ajax.reload(null, false);
                    }
                });
            });
            
            // Tambah
            $('#btnTambah').click(function(){
                $('#frmData')[0].reset();
                $('#idData').val('');
                $('#modalData').modal('show');
            });

            // Submit Tambah/Edit
            $('#frmData').submit(function(e){
                e.preventDefault();
                let id = $('#idData').val();
                let url = id ? '/kodetransaksi/'+id : "{{ route('kodetransaksi.store') }}";
                let method = id ? 'PUT' : 'POST';
                $.ajax({
                    url:url,
                    type:method,
                    data:$(this).serialize(),
                    success:function(){
                        $('#modalData').modal('hide');
                        tb.ajax.reload();
                    }
                });
            });

            // Edit
            $('#tbData').on('click','.editData',function(){
                let id = $(this).data('id');
                $.get('/kodetransaksi/'+id+'/edit', function(d){
                    $('#idData').val(d.id);
                    $('input[name="kodetransaksi"]').val(d.kodetransaksi);
                    $('input[name="transaksi"]').val(d.transaksi);
                    $('select[name="idcoa"]').val(d.idcoa);
                    $('#modalData').modal('show');
                });
            });

            // Delete
            $('#tbData').on('click','.deleteData',function(){
                if(confirm('Hapus data ini?')){
                    let id = $(this).data('id');
                    $.ajax({
                        url:'/kodetransaksi/'+id,
                        type:'DELETE',
                        data:{_token:'{{ csrf_token() }}'},
                        success:()=> tb.ajax.reload()
                    });
                }
            });

        </script>
    </x-slot>
</x-app-layout>
