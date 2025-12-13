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
                            <i class="bi bi-plus-circle"></i> Tambah Unit
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbUnits" class="table table-sm table-hover w-100" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama Unit</th>
                                <th>Tipe</th>
                                <th>Project</th>
                                <th>Jenis</th>
                                <th>Blok</th>
                                <th>Luas Tanah</th>
                                <th>Luas Bangunan</th>
                                <th>Harga Dasar</th>
                                <th>Jumlah</th>
                                <th width="10%">Aksi</th>
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
                        <h5 class="modal-title"><span id="modalTitle">Tambah</span> Unit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Project <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="idproject" id="idproject" required>
                                    <option value="">-- Pilih Project --</option>
                                    @foreach(\App\Models\Project::all() as $proj)
                                        <option value="{{ $proj->id }}">{{ $proj->namaproject }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Unit <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="idjenis" id="idjenis" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    @foreach(\App\Models\JenisUnit::all() as $jns)
                                        <option value="{{ $jns->id }}">{{ $jns->jenisunit }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Unit <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="namaunit" id="namaunit" required maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe</label>
                                <input type="text" class="form-control form-control-sm" name="tipe" id="tipe" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Blok</label>
                                <input type="text" class="form-control form-control-sm" name="blok" id="blok" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Unit <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" name="jumlah" id="jumlah" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Luas Tanah</label>
                                <input type="text" class="form-control form-control-sm" name="luastanah" id="luastanah" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Luas Bangunan</label>
                                <input type="text" class="form-control form-control-sm" name="luasbangunan" id="luasbangunan" maxlength="50">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Harga Dasar <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="hargadasar" id="hargadasar" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <span id="submitText">Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function() {
                // Inisialisasi DataTables
                let tbUnits = $('#tbUnits').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "{{ route('units.getdata') }}",
                        type: "GET"
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center' },
                        { data: 'namaunit', name: 'namaunit' },
                        { data: 'tipe', name: 'tipe' },
                        { data: 'project', name: 'project' },
                        { data: 'jenisunit', name: 'jenisunit' },
                        { data: 'blok', name: 'blok', className: 'text-center' },
                        { data: 'luastanah', name: 'luastanah' },
                        { data: 'luasbangunan', name: 'luasbangunan' },
                        { 
                            data: 'hargadasar', 
                            name: 'hargadasar',
                            className: 'text-end',
                            render: function(data) {
                                return data ? 'Rp ' + parseInt(data).toLocaleString('id-ID') : '-';
                            }
                        },
                        { 
                            data: 'jumlah', 
                            name: 'jumlah',
                            className: 'text-center',
                            render: function(data) {
                                return '<span class="badge bg-primary">' + data + '</span>';
                            }
                        },
                        { 
                            data: 'action', 
                            name: 'action',
                            orderable: false, 
                            searchable: false,
                            className: 'text-center'
                        }
                    ],
                    order: [[1, 'asc']],
                    language: {
                        emptyTable: "Tidak ada data unit",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        paginate: {
                            first: "Awal",
                            last: "Akhir",
                            next: "›",
                            previous: "‹"
                        }
                    }
                });

                // Fungsi untuk menampilkan pesan sukses
                function showSuccess(message) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses!',
                        text: message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }

                // Fungsi untuk menampilkan pesan error
                function showError(message) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: message
                    });
                }

                // Submit form (Add/Update)
                $('#frmUnit').submit(function(e) {
                    e.preventDefault();
                    
                    const formData = $(this).serialize();
                    const unitId = $('#idUnit').val();
                    
                    // Tentukan URL dan method
                    let url = "{{ route('units.store') }}";
                    let method = 'POST';
                    
                    if (unitId) {
                        url = "{{ url('units') }}/" + unitId;
                        method = 'PUT';
                    }
                    
                    // Tampilkan loading
                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                    
                    // Kirim request
                    $.ajax({
                        url: url,
                        type: method,
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            if (response.success) {
                                // Tampilkan pesan sukses
                                showSuccess(response.message);
                                
                                // Tutup modal
                                $('#modalUnit').modal('hide');
                                
                                // Reset form
                                $('#frmUnit')[0].reset();
                                $('#idUnit').val('');
                                $('#modalTitle').text('Tambah');
                                $('#submitText').text('Simpan');
                                
                                // Reload tabel
                                tbUnits.ajax.reload();
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function(xhr) {
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            if (xhr.status === 422) {
                                // Validasi error
                                let errors = xhr.responseJSON.errors;
                                let errorMessage = '';
                                
                                $.each(errors, function(key, value) {
                                    errorMessage += value[0] + '\n';
                                });
                                
                                showError(errorMessage);
                            } else if (xhr.status === 500) {
                                showError('Terjadi kesalahan server. Silakan coba lagi.');
                            } else {
                                showError('Terjadi kesalahan. Silakan coba lagi.');
                            }
                        }
                    });
                });

                // Edit Unit
                $(document).on('click', '.editUnit', function() {
                    const unitId = $(this).data('id');
                    
                    // Tampilkan loading
                    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                    
                    $.ajax({
                        url: "{{ url('units') }}/" + unitId + "/edit",
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            $('.editUnit[data-id="' + unitId + '"]').prop('disabled', false).html('<i class="bi bi-pencil"></i>');
                            
                            if (response.success) {
                                // Isi form dengan data unit
                                $('#idUnit').val(response.data.id);
                                $('#idproject').val(response.data.idproject);
                                $('#idjenis').val(response.data.idjenis);
                                $('#namaunit').val(response.data.namaunit);
                                $('#tipe').val(response.data.tipe);
                                $('#blok').val(response.data.blok);
                                $('#luastanah').val(response.data.luastanah);
                                $('#luasbangunan').val(response.data.luasbangunan);
                                $('#hargadasar').val(response.data.hargadasar);
                                $('#jumlah').val(response.data.jumlah);
                                
                                // Ubah judul modal
                                $('#modalTitle').text('Edit Unit');
                                $('#submitText').text('Update');
                                
                                // Tampilkan modal
                                $('#modalUnit').modal('show');
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function() {
                            $('.editUnit[data-id="' + unitId + '"]').prop('disabled', false).html('<i class="bi bi-pencil"></i>');
                            showError('Gagal memuat data unit');
                        }
                    });
                });

                // Delete Unit
                $(document).on('click', '.deleteUnit', function() {
                    const unitId = $(this).data('id');
                    const button = $(this);
                    
                    // Konfirmasi delete
                    Swal.fire({
                        title: 'Hapus Unit?',
                        text: "Data yang dihapus tidak dapat dikembalikan!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Tampilkan loading
                            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                            
                            $.ajax({
                                url: "{{ url('units') }}/" + unitId,
                                type: 'DELETE',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                dataType: 'json',
                                success: function(response) {
                                    button.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                                    
                                    if (response.success) {
                                        showSuccess(response.message);
                                        tbUnits.ajax.reload();
                                    } else {
                                        showError(response.message);
                                    }
                                },
                                error: function(xhr) {
                                    button.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                                    
                                    if (xhr.status === 400) {
                                        showError(xhr.responseJSON.message);
                                    } else if (xhr.status === 404) {
                                        showError('Unit tidak ditemukan');
                                    } else {
                                        showError('Gagal menghapus unit');
                                    }
                                }
                            });
                        }
                    });
                });

                // Reset form saat modal ditutup
                $('#modalUnit').on('hidden.bs.modal', function() {
                    $('#frmUnit')[0].reset();
                    $('#idUnit').val('');
                    $('#modalTitle').text('Tambah');
                    $('#submitText').text('Simpan');
                });
            });
        </script>
    </x-slot>
</x-app-layout>