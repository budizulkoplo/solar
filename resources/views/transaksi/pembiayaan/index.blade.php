{{-- resources/views/transaksi/pembiayaan/index.blade.php --}}
<x-app-layout>
    <x-slot name="pagetitle">Pembiayaan - {{ ucfirst($type) }} - {{ session('active_company_name') }}</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="mb-0">
                        Pembiayaan {{ $type === 'company' ? 'Company' : 'Project' }}
                    </h3>
                </div>
                <div class="col-md-6">
                    <div class="float-end">
                        <div class="btn-group">
    <a href="{{ route('transaksi.pembiayaan.type', 'company') }}"
       class="btn btn-sm btn-outline-primary {{ $type === 'company' ? 'active' : '' }}">
        <i class="bi bi-building"></i> Company
    </a>

    <a href="{{ route('transaksi.pembiayaan.type', 'project') }}"
       class="btn btn-sm btn-outline-success {{ $type === 'project' ? 'active' : '' }}">
        <i class="bi bi-diagram-3"></i> Project
    </a>
</div>

                        <button class="btn btn-sm btn-primary ms-2" id="btnTambahPembiayaan">
                            <i class="bi bi-plus-circle"></i> Tambah Pembiayaan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Pembiayaan akan menambah saldo rekening yang dipilih
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbPembiayaan" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Kode</th>
                                <th>Judul</th>
                                <th>{{ $type === 'company' ? 'Company' : 'Project' }}</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-center">Payment</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">User</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pembiayaan -->
    <div class="modal fade" id="modalPembiayaan" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="frmPembiayaan" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="idPembiayaan">
                    <input type="hidden" name="jenis" id="jenisPembiayaan" value="{{ $type }}">
                    
                    <div class="modal-header">
                        <h6 class="modal-title" id="modalPembiayaanTitle">
                            Form Pembiayaan {{ $type === 'company' ? 'Company' : 'Project' }}
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            {{-- Judul --}}
                            <div class="col-md-12">
                                <label class="form-label">Judul Pembiayaan *</label>
                                <input type="text" class="form-control form-control-sm" 
                                       name="judul" id="judul" required 
                                       placeholder="Contoh: Penyertaan Modal, Pinjaman Bank, dll">
                            </div>

                            {{-- Untuk Project: Pilih Project --}}
                            <div class="col-md-12" id="projectField" style="{{ $type === 'company' ? 'display:none;' : '' }}">
                                <label class="form-label">Project *</label>
                                <select class="form-select form-select-sm select2" name="idproject" id="idproject" style="width:100%;">
                                    <option value="">-- Pilih Project --</option>
                                </select>
                            </div>

                            {{-- Rekening --}}
                            <div class="col-md-8">
                                <label class="form-label">Rekening Tujuan *</label>
                                <select class="form-select form-select-sm select2" name="rekening_id" id="rekeningId" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach(\App\Models\Rekening::where('idcompany', session('active_company_id'))->get() as $rek)
                                        <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                            {{ $rek->norek }} - {{ $rek->namarek }}
                                            @if($rek->idproject)
                                                (Project: {{ $rek->project ? $rek->project->nama_project : 'N/A' }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Metode Pembayaran --}}
                            <div class="col-md-4">
                                <label class="form-label">Metode Pembayaran *</label>
                                <select class="form-select form-select-sm" name="metode_pembayaran" id="metodePembayaran" required>
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Transfer</option>
                                </select>
                            </div>

                            {{-- Tanggal --}}
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" 
                                       name="tanggal" id="tanggalPembiayaan" required>
                            </div>

                            {{-- Nominal --}}
                            <div class="col-md-8">
                                <label class="form-label">Nominal *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control form-control-sm text-end" 
                                           name="nominal" id="nominal" min="1" required 
                                           placeholder="Jumlah pembiayaan">
                                </div>
                            </div>

                            {{-- Deskripsi --}}
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control form-control-sm" name="deskripsi" id="deskripsi" 
                                          rows="3" placeholder="Deskripsi pembiayaan..."></textarea>
                            </div>

                            {{-- Dokumen Pendukung --}}
                            <div class="col-12">
                                <label class="form-label">Dokumen Pendukung</label>
                                <input type="file" class="form-control form-control-sm" 
                                       name="dokumen[]" id="dokumen" multiple 
                                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                                <small class="text-muted">Format: JPG, PNG, PDF, DOC, XLS (Max: 5MB per file)</small>
                            </div>

                            {{-- List dokumen yang diupload --}}
                            <div class="col-12" id="dokumenList" style="display:none;">
                                <div class="card mt-2">
                                    <div class="card-header py-1">
                                        <small class="fw-bold">Dokumen Terupload</small>
                                    </div>
                                    <div class="card-body p-2" id="uploadedFiles">
                                        <!-- List file akan ditampilkan di sini -->
                                    </div>
                                </div>
                            </div>

                            {{-- List dokumen existing untuk edit --}}
                            <div class="col-12" id="existingDokumen" style="display:none;">
                                <div class="card mt-2">
                                    <div class="card-header py-1">
                                        <small class="fw-bold">Dokumen Tersimpan</small>
                                    </div>
                                    <div class="card-body p-2" id="existingFiles">
                                        <!-- List file existing akan ditampilkan di sini -->
                                    </div>
                                </div>
                            </div>

                            {{-- Informasi Saldo --}}
                            <div class="col-12 mt-2">
                                <div class="alert alert-info p-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="d-block">
                                                <i class="bi bi-wallet2"></i> 
                                                <strong>Saldo Saat Ini:</strong> 
                                                <span id="saldoDisplay">Rp 0</span>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="d-block">
                                                <i class="bi bi-calculator"></i> 
                                                <strong>Saldo Setelah Pembiayaan:</strong> 
                                                <span id="saldoAfterDisplay">Rp 0</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span class="submit-text">Simpan</span>
                            <span class="loading-text" style="display:none;">
                                <i class="bi bi-hourglass-split"></i> Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal View Pembiayaan -->
    <div class="modal fade" id="modalViewPembiayaan" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Detail Pembiayaan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Kode</th>
                                    <td id="viewKode">-</td>
                                </tr>
                                <tr>
                                    <th>Judul</th>
                                    <td id="viewJudul">-</td>
                                </tr>
                                <tr>
                                    <th>Jenis</th>
                                    <td id="viewJenis">-</td>
                                </tr>
                                <tr id="viewProjectRow" style="display:none;">
                                    <th>Project</th>
                                    <td id="viewProject">-</td>
                                </tr>
                                <tr>
                                    <th>Tanggal</th>
                                    <td id="viewTanggal">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Rekening</th>
                                    <td id="viewRekening">-</td>
                                </tr>
                                <tr>
                                    <th>Nominal</th>
                                    <td id="viewNominal">-</td>
                                </tr>
                                <tr>
                                    <th>Payment</th>
                                    <td id="viewPayment">-</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td id="viewStatus">-</td>
                                </tr>
                                <tr>
                                    <th>User</th>
                                    <td id="viewUser">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Deskripsi:</strong>
                        <div id="viewDeskripsi" class="border p-2 rounded bg-light">
                            -
                        </div>
                    </div>

                    {{-- Dokumen --}}
                    <div id="viewDokumen" style="display:none;">
                        <strong>Dokumen Pendukung:</strong>
                        <div class="border rounded p-2 mt-1">
                            <div id="viewDokumenList" class="row">
                                <!-- Dokumen akan ditampilkan di sini -->
                            </div>
                        </div>
                    </div>

                    <h6 class="mt-3">Riwayat</h6>
                    <div id="viewLogContainer" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                        <p class="text-muted small mb-0">Tidak ada riwayat</p>
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
            const currentType = "{{ $type }}";
            
            // DataTable untuk Pembiayaan
            let tbPembiayaan = $('#tbPembiayaan').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.pembiayaan.getdata', $type) }}",
                columns: [
                    { 
                        data: 'DT_RowIndex', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    },
                    { data: 'kode_pembiayaan', name: 'kode_pembiayaan' },
                    { data: 'judul', name: 'judul' },
                    { data: 'target', name: 'target' },
                    { 
                        data: 'tanggal', 
                        name: 'tanggal',
                        className: 'text-center'
                    },
                    { 
                        data: 'nominal', 
                        name: 'nominal',
                        className: 'text-end',
                        render: function(data) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(data);
                        }
                    },
                    { 
                        data: 'metode_pembayaran', 
                        name: 'metode_pembayaran',
                        className: 'text-center'
                    },
                    { 
                        data: 'status', 
                        name: 'status',
                        className: 'text-center'
                    },
                    { 
                        data: 'user', 
                        name: 'creator.name',
                        className: 'text-center'
                    },
                    { 
                        data: 'action', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center'
                    }
                ]
            });

            // Set tanggal default ke hari ini
            function setDefaultDate() {
                let today = new Date().toISOString().split('T')[0];
                $('#tanggalPembiayaan').val(today);
            }

            // Format angka ke Rupiah
            function formatRupiah(angka) {
                if (!angka || isNaN(angka)) return 'Rp 0';
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
            }

            // Parse nilai dari format Rupiah
            function parseNumber(value) {
                if (!value) return 0;
                value = value.toString().replace(/[^\d.-]/g, '');
                let num = parseFloat(value);
                return isNaN(num) ? 0 : num;
            }

            // Reset form ke kondisi default
            function resetForm() {
                $('#frmPembiayaan')[0].reset();
                $('#idPembiayaan').val('');
                $('#jenisPembiayaan').val(currentType);
                $('.select2').val(null).trigger('change');
                $('#modalPembiayaanTitle').text(`Form Pembiayaan ${currentType === 'company' ? 'Company' : 'Project'}`);
                $('#saldoDisplay').text('Rp 0');
                $('#saldoAfterDisplay').text('Rp 0');
                $('#dokumenList').hide();
                $('#existingDokumen').hide();
                $('#uploadedFiles').empty();
                $('#existingFiles').empty();
                
                // Tampilkan field project jika jenis project
                if (currentType === 'project') {
                    $('#projectField').show();
                    loadProjects();
                } else {
                    $('#projectField').hide();
                    $('#idproject').val('');
                }
                
                setDefaultDate();
                initializeSelect2();
            }

            // Initialize select2
            function initializeSelect2() {
                $('.select2').select2({ 
                    dropdownParent: $('#modalPembiayaan'),
                    width: '100%'
                });
            }

            // Load projects untuk pembiayaan project
            function loadProjects() {
                $.get("{{ route('transaksi.pembiayaan.projects') }}", function(res) {
                    if (res.success) {
                        let options = '<option value="">-- Pilih Project --</option>';
                        res.data.forEach(function(project) {
                            options += `<option value="${project.id}">${project.nama_project}</option>`;
                        });
                        $('#idproject').html(options);
                    }
                });
            }

            // Ambil saldo rekening
            let currentSaldo = 0;

            // Update saldo rekening
            $('#rekeningId').change(function() {
                let id = $(this).val();
                if (id) {
                    let url = "{{ route('transaksi.pembiayaan.rekening.saldo', ['id' => ':id']) }}";
                    url = url.replace(':id', id);
                    
                    $.get(url, function(res) {
                        currentSaldo = res.saldo || 0;
                        $('#saldoDisplay').text(formatRupiah(currentSaldo));
                        updateSaldoAfter();
                    });
                } else {
                    currentSaldo = 0;
                    $('#saldoDisplay').text('Rp 0');
                    $('#saldoAfterDisplay').text('Rp 0');
                }
            });

            // Update saldo setelah pembiayaan
            function updateSaldoAfter() {
                let nominal = parseNumber($('#nominal').val());
                let saldoAfter = currentSaldo + nominal;
                $('#saldoAfterDisplay').text(formatRupiah(saldoAfter));
            }

            // Event listener untuk input nominal
            $(document).on('input', '#nominal', function() {
                updateSaldoAfter();
            });

            // Handle upload dokumen
            $('#dokumen').change(function() {
                let files = this.files;
                if (files.length > 0) {
                    let fileList = '';
                    for (let i = 0; i < files.length; i++) {
                        let file = files[i];
                        let fileSize = (file.size / 1024 / 1024).toFixed(2);
                        fileList += `
                            <div class="mb-1 d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-file-earmark"></i> 
                                    <small>${file.name} (${fileSize} MB)</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger remove-file" data-index="${i}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                    $('#uploadedFiles').html(fileList);
                    $('#dokumenList').show();
                } else {
                    $('#dokumenList').hide();
                }
            });

            // Remove file dari list
            $(document).on('click', '.remove-file', function() {
                let index = $(this).data('index');
                let dt = new DataTransfer();
                let files = $('#dokumen')[0].files;
                
                for (let i = 0; i < files.length; i++) {
                    if (i !== index) {
                        dt.items.add(files[i]);
                    }
                }
                
                $('#dokumen')[0].files = dt.files;
                $('#dokumen').trigger('change');
            });

            // Remove existing file
            $(document).on('click', '.remove-existing-file', function() {
                let fileId = $(this).data('id');
                let deletedFiles = $('#deletedFiles').val() || '';
                if (deletedFiles) {
                    deletedFiles += ',' + fileId;
                } else {
                    deletedFiles = fileId;
                }
                $('#deletedFiles').val(deletedFiles);
                $(this).closest('.file-item').remove();
                
                if ($('#existingFiles').children().length === 0) {
                    $('#existingDokumen').hide();
                }
            });

            // Tombol tambah pembiayaan
            $('#btnTambahPembiayaan').click(function() {
                resetForm();
                $('#modalPembiayaan').modal('show');
            });

            // View pembiayaan
            $(document).on('click', '.view-btn', function() {
                let pembiayaanId = $(this).data('id');
                
                $.get("{{ route('transaksi.pembiayaan.show', ['id' => ':id']) }}".replace(':id', pembiayaanId), function(res) {
                    if (res.success) {
                        let data = res.data;
                        
                        // Isi data
                        $('#viewKode').text(data.kode_pembiayaan);
                        $('#viewJudul').text(data.judul);
                        $('#viewJenis').text(data.jenis === 'company' ? 'Company' : 'Project');
                        $('#viewTanggal').text(new Date(data.tanggal).toLocaleDateString('id-ID'));
                        $('#viewRekening').text(data.rekening ? 
                            data.rekening.norek + ' - ' + data.rekening.namarek : '-');
                        $('#viewNominal').text(formatRupiah(data.nominal));
                        $('#viewPayment').text(data.metode_pembayaran === 'cash' ? 'Cash' : 'Transfer');
                        $('#viewStatus').html(getStatusBadge(data.status));
                        $('#viewUser').text(data.creator ? data.creator.name : '-');
                        $('#viewDeskripsi').text(data.deskripsi || '-');
                        
                        // Tampilkan project jika ada
                        if (data.jenis === 'project' && data.project) {
                            $('#viewProjectRow').show();
                            $('#viewProject').text(data.project.nama_project);
                        } else {
                            $('#viewProjectRow').hide();
                        }
                        
                        // Tampilkan dokumen
                        if (data.dokumen && data.dokumen.length > 0) {
                            let dokumenHtml = '';
                            data.dokumen.forEach(function(dokumen) {
                                let fileUrl = '/storage/' + dokumen.path_file;
                                let fileExt = dokumen.nama_file.split('.').pop().toLowerCase();
                                let icon = 'bi-file-earmark';
                                let btnClass = 'btn-primary';
                                
                                if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                                    icon = 'bi-image';
                                    btnClass = 'btn-success';
                                } else if (fileExt === 'pdf') {
                                    icon = 'bi-file-pdf';
                                    btnClass = 'btn-danger';
                                } else if (['doc', 'docx'].includes(fileExt)) {
                                    icon = 'bi-file-word';
                                    btnClass = 'btn-primary';
                                } else if (['xls', 'xlsx'].includes(fileExt)) {
                                    icon = 'bi-file-excel';
                                    btnClass = 'btn-success';
                                }
                                
                                dokumenHtml += `
                                    <div class="col-md-6 mb-2">
                                        <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi ${icon}"></i>
                                                <small>${dokumen.nama_file}</small>
                                            </div>
                                            <a href="${fileUrl}" target="_blank" class="btn btn-sm ${btnClass}">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                `;
                            });
                            $('#viewDokumenList').html(dokumenHtml);
                            $('#viewDokumen').show();
                        } else {
                            $('#viewDokumen').hide();
                        }
                        
                        // Load logs
                        if (data.logs && data.logs.length > 0) {
                            let logHtml = '';
                            data.logs.forEach(function(log) {
                                let waktu = new Date(log.created_at).toLocaleString('id-ID');
                                logHtml += `
                                    <div class="mb-2 pb-2 border-bottom">
                                        <small class="text-muted d-block mb-1">
                                            <i class="bi bi-clock"></i> ${waktu} - ${log.user ? log.user.name : 'System'}
                                        </small>
                                        <p class="mb-0 small">${log.description}</p>
                                    </div>
                                `;
                            });
                            $('#viewLogContainer').html(logHtml);
                        } else {
                            $('#viewLogContainer').html('<p class="text-muted small mb-0">Tidak ada riwayat</p>');
                        }
                        
                        $('#modalViewPembiayaan').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            });

            // Edit pembiayaan
            $(document).on('click', '.edit-btn', function() {
                let pembiayaanId = $(this).data('id');
                
                $.get("{{ route('transaksi.pembiayaan.edit', ['id' => ':id']) }}".replace(':id', pembiayaanId), function(res) {
                    if (res.success) {
                        let data = res.data;
                        
                        // Isi form dengan data existing
                        resetForm();
                        $('#idPembiayaan').val(data.id);
                        $('#judul').val(data.judul);
                        $('#jenisPembiayaan').val(data.jenis);
                        $('#tanggalPembiayaan').val(data.tanggal);
                        $('#rekeningId').val(data.rekening_id).trigger('change');
                        $('#nominal').val(data.nominal);
                        $('#deskripsi').val(data.deskripsi);
                        $('#metodePembayaran').val(data.metode_pembayaran);
                        
                        // Tampilkan project jika jenis project
                        if (data.jenis === 'project') {
                            $('#projectField').show();
                            loadProjects();
                            setTimeout(() => {
                                $('#idproject').val(data.idproject).trigger('change');
                            }, 500);
                        }
                        
                        // Tampilkan dokumen existing
                        if (data.dokumen && data.dokumen.length > 0) {
                            let dokumenHtml = '';
                            data.dokumen.forEach(function(dokumen) {
                                let fileUrl = '/storage/' + dokumen.path_file;
                                dokumenHtml += `
                                    <div class="file-item mb-1 d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-file-earmark"></i> 
                                            <small>${dokumen.nama_file}</small>
                                        </div>
                                        <div>
                                            <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger remove-existing-file" data-id="${dokumen.id}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                `;
                            });
                            $('#existingFiles').html(dokumenHtml);
                            $('#existingDokumen').show();
                            // Tambah hidden input untuk deleted files
                            if (!$('#deletedFiles').length) {
                                $('#frmPembiayaan').append('<input type="hidden" name="deleted_files" id="deletedFiles" value="">');
                            }
                        }
                        
                        // Update modal title
                        $('#modalPembiayaanTitle').text(`Edit Pembiayaan ${data.jenis === 'company' ? 'Company' : 'Project'}`);
                        
                        // Tampilkan modal
                        $('#modalPembiayaan').modal('show');
                        
                        // Initialize select2 setelah modal ditampilkan
                        setTimeout(() => {
                            initializeSelect2();
                        }, 300);
                        
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            });

            // Approve pembiayaan
            $(document).on('click', '.approve-btn', function() {
                let pembiayaanId = $(this).data('id');
                
                Swal.fire({
                    title: 'Approve Pembiayaan?',
                    text: "Pembiayaan akan di-approve dan siap untuk diproses.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Approve!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('transaksi.pembiayaan.approve', ['id' => ':id']) }}".replace(':id', pembiayaanId),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    tbPembiayaan.ajax.reload();
                                    Swal.fire('Berhasil!', res.message, 'success');
                                } else {
                                    Swal.fire('Error!', res.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Reject pembiayaan
            $(document).on('click', '.reject-btn', function() {
                let pembiayaanId = $(this).data('id');
                
                Swal.fire({
                    title: 'Reject Pembiayaan?',
                    text: "Pembiayaan akan di-reject dan tidak dapat diproses.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Reject!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('transaksi.pembiayaan.reject', ['id' => ':id']) }}".replace(':id', pembiayaanId),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    tbPembiayaan.ajax.reload();
                                    Swal.fire('Berhasil!', res.message, 'success');
                                } else {
                                    Swal.fire('Error!', res.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Complete pembiayaan (proses penambahan saldo)
            $(document).on('click', '.complete-btn', function() {
                let pembiayaanId = $(this).data('id');
                
                Swal.fire({
                    title: 'Proses Pembiayaan?',
                    html: `
                        <p>Pembiayaan akan diproses dan saldo rekening akan ditambahkan.</p>
                        <p class="text-danger"><strong>Pastikan dana sudah masuk ke rekening!</strong></p>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Proses!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('transaksi.pembiayaan.complete', ['id' => ':id']) }}".replace(':id', pembiayaanId),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    tbPembiayaan.ajax.reload();
                                    Swal.fire('Berhasil!', res.message, 'success');
                                } else {
                                    Swal.fire('Error!', res.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Delete pembiayaan
            $(document).on('click', '.delete-btn', function() {
                let pembiayaanId = $(this).data('id');
                
                Swal.fire({
                    title: 'Hapus Pembiayaan?',
                    text: "Pembiayaan akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('transaksi.pembiayaan.destroy', ['id' => ':id']) }}".replace(':id', pembiayaanId),
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    tbPembiayaan.ajax.reload();
                                    Swal.fire('Berhasil!', res.message, 'success');
                                } else {
                                    Swal.fire('Error!', res.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Submit form pembiayaan
            $('#frmPembiayaan').submit(function(e) {
                e.preventDefault();
                processFormSubmission(this);
            });

            function processFormSubmission(formElement) {
                // Validasi minimal
                if (!parseNumber($('#nominal').val())) {
                    Swal.fire('Peringatan', 'Nominal harus lebih dari 0', 'warning');
                    return;
                }

                // Validasi untuk project
                if (currentType === 'project' && !$('#idproject').val()) {
                    Swal.fire('Peringatan', 'Project harus dipilih untuk pembiayaan project', 'warning');
                    return;
                }

                let pembiayaanId = $('#idPembiayaan').val();
                let url = pembiayaanId ? 
                    "{{ route('transaksi.pembiayaan.update', ['id' => ':id']) }}".replace(':id', pembiayaanId) : 
                    "{{ route('transaksi.pembiayaan.store') }}";
                
                let formData = new FormData(formElement);
                if (pembiayaanId) {
                    formData.append('_method', 'PUT');
                }

                // Tampilkan loading
                $('#btnSubmit').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalPembiayaan').modal('hide');
                            tbPembiayaan.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        let errors = xhr.responseJSON?.errors;
                        let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data';
                        
                        if (errors) {
                            errorMsg = '';
                            $.each(errors, function(key, value) {
                                errorMsg += value[0] + '\n';
                            });
                        }
                        
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            }

            // Helper function untuk status badge
            function getStatusBadge(status) {
                const badge = {
                    'draft': 'bg-secondary',
                    'approved': 'bg-primary',
                    'completed': 'bg-success',
                    'rejected': 'bg-danger'
                };
                return `<span class="badge ${badge[status]}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
            }

            // Initialize
            initializeSelect2();
            setDefaultDate();
        });
        </script>
    </x-slot>
</x-app-layout>