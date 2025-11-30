<x-app-layout>
    <x-slot name="pagetitle">Rekening</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Rekening Company & Project</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Alert Notification -->
            <div id="alertContainer"></div>
            
            <div class="row">
                <!-- Rekening Company -->
                <div class="col-md-6">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header pt-1 pb-1">
                            <div class="card-tools">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalRekening" data-type="company">
                                    <i class="bi bi-file-earmark-plus"></i> Tambah Rekening Company
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="tbRekCompany" class="table table-sm table-striped w-100" style="font-size: small;">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>No Rek</th>
                                        <th>Nama</th>
                                        <th>Saldo Awal</th>
                                        <th>Saldo</th>
                                        <th>Company</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Rekening Project -->
                <div class="col-md-6">
                    <div class="card card-success card-outline mb-4">
                        <div class="card-header pt-1 pb-1">
                            <div class="card-tools">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalRekening" data-type="project">
                                    <i class="bi bi-file-earmark-plus"></i> Tambah Rekening Project
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="tbRekProject" class="table table-sm table-striped w-100" style="font-size: small;">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>No Rek</th>
                                        <th>Nama</th>
                                        <th>Saldo Awal</th>
                                        <th>Saldo</th>
                                        <th>Project</th>
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

    <!-- Modal Rekening -->
    <div class="modal fade" id="modalRekening" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="font-size: small;">
                <form id="frmRekening">
                    @csrf
                    <input type="hidden" name="idrek" id="idrek">
                    <input type="hidden" name="form_type" id="form_type">
                    <div class="modal-header">
                        <h6 class="modal-title" id="modalTitle">Form Rekening</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">No Rek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="norek" id="norek" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama Rekening <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="namarek" id="namarek" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Saldo Awal</label>
                            <input type="number" class="form-control form-control-sm" name="saldoawal" id="saldoawal" value="0" step="0.01">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Saldo</label>
                            <input type="number" class="form-control form-control-sm" name="saldo" id="saldo" value="0" step="0.01">
                        </div>
                        <div class="mb-2 selectCompany d-none">
                            <label class="form-label">Company <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="idcompany" id="idcompany_input">
                                <option value="">Pilih Company</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2 selectProject d-none">
                            <label class="form-label">Project <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="idproject" id="idproject_input">
                                <option value="">Pilih Project</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->namaproject }} ({{ $p->company->company_name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary" id="btnSubmit">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
    <script>
        // Fungsi untuk menampilkan alert
        function showAlert(message, type = 'success') {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('#alertContainer').html(alertHtml);
            
            // Auto hide setelah 5 detik
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }

        // Format Rupiah
        function formatRupiah(angka) {
            if (angka === null || angka === undefined || angka === '') return 'Rp 0';
            return 'Rp ' + parseFloat(angka).toLocaleString('id-ID');
        }

        // DataTables
        let tbRekCompany = $('#tbRekCompany').DataTable({
            responsive: true,
            processing: true,
            serverSide: false,
            ajax: { 
                url: "{{ route('rekening.index') }}", 
                data: {type: 'company'} 
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'norek' },
                { data: 'namarek' },
                { data: 'saldoawal', render: d => formatRupiah(d) },
                { data: 'saldo', render: d => formatRupiah(d) },
                { data: 'company.company_name' },
                { data: 'aksi', orderable: false, searchable: false }
            ]
        });

        let tbRekProject = $('#tbRekProject').DataTable({
            responsive: true,
            processing: true,
            serverSide: false,
            ajax: { 
                url: "{{ route('rekening.index') }}", 
                data: {type: 'project'} 
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'norek' },
                { data: 'namarek' },
                { data: 'saldoawal', render: d => formatRupiah(d) },
                { data: 'saldo', render: d => formatRupiah(d) },
                { data: 'project.namaproject' },
                { data: 'aksi', orderable: false, searchable: false }
            ]
        });

        // Submit form
        $('#frmRekening').submit(function(e){
            e.preventDefault();
            
            const submitBtn = $('#btnSubmit');
            const spinner = submitBtn.find('.spinner-border');
            
            // Show loading
            spinner.removeClass('d-none');
            submitBtn.prop('disabled', true).html('Menyimpan...');

            $.ajax({
                url: "{{ route('rekening.store') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#modalRekening').modal('hide');
                        showAlert(response.message, 'success');
                        tbRekCompany.ajax.reload(null, false);
                        tbRekProject.ajax.reload(null, false);
                    } else {
                        showAlert(response.message || 'Terjadi kesalahan', 'error');
                    }
                },
                error: function(xhr) {
                    let message = 'Terjadi kesalahan saat menyimpan data';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        message = xhr.responseJSON.error;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        message = 'Data yang dimasukkan tidak valid';
                    }
                    showAlert(message, 'error');
                },
                complete: function() {
                    // Hide loading
                    spinner.addClass('d-none');
                    submitBtn.prop('disabled', false).html('Simpan');
                }
            });
        });

        $(document).on('click', '.editRekening', function(){
            let id = $(this).data('id');
            let type = $(this).data('type');

            console.log('Edit clicked:', id, type);

            // Gunakan URL langsung tanpa route helper
            let url = '/rekening/' + id + '/edit';
            console.log('AJAX URL:', url);

            $.ajax({
                url: url,
                type: "GET",
                dataType: 'json',
                success: function(response) {
                    console.log('Response received:', response);
                    
                    // Method 3: Isi form secara eksplisit satu per satu
                    setTimeout(function() {
                        // Pastikan modal sudah siap
                        $('#modalRekening').modal('show');
                        
                        // Tunggu sedikit lalu isi form
                        setTimeout(function() {
                            console.log('Filling form...');
                            
                            // Clear dulu
                            $('#frmRekening')[0].reset();
                            
                            // Isi field
                            if (response.idrek) $('#idrek').val(response.idrek);
                            if (response.norek) $('#norek').val(response.norek);
                            if (response.namarek) $('#namarek').val(response.namarek);
                            if (response.saldo) $('#saldo').val(response.saldo);
                            if (response.saldoawal) $('#saldoawal').val(response.saldoawal);
                            
                            // Handle type
                            $('.selectCompany, .selectProject').addClass('d-none');
                            
                            if(type === 'company' && response.idcompany) {
                                $('.selectCompany').removeClass('d-none');
                                $('#idcompany_input').val(response.idcompany);
                                $('#modalTitle').text('Edit Rekening Company');
                            } else if(type === 'project' && response.idproject) {
                                $('.selectProject').removeClass('d-none');
                                $('#idproject_input').val(response.idproject);
                                $('#modalTitle').text('Edit Rekening Project');
                            }
                            
                            console.log('Form filled completed');
                        }, 100);
                    }, 100);
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr);
                    alert('Error: ' + xhr.status + ' - ' + xhr.statusText);
                }
            });
        });

        // Delete
        $(document).on('click', '.deleteRekening', function(){
            if(confirm('Apakah Anda yakin ingin menghapus rekening ini?')){
                const id = $(this).data('id');
                
                $.ajax({
                    url: "{{ route('rekening.destroy', ':id') }}".replace(':id', id),
                    type: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success');
                            tbRekCompany.ajax.reload(null, false);
                            tbRekProject.ajax.reload(null, false);
                        } else {
                            showAlert(response.message || 'Terjadi kesalahan', 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Terjadi kesalahan saat menghapus data';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        } else if (xhr.status === 404) {
                            message = 'Data tidak ditemukan';
                        }
                        showAlert(message, 'error');
                    }
                });
            }
        });

        // Modal open for new data
        $('#modalRekening').on('show.bs.modal', function(e){
            let type = $(e.relatedTarget).data('type');
            
            // Reset form
            $('#frmRekening')[0].reset();
            $('#idrek').val('');
            $('#form_type').val(type);
            
            // Show/hide appropriate fields
            $('.selectCompany, .selectProject').addClass('d-none');
            if(type === 'company') {
                $('.selectCompany').removeClass('d-none');
                $('#modalTitle').text('Tambah Rekening Company');
                $('#idcompany_input').val('');
            } else if(type === 'project') {
                $('.selectProject').removeClass('d-none');
                $('#modalTitle').text('Tambah Rekening Project');
                $('#idproject_input').val('');
            }
        });

        // Reset form when modal is hidden
        $('#modalRekening').on('hidden.bs.modal', function(){
            $('#frmRekening')[0].reset();
            $('#idrek').val('');
            $('.selectCompany, .selectProject').addClass('d-none');
        });

        // Debug: Test console log
        console.log('Rekening script loaded successfully');
    </script>
</x-slot>
</x-app-layout>