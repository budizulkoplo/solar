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
                            <input type="text" class="form-control form-control-sm" name="kodetransaksi" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Transaksi</label>
                            <input type="text" class="form-control form-control-sm" name="transaksi" required>
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
                    dropdownParent: $('body') // Untuk memastikan dropdown tidak terpotong
                });
            }

            let tb = $('#tbData').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 50,
                ajax: "{{ route('kodetransaksi.data') }}",
                columns: [
                    { data: 'DT_RowIndex', className: 'text-center' },
                    { data: 'kodetransaksi', className: 'text-center' },
                    { data: 'transaksi' },
                    { 
                        data: 'idheader',
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
                        orderable: false, 
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-warning editData" data-id="${row.id}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-danger deleteData" data-id="${row.id}" title="Hapus">
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

            // Update langsung dari table untuk semua field
            $('#tbData').on('change', '.select2-table', function(){
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
                        $.ajax({
                            url: '/kodetransaksi/'+id+'/update-field',
                            type: 'PATCH',
                            data: { 
                                field: field,
                                value: value,
                                _token: '{{ csrf_token() }}' 
                            },
                            success: function(res) {
                                if(res.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: 'Data berhasil diperbarui.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan saat memperbarui data.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
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
                let url = id ? '/kodetransaksi/'+id : "{{ route('kodetransaksi.store') }}";
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
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data berhasil disimpan.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#modalData').modal('hide');
                        tb.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.close();
                        let errors = xhr.responseJSON?.errors;
                        let errorMessage = 'Terjadi kesalahan saat menyimpan data.';
                        
                        if (errors) {
                            errorMessage = Object.values(errors).flat().join('<br>');
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: errorMessage
                        });
                    }
                });
            });

            // Edit Data
            $('#tbData').on('click', '.editData', function(){
                let id = $(this).data('id');
                
                Swal.fire({
                    title: 'Memuat Data',
                    text: 'Mohon tunggu...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.get('/kodetransaksi/'+id+'/edit', function(d){
                    Swal.close();
                    
                    $('#idData').val(d.id);
                    $('input[name="kodetransaksi"]').val(d.kodetransaksi);
                    $('input[name="transaksi"]').val(d.transaksi);
                    
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
                }).fail(function() {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Gagal memuat data.'
                    });
                });
            });

            // Delete Data
            $('#tbData').on('click', '.deleteData', function(){
                let id = $(this).data('id');
                
                Swal.fire({
                    title: 'Hapus Data?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
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
                            url: '/kodetransaksi/'+id,
                            type: 'DELETE',
                            data: {_token: '{{ csrf_token() }}'},
                            success: function() {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus!',
                                    text: 'Data berhasil dihapus.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                tb.ajax.reload();
                            },
                            error: function() {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Gagal menghapus data.'
                                });
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
        </style>
    </x-slot>
</x-app-layout>