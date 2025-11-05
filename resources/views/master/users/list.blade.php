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
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header pt-1 pb-1">
                            <div class="card-title">
                                <div class="row row-cols-auto">
                                    <div class="col">
                                        <div class="input-group input-group-sm"> 
                                            <span class="input-group-text" id="basic-addon1">HasRole</span> 
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
                                    <i class="bi bi-file-earmark-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="tbusers" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>UserName</th>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Roles</th>
                                        <th></th><th></th><th></th><th></th><th></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('users.updatepassword') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="userid" id="tuserid" required>

                    <div class="mb-3">
                        <label for="tpassword" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="tpassword" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saverole">Save changes</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <!-- Modal Add/Edit User -->
    <div class="modal fade" id="exampleModalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
            <form id="frmusers" class="needs-validation" novalidate enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="fidusers" id="fidusers">
                    <div class="row">
                        <div class="col col-lg-6 mb-1">
                            <label class="form-label">No.Anggota</label>
                            <input type="text" class="form-control form-control-sm" id="fnip" name="nip" disabled>
                        </div>
                        <div class="col col-lg-6 mb-1">
                            <label class="form-label">UserName</label>
                            <input type="text" class="form-control form-control-sm" id="fusername" name="username" disabled required>
                        </div>
                        <div class="col col-lg-6 mb-1">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control form-control-sm" id="fname" name="name" required>
                        </div>
                        <div class="col col-lg-6 mb-1">
                            <label class="form-label">NIK</label>
                            <input type="number" class="form-control form-control-sm" id="fnik" name="nik" required>
                        </div>
                        <div class="col col-lg-6 mb-1">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-sm" id="femail" name="email" required>
                        </div>
                        <div class="col col-lg-6 mb-1">
                            <label class="form-label">Jabatan</label>
                            <input type="text" class="form-control form-control-sm" id="fjabatan" name="jabatan" required>
                        </div>
                        <div class="col col-lg-6 mb-1">
                            <label class="form-label">Tgl.Masuk</label>
                            <input type="date" class="form-control form-control-sm" id="ftanggal_masuk" name="tanggal_masuk" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="flexCheckChecked" name="status" checked>
                            <label class="form-check-label" for="flexCheckChecked">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saverole">Save changes</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <x-slot name="csscustom"></x-slot>
    <x-slot name="jscustom">
        <script>
            const allRoles = @json(\Spatie\Permission\Models\Role::all(['id','name']));
        </script>
        <script>
            var table = $('#tbusers').DataTable({
                ordering: false,"responsive": true,"processing": true,"serverSide": true,
                "ajax": {
                    "url": "{{ route('users.getdata') }}",
                    "data":{role : function() { return $('#frole').val()},},
                    "async": true,
                    "type": "GET"
                },
                "columns": [
                    { "data": "nip","orderable": false },
                    { "data": "username"},
                    { "data": "nik","orderable": false},
                    { "data": "name","orderable": false },
                    { "data": "email","orderable": false },
                    { "data": "status"},
                    { "data": null,"orderable": false},
                    { "data": null,"orderable": false},
                    { "data": "idusers","visible": false},
                    { "data": "jabatan","visible": false},
                    { "data": "tanggal_masuk","visible": false},
                    { "data": "username","visible": false},
                ],
                "columnDefs": [
                    {
                        targets: [6],
                        render: function (data, type, row, meta) {
                            let rls = '';
                            allRoles.forEach(function(r) {
                                let isChecked = row.roles.some(ur => ur.name === r.name) ? 'checked' : '';
                                rls += `
                                    <div class="form-check float-start pe-2">
                                        <input class="form-check-input chkrole" 
                                            data-id="${row.idusers}" 
                                            type="checkbox" name="chkrole[]" 
                                            value="${r.name}" ${isChecked}>
                                        <label class="form-check-label">${r.name}</label>
                                    </div>`;
                            });
                            return rls;
                        }
                    },
                    { targets: [ 7 ], className: 'dt-right',
                        render: function (data, type, row, meta) {
                            return `
                                <span class="badge rounded-pill bg-info formcell" data-bs-toggle="modal" data-bs-target="#exampleModalForm"><i class="bi bi-pencil-square"></i></span>
                                <span class="badge rounded-pill bg-warning" data-bs-toggle="modal" data-bs-target="#exampleModal" onclick="$('#tuserid').val('`+row.id+`')">
                                    <i class="bi bi-key"></i>
                                </span>
                                <span class="badge rounded-pill bg-danger"><i class="fa-solid fa-trash-can"></i></span>`;
                        } 
                    },
                ],
            });

            table.on('draw', function () {
                $('.chkrole').on('click', function(){
                    var selectedID = $(this).data('id');
                    let checkedValues = $(this).closest('td').find('.chkrole:checked').map(function() {
                        return $(this).val();
                    }).get();

                    $.ajax({
                        url: "{{ route('users.assignRole') }}",
                        method:"POST",
                        data: { 
                            iduser:selectedID,
                            name:checkedValues,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if(response.success){
                                table.ajax.reload(null, false);
                            } else {
                                alert(response.message);
                            }
                        }
                    });
                });


            });

            function clearfrm(){
                $('#fidusers').val('');
                $('input[name="nip"]').val('');
                $('input[name="name"]').val('');
                $('input[name="username"]').val('').prop('disabled', false);
                $('input[name="nik"]').val('');
                $('input[name="jabatan"]').val('');
                $('input[name="tanggal_masuk"]').val('');
                $('input[name="email"]').val('');
                $('#flexCheckChecked').prop('checked', true);
            }

            $('#frmusers').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const disabled = form.querySelectorAll(':disabled');
                disabled.forEach(el => el.disabled = false);
                const formData = new FormData(this);
                disabled.forEach(el => el.disabled = true);
                $.ajax({
                    url: "{{ route('users.store') }}",
                    method: "POST",
                    data: formData,
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        table.ajax.reload();
                        $('#exampleModalForm').modal('hide');
                        clearfrm();
                    }
                });
            });

            $(document).ready(function() {
                $('#btnadd').on('click',function(){
                    clearfrm();
                    $.ajax({
                        url: "{{ route('users.getcode') }}",method: "GET",
                        success: function(response) {
                            $('input[name="nip"]').val(response);
                        }
                    });
                });

                $('#tbusers tbody').on('click', '.formcell', function () {
                    var row = table.row($(this).closest('tr')).data();
                    $('#fidusers').val(row.idusers);
                    $('input[name="nip"]').val(row.nip);
                    $('input[name="name"]').val(row.name);
                    $('input[name="username"]').val(row.username).prop('disabled', true);
                    $('input[name="nik"]').val(row.nik);
                    $('input[name="jabatan"]').val(row.jabatan);
                    $('input[name="tanggal_masuk"]').val(row.tanggal_masuk);
                    $('input[name="email"]').val(row.email);
                    if(row.status=='aktif'){
                        $('#flexCheckChecked').prop('checked', true);
                    }else{
                        $('#flexCheckChecked').prop('checked', false);
                    }   
                });

                $('#frole').on('change',function(){table.ajax.reload();})
            });

            document.addEventListener('DOMContentLoaded', function() {
                const togglePassword = document.querySelector('#togglePassword');
                const passwordInput = document.querySelector('#tpassword');

                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    // Ganti ikon (kalau pakai Bootstrap Icons)
                    this.innerHTML = type === 'password'
                        ? '<i class="bi bi-eye"></i>'
                        : '<i class="bi bi-eye-slash"></i>';
                });
            });
        </script>
    </x-slot>
</x-app-layout>
