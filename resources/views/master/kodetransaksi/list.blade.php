<x-app-layout>
    <x-slot name="pagetitle">Kode Transaksi</x-slot>

    {{-- Hidden iframe untuk download --}}
    <iframe id="downloadFrame" style="display:none;"></iframe>

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
                        {{-- Tombol Export --}}
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="javascript:void(0)" id="btnExportExcel">
                                    <i class="bi bi-file-earmark-excel"></i> Export Excel (.csv)
                                </a>
                            </div>
                        </div>
                        
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
                                <th>Header Transaksi</th>
                                <th>COA</th>
                                <th>Neraca</th>
                                <th>Laba Rugi</th>
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
                        <div class="mb-3">
                            <label class="form-label">Kode Transaksi</label>
                            <input type="text" class="form-control form-control-sm" name="kodetransaksi" id="kodetransaksi" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Transaksi</label>
                            <input type="text" class="form-control form-control-sm" name="transaksi" id="transaksi" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Header Transaksi</label>
                            <select class="form-select form-select-sm select2-modal" name="idheader" id="modalHeader">
                                <option value="">-- Pilih Header --</option>
                                @foreach($transaksiHeaders as $header)
                                    <option value="{{ $header->id }}">{{ $header->keterangan }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">COA</label>
                            <select class="form-select form-select-sm select2-modal" name="idcoa" id="modalCoa">
                                <option value="">-- Pilih COA --</option>
                                @foreach($coa as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Neraca</label>
                            <select class="form-select form-select-sm select2-modal" name="idneraca" id="modalNeraca">
                                <option value="">-- Pilih Neraca --</option>
                                @foreach($neracaHeaders as $neraca)
                                    <option value="{{ $neraca->id }}">{{ $neraca->rincian }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Laba Rugi</label>
                            <select class="form-select form-select-sm select2-modal" name="idlabarugi" id="modalLabaRugi">
                                <option value="">-- Pilih Laba Rugi --</option>
                                @foreach($labaRugiHeaders as $labarugi)
                                    <option value="{{ $labarugi->id }}">{{ $labarugi->rincian }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            // Inisialisasi Select2 untuk modal
            function initSelect2Modal() {
                $('.select2-modal').select2({
                    dropdownParent: $('#modalData'),
                    width: '100%',
                    placeholder: 'Pilih opsi',
                    allowClear: true
                });
            }

            // Fungsi untuk inisialisasi Select2 di tabel
            function initSelect2Table() {
                $('.select2-table').select2({
                    width: '100%',
                    placeholder: 'Pilih opsi',
                    allowClear: true,
                    dropdownParent: $('body')
                });
            }

            // Fungsi untuk menampilkan error
            function showError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    confirmButtonText: 'OK'
                });
            }

            // Fungsi untuk menampilkan success
            function showSuccess(message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: message,
                    timer: 1500,
                    showConfirmButton: false
                });
            }

            // Variabel untuk tracking export
            let exportLoading = null;

            let tb = $('#tbData').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 50,
                ajax: {
                    url: "{{ route('kodetransaksi.data') }}",
                    type: "GET",
                    error: function(xhr) {
                        console.error('Error loading data:', xhr);
                        showError('Gagal memuat data. Silakan refresh halaman.');
                    }
                },
                columns: [
                    { 
                        data: 'DT_RowIndex', 
                        name: 'DT_RowIndex',
                        className: 'text-center',
                        orderable: false,
                        searchable: false 
                    },
                    { 
                        data: 'kodetransaksi', 
                        name: 'kodetransaksi',
                        className: 'text-center' 
                    },
                    { 
                        data: 'transaksi', 
                        name: 'transaksi'
                    },
                    { 
                        data: 'idheader',
                        name: 'idheader',
                        render: function(data, type, row) {
                            let options = `<option value="">-- Pilih Header --</option>`;
                            @foreach($transaksiHeaders as $header)
                                options += `<option value="{{ $header->id }}" ${data == {{ $header->id }} ? 'selected' : ''}>{{ $header->keterangan }}</option>`;
                            @endforeach
                            return `<select class="form-select form-select-sm select2-table table-header" data-id="${row.id}" data-field="idheader">${options}</select>`;
                        },
                        orderable: false,
                        searchable: false
                    },
                    { 
                        data: 'idcoa',
                        name: 'idcoa',
                        render: function(data, type, row) {
                            let options = `<option value="">-- Pilih COA --</option>`;
                            @foreach($coa as $c)
                                options += `<option value="{{ $c->id }}" ${data == {{ $c->id }} ? 'selected' : ''}>{{ $c->name }}</option>`;
                            @endforeach
                            return `<select class="form-select form-select-sm select2-table table-coa" data-id="${row.id}" data-field="idcoa">${options}</select>`;
                        },
                        orderable: false,
                        searchable: false
                    },
                    { 
                        data: 'idneraca',
                        name: 'idneraca',
                        render: function(data, type, row) {
                            let options = `<option value="">-- Pilih Neraca --</option>`;
                            @foreach($neracaHeaders as $neraca)
                                options += `<option value="{{ $neraca->id }}" ${data == {{ $neraca->id }} ? 'selected' : ''}>{{ $neraca->rincian }}</option>`;
                            @endforeach
                            return `<select class="form-select form-select-sm select2-table table-neraca" data-id="${row.id}" data-field="idneraca">${options}</select>`;
                        },
                        orderable: false,
                        searchable: false
                    },
                    { 
                        data: 'idlabarugi',
                        name: 'idlabarugi',
                        render: function(data, type, row) {
                            let options = `<option value="">-- Pilih Laba Rugi --</option>`;
                            @foreach($labaRugiHeaders as $labarugi)
                                options += `<option value="{{ $labarugi->id }}" ${data == {{ $labarugi->id }} ? 'selected' : ''}>{{ $labarugi->rincian }}</option>`;
                            @endforeach
                            return `<select class="form-select form-select-sm select2-table table-labarugi" data-id="${row.id}" data-field="idlabarugi">${options}</select>`;
                        },
                        orderable: false,
                        searchable: false
                    },
                    { 
                        data: 'action', 
                        name: 'action',
                        orderable: false, 
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-warning btn-edit" data-id="${row.id}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-delete" data-id="${row.id}" data-kode="${row.kodetransaksi}" data-nama="${row.transaksi}" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                drawCallback: function() {
                    // Inisialisasi Select2 setiap kali tabel dirender ulang
                    initSelect2Table();
                },
                initComplete: function() {
                    // Inisialisasi Select2 saat pertama kali load
                    initSelect2Table();
                }
            });

            // Export Excel - Solusi 1: Menggunakan iframe
            $('#btnExportExcel').click(function(e) {
                e.preventDefault();
                
                // Tampilkan loading
                exportLoading = Swal.fire({
                    title: 'Export Data',
                    text: 'Sedang menyiapkan file...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Gunakan iframe untuk download
                const downloadFrame = document.getElementById('downloadFrame');
                downloadFrame.src = "{{ route('kodetransaksi.export.excel') }}";
                
                // Set timeout untuk menutup loading setelah beberapa detik
                setTimeout(() => {
                    if (exportLoading) {
                        exportLoading.close();
                        exportLoading = null;
                        Swal.fire({
                            icon: 'success',
                            title: 'Download Selesai',
                            text: 'File berhasil diunduh.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }, 3000); // 3 detik
            });

            // Update langsung dari table untuk semua field
            $(document).on('change', '.select2-table', function(){
                let id = $(this).data('id');
                let field = $(this).data('field');
                let value = $(this).val();
                
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Apakah Anda yakin ingin mengubah data ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Update!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Memperbarui...',
                            text: 'Mohon tunggu...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        $.ajax({
                            url: '{{ url("kodetransaksi") }}/' + id + '/update-field',
                            type: 'PATCH',
                            data: { 
                                field: field,
                                value: value,
                                _token: '{{ csrf_token() }}' 
                            },
                            success: function(res) {
                                Swal.close();
                                if(res.success) {
                                    showSuccess('Data berhasil diperbarui.');
                                } else {
                                    showError('Gagal memperbarui data.');
                                }
                            },
                            error: function(xhr) {
                                Swal.close();
                                let errorMsg = 'Terjadi kesalahan saat memperbarui data.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                showError(errorMsg);
                                tb.ajax.reload(null, false);
                            }
                        });
                    } else {
                        // Reload data untuk mengembalikan nilai sebelumnya
                        tb.ajax.reload(null, false);
                    }
                });
            });

            // Tambah Data
            $('#btnTambah').click(function(){
                $('#frmData')[0].reset();
                $('#idData').val('');
                
                // Reset Select2 di modal
                $('.select2-modal').val(null).trigger('change');
                
                // Set modal title
                $('.modal-title').text('Tambah Kode Transaksi');
                
                $('#modalData').modal('show');
                
                // Inisialisasi Select2 untuk modal
                setTimeout(() => {
                    initSelect2Modal();
                }, 100);
            });

            // Submit Tambah/Edit
            $('#frmData').submit(function(e){
                e.preventDefault();
                
                let id = $('#idData').val();
                let url = id ? '{{ url("kodetransaksi") }}/' + id : "{{ route('kodetransaksi.store') }}";
                let method = id ? 'PUT' : 'POST';
                
                Swal.fire({
                    title: 'Menyimpan Data',
                    text: 'Mohon tunggu...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: url,
                    type: method,
                    data: $(this).serialize(),
                    success: function(response) {
                        Swal.close();
                        showSuccess('Data berhasil disimpan.');
                        $('#modalData').modal('hide');
                        tb.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.close();
                        let errorMessage = 'Terjadi kesalahan saat menyimpan data.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            let errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: errorMessage
                        });
                    }
                });
            });

            // Edit Data - menggunakan event delegation
            $(document).on('click', '.btn-edit', function(){
                let id = $(this).data('id');
                
                Swal.fire({
                    title: 'Memuat Data',
                    text: 'Mohon tunggu...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: '{{ url("kodetransaksi") }}/' + id + '/edit',
                    type: 'GET',
                    success: function(d) {
                        Swal.close();
                        
                        if (!d || !d.id) {
                            showError('Data tidak ditemukan.');
                            return;
                        }
                        
                        $('#idData').val(d.id);
                        $('#kodetransaksi').val(d.kodetransaksi);
                        $('#transaksi').val(d.transaksi);
                        
                        // Set nilai untuk Select2 di modal
                        $('#modalHeader').val(d.idheader).trigger('change');
                        $('#modalCoa').val(d.idcoa).trigger('change');
                        $('#modalNeraca').val(d.idneraca).trigger('change');
                        $('#modalLabaRugi').val(d.idlabarugi).trigger('change');
                        
                        // Set modal title
                        $('.modal-title').text('Edit Kode Transaksi');
                        
                        $('#modalData').modal('show');
                        
                        // Inisialisasi Select2 untuk modal
                        setTimeout(() => {
                            initSelect2Modal();
                        }, 100);
                    },
                    error: function(xhr) {
                        Swal.close();
                        let errorMsg = 'Gagal memuat data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        showError(errorMsg);
                    }
                });
            });

            // Delete Data - menggunakan event delegation
            $(document).on('click', '.btn-delete', function(){
                let id = $(this).data('id');
                let kode = $(this).data('kode');
                let nama = $(this).data('nama');
                
                Swal.fire({
                    title: 'Hapus Data?',
                    html: `Apakah Anda yakin ingin menghapus:<br>
                           <strong>${kode} - ${nama}</strong><br>
                           Data yang dihapus tidak dapat dikembalikan!`,
                    icon: 'warning',
                    showConfirmButton: true,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menghapus...',
                            text: 'Mohon tunggu...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        $.ajax({
                            url: '{{ url("kodetransaksi") }}/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.close();
                                if (response && response.success) {
                                    showSuccess('Data berhasil dihapus.');
                                    tb.ajax.reload(null, false);
                                } else {
                                    showError('Gagal menghapus data.');
                                }
                            },
                            error: function(xhr) {
                                Swal.close();
                                let errorMsg = 'Gagal menghapus data.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                showError(errorMsg);
                            }
                        });
                    }
                });
            });

            // Inisialisasi Select2 untuk modal saat modal ditampilkan
            $('#modalData').on('shown.bs.modal', function () {
                initSelect2Modal();
            });

            // Reset Select2 saat modal ditutup
            $('#modalData').on('hidden.bs.modal', function () {
                $('.select2-modal').val(null).trigger('change');
            });

        </script>
    </x-slot>

    <x-slot name="csscustom">
        <style>
            .select2-container--bootstrap-5 .select2-selection {
                min-height: calc(1.5em + .5rem + 2px);
                padding: .25rem .5rem;
                font-size: .875rem;
                line-height: 1.5;
            }
            .select2-container--bootstrap-5 .select2-dropdown {
                font-size: .875rem;
            }
            .table .select2-container {
                width: 100% !important;
            }
            .table .select2-selection {
                border: 1px solid #dee2e6;
                border-radius: .25rem;
            }
            .btn-group .dropdown-toggle::after {
                margin-left: 0.255em;
            }
            .dataTables_wrapper {
                position: relative;
            }
            .btn-group {
                white-space: nowrap;
            }
        </style>
    </x-slot>
</x-app-layout>