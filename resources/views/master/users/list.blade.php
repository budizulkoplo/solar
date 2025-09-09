<x-app-layout>
    <x-slot name="pagetitle">Users</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">User Management</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-title">
                        <div class="row row-cols-auto">
                            <div class="col">
                                <div class="input-group input-group-sm"> 
                                    <span class="input-group-text">HasRole</span> 
                                    <select class="form-select form-select-sm" id="frole">
                                        <option value="all">ALL</option>
                                        @foreach ($roles as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-tools"> 
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalForm" id="btnadd">
                            <i class="bi bi-file-earmark-plus"></i> Tambah User
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <table id="tbusers" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th>No.Anggota</th>
                                <th>UserName</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Aksi</th>
                                <th hidden></th>
                                <th hidden></th>
                                <th hidden></th>
                                <th hidden></th>
                                <th hidden></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Reset Password --}}
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('users.updatepassword') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="userid" id="tuserid" required>
                        <div class="input-group mb-3">
                            <span class="input-group-text">New Password</span>
                            <input type="password" name="new_password" id="tpassword" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Tambah/Edit User --}}
    <div class="modal fade" id="exampleModalForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmusers" class="needs-validation" novalidate enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Form User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="fidusers" id="fidusers">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">No.Anggota</label>
                                <input type="text" class="form-control form-control-sm" id="fnomor_anggota" name="nomor_anggota" disabled>
                            </div>
                            <div class="col-6">
                                <label class="form-label">UserName</label>
                                <input type="text" class="form-control form-control-sm" id="fusername" name="username" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Nama</label>
                                <input type="text" class="form-control form-control-sm" id="fname" name="name" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">NIK</label>
                                <input type="number" class="form-control form-control-sm" id="fnik" name="nik" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control form-control-sm" id="femail" name="email" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Jabatan</label>
                                <input type="text" class="form-control form-control-sm" id="fjabatan" name="jabatan" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Unit Kerja</label>
                                <select class="form-select form-select-sm" name="unit_kerja" id="funit_kerja">
                                    @foreach ($unit as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama_unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tgl.Masuk</label>
                                <input type="date" class="form-control form-control-sm" id="ftanggal_masuk" name="tanggal_masuk" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="flexCheckChecked" name="status" checked>
                                    <label class="form-check-label" for="flexCheckChecked">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            var table = $('#tbusers').DataTable({
                ordering: false, responsive: true, processing: true, serverSide: true,
                ajax: {
                    url: "{{ route('users.getdata') }}",
                    data: {
                        loc: () => $('#floc').val(),
                        role: () => $('#frole').val(),
                    },
                    type: "GET"
                },
                columns: [
                    { data: "nomor_anggota" },
                    { data: "username" },
                    { data: "nik" },
                    { data: "name" },
                    { data: "email" },
                    { data: "nama_unit" },
                    { data: "status" },
                    { data: null },
                    { data: null },
                    { data: "idusers", visible: false },
                    { data: "jabatan", visible: false },
                    { data: "tanggal_masuk", visible: false },
                    { data: "username", visible: false },
                    { data: "unit_kerja", visible: false },
                ],
                columnDefs: [
                    { targets: [7],
                        render: function (data, type, row) {
                            // kosong atau bisa dikosongkan
                            return '';
                        }
                    },
                    { targets: [8], className: 'dt-center',
                        render: function (data, type, row) {
                            return `
                                <span class="badge rounded-pill bg-info formcell" data-bs-toggle="modal" data-bs-target="#exampleModalForm">
                                    <i class="bi bi-pencil-square"></i>
                                </span>
                                <span class="badge rounded-pill bg-warning" data-bs-toggle="modal" data-bs-target="#exampleModal" onclick="$('#tuserid').val('${row.id}')">
                                    <i class="bi bi-key"></i>
                                </span>
                                <span class="badge rounded-pill bg-danger deleteUser" data-id="${row.id}">
                                    <i class="fa-solid fa-trash-can"></i>
                                </span>`;
                        }
                    },
                ],
            });

            // helper render checkbox
            function roleCheckbox(row, roleKey, roleLabel) {
                let checked = row[roleKey] ? 'checked' : '';
                return `
                    <div class="form-check float-start pe-2">
                        <input class="form-check-input chkrole" data-id="${row.id}" type="checkbox" value="${roleKey}" ${checked}>
                        <label class="form-check-label">${roleLabel}</label>
                    </div>`;
            }

            // assign role
            table.on('draw', function () {
                $('.chkrole').on('click', function(){
                    let id = $(this).data('id');
                    let checkedValues = $(this).closest('td').find('.chkrole:checked').map(function() {
                        return $(this).val();
                    }).get();
                    $.get("{{ route('users.assignRole') }}", { iduser:id, name:checkedValues }, function(){
                        table.ajax.reload(null, false);
                    });
                });
            });

            // delete user
            $('#tbusers').on('click', '.deleteUser', function(){
                let id = $(this).data('id');
                if(confirm("Hapus user ini?")) {
                    $.ajax({
                        url: "{{ route('users.destroy', ':id') }}".replace(':id', id),
                        type: "DELETE",
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        success: function(){
                            table.ajax.reload(null, false);
                        },
                        error: function(xhr){
                            alert("Gagal menghapus user: " + xhr.responseText);
                        }
                    });
                }
            });


            // form tambah/edit
            function clearfrm(){
                $('#fidusers').val('');
                $('#frmusers')[0].reset();
                $('#fusername').prop('disabled', false);
                $('#flexCheckChecked').prop('checked', true);
            }

            $('#btnadd').on('click', function(){
                clearfrm();
                $.get("{{ route('users.getcode') }}", function(res){
                    $('#fnomor_anggota').val(res);
                });
            });

            $('#frmusers').on('submit', function(e){
                e.preventDefault();
                let form = this;
                let disabled = form.querySelectorAll(':disabled');
                disabled.forEach(el => el.disabled = false);
                let formData = new FormData(form);
                disabled.forEach(el => el.disabled = true);

                $.ajax({
                    url: "{{ route('users.store') }}",
                    method: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(){
                        table.ajax.reload();
                        $('#exampleModalForm').modal('hide');
                        clearfrm();
                    }
                });
            });

            $('#tbusers tbody').on('click', '.formcell', function(){
                let row = table.row($(this).closest('tr')).data();
                $('#fidusers').val(row.idusers);
                $('#fnomor_anggota').val(row.nomor_anggota);
                $('#fname').val(row.name);
                $('#fusername').val(row.username).prop('disabled', true);
                $('#fnik').val(row.nik);
                $('#fjabatan').val(row.jabatan);
                $('#funit_kerja').val(row.unit_kerja);
                $('#ftanggal_masuk').val(row.tanggal_masuk);
                $('#femail').val(row.email);
                $('#flexCheckChecked').prop('checked', row.status === 'aktif');
            });

            $('#frole').on('change', function(){ table.ajax.reload(); });
            $('#floc').on('change', function(){ table.ajax.reload(); });
        </script>
    </x-slot>
</x-app-layout>
