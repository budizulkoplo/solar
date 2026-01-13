<x-app-layout>
    <x-slot name="pagetitle">Unit Details</x-slot>

    <div class="container-fluid py-2">
        <!-- Header dengan Statistik -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Daftar Unit Details</h4>
                        @if($selectedUnit)
                            <small class="text-muted">Unit: {{ $selectedUnit->namaunit }}</small>
                        @endif
                    </div>
                    <div>
                        <button class="btn btn-sm btn-info" onclick="showAllStatusStats()">
                            <i class="bi bi-bar-chart"></i> Lihat Semua Status
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <form id="filterForm" class="row g-2 align-items-center">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Project</label>
                        <select class="form-select form-select-sm" name="project_id" id="filterProject">
                            <option value="">Semua Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" 
                                    {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->namaproject }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Unit</label>
                        <select class="form-select form-select-sm" name="unit_id" id="filterUnit">
                            <option value="">Semua Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}"
                                    {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->namaunit }} ({{ $unit->blok ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Status</label>
                        <select class="form-select form-select-sm" name="status" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                            <option value="booking_unit" {{ request('status') == 'booking_unit' ? 'selected' : '' }}>Booking Unit</option>
                            <option value="bi_check" {{ request('status') == 'bi_check' ? 'selected' : '' }}>BI Check</option>
                            <option value="pemberkasan_bank" {{ request('status') == 'pemberkasan_bank' ? 'selected' : '' }}>Pemberkasan Bank</option>
                            <option value="acc" {{ request('status') == 'acc' ? 'selected' : '' }}>ACC</option>
                            <option value="tidak_acc" {{ request('status') == 'tidak_acc' ? 'selected' : '' }}>Tidak ACC</option>
                            <option value="akad" {{ request('status') == 'akad' ? 'selected' : '' }}>Akad</option>
                            <option value="pencairan" {{ request('status') == 'pencairan' ? 'selected' : '' }}>Pencairan</option>
                            <option value="bast" {{ request('status') == 'bast' ? 'selected' : '' }}>BAST</option>
                            <option value="terjual" {{ request('status') == 'terjual' ? 'selected' : '' }}>Terjual</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">&nbsp;</label>
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-sm btn-primary flex-fill">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('units.details.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-3 align-items-stretch" id="statsCards">

            <!-- Total Unit -->
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-light shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Total Unit</h6>
                                <h4 class="mb-0" id="totalUnits">0</h4>
                            </div>
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-house fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tersedia -->
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-success bg-opacity-10 shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Tersedia</h6>
                                <h4 class="mb-0 text-success" id="tersediaCount">0</h4>
                                <small class="text-muted">
                                    <span id="tersediaPercent">0</span>%
                                </small>
                            </div>
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-check-circle fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking -->
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-warning bg-opacity-10 shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Booking</h6>
                                <h4 class="mb-0 text-warning" id="bookingCount">0</h4>
                                <small class="text-muted">
                                    <span id="bookingPercent">0</span>%
                                </small>
                            </div>
                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-calendar-check fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terjual -->
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-danger bg-opacity-10 shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Terjual</h6>
                                <h4 class="mb-0 text-danger" id="terjualCount">0</h4>
                                <small class="text-muted">
                                    <span id="terjualPercent">0</span>%
                                </small>
                            </div>
                            <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-cash-coin fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Unit Cards Grid -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-2" id="unitGrid">
            @foreach($projects as $project)
                @if($project->units->isNotEmpty())
                    @foreach($project->units as $unit)
                        @if(!request('unit_id') || request('unit_id') == $unit->id)
                            @foreach($unit->details as $detail)
                                @if(!request('status') || request('status') == $detail->status)
                                    <div class="col unit-card" 
                                         data-project="{{ $project->id }}"
                                         data-unit="{{ $unit->id }}"
                                         data-detail="{{ $detail->id }}"
                                         data-status="{{ $detail->status }}">
                                        <div class="card h-100 shadow-sm hover-shadow">
                                            <!-- Header dengan warna status -->
                                            <div class="card-header py-1 px-2 
                                                @if($detail->status === 'tersedia') bg-success text-white
                                                @elseif(in_array($detail->status, ['booking_unit', 'bi_check', 'pemberkasan_bank', 'akad', 'pencairan'])) bg-warning text-dark
                                                @elseif($detail->status === 'acc') bg-info text-white
                                                @elseif($detail->status === 'bast') bg-primary text-white
                                                @elseif($detail->status === 'terjual') bg-danger text-white
                                                @else bg-secondary text-white @endif">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="fw-bold text-truncate">
                                                        {{ $project->namaproject }}
                                                    </small>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm p-0 dropdown-toggle " type="button" 
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="bi bi-three-dots-vertical text-white"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end overflow-auto" style="max-height:300px;">

                                                            <li><h6 class="dropdown-header">Ubah Status</h6></li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="tersedia">
                                                                    <i class="bi bi-check-circle text-success me-2"></i>Tersedia
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="booking_unit">
                                                                    <i class="bi bi-calendar-check text-warning me-2"></i>Booking Unit
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="bi_check">
                                                                    <i class="bi bi-file-earmark-check text-info me-2"></i>BI Check
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="pemberkasan_bank">
                                                                    <i class="bi bi-folder-check text-primary me-2"></i>Pemberkasan Bank
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="acc">
                                                                    <i class="bi bi-check-all text-success me-2"></i>ACC
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="tidak_acc">
                                                                    <i class="bi bi-x-circle text-danger me-2"></i>Tidak ACC
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="akad">
                                                                    <i class="bi bi-file-earmark-text text-info me-2"></i>Akad
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="pencairan">
                                                                    <i class="bi bi-cash-coin text-primary me-2"></i>Pencairan
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="bast">
                                                                    <i class="bi bi-house-check text-success me-2"></i>BAST
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="terjual">
                                                                    <i class="bi bi-currency-dollar text-danger me-2"></i>Terjual
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <button class="dropdown-item view-detail" data-id="{{ $detail->id }}">
                                                                    <i class="bi bi-eye me-2"></i>Lihat Detail
                                                                </button>
                                                            </li>
                                                            @if($detail->customer_id)
                                                            <li>
                                                                <button class="dropdown-item view-customer" data-customer-id="{{ $detail->customer_id }}">
                                                                    <i class="bi bi-person me-2"></i>Lihat Customer
                                                                </button>
                                                            </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Card Body -->
                                            <div class="card-body p-2">
                                                <!-- Nama Unit dan Tipe -->
                                                <div class="mb-1">
                                                    <h6 class="card-title mb-0 text-truncate" title="{{ $unit->namaunit }}">
                                                        <i class="fas fa-home me-1 
                                                            @if($detail->status === 'tersedia') text-success
                                                            @elseif(in_array($detail->status, ['booking_unit', 'bi_check', 'pemberkasan_bank', 'akad', 'pencairan'])) text-warning
                                                            @elseif($detail->status === 'acc') text-info
                                                            @elseif($detail->status === 'bast') text-primary
                                                            @elseif($detail->status === 'terjual') text-danger
                                                            @else text-secondary @endif
                                                        "></i>
                                                        {{ $unit->namaunit }} - Tipe: {{ $unit->tipe }}
                                                    </h6>
                                                    <h6>&nbsp;</h6>
                                                </div>
                                                
                                                <!-- Tambahkan di card untuk menunjukkan alur status -->
                                                <div class="small mb-2">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Blok:</span>
                                                        <span class="fw-semibold">{{ $unit->blok ?? '-' }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Jenis:</span>
                                                        <span class="fw-semibold">{{ $unit->jenisUnit->jenisunit ?? '-' }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Status:</span>
                                                        <span class="fw-semibold badge 
                                                            @if($detail->status === 'tersedia') bg-success
                                                            @elseif(in_array($detail->status, ['booking_unit', 'bi_check', 'pemberkasan_bank', 'akad', 'pencairan'])) bg-warning text-dark
                                                            @elseif($detail->status === 'acc') bg-info
                                                            @elseif($detail->status === 'bast') bg-primary
                                                            @elseif($detail->status === 'terjual') bg-danger
                                                            @elseif($detail->status === 'tidak_acc') bg-danger
                                                            @else bg-secondary @endif
                                                        ">{{ str_replace('_', ' ', ucfirst($detail->status)) }}</span>
                                                    </div>
                                                    
                                                    <!-- Tampilkan alur status jika dalam proses -->
                                                    @if(in_array($detail->status, ['booking_unit', 'bi_check', 'pemberkasan_bank', 'acc', 'akad', 'pencairan', 'bast']))
                                                    <div class="mt-1">
                                                        <small class="text-muted">Proses:</small>
                                                        <div class="progress" style="height: 5px;">
                                                            @php
                                                                $statusOrder = ['booking_unit', 'bi_check', 'pemberkasan_bank', 'acc', 'akad', 'pencairan', 'bast', 'terjual'];
                                                                $currentIndex = array_search($detail->status, $statusOrder);
                                                                $progress = $currentIndex !== false ? (($currentIndex + 1) / count($statusOrder)) * 100 : 0;
                                                            @endphp
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                style="width: {{ $progress }}%" 
                                                                aria-valuenow="{{ $progress }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted">
                                                            @if($detail->status === 'booking_unit')
                                                                Booking →
                                                                <span class="badge bg-success">Booking</span>
                                                                → BI Check → Pemberkasan → ACC → Akad → Pencairan → BAST → Terjual

                                                            @elseif($detail->status === 'bi_check')
                                                                Booking →
                                                                <span class="badge bg-success">BI Check</span>
                                                                → Pemberkasan → ACC → Akad → Pencairan → BAST → Terjual

                                                            @elseif($detail->status === 'pemberkasan_bank')
                                                                Booking → BI Check →
                                                                <span class="badge bg-success">Pemberkasan</span>
                                                                → ACC → Akad → Pencairan → BAST → Terjual

                                                            @elseif($detail->status === 'acc')
                                                                Booking → BI Check → Pemberkasan →
                                                                <span class="badge bg-success">ACC</span>
                                                                → Akad → Pencairan → BAST → Terjual

                                                            @elseif($detail->status === 'akad')
                                                                Booking → BI Check → Pemberkasan → ACC →
                                                                <span class="badge bg-success">Akad</span>
                                                                → Pencairan → BAST → Terjual

                                                            @elseif($detail->status === 'pencairan')
                                                                Booking → BI Check → Pemberkasan → ACC → Akad →
                                                                <span class="badge bg-success">Pencairan</span>
                                                                → BAST → Terjual

                                                            @elseif($detail->status === 'bast')
                                                                Booking → BI Check → Pemberkasan → ACC → Akad → Pencairan →
                                                                <span class="badge bg-success">BAST</span>
                                                                → Terjual
                                                            @endif
                                                        </small>
                                                    </div>
                                                    @endif
                                                </div>
                                                
                                                <!-- No Unit -->
                                                <div class="text-center my-2">
                                                    <div class="bg-light rounded p-2">
                                                        <small class="text-muted d-block">No Rumah</small>
                                                        <strong class="text-primary fs-5">{{ $detail->no_rumah }}</strong>
                                                    </div>
                                                </div>
                                                
                                                <!-- Luas -->
                                                <div class="small text-center mb-2">
                                                    @if($unit->luastanah || $unit->luasbangunan)
                                                        <span class="badge bg-info text-dark">
                                                            <i class="bi bi-rulers"></i>
                                                            {{ $unit->luastanah ?? '0' }}/{{ $unit->luasbangunan ?? '0' }}
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                <!-- Customer Info jika ada -->
                                                @if($detail->customer_id && $detail->customer)
                                                <div class="small border-top pt-1 mt-1">
                                                    <small class="text-muted d-block">Customer:</small>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person me-1"></i>
                                                        <span class="text-truncate">{{ $detail->customer->nama_lengkap }}</span>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            
                                            <!-- Footer dengan Harga -->
                                            <div class="card-footer py-1 px-2 bg-transparent border-top">
                                                <div class="small">
                                                    @if($unit->hargadasar)
                                                        <div class="text-end">
                                                            <span class="fw-bold text-primary">
                                                                Rp {{ number_format($unit->hargadasar, 0, ',', '.') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                                        @if($detail->penjualan_id)
                                                            <span class="badge bg-success">Terjual</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>
        
        <!-- Empty State -->
        <div id="emptyState" class="text-center d-none">
            <div class="py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5>Tidak ada unit yang ditemukan</h5>
                <p class="text-muted">Coba gunakan filter yang berbeda</p>
            </div>
        </div>
    </div>

    <!-- Modal untuk Form Customer (Booking) -->
    <div class="modal fade" id="modalCustomer" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="frmCustomer">
                    @csrf
                    <input type="hidden" name="detail_id" id="detailId">
                    <input type="hidden" name="status" id="statusChange">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Form Customer untuk Booking</h5>
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
                                <input type="text" class="form-control form-control-sm" name="nama_lengkap" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">NIK <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="nik" required maxlength="16">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="tempat_lahir" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="tanggal_lahir" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="jenis_kelamin" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            
                            <!-- Alamat KTP -->
                            <div class="col-12 mt-3">
                                <h6 class="border-bottom pb-1 mb-3">Alamat sesuai KTP</h6>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" name="alamat_ktp" rows="2" required></textarea>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">RT/RW <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="rt_rw_ktp" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Kelurahan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="kelurahan_ktp" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Kecamatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="kecamatan_ktp" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Kota <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="kota_ktp" required>
                            </div>
                            
                            <!-- Kontak -->
                            <div class="col-12 mt-3">
                                <h6 class="border-bottom pb-1 mb-3">Kontak</h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">No. HP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="no_hp" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control form-control-sm" name="email">
                            </div>
                            
                            <!-- Pekerjaan -->
                            <div class="col-12 mt-3">
                                <h6 class="border-bottom pb-1 mb-3">Pekerjaan & Penghasilan</h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Pekerjaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="pekerjaan" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Penghasilan Bulanan (Rp)</label>
                                <input type="number" class="form-control form-control-sm" name="penghasilan_bulanan">
                            </div>
                            
                            <!-- Data Booking -->
                            <div class="col-12 mt-3">
                                <h6 class="border-bottom pb-1 mb-3">Data Booking</h6>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Booking <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="tanggal_booking" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">DP Awal (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" name="dp_awal" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="tanggal_jatuh_tempo" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Metode Pembayaran DP <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="metode_pembayaran_dp" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="transfer_bank">Transfer Bank</option>
                                    <option value="tunai">Tunai</option>
                                    <option value="kartu_kredit">Kartu Kredit</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Keterangan</label>
                                <input type="text" class="form-control form-control-sm" name="keterangan">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary">Simpan Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal untuk Penjualan -->
    <div class="modal fade" id="modalPenjualan" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="frmPenjualan">
                    @csrf
                    <input type="hidden" name="detail_id" id="penjualanDetailId">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Form Penjualan Unit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" name="harga_jual" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">DP Awal (Rp)</label>
                                <input type="number" class="form-control form-control-sm" name="dp_awal">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="metode_pembayaran" id="metodePembayaran" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="cash">Cash</option>
                                    <option value="kredit">Kredit</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Akad <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="tanggal_akad" required>
                            </div>
                            
                            <!-- Field untuk kredit -->
                            <div class="col-md-6 d-none" id="fieldBankKredit">
                                <label class="form-label">Bank Kredit <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="bank_kredit">
                            </div>
                            
                            <div class="col-md-6 d-none" id="fieldTenorKredit">
                                <label class="form-label">Tenor (Bulan) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" name="tenor_kredit" min="1">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <textarea class="form-control form-control-sm" name="keterangan" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary">Simpan Penjualan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail Unit -->
    <div class="modal fade" id="modalDetailUnit" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailUnitContent">
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
                // Set tanggal default untuk form
                setDefaultDates();
                
                // Load initial statistics
                loadStatistics();
                
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
                
                // Set default dates for forms
                function setDefaultDates() {
                    const today = new Date().toISOString().split('T')[0];
                    const nextMonth = new Date();
                    nextMonth.setMonth(nextMonth.getMonth() + 1);
                    const nextMonthStr = nextMonth.toISOString().split('T')[0];
                    
                    // Set default dates for customer form
                    $('#frmCustomer input[name="tanggal_booking"]').val(today);
                    $('#frmCustomer input[name="tanggal_jatuh_tempo"]').val(nextMonthStr);
                    $('#frmCustomer input[name="tanggal_lahir"]').val('1990-01-01');
                    
                    // Set default date for penjualan form
                    $('#frmPenjualan input[name="tanggal_akad"]').val(today);
                }
                
                // Load statistics function
                function loadStatistics() {
                    // Ambil nilai filter
                    const projectId = $('#filterProject').val();
                    const unitId = $('#filterUnit').val();
                    const status = $('#filterStatus').val();
                    
                    // Buat parameter URL
                    let params = new URLSearchParams();
                    if (projectId) params.append('project_id', projectId);
                    if (unitId) params.append('unit_id', unitId);
                    if (status) params.append('status', status);
                    
                    // Gunakan URL langsung untuk menghindari error route
                    const url = '/units/details/statistics?' + params.toString();
                    
                    $.get(url, function(response) {
                        if (response.error) {
                            console.error('Error loading statistics:', response.error);
                            return;
                        }
                        
                        $('#totalUnits').text(response.total);
                        $('#tersediaCount').text(response.tersedia);
                        $('#bookingCount').text(response.booking);
                        $('#terjualCount').text(response.terjual);
                        $('#tersediaPercent').text(response.tersedia_percent);
                        $('#bookingPercent').text(response.booking_percent);
                        $('#terjualPercent').text(response.terjual_percent);
                    }).fail(function(xhr) {
                        console.error('Failed to load statistics:', xhr.responseText);
                    });
                }
                
                // Show all status statistics
                window.showAllStatusStats = function() {
                    Swal.fire({
                        title: 'Statistik Semua Status',
                        html: `
                            <div class="text-start">
                                <p><strong>Total Unit:</strong> <span id="swalTotal">0</span></p>
                                <p><strong>Tersedia:</strong> <span id="swalTersedia">0</span> (<span id="swalTersediaPercent">0</span>%)</p>
                                <p><strong>Booking:</strong> <span id="swalBooking">0</span> (<span id="swalBookingPercent">0</span>%)</p>
                                <p><strong>BI Check:</strong> <span id="swalBiCheck">0</span></p>
                                <p><strong>Pemberkasan Bank:</strong> <span id="swalPemberkasan">0</span></p>
                                <p><strong>ACC:</strong> <span id="swalAcc">0</span></p>
                                <p><strong>Tidak ACC:</strong> <span id="swalTidakAcc">0</span></p>
                                <p><strong>Akad:</strong> <span id="swalAkad">0</span></p>
                                <p><strong>Pencairan:</strong> <span id="swalPencairan">0</span></p>
                                <p><strong>BAST:</strong> <span id="swalBast">0</span></p>
                                <p><strong>Terjual:</strong> <span id="swalTerjual">0</span> (<span id="swalTerjualPercent">0</span>%)</p>
                            </div>
                        `,
                        showConfirmButton: true,
                        confirmButtonText: 'Tutup'
                    });
                    
                    // Load detailed statistics
                    const url = '/units/details/statistics?detailed=true';
                    $.get(url, function(response) {
                        if (response.error) return;
                        
                        $('#swalTotal').text(response.total);
                        $('#swalTersedia').text(response.tersedia);
                        $('#swalTersediaPercent').text(response.tersedia_percent);
                        $('#swalBooking').text(response.booking);
                        $('#swalBookingPercent').text(response.booking_percent);
                        $('#swalBiCheck').text(response.bi_check);
                        $('#swalPemberkasan').text(response.pemberkasan_bank);
                        $('#swalAcc').text(response.acc);
                        $('#swalTidakAcc').text(response.tidak_acc);
                        $('#swalAkad').text(response.akad);
                        $('#swalPencairan').text(response.pencairan);
                        $('#swalBast').text(response.bast);
                        $('#swalTerjual').text(response.terjual);
                        $('#swalTerjualPercent').text(response.terjual_percent);
                    });
                }
                
                // Filter Form submit
                $('#filterForm').submit(function(e) {
                    e.preventDefault();
                    loadStatistics(); // Update statistics
                    filterCards(); // Filter cards
                });
                
                // Change project filter -> update unit dropdown
                $('#filterProject').change(function() {
                    loadStatistics();
                    filterCards();
                });
                
                // Change unit filter
                $('#filterUnit').change(function() {
                    loadStatistics();
                    filterCards();
                });
                
                // Change status filter
                $('#filterStatus').change(function() {
                    loadStatistics();
                    filterCards();
                });
                
                // Change status via dropdown
                $(document).on('click', '.change-status', function() {
                    const detailId = $(this).data('id');
                    const newStatus = $(this).data('status');
                    
                    // DAPATKAN STATUS TERKINI DARI DATA ATTRIBUTE
                    const card = $(this).closest('.unit-card');
                    const currentStatus = card.attr('data-status'); // Gunakan attr() bukan data()
                    
                    // Validasi alur status
                    if (!validateStatusFlow(currentStatus, newStatus)) {
                        showError(`Tidak bisa mengubah status dari ${formatStatus(currentStatus)} ke ${formatStatus(newStatus)}`);
                        return;
                    }
                    
                    // Jika status booking, tampilkan modal form customer
                    if (newStatus === 'booking_unit') {
                        $('#detailId').val(detailId);
                        $('#statusChange').val(newStatus);
                        setDefaultDates();
                        $('#modalCustomer').modal('show');
                        return;
                    }
                    
                    // Jika status terjual, cek dulu apakah sudah melalui semua tahap
                    if (newStatus === 'terjual') {
                        // Pastikan status sebelumnya adalah BAST
                        if (currentStatus !== 'bast') {
                            showError('Unit harus dalam status BAST sebelum bisa dijual');
                            return;
                        }
                        
                        $('#penjualanDetailId').val(detailId);
                        $('#frmPenjualan')[0].reset();
                        setDefaultDates();
                        
                        // Load data unit untuk harga default
                        $.get('/units/details/' + detailId + '/detail', function(response) {
                            if (response.success) {
                                const unit = response.data.unit;
                                if (unit && unit.hargadasar) {
                                    $('#frmPenjualan input[name="harga_jual"]').val(unit.hargadasar);
                                }
                            }
                        });
                        
                        $('#modalPenjualan').modal('show');
                        return;
                    }
                    
                    // Jika status tidak_acc, konfirmasi dulu
                    if (newStatus === 'tidak_acc') {
                        Swal.fire({
                            title: 'Konfirmasi Tidak ACC',
                            text: 'Jika status diubah menjadi Tidak ACC, unit akan kembali ke status Tersedia. Lanjutkan?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, Lanjutkan',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                updateStatusDirect(detailId, newStatus, card);
                            }
                        });
                        return;
                    }
                    
                    // Untuk status lainnya langsung update
                    updateStatusDirect(detailId, newStatus, card);
                });

                // Fungsi validasi alur status
                function validateStatusFlow(currentStatus, newStatus) {
                    const validFlows = {
                        'tersedia': ['booking_unit'],
                        'booking_unit': ['tersedia', 'bi_check', 'tidak_acc'],
                        'bi_check': ['booking_unit', 'pemberkasan_bank', 'tidak_acc'],
                        'pemberkasan_bank': ['bi_check', 'acc', 'tidak_acc'],
                        'acc': ['pemberkasan_bank', 'akad'],
                        'tidak_acc': ['tersedia'], // Kembali ke tersedia
                        'akad': ['acc', 'pencairan'],
                        'pencairan': ['akad', 'bast'],
                        'bast': ['pencairan', 'terjual'],
                        'terjual': [] // Tidak bisa diubah lagi setelah terjual
                    };
                    
                    // Jika sudah terjual, tidak bisa diubah ke status lain
                    if (currentStatus === 'terjual') {
                        return false;
                    }
                    
                    // Cek apakah alur valid
                    return validFlows[currentStatus]?.includes(newStatus) || false;
                }

                // Fungsi format status untuk display
                function formatStatus(status) {
                    const statusMap = {
                        'tersedia': 'Tersedia',
                        'booking_unit': 'Booking Unit',
                        'bi_check': 'BI Check',
                        'pemberkasan_bank': 'Pemberkasan Bank',
                        'acc': 'ACC',
                        'tidak_acc': 'Tidak ACC',
                        'akad': 'Akad',
                        'pencairan': 'Pencairan',
                        'bast': 'BAST',
                        'terjual': 'Terjual'
                    };
                    return statusMap[status] || status;
                }

                // Update dropdown berdasarkan status saat ini
                function updateDropdownByStatus(card, currentStatus) {
                    const dropdown = card.find('.dropdown-menu');
                    const statusItems = dropdown.find('.change-status');
                    
                    // Sembunyikan semua item dulu
                    statusItems.hide();
                    
                    // Tampilkan hanya yang sesuai dengan alur
                    const validNextStatus = getValidNextStatus(currentStatus);
                    
                    statusItems.each(function() {
                        const status = $(this).data('status');
                        if (validNextStatus.includes(status)) {
                            $(this).show();
                        }
                    });
                }

                // Fungsi untuk mendapatkan status berikutnya yang valid
                function getValidNextStatus(currentStatus) {
                    const flowMap = {
                        'tersedia': ['booking_unit'],
                        'booking_unit': ['tersedia', 'bi_check', 'tidak_acc'],
                        'bi_check': ['booking_unit', 'pemberkasan_bank', 'tidak_acc'],
                        'pemberkasan_bank': ['bi_check', 'acc', 'tidak_acc'],
                        'acc': ['pemberkasan_bank', 'akad'],
                        'tidak_acc': ['tersedia'],
                        'akad': ['acc', 'pencairan'],
                        'pencairan': ['akad', 'bast'],
                        'bast': ['pencairan', 'terjual'],
                        'terjual': []
                    };
                    
                    return flowMap[currentStatus] || [];
                }
                
                // Submit form customer (booking)
                $('#frmCustomer').submit(function(e) {
                    e.preventDefault();
                    
                    const detailId = $('#detailId').val();
                    const newStatus = $('#statusChange').val();
                    const formData = $(this).serializeArray();
                    
                    // Validasi NIK
                    const nik = formData.find(item => item.name === 'nik').value;
                    if (nik.length !== 16) {
                        showError('NIK harus 16 digit');
                        return;
                    }
                    
                    // Format data untuk API
                    const requestData = {
                        status: newStatus,
                        customer_data: {},
                        booking_data: {}
                    };
                    
                    // Pisahkan data customer dan booking
                    formData.forEach(function(item) {
                        if (['_token', 'detail_id', 'status'].includes(item.name)) {
                            return;
                        }

                        // ===== CUSTOMER =====
                        if (
                            item.name.startsWith('nama_') ||
                            item.name.startsWith('tempat_') ||
                            item.name === 'tanggal_lahir' ||
                            item.name === 'jenis_kelamin' ||
                            item.name === 'nik' ||
                            item.name === 'no_kk' ||
                            item.name.includes('ktp') ||
                            item.name === 'no_hp' ||
                            item.name === 'email' ||
                            item.name === 'pekerjaan' ||
                            item.name === 'penghasilan_bulanan'
                        ) {
                            requestData.customer_data[item.name] = item.value;
                        }

                        // ===== BOOKING =====
                        else if (
                            item.name === 'tanggal_booking' ||
                            item.name === 'tanggal_jatuh_tempo' ||
                            item.name === 'dp_awal' ||
                            item.name === 'metode_pembayaran_dp' ||
                            item.name === 'keterangan'
                        ) {
                            requestData.booking_data[item.name] = item.value;
                        }
                    });

                    // Tampilkan loading
                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                    
                    // Kirim request
                    $.ajax({
                        url: '/units/details/' + detailId + '/status',
                        type: 'PUT',
                        data: JSON.stringify(requestData),
                        contentType: 'application/json',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            if (response.success) {
                                showSuccess(response.message);
                                $('#modalCustomer').modal('hide');
                                $('#frmCustomer')[0].reset();
                                
                                // Update card
                                const card = $('.unit-card[data-detail="' + detailId + '"]');
                                
                                // PERBAIKAN: UPDATE DATA ATTRIBUTE STATUS
                                card.attr('data-status', newStatus);
                                
                                updateCardStatus(card, newStatus);
                                
                                // Update customer info in card
                                if (response.data.customer) {
                                    const customerInfo = `
                                        <div class="small border-top pt-1 mt-1">
                                            <small class="text-muted d-block">Customer:</small>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person me-1"></i>
                                                <span class="text-truncate">${response.data.customer.nama_lengkap}</span>
                                            </div>
                                        </div>
                                    `;
                                    card.find('.card-body').append(customerInfo);
                                }
                                
                                // Reload statistics
                                loadStatistics();
                                filterCards();
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function(xhr) {
                            submitBtn.prop('disabled', false).html(originalText);
                            if (xhr.status === 422) {
                                const errors = xhr.responseJSON.errors;
                                let errorMessage = '';
                                for (const key in errors) {
                                    errorMessage += errors[key][0] + '\n';
                                }
                                showError(errorMessage);
                            } else {
                                showError('Terjadi kesalahan saat menyimpan data');
                            }
                        }
                    });
                });
                
                // Submit form penjualan
                $('#frmPenjualan').submit(function(e) {
                    e.preventDefault();
                    
                    const detailId = $('#penjualanDetailId').val();
                    const formData = $(this).serializeArray();
                    
                    // Validasi data
                    const metodePembayaran = formData.find(item => item.name === 'metode_pembayaran').value;
                    if (metodePembayaran === 'kredit') {
                        const bankKredit = formData.find(item => item.name === 'bank_kredit')?.value;
                        const tenorKredit = formData.find(item => item.name === 'tenor_kredit')?.value;
                        
                        if (!bankKredit || !tenorKredit) {
                            showError('Bank dan Tenor Kredit harus diisi');
                            return;
                        }
                    }
                    
                    // Format data untuk API
                    const requestData = {
                        status: 'terjual',
                        penjualan_data: {}
                    };
                    
                    formData.forEach(function(item) {
                        if (item.name !== '_token' && item.name !== 'detail_id') {
                            requestData.penjualan_data[item.name] = item.value;
                        }
                    });
                    
                    // Tampilkan loading
                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                    
                    // Kirim request
                    $.ajax({
                        url: '/units/details/' + detailId + '/status',
                        type: 'PUT',
                        data: JSON.stringify(requestData),
                        contentType: 'application/json',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            if (response.success) {
                                showSuccess(response.message);
                                $('#modalPenjualan').modal('hide');
                                $('#frmPenjualan')[0].reset();
                                
                                // Update card
                                const card = $('.unit-card[data-detail="' + detailId + '"]');
                                
                                // PERBAIKAN: UPDATE DATA ATTRIBUTE STATUS
                                card.attr('data-status', 'terjual');
                                
                                updateCardStatus(card, 'terjual');
                                
                                // Reload statistics
                                loadStatistics();
                                filterCards();
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function(xhr) {
                            submitBtn.prop('disabled', false).html(originalText);
                            showError('Terjadi kesalahan saat menyimpan data');
                        }
                    });
                });
                
                // Toggle field kredit
                $('#metodePembayaran').change(function() {
                    if ($(this).val() === 'kredit') {
                        $('#fieldBankKredit, #fieldTenorKredit').removeClass('d-none');
                        $('input[name="bank_kredit"], input[name="tenor_kredit"]').prop('required', true);
                    } else {
                        $('#fieldBankKredit, #fieldTenorKredit').addClass('d-none');
                        $('input[name="bank_kredit"], input[name="tenor_kredit"]').prop('required', false);
                    }
                });
                
                // View detail unit
                $(document).on('click', '.view-detail', function() {
                    const detailId = $(this).data('id');
                    
                    $.get('/units/details/' + detailId + '/detail', function(response) {
                        if (response.success) {
                            const data = response.data;
                            const unit = data.unit;
                            const detail = data;
                            
                            let html = `
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Informasi Unit</h6>
                                        <p><strong>Nama Unit:</strong> ${unit.namaunit}</p>
                                        <p><strong>Tipe:</strong> ${unit.tipe || '-'}</p>
                                        <p><strong>Blok:</strong> ${unit.blok || '-'}</p>
                                        
                                        <p><strong>Luas Tanah:</strong> ${unit.luastanah || '-'}</p>
                                        <p><strong>Luas Bangunan:</strong> ${unit.luasbangunan || '-'}</p>
                                        <p><strong>Harga Dasar:</strong> Rp ${unit.hargadasar ? unit.hargadasar.toLocaleString('id-ID') : '-'}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Status & Detail</h6>
                                        <p><strong>No Unit:</strong> ${detail.id}</p>
                                        <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(detail.status)}">${detail.status.replace('_', ' ').toUpperCase()}</span></p>
                                        <p><strong>Dibuat:</strong> ${new Date(detail.created_at).toLocaleDateString('id-ID')}</p>
                            `;
                            
                            if (data.customer) {
                                html += `
                                    <hr>
                                    <h6>Informasi Customer</h6>
                                    <p><strong>Nama:</strong> ${data.customer.nama_lengkap}</p>
                                    <p><strong>NIK:</strong> ${data.customer.nik}</p>
                                    <p><strong>No. HP:</strong> ${data.customer.no_hp}</p>
                                `;
                            }
                            
                            if (data.booking) {
                                html += `
                                    <hr>
                                    <h6>Informasi Booking</h6>
                                    <p><strong>Kode Booking:</strong> ${data.booking.kode_booking}</p>
                                    <p><strong>DP Awal:</strong> Rp ${data.booking.dp_awal.toLocaleString('id-ID')}</p>
                                    <p><strong>Tanggal Booking:</strong> ${new Date(data.booking.tanggal_booking).toLocaleDateString('id-ID')}</p>
                                `;
                            }
                            
                            if (data.penjualan) {
                                html += `
                                    <hr>
                                    <h6>Informasi Penjualan</h6>
                                    <p><strong>Kode Penjualan:</strong> ${data.penjualan.kode_penjualan}</p>
                                    <p><strong>Harga Jual:</strong> Rp ${data.penjualan.harga_jual.toLocaleString('id-ID')}</p>
                                    <p><strong>Metode Pembayaran:</strong> ${data.penjualan.metode_pembayaran}</p>
                                `;
                            }
                            
                            html += `</div></div>`;
                            
                            $('#detailUnitContent').html(html);
                            $('#modalDetailUnit').modal('show');
                        } else {
                            showError('Gagal memuat detail unit');
                        }
                    });
                });
                
                // View customer
                $(document).on('click', '.view-customer', function() {
                    const customerId = $(this).data('customer-id');
                    
                    $.get('/customers/' + customerId, function(response) {
                        if (response.success) {
                            const customer = response.data;
                            
                            let html = `
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Data Pribadi</h6>
                                        <p><strong>Kode Customer:</strong> ${customer.kode_customer}</p>
                                        <p><strong>Nama Lengkap:</strong> ${customer.nama_lengkap}</p>
                                        <p><strong>NIK:</strong> ${customer.nik}</p>
                                        <p><strong>Tempat/Tgl Lahir:</strong> ${customer.tempat_lahir}, ${new Date(customer.tanggal_lahir).toLocaleDateString('id-ID')}</p>
                                        <p><strong>Jenis Kelamin:</strong> ${customer.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Kontak & Pekerjaan</h6>
                                        <p><strong>No. HP:</strong> ${customer.no_hp}</p>
                                        <p><strong>Email:</strong> ${customer.email || '-'}</p>
                                        <p><strong>Pekerjaan:</strong> ${customer.pekerjaan}</p>
                                        <p><strong>Penghasilan:</strong> ${customer.penghasilan_bulanan ? 'Rp ' + customer.penghasilan_bulanan.toLocaleString('id-ID') : '-'}</p>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6>Alamat KTP</h6>
                                        <p>${customer.alamat_ktp}</p>
                                        <p>${customer.rt_rw_ktp}, ${customer.kelurahan_ktp}, ${customer.kecamatan_ktp}</p>
                                        <p>${customer.kota_ktp}, ${customer.provinsi_ktp} ${customer.kode_pos_ktp}</p>
                                    </div>
                                </div>
                            `;
                            
                            Swal.fire({
                                title: 'Detail Customer',
                                html: html,
                                showConfirmButton: true,
                                confirmButtonText: 'Tutup',
                                width: '800px',
                                customClass: {
                                    htmlContainer: 'text-start'
                                }
                            });
                        }
                    });
                });
                
                // Fungsi untuk update status langsung (tanpa modal)
                function updateStatusDirect(detailId, newStatus, card) {
                    Swal.fire({
                        title: 'Ubah Status?',
                        text: `Apakah Anda yakin ingin mengubah status unit menjadi ${newStatus.replace('_', ' ')}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Ubah!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.showLoading();
                            
                            $.ajax({
                                url: '/units/details/' + detailId + '/status',
                                type: 'PUT',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    status: newStatus
                                },
                                success: function(response) {
                                    Swal.close();
                                    
                                    if (response.success) {
                                        showSuccess(response.message);
                                        
                                        // PERBAIKAN: UPDATE DATA ATTRIBUTE STATUS
                                        card.attr('data-status', newStatus);
                                        
                                        // Update card appearance
                                        updateCardStatus(card, newStatus);
                                        
                                        // Reload statistics
                                        loadStatistics();
                                        
                                        // Re-filter cards jika ada filter status aktif
                                        filterCards();
                                    } else {
                                        showError(response.message);
                                    }
                                },
                                error: function(xhr) {
                                    Swal.close();
                                    showError('Terjadi kesalahan saat mengubah status');
                                }
                            });
                        }
                    });
                }
                
                // Filter cards function
                function filterCards() {
                    const projectId = $('#filterProject').val();
                    const unitId = $('#filterUnit').val();
                    const status = $('#filterStatus').val();
                    let visibleCount = 0;
                    
                    $('.unit-card').each(function() {
                        const card = $(this);
                        const cardProject = card.data('project');
                        const cardUnit = card.data('unit');
                        
                        // PERBAIKAN: GUNAKAN ATTR() UNTUK MENDAPATKAN STATUS TERKINI
                        const cardStatus = card.attr('data-status') || card.data('status');
                        
                        // Check filters
                        const projectMatch = !projectId || cardProject == projectId;
                        const unitMatch = !unitId || cardUnit == unitId;
                        const statusMatch = !status || cardStatus == status;
                        
                        if (projectMatch && unitMatch && statusMatch) {
                            card.show();
                            visibleCount++;
                        } else {
                            card.hide();
                        }
                    });
                    
                    // Update empty state
                    if (visibleCount === 0) {
                        $('#emptyState').removeClass('d-none');
                    } else {
                        $('#emptyState').addClass('d-none');
                    }
                }
                
                // Update card status appearance dengan semua status
                function updateCardStatus(card, newStatus) {
                    const header = card.find('.card-header');
                    header.removeClass('bg-success bg-warning bg-danger bg-info bg-primary bg-secondary text-white text-dark');
                    
                    // Tentukan warna berdasarkan status
                    if (newStatus === 'tersedia') {
                        header.addClass('bg-success text-white');
                    } else if (['booking_unit', 'bi_check', 'pemberkasan_bank', 'akad', 'pencairan'].includes(newStatus)) {
                        header.addClass('bg-warning text-dark');
                    } else if (newStatus === 'acc') {
                        header.addClass('bg-info text-white');
                    } else if (newStatus === 'bast') {
                        header.addClass('bg-primary text-white');
                    } else if (newStatus === 'terjual') {
                        header.addClass('bg-danger text-white');
                    } else if (newStatus === 'tidak_acc') {
                        header.addClass('bg-danger text-white');
                    } else {
                        header.addClass('bg-secondary text-white');
                    }
                    
                    // Update status badge di body
                    const statusBadge = card.find('.card-body .badge');
                    if (statusBadge.length) {
                        statusBadge.removeClass('bg-success bg-warning bg-danger bg-info bg-primary bg-secondary');
                        statusBadge.addClass(getStatusBadgeClass(newStatus));
                        statusBadge.text(newStatus.replace('_', ' ').toUpperCase());
                    }
                    
                    // Update icon warna
                    const icon = card.find('.card-title i');
                    icon.removeClass('text-success text-warning text-danger text-info text-primary text-secondary');
                    if (newStatus === 'tersedia') {
                        icon.addClass('text-success');
                    } else if (['booking_unit', 'bi_check', 'pemberkasan_bank', 'akad', 'pencairan'].includes(newStatus)) {
                        icon.addClass('text-warning');
                    } else if (newStatus === 'acc') {
                        icon.addClass('text-info');
                    } else if (newStatus === 'bast') {
                        icon.addClass('text-primary');
                    } else if (newStatus === 'terjual') {
                        icon.addClass('text-danger');
                    } else if (newStatus === 'tidak_acc') {
                        icon.addClass('text-danger');
                    } else {
                        icon.addClass('text-secondary');
                    }
                }
                
                // Helper function untuk mendapatkan class badge berdasarkan status
                function getStatusBadgeClass(status) {
                    if (status === 'tersedia') return 'bg-success';
                    if (['booking_unit', 'bi_check', 'pemberkasan_bank', 'akad', 'pencairan'].includes(status)) return 'bg-warning text-dark';
                    if (status === 'acc') return 'bg-info';
                    if (status === 'bast') return 'bg-primary';
                    if (status === 'terjual') return 'bg-danger';
                    if (status === 'tidak_acc') return 'bg-danger';
                    return 'bg-secondary';
                }
                
                // Initial filter and statistics load
                filterCards();
            });
        </script>
    </x-slot>
</x-app-layout>