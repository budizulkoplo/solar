<x-app-layout>
    <x-slot name="pagetitle">Companies</x-slot>
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Company & Project</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Kolom Company -->
                <div class="col-md-5">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header pt-1 pb-1">
                            <div class="card-tools">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCompany" id="btnaddCompany">
                                    <i class="bi bi-file-earmark-plus"></i> Tambah Company
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="tbcompanies" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Logo</th>
                                        <th>Nama</th>
                                        <th>SIUP</th>
                                        <th>NPWP</th>
                                        <th>Projects</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Kolom Project -->
                <div class="col-md-7">
                    <div class="card card-success card-outline mb-4">
                        <div class="card-header pt-1 pb-1">
                            <h5 class="mb-0">Projects <span id="companyTitle"></span></h5>
                            <div class="card-tools">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalProject" id="btnaddProject">
                                    <i class="bi bi-file-earmark-plus"></i> Tambah Project
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="tbprojects" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Logo</th>
                                        <th>Nama Project</th>
                                        <th>Retail</th>
                                        <th>Lokasi</th>
                                        <th>Luas</th>
                                        <th>Aksi</th>
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

    <!-- Modal Company -->
    <div class="modal fade" id="modalCompany" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmCompany" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="company_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Company</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control form-control-sm" name="company_name" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">SIUP</label>
                            <input type="text" class="form-control form-control-sm" name="siup">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NPWP</label>
                            <input type="text" class="form-control form-control-sm" name="npwp">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control form-control-sm" name="alamat"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control form-control-sm" name="logo" accept="image/*">
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

    <!-- Modal Project -->
    <div class="modal fade" id="modalProject" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmProject" enctype="multipart/form-data"> {{-- ⬅️ tambahin enctype --}}
                    @csrf
                    <input type="hidden" name="id" id="project_id">
                    <input type="hidden" name="idcompany" id="idcompany">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Retail</label>
                            <select name="idretail" class="form-select form-select-sm" required>
                                <option value="">Pilih Retail</option>
                                @foreach($retails as $retail)
                                    <option value="{{ $retail->id }}">{{ $retail->namaretail }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama Project</label>
                            <input type="text" class="form-control form-control-sm" name="namaproject" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Lokasi</label>
                            <input type="text" class="form-control form-control-sm" name="lokasi">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Luas</label>
                            <input type="text" class="form-control form-control-sm" name="luas">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control form-control-sm" name="logo" accept="image/*">
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
            var companyTable = $('#tbcompanies').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('companies.index') }}",
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'logo', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'company_name' },
                    { data: 'siup' },
                    { data: 'npwp' },
                    { data: 'projects_count', className: 'text-center' },
                    { data: 'aksi', orderable: false, searchable: false, className: 'text-center' },
                ]
            });

            var projectTable;
            var selectedCompanyId = null;

            // Klik row untuk load project
            $('#tbcompanies').on('click', 'tr', function(e) {
                // kalau yang diklik adalah tombol aksi, jangan load project
                if ($(e.target).closest('.editCompany, .deleteCompany').length) return;

                var data = companyTable.row(this).data();
                if (!data) return;

                selectedCompanyId = data.id;
                $('#idcompany').val(data.id);
                $('#companyTitle').text("of " + data.company_name);

                if (projectTable) projectTable.destroy();
                    projectTable = $('#tbprojects').DataTable({
                    ajax: {
                        url: "/companies/" + data.id,
                        dataSrc: function (json) {
                            return json.projects;
                        }
                    },
                    processing: true,
                    serverSide: false,
                    columns: [
                        { data: null, render: (d,t,r,m) => m.row+1, className: 'text-center' },
                        { data: 'logo', render: function(data){
                            return data ? `<img src="/storage/${data}" width="40">` : '-';
                        }, orderable: false, searchable: false, className: 'text-center' },
                        { data: 'namaproject' },
                        { data: 'retail.namaretail' },
                        { data: 'lokasi' },
                        { data: 'luas' },
                        { data: null, render: function(row){
                            return `
                                <span class="badge bg-info editProject" data-id="${row.id}"><i class="bi bi-pencil"></i></span>
                                <span class="badge bg-danger deleteProject" data-id="${row.id}"><i class="fa fa-trash"></i></span>
                            `;
                        }, orderable: false, className: 'text-center' }
                    ]
                });
            });

            // Save Company
            $('#frmCompany').submit(function(e){
                e.preventDefault();
                var formData = new FormData(this);
                $.ajax({   
                    url: "{{ route('companies.store') }}",
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(){
                        $('#modalCompany').modal('hide');
                        companyTable.ajax.reload();
                    }
                });
            });

            // Save Project
            $('#frmProject').submit(function(e){
                e.preventDefault();
                var formData = new FormData(this);

                $.ajax({
                    url: "{{ route('companies.projects.store') }}",
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(){
                        $('#modalProject').modal('hide');
                        projectTable.ajax.reload();
                    }
                });
            });

            // Delete Project
            $('#tbprojects').on('click', '.deleteProject', function(){
                if(confirm("Hapus project ini?")){
                    $.ajax({
                        url: "/companies/projects/" + $(this).data('id'),
                        method: "DELETE",
                        data: {_token: "{{ csrf_token() }}"},
                        success: function(){
                            projectTable.ajax.reload();
                        }
                    });
                }
            });

            // Delete Company
            $('#tbcompanies').on('click', '.deleteCompany', function(e){
                e.stopPropagation();
                if(confirm("Hapus company ini?")){
                    $.ajax({
                        url: "/companies/" + $(this).data('id'),
                        method: "DELETE",
                        data: {_token: "{{ csrf_token() }}"},
                        success: function(){
                            companyTable.ajax.reload();
                        }
                    });
                }
            });

            // Edit Company
            $('#tbcompanies').on('click', '.editCompany', function(e){
                e.stopPropagation();
                var id = $(this).data('id');
                $.get('/companies/' + id + '/edit', function(data){
                    $('#company_id').val(data.id);
                    $('input[name="company_name"]').val(data.company_name);
                    $('input[name="siup"]').val(data.siup);
                    $('input[name="npwp"]').val(data.npwp);
                    $('textarea[name="alamat"]').val(data.alamat);

                    $('#modalCompany').modal('show');
                });
            });

            // Edit Project
            $('#tbprojects').on('click', '.editProject', function(){
                var id = $(this).data('id');
                $.get('/companies/projects/' + id + '/edit', function(data){
                    $('#project_id').val(data.id);
                    $('#idcompany').val(data.idcompany);
                    $('select[name="idretail"]').val(data.idretail);
                    $('input[name="namaproject"]').val(data.namaproject);
                    $('input[name="lokasi"]').val(data.lokasi);
                    $('input[name="luas"]').val(data.luas);

                    $('#modalProject').modal('show');
                });
            });

            // Reset form kalau klik tambah
            $('#btnaddCompany').click(function(){
                $('#frmCompany')[0].reset();
                $('#company_id').val('');
            });
            $('#btnaddProject').click(function(){
                $('#frmProject')[0].reset();
                $('#project_id').val('');
            });

        </script>
    </x-slot>
</x-app-layout>
