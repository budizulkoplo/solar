{{-- resources/views/construction/index.blade.php --}}
<x-app-layout>
    <x-slot name="pagetitle">Pekerjaan Konstruksi</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Pekerjaan Konstruksi</h3>
                <div>
                    <a href="{{ route('pekerjaan-konstruksi.report') }}" class="btn btn-info btn-sm">
                        <i class="bi bi-file-bar-graph"></i> Laporan
                    </a>
                    <button class="btn btn-primary btn-sm" id="btnTambah">
                        <i class="bi bi-plus-circle"></i> Tambah Pekerjaan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Filter -->
            <div class="card card-primary card-outline mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filter</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Project</label>
                            <select class="form-select form-select-sm" id="filterProject">
                                <option value="">Semua Project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->namaproject }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kode Transaksi</label>
                            <select class="form-select form-select-sm select2-filter" id="filterJenis">
                                <option value="">Semua Kode</option>
                                @foreach($kodeTransaksi as $item)
                                    <option value="{{ $item->id }}">{{ $item->kodetransaksi }} - {{ $item->transaksi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select form-select-sm" id="filterStatus">
                                <option value="">Semua Status</option>
                                @foreach($statusPekerjaan as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-sm btn-primary d-block" id="btnFilter">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar Pekerjaan Konstruksi</h5>
                </div>
                <div class="card-body">
                    <table id="tbPekerjaan" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Project</th>
                                <th>Nama Pekerjaan</th>
                                <th>Kode Transaksi</th>
                                <th>Lokasi</th>
                                <th>Volume</th>
                                <th class="text-end">Anggaran</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    <div class="modal fade" id="modalPekerjaan" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formPekerjaan">
                    @csrf
                    <input type="hidden" id="idPekerjaan" name="id">
                    
                    <div class="modal-header">
                        <h6 class="modal-title" id="modalTitle">Tambah Pekerjaan Konstruksi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Project *</label>
                                <select class="form-select form-select-sm" name="idproject" id="idproject" required>
                                    <option value="">-- Pilih Project --</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->namaproject }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Kode Transaksi *</label>
                                <select class="form-select form-select-sm select2-kode-transaksi" name="idkodetransaksi" id="idkodetransaksi" required>
                                    <option value="">-- Pilih Kode Transaksi --</option>
                                    @foreach($kodeTransaksi as $item)
                                        <option value="{{ $item->id }}">{{ $item->kodetransaksi }} - {{ $item->transaksi }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Nama Pekerjaan *</label>
                                <input type="text" class="form-control form-control-sm" 
                                       name="nama_pekerjaan" id="nama_pekerjaan" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Lokasi</label>
                                <input type="text" class="form-control form-control-sm" 
                                       name="lokasi" id="lokasi">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Volume</label>
                                <input type="number" class="form-control form-control-sm text-end" 
                                       name="volume" id="volume" min="0" step="0.01">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Satuan</label>
                                <input type="text" class="form-control form-control-sm" 
                                       name="satuan" id="satuan" placeholder="m, m², unit, etc">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Anggaran *</label>
                                <input type="number" class="form-control form-control-sm text-end" 
                                       name="anggaran" id="anggaran" required min="0">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Harga Satuan</label>
                                <input type="number" class="form-control form-control-sm text-end" 
                                       name="harga_satuan" id="harga_satuan" min="0" step="0.01">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control form-control-sm" 
                                       name="tanggal_mulai" id="tanggal_mulai">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control form-control-sm" 
                                       name="tanggal_selesai" id="tanggal_selesai">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select class="form-select form-select-sm" name="status" id="status" required>
                                    <option value="planning">Planning</option>
                                    <option value="ongoing">Sedang Berjalan</option>
                                    <option value="completed">Selesai</option>
                                    <option value="canceled">Dibatalkan</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Keterangan</label>
                                <textarea class="form-control form-control-sm" 
                                          name="keterangan" id="keterangan" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span class="submit-text">Simpan</span>
                            <span class="loading-text" style="display:none;">
                                <i class="bi bi-hourglass-split"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal View Detail -->
    <div class="modal fade" id="modalViewPekerjaan" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pekerjaan Konstruksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Project</th>
                                    <td id="viewProject">-</td>
                                </tr>
                                <tr>
                                    <th>Nama Pekerjaan</th>
                                    <td id="viewNamaPekerjaan">-</td>
                                </tr>
                                <tr>
                                    <th>Kode Transaksi</th>
                                    <td id="viewKodeTransaksi">-</td>
                                </tr>
                                <tr>
                                    <th>Lokasi</th>
                                    <td id="viewLokasi">-</td>
                                </tr>
                                <tr>
                                    <th>Volume</th>
                                    <td id="viewVolume">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Anggaran</th>
                                    <td id="viewAnggaran">-</td>
                                </tr>
                                <tr>
                                    <th>Harga Satuan</th>
                                    <td id="viewHargaSatuan">-</td>
                                </tr>
                                <tr>
                                    <th>Durasi</th>
                                    <td id="viewDurasi">-</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td id="viewStatus">-</td>
                                </tr>
                                <tr>
                                    <th>Progress</th>
                                    <td id="viewProgress">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2">Keterangan</h6>
                            <p id="viewKeterangan" class="mb-0">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
            function initSelect2() {
                if ($.fn.select2) {
                    $('#filterJenis').select2({
                        width: '100%',
                        placeholder: 'Semua Kode',
                        allowClear: true
                    });

                    $('#idkodetransaksi').select2({
                        dropdownParent: $('#modalPekerjaan'),
                        width: '100%',
                        placeholder: '-- Pilih Kode Transaksi --'
                    });
                }
            }

            initSelect2();

            // DataTable
            let tbPekerjaan = $('#tbPekerjaan').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: "{{ route('pekerjaan-konstruksi.get-data') }}",
                    data: function(d) {
                        d.project_id = $('#filterProject').val();
                        d.idkodetransaksi = $('#filterJenis').val();
                        d.status = $('#filterStatus').val();
                    }
                },
                columns: [
                    { 
                        data: 'DT_RowIndex', 
                        name: 'DT_RowIndex', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    },
                    { data: 'project', name: 'project' },
                    { data: 'nama_pekerjaan', name: 'nama_pekerjaan' },
                    { data: 'kode_transaksi_formatted', name: 'idkodetransaksi' },
                    { data: 'lokasi', name: 'lokasi' },
                    { 
                        data: 'volume', 
                        name: 'volume',
                        render: function(data, type, row) {
                            if (data && row.satuan) {
                                return data + ' ' + row.satuan;
                            }
                            return data || '-';
                        }
                    },
                    { data: 'anggaran_formatted', name: 'anggaran' },
                    { data: 'durasi', name: 'durasi' },
                    { data: 'status_formatted', name: 'status' },
                    { data: 'progress', name: 'progress' },
                    { 
                        data: 'action', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    }
                ]
            });

            // Filter
            $('#btnFilter').click(function() {
                tbPekerjaan.ajax.reload();
            });

            // Tambah Pekerjaan
            $('#btnTambah').click(function() {
                $('#formPekerjaan')[0].reset();
                $('#idPekerjaan').val('');
                $('#idkodetransaksi').val('').trigger('change');
                $('#modalTitle').text('Tambah Pekerjaan Konstruksi');
                $('#modalPekerjaan').modal('show');
            });

            // Edit Pekerjaan - PERBAIKAN INI
            $(document).on('click', '.editPekerjaan', function() {
                let id = $(this).data('id');
                
                console.log('Edit clicked, ID:', id);
                
                if (!id) {
                    console.error('ID tidak ditemukan');
                    Swal.fire('Error', 'ID pekerjaan tidak ditemukan', 'error');
                    return;
                }
                
                // Method 1: Gunakan route dengan parameter
                let url = "{{ route('pekerjaan-konstruksi.edit', ':id') }}".replace(':id', id);
                
                console.log('Fetching URL:', url);
                
                $.get(url, function(res) {
                    if (res.success) {
                        let data = res.data;
                        
                        console.log('Data received:', data);
                        
                        // Format tanggal untuk input date (YYYY-MM-DD)
                        function formatDateForInput(dateString) {
                            if (!dateString) return '';
                            let date = new Date(dateString);
                            if (isNaN(date.getTime())) return '';
                            return date.toISOString().split('T')[0];
                        }
                        
                        $('#idPekerjaan').val(data.id);
                        $('#idproject').val(data.idproject);
                        $('#idkodetransaksi').val(data.idkodetransaksi).trigger('change');
                        $('#nama_pekerjaan').val(data.nama_pekerjaan);
                        $('#lokasi').val(data.lokasi);
                        $('#volume').val(data.volume);
                        $('#satuan').val(data.satuan);
                        $('#anggaran').val(data.anggaran);
                        $('#harga_satuan').val(data.harga_satuan);
                        
                        // PERBAIKAN: Format tanggal untuk input date
                        $('#tanggal_mulai').val(formatDateForInput(data.tanggal_mulai));
                        $('#tanggal_selesai').val(formatDateForInput(data.tanggal_selesai));
                        
                        $('#status').val(data.status);
                        $('#keterangan').val(data.keterangan);
                        
                        $('#modalTitle').text('Edit Pekerjaan Konstruksi');
                        $('#modalPekerjaan').modal('show');
                        
                        // Set min date for tanggal_selesai berdasarkan tanggal_mulai
                        if ($('#tanggal_mulai').val()) {
                            $('#tanggal_selesai').attr('min', $('#tanggal_mulai').val());
                        }
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    console.error('Error fetching:', xhr);
                    Swal.fire('Error', 'Gagal memuat data: ' + xhr.responseJSON?.message, 'error');
                });
            });

            // Delete Pekerjaan
            $(document).on('click', '.deletePekerjaan', function() {
                let id = $(this).data('id');
                
                console.log('Delete clicked, ID:', id); // Debug
                
                if (!id) {
                    Swal.fire('Error', 'ID pekerjaan tidak ditemukan', 'error');
                    return;
                }
                
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Pekerjaan konstruksi ini akan dihapus!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Gunakan route dengan parameter
                        let url = "{{ route('pekerjaan-konstruksi.destroy', ':id') }}".replace(':id', id);
                        
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    tbPekerjaan.ajax.reload();
                                    Swal.fire('Berhasil!', res.message, 'success');
                                } else {
                                    Swal.fire('Error!', res.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', 'Terjadi kesalahan: ' + xhr.responseJSON?.message, 'error');
                            }
                        });
                    }
                });
            });

            // Submit Form
            $('#formPekerjaan').submit(function(e) {
                e.preventDefault();
                
                let formData = $(this).serialize();
                let id = $('#idPekerjaan').val();
                
                // Tentukan URL dan method
                let url, method;
                
                if (id) {
                    // Edit - gunakan route dengan parameter
                    url = "{{ route('pekerjaan-konstruksi.update', ':id') }}".replace(':id', id);
                    method = 'PUT';
                } else {
                    // Tambah
                    url = "{{ route('pekerjaan-konstruksi.store') }}";
                    method = 'POST';
                }
                
                console.log('Submitting to:', url, 'Method:', method); // Debug
                
                // Show loading
                $('#btnSubmit').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();
                
                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    success: function(res) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalPekerjaan').modal('hide');
                            tbPekerjaan.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            });

            // Set min date for tanggal_selesai
            $('#tanggal_mulai').change(function() {
                $('#tanggal_selesai').attr('min', $(this).val());
            });
        });

        // View Pekerjaan
        $(document).on('click', '.viewPekerjaan', function() {
            let id = $(this).data('id');
            
            console.log('View clicked, ID:', id);
            
            if (!id) {
                Swal.fire('Error', 'ID pekerjaan tidak ditemukan', 'error');
                return;
            }
            
            // Method 1: Gunakan route dengan parameter
            let url = "{{ route('pekerjaan-konstruksi.show', ':id') }}".replace(':id', id);
            
            $.get(url, function(res) {
                if (res.success) {
                    let data = res.data;
                    
                    // Format tanggal
                    function formatDate(dateString) {
                        if (!dateString) return '-';
                        let date = new Date(dateString);
                        return date.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                    }
                    
                    // Format angka
                    function formatNumber(num) {
                        return num ? 'Rp ' + num.toLocaleString('id-ID') : 'Rp 0';
                    }
                    
                    // Format status
                    let statusMap = {
                        'planning': '<span class="badge bg-secondary">Planning</span>',
                        'ongoing': '<span class="badge bg-warning">Sedang Berjalan</span>',
                        'completed': '<span class="badge bg-success">Selesai</span>',
                        'canceled': '<span class="badge bg-danger">Dibatalkan</span>'
                    };
                    
                    // Isi data ke modal
                    $('#viewProject').text(data.project?.namaproject || '-');
                    $('#viewNamaPekerjaan').text(data.nama_pekerjaan || '-');
                    $('#viewKodeTransaksi').text(
                        data.kode_transaksi?.kodetransaksi
                        ? `${data.kode_transaksi.kodetransaksi} - ${data.kode_transaksi.transaksi}`
                        : '-'
                    );
                    $('#viewLokasi').text(data.lokasi || '-');
                    
                    // Volume dengan satuan
                    let volumeText = '-';
                    if (data.volume && data.satuan) {
                        volumeText = data.volume + ' ' + data.satuan;
                    } else if (data.volume) {
                        volumeText = data.volume;
                    }
                    $('#viewVolume').text(volumeText);
                    
                    $('#viewAnggaran').html(formatNumber(data.anggaran || 0));
                    $('#viewHargaSatuan').html(formatNumber(data.harga_satuan || 0));
                    
                    // Durasi
                    let durasiText = '-';
                    if (data.tanggal_mulai && data.tanggal_selesai) {
                        durasiText = formatDate(data.tanggal_mulai) + ' - ' + formatDate(data.tanggal_selesai);
                    }
                    $('#viewDurasi').text(durasiText);
                    
                    $('#viewStatus').html(statusMap[data.status] || data.status);
                    
                    // Progress
                    let progressHtml = '-';
                    if (data.status === 'completed') {
                        progressHtml = '<div class="progress" style="height: 20px;">' +
                            '<div class="progress-bar bg-success" style="width: 100%">100%</div>' +
                            '</div>';
                    } else if (data.status === 'ongoing') {
                        // Hitung progress jika ada tanggal
                        if (data.tanggal_mulai && data.tanggal_selesai) {
                            let start = new Date(data.tanggal_mulai);
                            let end = new Date(data.tanggal_selesai);
                            let today = new Date();
                            
                            let totalDays = (end - start) / (1000 * 60 * 60 * 24);
                            let passedDays = (today - start) / (1000 * 60 * 60 * 24);
                            
                            let progress = Math.min(100, Math.max(0, (passedDays / totalDays) * 100));
                            
                            progressHtml = '<div class="progress" style="height: 20px;">' +
                                '<div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" ' +
                                'style="width: ' + progress.toFixed(0) + '%">' + progress.toFixed(0) + '%</div>' +
                                '</div>';
                        }
                    }
                    $('#viewProgress').html(progressHtml);
                    
                    $('#viewKeterangan').text(data.keterangan || '-');
                    
                    // Show modal
                    $('#modalViewPekerjaan').modal('show');
                    
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }).fail(function(xhr) {
                console.error('Error fetching:', xhr);
                Swal.fire('Error', 'Gagal memuat data: ' + xhr.responseJSON?.message, 'error');
            });
        });
        </script>
    </x-slot>
</x-app-layout>
