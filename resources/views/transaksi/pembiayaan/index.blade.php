<x-app-layout>
    <x-slot name="pagetitle">Pembiayaan - {{ ucfirst($type) }} - {{ session('active_company_name') }}</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">
                Pembiayaan {{ $type === 'company' ? 'Company' : 'Project' }}
                @if($type === 'project' && $projectName)
                    <small class="text-success">- {{ $projectName }}</small>
                @endif
            </h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            @if($type === 'project' && !$projectId)
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Tidak ada project yang dipilih. Silakan pilih project terlebih dahulu.
                </div>
            @else
                <div class="card card-info card-outline mb-4">
                    <div class="card-header pt-1 pb-1">
                        <div class="card-tools">
                            <button class="btn btn-sm btn-primary" id="btnTambahPembiayaan">
                                <i class="bi bi-plus-circle"></i> Tambah Pembiayaan
                            </button>
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
                                    <th class="text-end">Terbayar</th>
                                    <th class="text-end">Sisa</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">User</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            @endif
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
                    
                    @if($type === 'project')
                        <input type="hidden" name="project_info" id="projectInfo">
                    @endif
                    
                    <div class="modal-header">
                        <h6 class="modal-title" id="modalPembiayaanTitle">
                            Form Pembiayaan {{ $type === 'company' ? 'Company' : 'Project' }}
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            {{-- Info Project untuk type project --}}
                            @if($type === 'project')
                                <div class="col-md-12 mb-2">
                                    <div class="alert alert-success py-1">
                                        <i class="bi bi-diagram-3"></i> 
                                        <strong>Project:</strong> 
                                        <span id="projectNameDisplay">{{ $projectName ?? 'Belum dipilih' }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Judul --}}
                            <div class="col-md-8">
                                <label class="form-label">Judul Pembiayaan *</label>
                                <input type="text" class="form-control form-control-sm" 
                                       name="judul" id="judul" required 
                                       placeholder="Contoh: Penyertaan Modal, Pinjaman Bank, dll">
                            </div>

                            {{-- Tanggal --}}
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" 
                                       name="tanggal" id="tanggalPembiayaan" required>
                            </div>

                            {{-- Rekening --}}
                            <div class="col-md-12">
                                <label class="form-label">Rekening Tujuan *</label>
                                <select class="form-select form-select-sm select2-pembiayaan" name="rekening_id" id="rekeningId" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @php
                                        if($type === 'company') {
                                            $rekenings = \App\Models\Rekening::where('idcompany', session('active_company_id'))->get();
                                        } else {
                                            $rekenings = \App\Models\Rekening::where('idproject', $projectId)->get();
                                        }
                                    @endphp
                                    
                                    @foreach($rekenings as $rek)
                                        <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                            {{ $rek->norek }} - {{ $rek->namarek }}
                                            @if($rek->idproject && $type === 'company')
                                                (Project: {{ $rek->project ? $rek->project->namaproject : 'N/A' }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Nominal --}}
                            <div class="col-md-12">
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
                                    <div class="row mt-1">
                                        <div class="col-12">
                                            <small class="text-success">
                                                <i class="bi bi-info-circle"></i> 
                                                <strong>Note:</strong> Pembiayaan akan langsung menambah saldo rekening
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
                            <span class="submit-text">Simpan Pembiayaan</span>
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
        <div class="modal-dialog modal-xxl">
            <div class="modal-content">
                <div class="row g-0">
                    <!-- Kolom Kiri: Data Pembiayaan (90%) -->
                    <div class="col-md-9">
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

                            {{-- Summary Box --}}
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <div class="card border-success shadow-sm">
                                        <div class="card-body text-center py-1 px-2">
                                            <small class="text-success fw-semibold d-block">
                                                Total Pembiayaan
                                            </small>
                                            <h6 class="mb-0" id="viewTotalNominal">-</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card border-primary shadow-sm">
                                        <div class="card-body text-center py-1 px-2">
                                            <small class="text-primary fw-semibold d-block">
                                                Total Terbayar
                                            </small>
                                            <h6 class="mb-0" id="viewTotalTerbayar">-</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card border-warning shadow-sm">
                                        <div class="card-body text-center py-1 px-2">
                                            <small class="text-warning fw-semibold d-block">
                                                Sisa
                                            </small>
                                            <h6 class="mb-0" id="viewSisa">-</h6>
                                        </div>
                                    </div>
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

                            {{-- History Setoran --}}
                            <h6 class="mt-3">History Setoran</h6>
                            <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0" id="tblSetoran">
                                    <thead>
                                        <tr>
                                            <th>Kode Setoran</th>
                                            <th>Tanggal</th>
                                            <th class="text-end">Pokok</th>
                                            <th class="text-end">Administrasi</th>
                                            <th class="text-end">Margin</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data setoran akan ditampilkan di sini -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                    
                    <!-- Kolom Kanan: Log Update (10%) -->
                    <div class="col-md-3 border-start">
                        <div class="modal-header border-bottom bg-light">
                            <h6 class="modal-title m-0"><i class="bi bi-clock-history"></i> Riwayat Perubahan</h6>
                        </div>
                        <div class="modal-body p-2" style="height: calc(100vh - 150px); overflow-y: auto;">
                            <div id="viewLogContainer">
                                <p class="text-muted small">Tidak ada riwayat perubahan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Setoran -->
    <div class="modal fade" id="modalSetoran" tabindex="-1">
        <div class="modal-dialog modal-xxl">
            <div class="modal-content">
                <div class="row g-0">
                    <!-- Kolom Kiri: Form Setoran (80%) -->
                    <div class="col-md-9">
                        <form id="frmSetoran" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" id="pembiayaanId">
                            
                            <div class="modal-header">
                                <h6 class="modal-title">Setoran Pembiayaan</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="card border-info">
                                            <div class="card-body py-2">
                                                <small class="d-block">Total Pembiayaan</small>
                                                <h5 class="mb-0" id="setoranTotalNominal">-</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-warning">
                                            <div class="card-body py-2">
                                                <small class="d-block">Sisa</small>
                                                <h5 class="mb-0" id="setoranSisa">-</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    {{-- Tanggal --}}
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal *</label>
                                        <input type="date" class="form-control form-control-sm" 
                                               name="tanggal" id="tanggalSetoran" required>
                                    </div>

                                    {{-- Rekening Sumber --}}
                                    <div class="col-md-8">
                                        <label class="form-label">Rekening Sumber *</label>
                                        <select class="form-select form-select-sm select2-setoran" 
                                                name="rekening_id" 
                                                id="rekeningSumber" 
                                                style="width:100%;" required>
                                            <option value="">-- Pilih Rekening Sumber --</option>
                                            @php
                                                if($type === 'company') {
                                                    $rekenings = \App\Models\Rekening::where('idcompany', session('active_company_id'))->get();
                                                } else {
                                                    $rekenings = \App\Models\Rekening::where('idproject', $projectId)->get();
                                                }
                                            @endphp
                                            
                                            @foreach($rekenings as $rek)
                                                <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                                    {{ $rek->norek }} - {{ $rek->namarek }}
                                                    @if($rek->saldo > 0)
                                                        (Saldo: Rp {{ number_format($rek->saldo, 0, ',', '.') }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block" id="saldoRekeningInfo">Saldo: Rp 0</small>
                                    </div>

                                    {{-- Pokok --}}
                                    <div class="col-md-8">
                                        <label class="form-label">Pokok *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control form-control-sm text-end" 
                                                   name="pokok" id="pokok" min="1" required 
                                                   placeholder="Jumlah pokok">
                                        </div>
                                        <small class="text-muted" id="sisaInfo"></small>
                                    </div>

                                    {{-- Administrasi --}}
                                    <div class="col-md-4">
                                        <label class="form-label">Administrasi</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control form-control-sm text-end" 
                                                   name="administrasi" id="administrasiSetoran" min="0"
                                                   placeholder="0">
                                        </div>
                                    </div>

                                    {{-- Margin --}}
                                    <div class="col-md-4">
                                        <label class="form-label">Margin</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control form-control-sm text-end" 
                                                   name="margin" id="margin" min="0"
                                                   placeholder="0">
                                        </div>
                                    </div>

                                    {{-- Total --}}
                                    <div class="col-md-4">
                                        <label class="form-label">Total</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control form-control-sm text-end fw-bold" 
                                                   id="totalSetoran" value="0" readonly>
                                        </div>
                                    </div>

                                    {{-- Deskripsi --}}
                                    <div class="col-12">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea class="form-control form-control-sm" name="deskripsi" id="deskripsiSetoran" 
                                                  rows="2" placeholder="Keterangan setoran..."></textarea>
                                    </div>

                                    {{-- Bukti --}}
                                    <div class="col-12">
                                        <label class="form-label">Bukti Transfer</label>
                                        <input type="file" class="form-control form-control-sm" 
                                               name="bukti" id="buktiSetoran" 
                                               accept=".jpg,.jpeg,.png,.pdf">
                                        <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                                        <div id="buktiPreview" class="mt-2" style="display:none;">
                                            <img id="previewBukti" src="#" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                                        </div>
                                    </div>

                                    {{-- Informasi Saldo --}}
                                    <div class="col-12 mt-2">
                                        <div class="alert alert-warning p-2">
                                            <small>
                                                <i class="bi bi-exclamation-triangle"></i> 
                                                <strong>Perhatian:</strong> Setoran akan mengurangi saldo rekening sumber
                                            </small>
                                        </div>
                                        <div class="alert alert-info p-2">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <small class="d-block">
                                                        <i class="bi bi-wallet2"></i> 
                                                        <strong>Saldo Sumber:</strong> 
                                                        <span id="saldoSumberDisplay">Rp 0</span>
                                                    </small>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="d-block">
                                                        <i class="bi bi-calculator"></i> 
                                                        <strong>Saldo Setelah Setoran:</strong> 
                                                        <span id="saldoAfterSetoran">Rp 0</span>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary" id="btnSubmitSetoran">
                                    <span class="submit-text">Simpan Setoran</span>
                                    <span class="loading-text" style="display:none;">
                                        <i class="bi bi-hourglass-split"></i> Memproses...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Kolom Kanan: Riwayat Setoran (20%) -->
                    <div class="col-md-3 border-start">
                        <div class="modal-header border-bottom bg-light">
                            <h6 class="modal-title m-0"><i class="bi bi-clock-history"></i> Riwayat Setoran</h6>
                        </div>
                        <div class="modal-body p-2" style="height: calc(100vh - 150px); overflow-y: auto;">
                            <div id="setoranLogContainer">
                                <p class="text-muted small">Tidak ada riwayat setoran</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <style>
            .progress-container {
                height: 10px;
                background-color: #e9ecef;
                border-radius: 5px;
                margin: 10px 0;
            }
            
            .progress-bar {
                height: 100%;
                border-radius: 5px;
                transition: width 0.3s ease;
            }
            
            .progress-bar-lunas {
                background-color: #28a745;
            }
            
            .progress-bar-sisa {
                background-color: #ffc107;
            }
            
            .modal-xxl {
                max-width: 80% !important;
            }
            
            .border-start {
                border-left: 1px solid #dee2e6 !important;
            }
            
            /* Untuk select2 di modal setoran */
            .select2-setoran + .select2-container,
            .select2-pembiayaan + .select2-container {
                z-index: 1060 !important;
            }
            
            /* Untuk preview bukti */
            #previewBukti {
                max-width: 100%;
                height: auto;
            }
            
            /* Untuk info saldo */
            #saldoSumberDisplay.text-danger,
            #saldoAfterSetoran.text-danger {
                font-weight: bold;
            }
            
            /* Untuk tabel setoran di modal view */
            #tblSetoran tbody tr:hover {
                background-color: #f8f9fa;
            }
            
            #tblSetoran tbody tr.table-info {
                background-color: #cfe2ff !important;
            }
            
            /* Untuk riwayat setoran yang sederhana */
            .riwayat-item {
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            
            .riwayat-item:last-child {
                border-bottom: none;
            }
            
            .riwayat-tanggal {
                font-size: 0.8rem;
                color: #666;
            }
            
            .riwayat-nominal {
                font-size: 0.9rem;
                font-weight: bold;
            }
            
            .riwayat-keterangan {
                font-size: 0.85rem;
                color: #444;
                margin-top: 2px;
            }
        </style>

        <script>
        $(document).ready(function() {
            const currentType = "{{ $type }}";
            const projectId = "{{ $projectId }}";
            
            // ============ DATA TABLE ============ //
            let tbPembiayaan = $('#tbPembiayaan').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('transaksi.pembiayaan.getdata', $type) }}",
                    data: function(d) {
                        @if($type === 'project' && $projectId)
                            d.project_id = "{{ $projectId }}";
                        @endif
                    }
                },
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
                        name: 'nominal'
                        
                    },
                    { 
                        data: 'terbayar', 
                        name: 'terbayar'
                        
                    },
                    { 
                        data: 'sisa', 
                        name: 'sisa'
                        
                    },
                    { 
                        data: 'status', 
                        name: 'status',
                        className: 'text-center',
                        render: function(data, type, row) {
                            return getStatusBadge(data, row.sisa);
                        }
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
                ],
                order: [[4, 'desc']],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data",
                    zeroRecords: "Data tidak ditemukan",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "→",
                        previous: "←"
                    }
                }
            });

            // ============ UTILITY FUNCTIONS ============ //
            
            // Set tanggal default ke hari ini
            function setDefaultDate() {
                let today = new Date().toISOString().split('T')[0];
                $('#tanggalPembiayaan').val(today);
                $('#tanggalSetoran').val(today);
            }

            // Format angka ke Rupiah
            function formatRupiah(angka) {
                if (angka === null || angka === undefined || isNaN(angka)) return 'Rp 0';
                
                let number = parseFloat(angka);
                if (isNaN(number)) return 'Rp 0';
                
                return 'Rp ' + new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(number);
            }

            // Parse nilai dari format Rupiah
            function parseNumber(value) {
                if (!value) return 0;
                
                if (typeof value === 'number') return value;
                
                value = value.toString().replace('Rp', '').trim();
                value = value.replace(/[^\d,.-]/g, '');
                value = value.replace(',', '.');
                
                let parts = value.split('.');
                if (parts.length > 1) {
                    value = parts[0].replace(/\./g, '') + '.' + parts.slice(1).join('');
                } else {
                    value = value.replace(/\./g, '');
                }
                
                let num = parseFloat(value);
                return isNaN(num) ? 0 : num;
            }

            // Helper function untuk status badge
            function getStatusBadge(status, sisa = 0) {
                const badge = {
                    'draft': 'bg-secondary',
                    'completed': 'bg-primary',
                    'lunas': 'bg-success',
                    'rejected': 'bg-danger'
                };
                
                let statusText = ucfirst(status);
                
                if (status === 'completed' && sisa > 0) {
                    statusText = 'Aktif';
                } else if (status === 'completed' && sisa <= 0) {
                    statusText = 'Lunas';
                }
                
                return `<span>${statusText}</span>`;
            }

            // Helper untuk capitalize
            function ucfirst(str) {
                return str.charAt(0).toUpperCase() + str.slice(1);
            }

            // ============ MODAL PEMBIAYAAN FUNCTIONS ============ //
            
            // Initialize select2
            function initializeSelect2() {
                $('.select2-pembiayaan').select2({ 
                    dropdownParent: $('#modalPembiayaan'),
                    width: '100%'
                });
            }

            // Initialize select2 untuk modal setoran
            function initializeSelect2Setoran() {
                $('.select2-setoran').select2({ 
                    dropdownParent: $('#modalSetoran'),
                    width: '100%'
                });
            }

            // Reset form pembiayaan
            function resetForm() {
                $('#frmPembiayaan')[0].reset();
                $('#idPembiayaan').val('');
                $('#jenisPembiayaan').val(currentType);
                $('.select2-pembiayaan').val(null).trigger('change');
                $('#modalPembiayaanTitle').text(`Form Pembiayaan ${currentType === 'company' ? 'Company' : 'Project'}`);
                $('#saldoDisplay').text('Rp 0');
                $('#saldoAfterDisplay').text('Rp 0');
                $('#dokumenList').hide();
                $('#existingDokumen').hide();
                $('#uploadedFiles').empty();
                $('#existingFiles').empty();
                $('input[name="deleted_files"]').remove();
                
                if (currentType === 'project') {
                    loadProjectInfo();
                }
                
                setDefaultDate();
                initializeSelect2();
                updateSaldoAfter();
            }

            // Load project info untuk pembiayaan project
            function loadProjectInfo() {
                $.get("{{ route('transaksi.pembiayaan.project.session') }}", function(res) {
                    if (res.success) {
                        $('#projectNameDisplay').text(res.data.namaproject);
                        $('#projectInfo').val(JSON.stringify(res.data));
                    } else {
                        Swal.fire('Peringatan', res.message, 'warning');
                        $('#modalPembiayaan').modal('hide');
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
                let nominal = parseFloat($('#nominal').val()) || 0;
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
                @if($type === 'project' && !$projectId)
                    Swal.fire('Peringatan', 'Silakan pilih project terlebih dahulu', 'warning');
                    return;
                @endif
                
                resetForm();
                $('#modalPembiayaan').modal('show');
            });

            // ============ MODAL VIEW FUNCTIONS ============ //
            
            // View pembiayaan
            $(document).on('click', '.view-btn', function() {
                let pembiayaanId = $(this).data('id');
                
                $.get("{{ route('transaksi.pembiayaan.show', ['id' => ':id']) }}".replace(':id', pembiayaanId), function(res) {
                    if (res.success) {
                        let data = res.data;
                        let summary = res.summary;
                        
                        // Isi data
                        $('#viewKode').text(data.kode_pembiayaan);
                        $('#viewJudul').text(data.judul);
                        $('#viewJenis').text(data.jenis === 'company' ? 'Company' : 'Project');
                        $('#viewTanggal').text(new Date(data.tanggal).toLocaleDateString('id-ID'));
                        $('#viewRekening').text(data.rekening ? 
                            data.rekening.norek + ' - ' + data.rekening.namarek : '-');
                        $('#viewNominal').text(formatRupiah(data.nominal));
                        $('#viewTotalNominal').text(formatRupiah(data.nominal));
                        $('#viewTotalTerbayar').text(formatRupiah(summary.total_setoran));
                        $('#viewSisa').text(formatRupiah(summary.sisa));
                        $('#viewStatus').html(getStatusBadge(data.status, summary.sisa));
                        $('#viewUser').text(data.creator ? data.creator.name : '-');
                        $('#viewDeskripsi').text(data.deskripsi || '-');
                        
                        // Tampilkan project jika ada
                        if (data.jenis === 'project' && data.project) {
                            $('#viewProjectRow').show();
                            $('#viewProject').text(data.project.namaproject);
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
                        
                        // Load setoran untuk tabel view
                        loadSetoranForView(pembiayaanId);
                        
                        $('#modalViewPembiayaan').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            });

            // Load setoran untuk modal view (tabel)
            function loadSetoranForView(pembiayaanId) {
                $.get("{{ route('transaksi.pembiayaan.setoran.get', ['id' => ':id']) }}".replace(':id', pembiayaanId), function(res) {
                    if (res.success) {
                        let setoranHtml = '';
                        let setorans = res.setorans || [];
                        
                        if (setorans.length > 0) {
                            let totalPokok = 0;
                            let totalAdministrasi = 0;
                            let totalMargin = 0;
                            let totalAll = 0;
                            
                            setorans.forEach(function(setoran) {
                                totalPokok += parseFloat(setoran.pokok) || 0;
                                totalAdministrasi += parseFloat(setoran.administrasi) || 0;
                                totalMargin += parseFloat(setoran.margin) || 0;
                                totalAll += parseFloat(setoran.total) || 0;
                                
                                setoranHtml += `
                                    <tr>
                                        <td>${setoran.kode_setoran}</td>
                                        <td>${new Date(setoran.tanggal).toLocaleDateString('id-ID')}</td>
                                        <td class="text-end">${formatRupiah(setoran.pokok)}</td>
                                        <td class="text-end">${formatRupiah(setoran.administrasi)}</td>
                                        <td class="text-end">${formatRupiah(setoran.margin)}</td>
                                        <td class="text-end">${formatRupiah(setoran.total)}</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-danger delete-setoran-btn" 
                                                    data-id="${setoran.id}" 
                                                    data-pembiayaan-id="${pembiayaanId}"
                                                    title="Hapus Setoran">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                            
                            // Tambahkan total row
                            setoranHtml += `
                                <tr class="table-info">
                                    <td colspan="2" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>${formatRupiah(totalPokok)}</strong></td>
                                    <td class="text-end"><strong>${formatRupiah(totalAdministrasi)}</strong></td>
                                    <td class="text-end"><strong>${formatRupiah(totalMargin)}</strong></td>
                                    <td class="text-end"><strong>${formatRupiah(totalAll)}</strong></td>
                                    <td></td>
                                </tr>
                            `;
                        } else {
                            setoranHtml = `
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada setoran</td>
                                </tr>
                            `;
                        }
                        
                        $('#tblSetoran tbody').html(setoranHtml);
                    }
                });
            }

            // Hapus setoran
            $(document).on('click', '.delete-setoran-btn', function() {
                let setoranId = $(this).data('id');
                let pembiayaanId = $(this).data('pembiayaan-id');
                
                Swal.fire({
                    title: 'Hapus Setoran?',
                    text: "Setoran akan dihapus dan saldo akan dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('transaksi.pembiayaan.setoran.delete', ['id' => ':id', 'setoranId' => ':setoranId']) }}"
                                .replace(':id', pembiayaanId)
                                .replace(':setoranId', setoranId),
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    loadSetoranForView(pembiayaanId);
                                    loadRiwayatSetoranSederhana(pembiayaanId, '#setoranLogContainer');
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
                        
                        // Update modal title
                        $('#modalPembiayaanTitle').text(`Edit Pembiayaan ${data.jenis === 'company' ? 'Company' : 'Project'}`);
                        
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

            // ============ MODAL SETORAN FUNCTIONS ============ //
            
            let saldoRekeningSumber = 0;

            // Fungsi sederhana untuk riwayat setoran (sama seperti di view transaksi)
            function loadRiwayatSetoranSederhana(pembiayaanId, targetElement = '#setoranLogContainer') {
                if (!pembiayaanId) {
                    $(targetElement).html('<p class="text-muted small">Tidak ada riwayat setoran</p>');
                    return;
                }
                
                $.ajax({
                    url: "{{ route('transaksi.pembiayaan.setoran.get', ['id' => ':id']) }}".replace(':id', pembiayaanId),
                    type: 'GET',
                    success: function(res) {
                        if (res.success && res.setorans && res.setorans.length > 0) {
                            let setorans = res.setorans;
                            let historyHtml = '';
                            
                            // Sort by tanggal descending (terbaru di atas)
                            setorans.sort((a, b) => new Date(b.tanggal) - new Date(a.tanggal));
                            
                            setorans.forEach(function(setoran) {
                                let tanggal = new Date(setoran.tanggal).toLocaleDateString('id-ID');
                                let totalSetoran = parseFloat(setoran.total) || 0;
                                
                                historyHtml += `
                                    <div class="riwayat-item">
                                        <div class="riwayat-tanggal">${tanggal}</div>
                                        <div class="riwayat-nominal text-success">${formatRupiah(totalSetoran)}</div>
                                        ${setoran.deskripsi ? `
                                            <div class="riwayat-keterangan">${setoran.deskripsi}</div>
                                        ` : ''}
                                        <div class="riwayat-keterangan">
                                            <small class="text-muted">${setoran.kode_setoran || '-'}</small>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            $(targetElement).html(historyHtml);
                        } else {
                            $(targetElement).html('<p class="text-muted small">Belum ada riwayat setoran</p>');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading setoran history:', xhr);
                        $(targetElement).html('<p class="text-muted small">Error memuat riwayat setoran</p>');
                    }
                });
            }

            // Update saldo rekening sumber
            $('#rekeningSumber').change(function() {
                let selectedOption = $(this).find('option:selected');
                let saldo = selectedOption.data('saldo') || 0;
                saldoRekeningSumber = saldo;
                
                $('#saldoRekeningInfo').text('Saldo: ' + formatRupiah(saldo));
                $('#saldoSumberDisplay').text(formatRupiah(saldo));
                updateSaldoAfterSetoran();
            });

            // Update saldo setelah setoran
            function updateSaldoAfterSetoran() {
                let totalSetoran = parseNumber($('#totalSetoran').val());
                let saldoAfter = saldoRekeningSumber - totalSetoran;
                $('#saldoAfterSetoran').text(formatRupiah(saldoAfter));
                
                if (saldoAfter < 0) {
                    $('#saldoAfterSetoran').addClass('text-danger');
                    $('#saldoSumberDisplay').addClass('text-danger');
                } else {
                    $('#saldoAfterSetoran').removeClass('text-danger');
                    $('#saldoSumberDisplay').removeClass('text-danger');
                }
            }

            // Tombol setoran
            $(document).on('click', '.setoran-btn', function() {
                let pembiayaanId = $(this).data('id');
                
                $.get("{{ route('transaksi.pembiayaan.setoran.get', ['id' => ':id']) }}".replace(':id', pembiayaanId), function(res) {
                    if (res.success) {
                        let data = res.data;
                        let sisa = res.sisa;
                        
                        // Isi data modal
                        $('#pembiayaanId').val(pembiayaanId);
                        $('#setoranTotalNominal').text(formatRupiah(data.nominal));
                        $('#setoranSisa').text(formatRupiah(sisa));
                        
                        // Update info sisa
                        $('#sisaInfo').text('Sisa: ' + formatRupiah(sisa) + ' (Maksimal yang bisa dibayar)');
                        
                        // Set maksimal input pokok
                        $('#pokok').attr('max', sisa);
                        
                        // Reset form setoran
                        $('#frmSetoran')[0].reset();
                        setDefaultDate();
                        $('#rekeningSumber').val(null).trigger('change');
                        
                        // Reset preview
                        $('#buktiPreview').hide();
                        
                        // Hitung total awal
                        hitungTotalSetoran();
                        
                        // Load riwayat setoran sederhana ke sidebar kanan
                        loadRiwayatSetoranSederhana(pembiayaanId, '#setoranLogContainer');
                        
                        // Initialize select2 untuk modal setoran
                        setTimeout(() => {
                            initializeSelect2Setoran();
                        }, 300);
                        
                        $('#modalSetoran').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            });

            // Hitung total setoran
            function hitungTotalSetoran() {
                let pokok = parseNumber($('#pokok').val()) || 0;
                let administrasi = parseNumber($('#administrasiSetoran').val()) || 0;
                let margin = parseNumber($('#margin').val()) || 0;
                let total = pokok + administrasi + margin;
                $('#totalSetoran').val(formatRupiah(total));
                updateSaldoAfterSetoran();
            }

            // Event listeners untuk input setoran
            $(document).on('input', '#pokok, #administrasiSetoran, #margin', function() {
                hitungTotalSetoran();
                
                if ($(this).attr('id') === 'pokok') {
                    let pokok = parseNumber($(this).val()) || 0;
                    let sisa = parseNumber($('#pokok').attr('max')) || 0;
                    
                    if (pokok > sisa) {
                        $(this).val(sisa);
                        hitungTotalSetoran();
                        Swal.fire('Peringatan', 'Pokok tidak boleh melebihi sisa pembiayaan. Nilai telah disesuaikan.', 'warning');
                    }
                }
            });

            // Preview bukti setoran
            $('#buktiSetoran').change(function() {
                const file = this.files[0];
                if (file) {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#previewBukti').attr('src', e.target.result);
                            $('#buktiPreview').show();
                        }
                        reader.readAsDataURL(file);
                    } else {
                        $('#buktiPreview').hide();
                    }
                } else {
                    $('#buktiPreview').hide();
                }
            });

            // Submit form setoran
            $('#frmSetoran').submit(function(e) {
                e.preventDefault();
                
                let pembiayaanId = $('#pembiayaanId').val();
                let pokok = parseNumber($('#pokok').val());
                let sisa = parseNumber($('#setoranSisa').text());
                let totalSetoran = parseNumber($('#totalSetoran').val());
                
                // Validasi rekening sumber
                if (!$('#rekeningSumber').val()) {
                    Swal.fire('Peringatan', 'Rekening sumber harus dipilih', 'warning');
                    return;
                }
                
                // Validasi pokok
                if (pokok <= 0) {
                    Swal.fire('Peringatan', 'Pokok harus lebih dari 0', 'warning');
                    return;
                }
                
                // Validasi tidak melebihi sisa
                if (pokok > sisa) {
                    Swal.fire('Peringatan', 'Pokok (' + formatRupiah(pokok) + ') tidak boleh melebihi sisa pembiayaan (' + formatRupiah(sisa) + ')', 'warning');
                    return;
                }
                
                // Validasi saldo sumber
                if (totalSetoran > saldoRekeningSumber) {
                    Swal.fire({
                        title: 'Saldo Tidak Cukup',
                        text: 'Saldo rekening sumber (' + formatRupiah(saldoRekeningSumber) + ') tidak mencukupi untuk setoran ini (' + formatRupiah(totalSetoran) + ').',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Lanjutkan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitFormSetoran();
                        }
                    });
                    return;
                }

                let formData = new FormData(this);
                
                // Tampilkan loading
                $('#btnSubmitSetoran').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();

                $.ajax({
                    url: "{{ route('transaksi.pembiayaan.setoran.store', ['id' => ':id']) }}".replace(':id', pembiayaanId),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btnSubmitSetoran').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalSetoran').modal('hide');
                            tbPembiayaan.ajax.reload();
                            
                            // Refresh view modal jika terbuka
                            if ($('#modalViewPembiayaan').hasClass('show')) {
                                loadSetoranForView(pembiayaanId);
                            }
                            
                            Swal.fire('Berhasil!', res.message, 'success');
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#btnSubmitSetoran').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        let errors = xhr.responseJSON?.errors;
                        let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan setoran';
                        
                        if (errors) {
                            errorMsg = '';
                            $.each(errors, function(key, value) {
                                errorMsg += value[0] + '\n';
                            });
                        }
                        
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            });

            // ============ FORM SUBMISSION ============ //
            
            // Submit form pembiayaan
            $('#frmPembiayaan').submit(function(e) {
                e.preventDefault();
                processFormSubmission(this);
            });

            function processFormSubmission(formElement) {
                let nominal = parseNumber($('#nominal').val());
                if (nominal <= 0) {
                    Swal.fire('Peringatan', 'Nominal harus lebih dari 0', 'warning');
                    return;
                }

                if (!$('#rekeningId').val()) {
                    Swal.fire('Peringatan', 'Rekening tujuan harus dipilih', 'warning');
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

            // ============ INITIALIZATION ============ //
            
            initializeSelect2();
            setDefaultDate();
        });
        </script>
    </x-slot>
</x-app-layout>