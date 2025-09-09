<x-app-layout>
    <x-slot name="pagetitle">Armadas</x-slot>
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Armada</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalArmada" id="btnadd">
                            <i class="bi bi-file-earmark-plus"></i> Tambah Armada
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbarmadas" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Vendor</th>
                                <th>No Polisi</th>
                                <th>Panjang</th>
                                <th>Lebar</th>
                                <th>Tinggi</th>
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
    <div class="modal fade" id="modalArmada" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmArmada">
                    @csrf
                    <input type="hidden" name="id" id="armada_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Armada</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select form-select-sm" required>
                                <option value="">Pilih Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">No Polisi</label>
                            <input type="text" class="form-control form-control-sm" name="nopol" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Panjang</label>
                            <input type="number" class="form-control form-control-sm" name="panjang" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Lebar</label>
                            <input type="number" class="form-control form-control-sm" name="lebar" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tinggi</label>
                            <input type="number" class="form-control form-control-sm" name="tinggi" required>
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
            var table = $('#tbarmadas').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('armadas.index') }}",
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'vendor' }, // biar tetap rata kiri
                    { data: 'nopol' },  // biar tetap rata kiri
                    { data: 'panjang', className: 'text-center' },
                    { data: 'lebar', className: 'text-center' },
                    { data: 'tinggi', className: 'text-center' },
                    { data: 'aksi', orderable: false, searchable: false, className: 'text-center' },
                ]
            });


            $('#btnadd').on('click', function() {
                $('#frmArmada')[0].reset();
                $('#armada_id').val('');
            });

            $('#frmArmada').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('armadas.store') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(res) {
                        $('#modalArmada').modal('hide');
                        table.ajax.reload();
                    }
                });
            });

            $('#tbarmadas').on('click', '.formcell', function() {
                var id = $(this).data('id');
                $.get("{{ url('armadas') }}/" + id, function(armada) {
                    $('#armada_id').val(armada.id);
                    $('select[name="vendor_id"]').val(armada.vendor_id);
                    $('input[name="nopol"]').val(armada.nopol);
                    $('input[name="panjang"]').val(armada.panjang);
                    $('input[name="lebar"]').val(armada.lebar);
                    $('input[name="tinggi"]').val(armada.tinggi);
                    $('#modalArmada').modal('show');
                });
            });

            $('#tbarmadas').on('click', '.deleteArmada', function() {
                if (confirm('Hapus armada ini?')) {
                    $.ajax({
                        url: "{{ url('armadas') }}/" + $(this).data('id'),
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
