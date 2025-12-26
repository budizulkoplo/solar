<x-app-layout>
    <x-slot name="pagetitle">Update Progress Pekerjaan Konstruksi</x-slot>

    <div class="container-fluid py-2">
        <!-- Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Update Progress Pekerjaan Konstruksi</h4>
                        <small class="text-muted">Monitor dan update progress pekerjaan konstruksi</small>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('pekerjaan-konstruksi.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Project</label>
                        <select class="form-select form-select-sm" id="filterProject">
                            <option value="">Semua Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">
                                    {{ $project->namaproject }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Status</label>
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="planning">Planning</option>
                            <option value="ongoing">Sedang Berjalan</option>
                            <option value="completed">Selesai</option>
                            <option value="canceled">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Jenis Pekerjaan</label>
                        <select class="form-select form-select-sm" id="filterJenis">
                            <option value="">Semua Jenis</option>
                            <option value="irigasi">Irigasi</option>
                            <option value="renovasi">Renovasi</option>
                            <option value="jalan">Jalan</option>
                            <option value="bangunan">Bangunan</option>
                            <option value="jembatan">Jembatan</option>
                            <option value="drainase">Drainase</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">&nbsp;</label>
                        <div class="d-flex gap-1">
                            <button type="button" id="btnFilter" class="btn btn-sm btn-primary flex-fill">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <button type="button" id="btnReset" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-3 align-items-stretch">
            <!-- Total Pekerjaan -->
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-light shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Total Pekerjaan</h6>
                                <h4 class="mb-0" id="totalPekerjaan">0</h4>
                            </div>
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-hammer fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Planning -->
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-secondary bg-opacity-10 shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Planning</h6>
                                <h4 class="mb-0 text-secondary" id="planningCount">0</h4>
                                <small class="text-muted" id="planningPercent">0%</small>
                            </div>
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-calendar fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ongoing -->
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-warning bg-opacity-10 shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Sedang Berjalan</h6>
                                <h4 class="mb-0 text-warning" id="ongoingCount">0</h4>
                                <small class="text-muted" id="ongoingPercent">0%</small>
                            </div>
                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-gear fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed -->
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-success bg-opacity-10 shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Selesai</h6>
                                <h4 class="mb-0 text-success" id="completedCount">0</h4>
                                <small class="text-muted" id="completedPercent">0%</small>
                            </div>
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-check-circle fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="progressTable" style="width:100%">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Nama Pekerjaan</th>
                                <th>Project</th>
                                <th>Jenis Pekerjaan</th>
                                <th>Durasi</th>
                                <th>Anggaran</th>
                                <th>Progress</th>
                                <th>Sisa Waktu</th>
                                <th>Status</th>
                                <th width="120">Action</th>
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

    <!-- Modal Update Progress -->
    <div class="modal fade" id="modalUpdateProgress" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="frmUpdateProgress">
                    @csrf
                    <input type="hidden" name="id" id="progressId">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Update Progress Pekerjaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Info Pekerjaan -->
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6 id="pekerjaanTitle"></h6>
                                    <div class="row small">
                                        <div class="col-md-6">
                                            <strong>Project:</strong> <span id="pekerjaanProject"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Jenis:</strong> <span id="pekerjaanJenis"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Durasi:</strong> <span id="pekerjaanDurasi"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Anggaran:</strong> <span id="pekerjaanAnggaran"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Progress Saat Ini -->
                            <div class="col-md-6">
                                <label class="form-label">Progress Saat Ini</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="currentProgress" readonly>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Progress berdasarkan tanggal</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Sisa Waktu</label>
                                <input type="text" class="form-control" id="timeRemaining" readonly>
                            </div>
                            
                            <!-- Form Update -->
                            <div class="col-12 border-top pt-3">
                                <h6>Update Progress</h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Progress (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="range" class="form-range" id="progressSlider" min="0" max="100" step="1">
                                    <input type="number" class="form-control" name="progress" id="progressInput" 
                                           min="0" max="100" step="1" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Drag slider atau ketik langsung</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" id="statusSelect" required>
                                    <option value="ongoing">Sedang Berjalan</option>
                                    <option value="completed">Selesai</option>
                                    <option value="canceled">Dibatalkan</option>
                                </select>
                            </div>
                            
                            <!-- <div class="col-md-6">
                                <label class="form-label">Realisasi Anggaran (Rp)</label>
                                <input type="number" class="form-control" name="realisasi_anggaran" 
                                       id="realisasiAnggaran" min="0">
                            </div> -->
                            
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Update <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_update" 
                                       value="{{ date('Y-m-d') }}" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Keterangan Progress</label>
                                <textarea class="form-control" name="keterangan_progress" 
                                          id="keteranganProgress" rows="3" 
                                          placeholder="Catatan perkembangan pekerjaan..."></textarea>
                            </div>
                            
                            <!-- History Logs -->
                            <div class="col-12 border-top pt-3">
                                <h6>Riwayat Update</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm" id="progressLogsTable">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Progress</th>
                                                <th>Status</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Logs akan diisi via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal View Detail -->
    <div class="modal fade" id="modalViewDetail" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pekerjaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content akan diisi via JS -->
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
                // Inisialisasi DataTable
                const table = $('#progressTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route("construction.progress.data") }}',
                        data: function(d) {
                            d.project_id = $('#filterProject').val();
                            d.status = $('#filterStatus').val();
                            d.jenis_pekerjaan = $('#filterJenis').val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'nama_pekerjaan', name: 'nama_pekerjaan' },
                        { data: 'project', name: 'project' },
                        { data: 'jenis_pekerjaan_formatted', name: 'jenis_pekerjaan' },
                        { data: 'durasi', name: 'durasi' },
                        { data: 'anggaran_formatted', name: 'anggaran' },
                        { data: 'progress_bar', name: 'progress' },
                        { data: 'time_remaining', name: 'time_remaining' },
                        { data: 'status_formatted', name: 'status' },
                        { data: 'action', name: 'action', orderable: false, searchable: false }
                    ],
                    order: [[1, 'asc']],
                    drawCallback: function(settings) {
                        updateStatistics();
                    }
                });
                
                // Filter button
                $('#btnFilter').click(function() {
                    table.ajax.reload();
                });
                
                // Reset filter
                $('#btnReset').click(function() {
                    $('#filterProject, #filterStatus, #filterJenis').val('');
                    table.ajax.reload();
                });
                
                // Update statistics
                function updateStatistics() {
                    $.ajax({
                        url: '{{ route("construction.progress.data") }}',
                        data: {
                            project_id: $('#filterProject').val(),
                            status: $('#filterStatus').val(),
                            jenis_pekerjaan: $('#filterJenis').val(),
                            draw: 1, // Untuk bypass DataTables
                            start: 0,
                            length: -1 // Get all data
                        },
                        success: function(response) {
                            const data = response.data;
                            const total = data.length;
                            const planning = data.filter(item => item.status === 'planning').length;
                            const ongoing = data.filter(item => item.status === 'ongoing').length;
                            const completed = data.filter(item => item.status === 'completed').length;
                            
                            // Hitung rata-rata progress hanya untuk ongoing dan completed
                            const activeItems = data.filter(item => 
                                item.status === 'ongoing' || item.status === 'completed'
                            );
                            const avgProgress = activeItems.length > 0 ? 
                                activeItems.reduce((sum, item) => sum + item.progress_value, 0) / activeItems.length : 0;
                            
                            $('#totalPekerjaan').text(total);
                            $('#planningCount').text(planning);
                            $('#ongoingCount').text(ongoing);
                            $('#completedCount').text(completed);
                            $('#planningPercent').text(total > 0 ? Math.round((planning/total)*100) + '%' : '0%');
                            $('#ongoingPercent').text(total > 0 ? Math.round((ongoing/total)*100) + '%' : '0%');
                            $('#completedPercent').text(total > 0 ? Math.round((completed/total)*100) + '%' : '0%');
                            $('#avgProgress').text(Math.round(avgProgress) + '%');
                        }
                    });
                }
                
                // View detail
                $(document).on('click', '.viewPekerjaan', function() {
                    const id = $(this).data('id');
                    
                    $.get('/construction/progress/' + id + '/detail', function(response) {
                        if (response.success) {
                            const data = response.data;
                            const html = `
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Informasi Pekerjaan</h6>
                                        <p><strong>Nama Pekerjaan:</strong> ${data.nama_pekerjaan}</p>
                                        <p><strong>Project:</strong> ${data.project.namaproject}</p>
                                        <p><strong>Jenis:</strong> ${data.jenis_pekerjaan}</p>
                                        <p><strong>Lokasi:</strong> ${data.lokasi || '-'}</p>
                                        <p><strong>Volume:</strong> ${data.volume || '-'} ${data.satuan || ''}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Status & Progress</h6>
                                        <p><strong>Status:</strong> <span class="badge ${getStatusClass(data.status)}">${data.status}</span></p>
                                        <p><strong>Progress:</strong> ${response.progress.toFixed(1)}%</p>
                                        <p><strong>Sisa Waktu:</strong> ${response.time_remaining || '-'}</p>
                                        <p><strong>Anggaran:</strong> Rp ${Number(data.anggaran).toLocaleString('id-ID')}</p>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <h6>Jadwal</h6>
                                        <p><strong>Mulai:</strong> ${formatDate(data.tanggal_mulai)}</p>
                                        <p><strong>Selesai:</strong> ${formatDate(data.tanggal_selesai)}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Detail Lain</h6>
                                        <p><strong>Jumlah:</strong> ${data.jumlah}</p>
                                        <p><strong>Keterangan:</strong> ${data.keterangan || '-'}</p>
                                    </div>
                                </div>
                            `;
                            
                            $('#detailContent').html(html);
                            $('#modalViewDetail').modal('show');
                        }
                    });
                });
                
                // TOMBOL MULAI PROGRESS - INI YANG BARU
                $(document).on('click', '.startProgress', function() {
                    const id = $(this).data('id');
                    
                    Swal.fire({
                        title: 'Mulai Pekerjaan?',
                        text: 'Apakah Anda yakin ingin memulai pekerjaan ini? Status akan berubah dari Planning ke Sedang Berjalan.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Mulai Sekarang!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'Memproses...',
                                text: 'Sedang memulai pekerjaan',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            $.ajax({
                                url: '/construction/progress/' + id + '/start',
                                type: 'PUT',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(response) {
                                    Swal.close();
                                    
                                    if (response.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Berhasil!',
                                            text: response.message,
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                        table.ajax.reload();
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error!',
                                            text: response.message
                                        });
                                    }
                                },
                                error: function(xhr) {
                                    Swal.close();
                                    let errorMessage = 'Terjadi kesalahan';
                                    
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    } else if (xhr.status === 422) {
                                        errorMessage = 'Validasi gagal. Pastikan semua data sudah benar.';
                                    }
                                    
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: errorMessage
                                    });
                                }
                            });
                        }
                    });
                });
                
                // Update progress
                $(document).on('click', '.updateProgress', function() {
                    const id = $(this).data('id');
                    
                    // Load data pekerjaan
                    $.get('/construction/progress/' + id + '/detail', function(response) {
                        if (response.success) {
                            const data = response.data;
                            
                            // Isi form
                            $('#progressId').val(id);
                            $('#pekerjaanTitle').text(data.nama_pekerjaan);
                            $('#pekerjaanProject').text(data.project.namaproject);
                            $('#pekerjaanJenis').text(data.jenis_pekerjaan);
                            $('#pekerjaanDurasi').text(formatDate(data.tanggal_mulai) + ' - ' + formatDate(data.tanggal_selesai));
                            $('#pekerjaanAnggaran').text('Rp ' + Number(data.anggaran).toLocaleString('id-ID'));
                            $('#currentProgress').val(response.progress.toFixed(1));
                            $('#timeRemaining').val(response.time_remaining || '-');
                            
                            // Set slider dan input
                            $('#progressSlider').val(response.progress);
                            $('#progressInput').val(response.progress.toFixed(1));
                            
                            // Load logs
                            loadProgressLogs(id);
                            
                            // Tampilkan modal
                            $('#modalUpdateProgress').modal('show');
                        }
                    });
                });
                
                // Sync slider dan input
                $('#progressSlider').on('input', function() {
                    $('#progressInput').val($(this).val());
                    updateStatusByProgress($(this).val());
                });
                
                $('#progressInput').on('input', function() {
                    const value = $(this).val();
                    $('#progressSlider').val(value);
                    updateStatusByProgress(value);
                });
                
                // Update status berdasarkan progress
                function updateStatusByProgress(progress) {
                    if (progress >= 100) {
                        $('#statusSelect').val('completed');
                    } else if (progress <= 0) {
                        $('#statusSelect').val('planning');
                    } else {
                        $('#statusSelect').val('ongoing');
                    }
                }
                
                // Load progress logs
                function loadProgressLogs(id) {
                    $.get('/construction/progress/' + id + '/logs', function(response) {
                        if (response.success) {
                            const tbody = $('#progressLogsTable tbody');
                            tbody.empty();
                            
                            if (response.data.length === 0) {
                                tbody.append('<tr><td colspan="4" class="text-center text-muted">Belum ada riwayat update</td></tr>');
                            } else {
                                response.data.forEach(log => {
                                    tbody.append(`
                                        <tr>
                                            <td>${formatDate(log.tanggal_update)}</td>
                                            <td>${log.progress}%</td>
                                            <td><span class="badge ${getStatusClass(log.status)}">${log.status}</span></td>
                                            <td>${log.keterangan || '-'}</td>
                                        </tr>
                                    `);
                                });
                            }
                        }
                    });
                }
                
                // Submit form update progress
                $('#frmUpdateProgress').submit(function(e) {
                    e.preventDefault();
                    
                    const id = $('#progressId').val();
                    const formData = $(this).serialize();
                    
                    // Tampilkan loading
                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...');
                    
                    $.ajax({
                        url: '/construction/progress/' + id + '/update',
                        type: 'PUT',
                        data: formData,
                        success: function(response) {
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sukses!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                
                                $('#modalUpdateProgress').modal('hide');
                                table.ajax.reload();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr) {
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            let errorMessage = 'Terjadi kesalahan saat menyimpan data';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: errorMessage
                            });
                        }
                    });
                });
                
                // Helper functions
                function formatDate(dateString) {
                    if (!dateString) return '-';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID');
                }
                
                function getStatusClass(status) {
                    const classes = {
                        'planning': 'bg-secondary',
                        'ongoing': 'bg-warning',
                        'completed': 'bg-success',
                        'canceled': 'bg-danger'
                    };
                    return classes[status] || 'bg-secondary';
                }
                
                // Initial load
                table.ajax.reload();
            });
        </script>
        
        <style>
            .progress-bar-animated {
                animation: progress-bar-stripes 1s linear infinite;
            }
            
            @keyframes progress-bar-stripes {
                0% { background-position: 1rem 0; }
                100% { background-position: 0 0; }
            }
            
            .bg-warning {
                background-color: #ffc107 !important;
            }
            
            .bg-warning.progress-bar-striped {
                background-image: linear-gradient(45deg, rgba(255,255,255,0.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.15) 75%, transparent 75%, transparent);
                background-size: 1rem 1rem;
            }
        </style>
    </x-slot>
</x-app-layout>