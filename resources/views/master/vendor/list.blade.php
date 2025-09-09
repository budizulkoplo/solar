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
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalVendor" id="btnadd">
                            <i class="bi bi-file-earmark-plus"></i> Tambah Vendor
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbvendors" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Vendor</th>
                                <th>Alamat</th>
                                <th>Telepon</th>
                                <th>Email</th>
                                <th>Kontak Person</th>
                                <th>Status</th>
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
    <div class="modal fade" id="modalVendor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmVendor">
                    @csrf
                    <input type="hidden" name="id" id="vendor_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Vendor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama Vendor</label>
                            <input type="text" class="form-control form-control-sm" name="nama_vendor" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control form-control-sm" name="alamat"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-control form-control-sm" name="telepon">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-sm" name="email">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Kontak Person</label>
                            <input type="text" class="form-control form-control-sm" name="kontak_person">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
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
            var table = $('#tbvendors').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('vendors.index') }}",
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nama_vendor' },
                    { data: 'alamat' },
                    { data: 'telepon' },
                    { data: 'email' },
                    { data: 'kontak_person' },
                    { data: 'status' },
                    { data: 'aksi', orderable: false, searchable: false },
                ]
            });

            $('#btnadd').on('click', function() {
                $('#frmVendor')[0].reset();
                $('#vendor_id').val('');
            });

            // Submit form
            $('#frmVendor').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('vendors.store') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(res) {
                        $('#modalVendor').modal('hide');
                        table.ajax.reload();
                    }
                });
            });

            // Edit
            $('#tbvendors').on('click', '.formcell', function() {
                var id = $(this).data('id');
                $.get("{{ url('vendors') }}/" + id, function(vendor) {
                    $('#vendor_id').val(vendor.id);
                    $('input[name="nama_vendor"]').val(vendor.nama_vendor);
                    $('textarea[name="alamat"]').val(vendor.alamat);
                    $('input[name="telepon"]').val(vendor.telepon);
                    $('input[name="email"]').val(vendor.email);
                    $('input[name="kontak_person"]').val(vendor.kontak_person);
                    $('select[name="status"]').val(vendor.status);
                    $('#modalVendor').modal('show');
                });
            });

            // Delete
            $('#tbvendors').on('click', '.deleteVendor', function() {
                if (confirm('Hapus vendor ini?')) {
                    $.ajax({
                        url: "{{ url('vendors') }}/" + $(this).data('id'),
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
