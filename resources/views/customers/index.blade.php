<x-app-layout>
    <x-slot name="pagetitle">Master Customers</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Customers</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCustomer">
                            <i class="bi bi-plus-circle"></i> Tambah Customer
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbCustomers" class="table table-sm table-hover w-100" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Kode</th>
                                <th>Nama Lengkap</th>
                                <th>NIK</th>
                                <th>No. HP</th>
                                <th>Jenis Kelamin</th>
                                <th>Pekerjaan</th>
                                <th width="12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data akan diisi oleh DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Customer -->
    <div class="modal fade" id="modalCustomer" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="frmCustomer">
                    @csrf
                    <input type="hidden" name="id" id="idCustomer">

                    <div class="modal-header">
                        <h5 class="modal-title"><span id="modalTitle">Tambah</span> Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <!-- Data Pribadi -->
                            <div class="col-12">
                                <h6 class="border-bottom pb-1 mb-3">Data Pribadi</h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="nama_lengkap" id="nama_lengkap" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="tempat_lahir" id="tempat_lahir" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="tanggal_lahir" id="tanggal_lahir" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="jenis_kelamin" id="jenis_kelamin" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">NIK <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="nik" id="nik" required maxlength="16" pattern="[0-9]{16}" title="NIK harus 16 digit angka">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Status Pernikahan</label>
                                <input type="text" class="form-control form-control-sm" name="status_pernikahan" id="status_pernikahan">
                            </div>
                            
                            <!-- Alamat KTP -->
                            <div class="col-12 mt-3">
                                <h6 class="border-bottom pb-1 mb-3">Alamat sesuai KTP</h6>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" name="alamat_ktp" id="alamat_ktp" rows="2" required></textarea>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">RT/RW <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="rt_rw_ktp" id="rt_rw_ktp" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Kelurahan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="kelurahan_ktp" id="kelurahan_ktp" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Kecamatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="kecamatan_ktp" id="kecamatan_ktp" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Kota <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="kota_ktp" id="kota_ktp" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Provinsi</label>
                                <input type="text" class="form-control form-control-sm" name="provinsi_ktp" id="provinsi_ktp">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" class="form-control form-control-sm" name="kode_pos_ktp" id="kode_pos_ktp" maxlength="10">
                            </div>
                            
                            <!-- Kontak & Pekerjaan -->
                            <div class="col-12 mt-3">
                                <h6 class="border-bottom pb-1 mb-3">Kontak & Pekerjaan</h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">No. HP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="no_hp" id="no_hp" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control form-control-sm" name="email" id="email">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Pekerjaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="pekerjaan" id="pekerjaan" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Penghasilan Bulanan (Rp)</label>
                                <input type="number" class="form-control form-control-sm" name="penghasilan_bulanan" id="penghasilan_bulanan">
                            </div>
                            
                            <!-- Data Keluarga -->
                            <div class="col-12 mt-3">
                                <h6 class="border-bottom pb-1 mb-3">Data Keluarga</h6>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Nama Ibu Kandung</label>
                                <input type="text" class="form-control form-control-sm" name="nama_ibu_kandung" id="nama_ibu_kandung">
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

    <!-- Modal Detail Customer -->
    <div class="modal fade" id="modalDetailCustomer" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailCustomerContent">
                    <!-- Content akan diisi via JS -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function() {
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

                // Inisialisasi DataTables
                let tbCustomers = $('#tbCustomers').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('customers.getdata') }}",
                        type: "GET",
                        error: function(xhr, error, thrown) {
                            console.error('DataTables error:', error, thrown);
                            showError('Gagal memuat data customer');
                        }
                    },
                    columns: [
                        { 
                            data: null,
                            name: 'no',
                            className: 'text-center',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        { 
                            data: 'kode_customer', 
                            name: 'kode_customer'
                        },
                        { 
                            data: 'nama_lengkap', 
                            name: 'nama_lengkap'
                        },
                        { 
                            data: 'nik', 
                            name: 'nik'
                        },
                        { 
                            data: 'no_hp', 
                            name: 'no_hp'
                        },
                        { 
                            data: 'jenis_kelamin', 
                            name: 'jenis_kelamin'
                        },
                        { 
                            data: 'pekerjaan', 
                            name: 'pekerjaan'
                        },
                        
                        { 
                            data: 'action', 
                            name: 'action',
                            orderable: false, 
                            searchable: false,
                            className: 'text-center'
                        }
                    ],
                    order: [[1, 'desc']],
                    language: {
                        emptyTable: "Tidak ada data customer",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Tidak ditemukan data yang cocok",
                        loadingRecords: "Memuat...",
                        processing: "Memproses...",
                        paginate: {
                            first: "Awal",
                            last: "Akhir",
                            next: "›",
                            previous: "‹"
                        }
                    },
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]]
                });

                // Submit form (Add/Update)
                $('#frmCustomer').submit(function(e) {
                    e.preventDefault();
                    
                    const formData = $(this).serialize();
                    const customerId = $('#idCustomer').val();
                    
                    // Tentukan URL dan method
                    let url = "{{ route('customers.store') }}";
                    let method = 'POST';
                    
                    if (customerId) {
                        url = "{{ url('customers') }}/" + customerId;
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
                                $('#modalCustomer').modal('hide');
                                
                                // Reset form
                                $('#frmCustomer')[0].reset();
                                $('#idCustomer').val('');
                                $('#modalTitle').text('Tambah');
                                $('#submitText').text('Simpan');
                                
                                // Reload tabel
                                tbCustomers.ajax.reload(null, false); // false untuk tetap di halaman saat ini
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

                // Edit Customer
                $(document).on('click', '.editCustomer', function() {
                    const customerId = $(this).data('id');
                    const button = $(this);
                    
                    // Tampilkan loading
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                    
                    $.ajax({
                        url: "{{ url('customers') }}/" + customerId + "/edit",
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            button.prop('disabled', false).html('<i class="bi bi-pencil"></i>');
                            
                            if (response.success) {
                                // Isi form dengan data customer
                                $('#idCustomer').val(response.data.id);
                                $('#nama_lengkap').val(response.data.nama_lengkap);
                                $('#tempat_lahir').val(response.data.tempat_lahir);
                                $('#tanggal_lahir').val(response.data.tanggal_lahir.split('T')[0]);
                                $('#jenis_kelamin').val(response.data.jenis_kelamin);
                                $('#nik').val(response.data.nik);
                                $('#status_pernikahan').val(response.data.status_pernikahan);
                                $('#alamat_ktp').val(response.data.alamat_ktp);
                                $('#rt_rw_ktp').val(response.data.rt_rw_ktp);
                                $('#kelurahan_ktp').val(response.data.kelurahan_ktp);
                                $('#kecamatan_ktp').val(response.data.kecamatan_ktp);
                                $('#kota_ktp').val(response.data.kota_ktp);
                                $('#provinsi_ktp').val(response.data.provinsi_ktp);
                                $('#kode_pos_ktp').val(response.data.kode_pos_ktp);
                                $('#no_hp').val(response.data.no_hp);
                                $('#email').val(response.data.email);
                                $('#pekerjaan').val(response.data.pekerjaan);
                                $('#penghasilan_bulanan').val(response.data.penghasilan_bulanan);
                                $('#nama_ibu_kandung').val(response.data.nama_ibu_kandung);
                                
                                // Ubah judul modal
                                $('#modalTitle').text('Edit Customer');
                                $('#submitText').text('Update');
                                
                                // Tampilkan modal
                                $('#modalCustomer').modal('show');
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function(xhr) {
                            button.prop('disabled', false).html('<i class="bi bi-pencil"></i>');
                            
                            if (xhr.status === 404) {
                                showError('Customer tidak ditemukan');
                            } else {
                                showError('Gagal memuat data customer');
                            }
                        }
                    });
                });

                // View Detail Customer
                $(document).on('click', '.viewCustomer', function() {
                    const customerId = $(this).data('id');
                    const button = $(this);
                    
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                    
                    $.ajax({
                        url: "{{ url('customers') }}/" + customerId + "/detail",
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            button.prop('disabled', false).html('<i class="bi bi-eye"></i>');
                            
                            if (response.success) {
                                const customer = response.data;
                                
                                // Hitung umur
                                const birthDate = new Date(customer.tanggal_lahir);
                                const today = new Date();
                                let age = today.getFullYear() - birthDate.getFullYear();
                                const monthDiff = today.getMonth() - birthDate.getMonth();
                                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                                    age--;
                                }
                                
                                let html = `
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Data Pribadi</h6>
                                            <p><strong>Kode Customer:</strong> ${customer.kode_customer}</p>
                                            <p><strong>Nama Lengkap:</strong> ${customer.nama_lengkap}</p>
                                            <p><strong>NIK:</strong> ${customer.nik}</p>
                                            <p><strong>Tempat/Tgl Lahir:</strong> ${customer.tempat_lahir}, ${new Date(customer.tanggal_lahir).toLocaleDateString('id-ID')}</p>
                                            <p><strong>Umur:</strong> ${age} tahun</p>
                                            <p><strong>Jenis Kelamin:</strong> ${customer.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</p>
                                            <p><strong>Status Pernikahan:</strong> ${customer.status_pernikahan || '-'}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Kontak & Pekerjaan</h6>
                                            <p><strong>No. HP:</strong> ${customer.no_hp}</p>
                                            <p><strong>Email:</strong> ${customer.email || '-'}</p>
                                            <p><strong>Pekerjaan:</strong> ${customer.pekerjaan}</p>
                                            <p><strong>Penghasilan:</strong> ${customer.penghasilan_bulanan ? 'Rp ' + parseInt(customer.penghasilan_bulanan).toLocaleString('id-ID') : '-'}</p>
                                            <p><strong>Nama Ibu Kandung:</strong> ${customer.nama_ibu_kandung || '-'}</p>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6>Alamat KTP</h6>
                                            <p>${customer.alamat_ktp}</p>
                                            <p>${customer.rt_rw_ktp}, ${customer.kelurahan_ktp}, ${customer.kecamatan_ktp}</p>
                                            <p>${customer.kota_ktp}${customer.provinsi_ktp ? ', ' + customer.provinsi_ktp : ''}${customer.kode_pos_ktp ? ' ' + customer.kode_pos_ktp : ''}</p>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6>Data Sistem</h6>
                                            <p><strong>Dibuat:</strong> ${new Date(customer.created_at).toLocaleDateString('id-ID')}</p>
                                            <p><strong>Diperbarui:</strong> ${new Date(customer.updated_at).toLocaleDateString('id-ID')}</p>
                                        </div>
                                    </div>
                                `;
                                
                                $('#detailCustomerContent').html(html);
                                $('#modalDetailCustomer').modal('show');
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function(xhr) {
                            button.prop('disabled', false).html('<i class="bi bi-eye"></i>');
                            
                            if (xhr.status === 404) {
                                showError('Customer tidak ditemukan');
                            } else {
                                showError('Gagal memuat detail customer');
                            }
                        }
                    });
                });

                // Delete Customer
                $(document).on('click', '.deleteCustomer', function() {
                    const customerId = $(this).data('id');
                    const button = $(this);
                    
                    // Konfirmasi delete
                    Swal.fire({
                        title: 'Hapus Customer?',
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
                                url: "{{ url('customers') }}/" + customerId,
                                type: 'DELETE',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                dataType: 'json',
                                success: function(response) {
                                    button.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                                    
                                    if (response.success) {
                                        showSuccess(response.message);
                                        tbCustomers.ajax.reload(null, false);
                                    } else {
                                        showError(response.message);
                                    }
                                },
                                error: function(xhr) {
                                    button.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                                    
                                    if (xhr.status === 400) {
                                        showError(xhr.responseJSON.message);
                                    } else if (xhr.status === 404) {
                                        showError('Customer tidak ditemukan');
                                    } else {
                                        showError('Gagal menghapus customer');
                                    }
                                }
                            });
                        }
                    });
                });

                // Reset form saat modal ditutup
                $('#modalCustomer').on('hidden.bs.modal', function() {
                    $('#frmCustomer')[0].reset();
                    $('#idCustomer').val('');
                    $('#modalTitle').text('Tambah');
                    $('#submitText').text('Simpan');
                });

                // Format NIK input
                $('#nik').on('input', function() {
                    this.value = this.value.replace(/\D/g, '').slice(0, 16);
                });

                // Format phone number input
                $('#no_hp').on('input', function() {
                    this.value = this.value.replace(/\D/g, '').slice(0, 20);
                });
            });
        </script>
    </x-slot>
</x-app-layout>