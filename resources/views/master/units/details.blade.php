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
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-info" onclick="showAllStatusStats()">
                            <i class="bi bi-bar-chart"></i> Lihat Semua Status
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="toggleAlurPenjualan()">
                            <i class="bi bi-diagram-3"></i> Lihat Alur
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Alur Penjualan (Hidden by Default) -->
        <div class="row mb-3 d-none" id="alurPenjualanCard">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white py-1">
                        <h6 class="mb-0">
                            <i class="bi bi-credit-card me-1"></i> Alur Penjualan Kredit
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            @php
                                $statusKredit = ['booking_unit', 'bi_check', 'pemberkasan_bank', 'acc', 'akad', 'pencairan', 'bast', 'terjual'];
                                $iconsKredit = [
                                    'booking_unit' => 'bi-handshake',
                                    'bi_check' => 'bi-file-earmark-check',
                                    'pemberkasan_bank' => 'bi-folder-check',
                                    'acc' => 'bi-check-all',
                                    'akad' => 'bi-file-earmark-text',
                                    'pencairan' => 'bi-cash-coin',
                                    'bast' => 'bi-house-check',
                                    'terjual' => 'bi-currency-dollar'
                                ];
                            @endphp
                            @foreach($statusKredit as $status)
                                <div class="text-center">
                                    <div class="mb-1">
                                        <i class="bi {{ $iconsKredit[$status] ?? 'bi-circle' }} fa-lg text-primary"></i>
                                    </div>
                                    <small class="d-block text-truncate" style="max-width: 80px;">
                                        {{ str_replace('_', ' ', ucfirst($status)) }}
                                    </small>
                                    @if(!$loop->last)
                                        <div class="mt-1">
                                            <i class="bi bi-arrow-right text-muted"></i>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white py-1">
                        <h6 class="mb-0">
                            <i class="bi bi-cash me-1"></i> Alur Penjualan Cash
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            @php
                                $statusCash = ['booking_unit', 'pemberkasan_notaris', 'akad', 'bast', 'terjual'];
                                $iconsCash = [
                                    'booking_unit' => 'bi-handshake',
                                    'pemberkasan_notaris' => 'bi-file-earmark-check',
                                    'akad' => 'bi-file-earmark-text',
                                    'bast' => 'bi-house-check',
                                    'terjual' => 'bi-currency-dollar'
                                ];
                            @endphp
                            @foreach($statusCash as $status)
                                <div class="text-center">
                                    <div class="mb-1">
                                        <i class="bi {{ $iconsCash[$status] ?? 'bi-circle' }} fa-lg text-success"></i>
                                    </div>
                                    <small class="d-block text-truncate" style="max-width: 80px;">
                                        {{ str_replace('_', ' ', ucfirst($status)) }}
                                    </small>
                                    @if(!$loop->last)
                                        <div class="mt-1">
                                            <i class="bi bi-arrow-right text-muted"></i>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
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
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small mb-1">Status</label>
                        <select class="form-select form-select-sm" name="status" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                            <option value="booking_unit" {{ request('status') == 'booking_unit' ? 'selected' : '' }}>Booking</option>
                            <option value="bi_check" {{ request('status') == 'bi_check' ? 'selected' : '' }}>BI Check</option>
                            <option value="pemberkasan_bank" {{ request('status') == 'pemberkasan_bank' ? 'selected' : '' }}>Pemberkasan Bank</option>
                            <option value="pemberkasan_notaris" {{ request('status') == 'pemberkasan_notaris' ? 'selected' : '' }}>Pemberkasan Notaris</option>
                            <option value="acc" {{ request('status') == 'acc' ? 'selected' : '' }}>ACC</option>
                            <option value="tidak_acc" {{ request('status') == 'tidak_acc' ? 'selected' : '' }}>Tidak ACC</option>
                            <option value="akad" {{ request('status') == 'akad' ? 'selected' : '' }}>Akad</option>
                            <option value="pencairan" {{ request('status') == 'pencairan' ? 'selected' : '' }}>Pencairan</option>
                            <option value="bast" {{ request('status') == 'bast' ? 'selected' : '' }}>BAST</option>
                            <option value="terjual" {{ request('status') == 'terjual' ? 'selected' : '' }}>Terjual</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small mb-1">Tipe Penjualan</label>
                        <select class="form-select form-select-sm" name="tipe_penjualan" id="filterTipePenjualan">
                            <option value="">Semua Tipe</option>
                            <option value="kredit" {{ request('tipe_penjualan') == 'kredit' ? 'selected' : '' }}>Kredit</option>
                            <option value="cash" {{ request('tipe_penjualan') == 'cash' ? 'selected' : '' }}>Cash</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-12">
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
            <div class="col-md-2 col-sm-6 mb-2">
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
            <div class="col-md-2 col-sm-6 mb-2">
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
            <div class="col-md-2 col-sm-6 mb-2">
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
            <div class="col-md-2 col-sm-6 mb-2">
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

            <!-- Cash -->
            <div class="col-md-2 col-sm-6 mb-2">
                <div class="card border-0 bg-success shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Penjualan Cash</h6>
                                <h4 class="mb-0 text-white" id="cashCount">0</h4>
                            </div>
                            <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-cash-stack fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kredit -->
            <div class="col-md-2 col-sm-6 mb-2">
                <div class="card border-0 bg-primary shadow-sm h-100">
                    <div class="card-body py-2 d-flex align-items-center">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <h6 class="mb-0">Penjualan Kredit</h6>
                                <h4 class="mb-0 text-white" id="kreditCount">0</h4>
                            </div>
                            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center"
                                style="width:42px; height:42px;">
                                <i class="bi bi-credit-card fs-5"></i>
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
                                @php
                                    $showCard = true;
                                    // Filter by status if set
                                    if (request('status') && request('status') != $detail->status) {
                                        $showCard = false;
                                    }
                                    // Filter by tipe_penjualan if set
                                    if (request('tipe_penjualan') && $detail->tipe_penjualan && request('tipe_penjualan') != $detail->tipe_penjualan) {
                                        $showCard = false;
                                    }
                                @endphp
                                
                                @if($showCard)
                                    <div class="col unit-card" 
                                         data-project="{{ $project->id }}"
                                         data-unit="{{ $unit->id }}"
                                         data-detail="{{ $detail->id }}"
                                         data-status="{{ $detail->status }}"
                                         data-tipe-penjualan="{{ $detail->tipe_penjualan ?? '' }}">
                                        <div class="card h-100 shadow-sm hover-shadow">
                                            <!-- Header dengan warna status -->
                                            <div class="card-header py-1 px-2 
                                                @if($detail->status === 'tersedia') bg-success text-white
                                                @elseif(in_array($detail->status, ['booking_unit', 'bi_check', 'pemberkasan_bank', 'pemberkasan_notaris', 'akad', 'pencairan'])) bg-warning text-dark
                                                @elseif($detail->status === 'acc') bg-info text-white
                                                @elseif($detail->status === 'bast') bg-primary text-white
                                                @elseif($detail->status === 'terjual') bg-danger text-white
                                                @elseif($detail->status === 'tidak_acc') bg-danger text-white
                                                @else bg-secondary text-white @endif">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <small class="fw-bold text-truncate">
                                                            {{ $project->namaproject }}
                                                        </small>
                                                        @if($detail->tipe_penjualan)
                                                            <span class="badge 
                                                                @if($detail->tipe_penjualan === 'cash') bg-success
                                                                @elseif($detail->tipe_penjualan === 'kredit') bg-primary
                                                                @else bg-secondary @endif">
                                                                {{ strtoupper($detail->tipe_penjualan) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm p-0 dropdown-toggle " type="button" 
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="bi bi-three-dots-vertical 
                                                                @if(in_array($detail->status, ['booking_unit', 'bi_check', 'pemberkasan_bank', 'pemberkasan_notaris', 'akad', 'pencairan'])) text-dark
                                                                @else text-white @endif"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end overflow-auto" style="max-height:300px;">
                                                            <li><h6 class="dropdown-header">Ubah Status</h6></li>
                                                            <!-- Status options akan di-update via JavaScript berdasarkan tipe penjualan -->
                                                            <li id="status-options-{{ $detail->id }}">
                                                                <!-- Options akan diisi oleh JavaScript -->
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
                                                            @elseif(in_array($detail->status, ['booking_unit', 'bi_check', 'pemberkasan_bank', 'pemberkasan_notaris', 'akad', 'pencairan'])) text-warning
                                                            @elseif($detail->status === 'acc') text-info
                                                            @elseif($detail->status === 'bast') text-primary
                                                            @elseif($detail->status === 'terjual') text-danger
                                                            @elseif($detail->status === 'tidak_acc') text-danger
                                                            @else text-secondary @endif
                                                        "></i>
                                                        {{ $unit->namaunit }} - Tipe: {{ $unit->tipe }}
                                                    </h6>
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
                                                            @elseif(in_array($detail->status, ['booking_unit', 'bi_check', 'pemberkasan_bank', 'pemberkasan_notaris', 'akad', 'pencairan'])) bg-warning text-dark
                                                            @elseif($detail->status === 'acc') bg-info
                                                            @elseif($detail->status === 'bast') bg-primary
                                                            @elseif($detail->status === 'terjual') bg-danger
                                                            @elseif($detail->status === 'tidak_acc') bg-danger
                                                            @else bg-secondary @endif
                                                        ">{{ str_replace('_', ' ', ucfirst($detail->status)) }}</span>
                                                    </div>
                                                    
                                                    <!-- Tampilkan alur status jika dalam proses -->
                                                    @if(in_array($detail->status, ['booking_unit', 'bi_check', 'pemberkasan_bank', 'pemberkasan_notaris', 'acc', 'akad', 'pencairan', 'bast']))
                                                    <div class="mt-1">
                                                        <small class="text-muted">Proses:</small>
                                                        <div class="progress" style="height: 5px;">
                                                            @php
                                                                // Tentukan alur berdasarkan tipe penjualan
                                                                if ($detail->tipe_penjualan === 'cash') {
                                                                    $statusOrder = ['booking_unit', 'pemberkasan_notaris', 'akad', 'bast', 'terjual'];
                                                                } else {
                                                                    // Default kredit
                                                                    $statusOrder = ['booking_unit', 'bi_check', 'pemberkasan_bank', 'acc', 'akad', 'pencairan', 'bast', 'terjual'];
                                                                }
                                                                $currentIndex = array_search($detail->status, $statusOrder);
                                                                $progress = $currentIndex !== false ? (($currentIndex + 1) / count($statusOrder)) * 100 : 0;
                                                            @endphp
                                                            <div class="progress-bar 
                                                                @if($detail->tipe_penjualan === 'cash') bg-success
                                                                @else bg-primary @endif" 
                                                                role="progressbar" 
                                                                style="width: {{ $progress }}%" 
                                                                aria-valuenow="{{ $progress }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100"></div>
                                                        </div>
                                                        <small class="text-muted">
                                                            @if($detail->tipe_penjualan === 'cash')
                                                                <!-- Alur Cash -->
                                                                @if($detail->status === 'booking_unit')
                                                                    <span class="badge bg-success">Booking</span>
                                                                    → Pemberkasan Notaris → Akad → BAST → Terjual
                                                                @elseif($detail->status === 'pemberkasan_notaris')
                                                                    Booking →
                                                                    <span class="badge bg-success">Pemberkasan Notaris</span>
                                                                    → Akad → BAST → Terjual
                                                                @elseif($detail->status === 'akad')
                                                                    Booking → Pemberkasan Notaris →
                                                                    <span class="badge bg-success">Akad</span>
                                                                    → BAST → Terjual
                                                                @elseif($detail->status === 'bast')
                                                                    Booking → Pemberkasan Notaris → Akad →
                                                                    <span class="badge bg-success">BAST</span>
                                                                    → Terjual
                                                                @endif
                                                            @else
                                                                <!-- Alur Kredit -->
                                                                @if($detail->status === 'booking_unit')
                                                                    <span class="badge bg-primary">Booking</span>
                                                                    → BI Check → Pemberkasan Bank → ACC → Akad → Pencairan → BAST → Terjual
                                                                @elseif($detail->status === 'bi_check')
                                                                    Booking →
                                                                    <span class="badge bg-primary">BI Check</span>
                                                                    → Pemberkasan Bank → ACC → Akad → Pencairan → BAST → Terjual
                                                                @elseif($detail->status === 'pemberkasan_bank')
                                                                    Booking → BI Check →
                                                                    <span class="badge bg-primary">Pemberkasan Bank</span>
                                                                    → ACC → Akad → Pencairan → BAST → Terjual
                                                                @elseif($detail->status === 'acc')
                                                                    Booking → BI Check → Pemberkasan Bank →
                                                                    <span class="badge bg-primary">ACC</span>
                                                                    → Akad → Pencairan → BAST → Terjual
                                                                @elseif($detail->status === 'akad')
                                                                    Booking → BI Check → Pemberkasan Bank → ACC →
                                                                    <span class="badge bg-primary">Akad</span>
                                                                    → Pencairan → BAST → Terjual
                                                                @elseif($detail->status === 'pencairan')
                                                                    Booking → BI Check → Pemberkasan Bank → ACC → Akad →
                                                                    <span class="badge bg-primary">Pencairan</span>
                                                                    → BAST → Terjual
                                                                @elseif($detail->status === 'bast')
                                                                    Booking → BI Check → Pemberkasan Bank → ACC → Akad → Pencairan →
                                                                    <span class="badge bg-primary">BAST</span>
                                                                    → Terjual
                                                                @endif
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

    <!-- Modal untuk Form Booking (Pemilihan Tipe Penjualan) -->
    <div class="modal fade" id="modalBookingType" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Tipe Penjualan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="bookingDetailId">
                    <input type="hidden" id="bookingNewStatus" value="booking_unit">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card text-center h-100 border-primary" style="cursor: pointer;" onclick="selectPenjualanType('kredit')">
                                <div class="card-body">
                                    <i class="bi bi-credit-card fa-3x text-primary mb-3"></i>
                                    <h5 class="card-title">Kredit</h5>
                                    <p class="card-text small">Booking → BI Check → Pemberkasan Bank → ACC → Akad → Pencairan → BAST → Terjual</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card text-center h-100 border-success" style="cursor: pointer;" onclick="selectPenjualanType('cash')">
                                <div class="card-body">
                                    <i class="bi bi-cash-stack fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Cash</h5>
                                    <p class="card-text small">Booking → Pemberkasan Notaris → Akad → BAST → Terjual</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <input type="hidden" name="booking_data[tipe_penjualan]" id="tipePenjualan">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Form Customer untuk Booking - 
                            <span id="modalTipePenjualan" class="badge bg-primary">Kredit</span>
                        </h5>
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
                                <label class="form-label">Booking (Rp) <span class="text-danger">*</span></label>
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
                    <input type="hidden" name="status" value="terjual">
                    
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
                                <label class="form-label">Booking (Rp)</label>
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
                
                // Inisialisasi dropdown status options
                initStatusOptions();
                
                // Toggle alur penjualan
                window.toggleAlurPenjualan = function() {
                    $('#alurPenjualanCard').toggleClass('d-none');
                }
                
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
                
                // Inisialisasi status options di dropdown
                function initStatusOptions() {
                    $('.unit-card').each(function() {
                        const detailId = $(this).data('detail');
                        const currentStatus = $(this).attr('data-status');
                        const tipePenjualan = $(this).attr('data-tipe-penjualan');
                        
                        updateStatusDropdown(detailId, currentStatus, tipePenjualan);
                    });
                }
                
                // Update dropdown status berdasarkan status saat ini dan tipe penjualan
                function updateStatusDropdown(detailId, currentStatus, tipePenjualan) {
                    const dropdownContainer = $('#status-options-' + detailId);
                    if (!dropdownContainer.length) return;
                    
                    let validStatuses = [];
                    
                    // Tentukan status berikutnya yang valid berdasarkan tipe penjualan
                    if (tipePenjualan === 'cash') {
                        // Alur penjualan Cash
                        const cashFlow = {
                            'tersedia': ['booking_unit'],
                            'booking_unit': ['tersedia', 'pemberkasan_notaris', 'tidak_acc'],
                            'pemberkasan_notaris': ['booking_unit', 'akad', 'tidak_acc'],
                            'tidak_acc': ['tersedia'],
                            'akad': ['pemberkasan_notaris', 'bast'],
                            'bast': ['akad', 'terjual'],
                            'terjual': []
                        };
                        validStatuses = cashFlow[currentStatus] || [];
                    } else {
                        // Default: Alur penjualan Kredit
                        const kreditFlow = {
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
                        validStatuses = kreditFlow[currentStatus] || [];
                    }
                    
                    // Icon dan warna untuk setiap status
                    const statusConfig = {
                        'tersedia': { icon: 'bi-check-circle', color: 'success', text: 'Tersedia' },
                        'booking_unit': { icon: 'bi-calendar-check', color: 'warning', text: 'Booking Unit' },
                        'bi_check': { icon: 'bi-file-earmark-check', color: 'info', text: 'BI Check' },
                        'pemberkasan_bank': { icon: 'bi-folder-check', color: 'primary', text: 'Pemberkasan Bank' },
                        'pemberkasan_notaris': { icon: 'bi-file-earmark-check', color: 'primary', text: 'Pemberkasan Notaris' },
                        'acc': { icon: 'bi-check-all', color: 'success', text: 'ACC' },
                        'tidak_acc': { icon: 'bi-x-circle', color: 'danger', text: 'Tidak ACC' },
                        'akad': { icon: 'bi-file-earmark-text', color: 'info', text: 'Akad' },
                        'pencairan': { icon: 'bi-cash-coin', color: 'primary', text: 'Pencairan' },
                        'bast': { icon: 'bi-house-check', color: 'success', text: 'BAST' },
                        'terjual': { icon: 'bi-currency-dollar', color: 'danger', text: 'Terjual' }
                    };
                    
                    // Kosongkan container
                    dropdownContainer.empty();
                    
                    // Tambahkan opsi untuk setiap status yang valid
                    validStatuses.forEach(status => {
                        const config = statusConfig[status] || { icon: 'bi-circle', color: 'secondary', text: status };
                        
                        const option = `
                            <button class="dropdown-item change-status" 
                                    data-id="${detailId}" 
                                    data-status="${status}">
                                <i class="bi ${config.icon} text-${config.color} me-2"></i>${config.text}
                            </button>
                        `;
                        dropdownContainer.append(option);
                    });
                }
                
                // Load statistics function
                function loadStatistics() {
                    // Ambil nilai filter
                    const projectId = $('#filterProject').val();
                    const unitId = $('#filterUnit').val();
                    const status = $('#filterStatus').val();
                    const tipePenjualan = $('#filterTipePenjualan').val();
                    
                    // Buat parameter URL
                    let params = new URLSearchParams();
                    if (projectId) params.append('project_id', projectId);
                    if (unitId) params.append('unit_id', unitId);
                    if (status) params.append('status', status);
                    if (tipePenjualan) params.append('tipe_penjualan', tipePenjualan);
                    
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
                        $('#cashCount').text(response.cash_count || 0);
                        $('#kreditCount').text(response.kredit_count || 0);
                        $('#tersediaPercent').text(response.tersedia_percent);
                        $('#bookingPercent').text(response.booking_percent);
                        $('#terjualPercent').text(response.terjual_percent);
                    }).fail(function(xhr) {
                        console.error('Failed to load statistics:', xhr.responseText);
                    });
                }
                
                // Show all status statistics
                window.showAllStatusStats = function() {
                    // Ambil nilai filter
                    const projectId = $('#filterProject').val();
                    const unitId = $('#filterUnit').val();
                    const tipePenjualan = $('#filterTipePenjualan').val();
                    
                    // Buat parameter URL
                    let params = new URLSearchParams();
                    if (projectId) params.append('project_id', projectId);
                    if (unitId) params.append('unit_id', unitId);
                    if (tipePenjualan) params.append('tipe_penjualan', tipePenjualan);
                    
                    const url = '/units/details/statistics?' + params.toString();
                    
                    $.get(url, function(response) {
                        if (response.error) return;
                        
                        Swal.fire({
                            title: 'Statistik Semua Status',
                            html: `
                                <div class="text-start">
                                    <p><strong>Total Unit:</strong> ${response.total}</p>
                                    <p><strong>Tersedia:</strong> ${response.tersedia} (${response.tersedia_percent}%)</p>
                                    <p><strong>Booking:</strong> ${response.booking} (${response.booking_percent}%)</p>
                                    <p><strong>BI Check:</strong> ${response.bi_check}</p>
                                    <p><strong>Pemberkasan Bank:</strong> ${response.pemberkasan_bank}</p>
                                    <p><strong>Pemberkasan Notaris:</strong> ${response.pemberkasan_notaris || 0}</p>
                                    <p><strong>ACC:</strong> ${response.acc}</p>
                                    <p><strong>Tidak ACC:</strong> ${response.tidak_acc}</p>
                                    <p><strong>Akad:</strong> ${response.akad}</p>
                                    <p><strong>Pencairan:</strong> ${response.pencairan}</p>
                                    <p><strong>BAST:</strong> ${response.bast}</p>
                                    <p><strong>Terjual:</strong> ${response.terjual} (${response.terjual_percent}%)</p>
                                    <hr>
                                    <p><strong>Penjualan Cash:</strong> ${response.cash_count || 0}</p>
                                    <p><strong>Penjualan Kredit:</strong> ${response.kredit_count || 0}</p>
                                    <p><strong>Belum Ditentukan:</strong> ${response.belum_ditentukan || 0}</p>
                                </div>
                            `,
                            showConfirmButton: true,
                            confirmButtonText: 'Tutup',
                            width: '600px'
                        });
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
                
                // Change tipe penjualan filter
                $('#filterTipePenjualan').change(function() {
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
                    const tipePenjualan = card.attr('data-tipe-penjualan');
                    
                    // Validasi alur status
                    if (!validateStatusFlow(currentStatus, newStatus, tipePenjualan)) {
                        showError(`Tidak bisa mengubah status dari ${formatStatus(currentStatus)} ke ${formatStatus(newStatus)}`);
                        return;
                    }
                    
                    // Jika status booking, tampilkan modal pemilihan tipe penjualan
                    if (newStatus === 'booking_unit') {
                        $('#bookingDetailId').val(detailId);
                        $('#modalBookingType').modal('show');
                        return;
                    }
                    
                    // Jika status terjual, cek dulu apakah sudah melalui semua tahap
                    if (newStatus === 'terjual') {
                        // Validasi berdasarkan tipe penjualan
                        if (tipePenjualan === 'cash') {
                            // Untuk cash, harus dari BAST
                            if (currentStatus !== 'bast') {
                                showError('Unit harus dalam status BAST sebelum bisa dijual (Cash)');
                                return;
                            }
                        } else {
                            // Untuk kredit, harus dari BAST
                            if (currentStatus !== 'bast') {
                                showError('Unit harus dalam status BAST sebelum bisa dijual (Kredit)');
                                return;
                            }
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
                                updateStatusDirect(detailId, newStatus, card, tipePenjualan);
                            }
                        });
                        return;
                    }
                    
                    // Untuk status lainnya langsung update
                    updateStatusDirect(detailId, newStatus, card, tipePenjualan);
                });

                // Fungsi untuk memilih tipe penjualan
                window.selectPenjualanType = function(tipe) {
                    const detailId = $('#bookingDetailId').val();
                    $('#modalBookingType').modal('hide');
                    
                    $('#detailId').val(detailId);
                    $('#statusChange').val('booking_unit');
                    $('#tipePenjualan').val(tipe);
                    
                    // Update badge di modal
                    const badgeClass = tipe === 'cash' ? 'bg-success' : 'bg-primary';
                    const badgeText = tipe === 'cash' ? 'CASH' : 'KREDIT';
                    $('#modalTipePenjualan').removeClass('bg-primary bg-success').addClass(badgeClass).text(badgeText);
                    
                    $('#modalCustomer').modal('show');
                }

                // Fungsi validasi alur status berdasarkan tipe penjualan
                function validateStatusFlow(currentStatus, newStatus, tipePenjualan) {
                    let validFlows;
                    
                    if (tipePenjualan === 'cash') {
                        // Alur penjualan Cash
                        validFlows = {
                            'tersedia': ['booking_unit'],
                            'booking_unit': ['tersedia', 'pemberkasan_notaris', 'tidak_acc'],
                            'pemberkasan_notaris': ['booking_unit', 'akad', 'tidak_acc'],
                            'tidak_acc': ['tersedia'],
                            'akad': ['pemberkasan_notaris', 'bast'],
                            'bast': ['akad', 'terjual'],
                            'terjual': []
                        };
                    } else {
                        // Default: Alur penjualan Kredit
                        validFlows = {
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
                    }
                    
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
                        'pemberkasan_notaris': 'Pemberkasan Notaris',
                        'acc': 'ACC',
                        'tidak_acc': 'Tidak ACC',
                        'akad': 'Akad',
                        'pencairan': 'Pencairan',
                        'bast': 'BAST',
                        'terjual': 'Terjual'
                    };
                    return statusMap[status] || status;
                }
                
                // Submit form customer (booking)
                $('#frmCustomer').submit(function(e) {
                    e.preventDefault();
                    
                    const detailId = $('#detailId').val();
                    const newStatus = $('#statusChange').val();
                    const tipePenjualan = $('#tipePenjualan').val();
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
                        booking_data: {
                            tipe_penjualan: tipePenjualan
                        }
                    };
                    
                    // Pisahkan data customer dan booking
                    formData.forEach(function(item) {
                        if (['_token', 'detail_id', 'status', 'booking_data[tipe_penjualan]'].includes(item.name)) {
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
                            // Remove 'booking_data[]' prefix jika ada
                            const key = item.name.replace('booking_data[', '').replace(']', '');
                            requestData.booking_data[key] = item.value;
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
                                
                                // Update data attributes
                                card.attr('data-status', newStatus);
                                card.attr('data-tipe-penjualan', tipePenjualan);
                                
                                updateCardStatus(card, newStatus, tipePenjualan);
                                updateStatusDropdown(detailId, newStatus, tipePenjualan);
                                
                                // Update badge tipe penjualan di header
                                const header = card.find('.card-header');
                                const existingBadge = header.find('.badge');
                                if (existingBadge.length) {
                                    existingBadge.remove();
                                }
                                
                                const badgeClass = tipePenjualan === 'cash' ? 'bg-success' : 'bg-primary';
                                header.find('.text-truncate').after(`
                                    <span class="badge ${badgeClass} ms-1">
                                        ${tipePenjualan.toUpperCase()}
                                    </span>
                                `);
                                
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
                                    
                                    // Hapus customer info lama jika ada
                                    card.find('.card-body .border-top').remove();
                                    card.find('.card-body').append(customerInfo);
                                }
                                
                                // Reload statistics
                                loadStatistics();
                                filterCards();
                            } else {
                                // TAMPILKAN ERROR YANG SESUAI DARI RESPONSE
                                showError(response.message || 'Terjadi kesalahan');
                            }
                        },
                        error: function(xhr) {
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            // TAMPILKAN ERROR YANG SESUAI DARI RESPONSE
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                showError(xhr.responseJSON.message);
                            } else if (xhr.status === 422) {
                                const errors = xhr.responseJSON.errors;
                                let errorMessage = 'Validasi gagal:\n';
                                for (const key in errors) {
                                    errorMessage += `• ${errors[key][0]}\n`;
                                }
                                showError(errorMessage);
                            } else if (xhr.status === 500) {
                                showError('Terjadi kesalahan server. Silakan coba lagi nanti.');
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
                        if (item.name !== '_token' && item.name !== 'detail_id' && item.name !== 'status') {
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
                                const tipePenjualan = card.attr('data-tipe-penjualan');
                                
                                // Update data attributes
                                card.attr('data-status', 'terjual');
                                
                                updateCardStatus(card, 'terjual', tipePenjualan);
                                updateStatusDropdown(detailId, 'terjual', tipePenjualan);
                                
                                // Reload statistics
                                loadStatistics();
                                filterCards();
                            } else {
                                // TAMPILKAN ERROR YANG SESUAI DARI RESPONSE
                                showError(response.message || 'Terjadi kesalahan');
                            }
                        },
                        error: function(xhr) {
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            // TAMPILKAN ERROR YANG SESUAI DARI RESPONSE
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                showError(xhr.responseJSON.message);
                            } else if (xhr.status === 422) {
                                const errors = xhr.responseJSON.errors;
                                let errorMessage = 'Validasi gagal:\n';
                                for (const key in errors) {
                                    errorMessage += `• ${errors[key][0]}\n`;
                                }
                                showError(errorMessage);
                            } else if (xhr.status === 500) {
                                showError('Terjadi kesalahan server. Silakan coba lagi nanti.');
                            } else {
                                showError('Terjadi kesalahan saat menyimpan data');
                            }
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
                                        <p><strong>Tipe Penjualan:</strong> <span class="badge ${detail.tipe_penjualan === 'cash' ? 'bg-success' : 'bg-primary'}">${detail.tipe_penjualan ? detail.tipe_penjualan.toUpperCase() : 'Belum ditentukan'}</span></p>
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
                                    <p><strong>Tipe Penjualan:</strong> ${data.booking.tipe_penjualan || '-'}</p>
<<<<<<< HEAD
                                    <p><strong>Booking:</strong> Rp ${data.booking.dp_awal.toLocaleString('id-ID')}</p>
=======
                                    <p><strong>DP Awal:</strong> Rp ${data.booking.dp_awal.toLocaleString('id-ID')}</p>
>>>>>>> 8819a3e43fcb44f2707daef5e1b8d584491b26b6
                                    <p><strong>Tanggal Booking:</strong> ${new Date(data.booking.tanggal_booking).toLocaleDateString('id-ID')}</p>
                                `;
                            }
                            
                            if (data.penjualan) {
                                html += `
                                    <hr>
                                    <h6>Informasi Penjualan</h6>
                                    <p><strong>Kode Penjualan:</strong> ${data.penjualan.kode_penjualan}</p>
                                    <p><strong>Tipe Penjualan:</strong> ${data.penjualan.tipe_penjualan || '-'}</p>
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
                
                function updateStatusDirect(detailId, newStatus, card, tipePenjualan) {
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
                                        
                                        // Update data attributes
                                        card.attr('data-status', newStatus);
                                        
                                        // Update card appearance
                                        updateCardStatus(card, newStatus, tipePenjualan);
                                        updateStatusDropdown(detailId, newStatus, tipePenjualan);
                                        
                                        // Jika status menjadi tidak_acc, otomatis kembali ke tersedia
                                        if (newStatus === 'tidak_acc') {
                                            setTimeout(() => {
                                                card.attr('data-status', 'tersedia');
                                                updateCardStatus(card, 'tersedia', '');
                                                updateStatusDropdown(detailId, 'tersedia', '');
                                                // Hapus data tipe_penjualan karena kembali ke tersedia
                                                card.attr('data-tipe-penjualan', '');
                                                // Hapus badge tipe penjualan
                                                card.find('.card-header .badge.bg-success, .card-header .badge.bg-primary').remove();
                                            }, 100);
                                        }
                                        
                                        // Reload statistics
                                        loadStatistics();
                                        
                                        // Re-filter cards jika ada filter status aktif
                                        filterCards();
                                    } else {
                                        // TAMPILKAN ERROR YANG SESUAI DARI RESPONSE
                                        showError(response.message || 'Terjadi kesalahan');
                                    }
                                },
                                error: function(xhr) {
                                    Swal.close();
                                    
                                    // TAMPILKAN ERROR YANG SESUAI DARI RESPONSE JSON
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        showError(xhr.responseJSON.message);
                                    } else if (xhr.status === 422) {
                                        const errors = xhr.responseJSON.errors;
                                        let errorMessage = 'Validasi gagal:\n';
                                        for (const key in errors) {
                                            errorMessage += `• ${errors[key][0]}\n`;
                                        }
                                        showError(errorMessage);
                                    } else if (xhr.status === 500) {
                                        showError('Terjadi kesalahan server. Silakan coba lagi nanti.');
                                    } else {
                                        showError('Terjadi kesalahan saat mengubah status');
                                    }
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
                    const tipePenjualan = $('#filterTipePenjualan').val();
                    let visibleCount = 0;
                    
                    $('.unit-card').each(function() {
                        const card = $(this);
                        const cardProject = card.data('project');
                        const cardUnit = card.data('unit');
                        
                        // PERBAIKAN: GUNAKAN ATTR() UNTUK MENDAPATKAN STATUS TERKINI
                        const cardStatus = card.attr('data-status') || card.data('status');
                        const cardTipePenjualan = card.attr('data-tipe-penjualan') || '';
                        
                        // Check filters
                        const projectMatch = !projectId || cardProject == projectId;
                        const unitMatch = !unitId || cardUnit == unitId;
                        const statusMatch = !status || cardStatus == status;
                        const tipeMatch = !tipePenjualan || cardTipePenjualan == tipePenjualan;
                        
                        if (projectMatch && unitMatch && statusMatch && tipeMatch) {
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
                function updateCardStatus(card, newStatus, tipePenjualan) {
                    const header = card.find('.card-header');
                    header.removeClass('bg-success bg-warning bg-danger bg-info bg-primary bg-secondary text-white text-dark');
                    
                    // Tentukan warna berdasarkan status
                    if (newStatus === 'tersedia') {
                        header.addClass('bg-success text-white');
                    } else if (['booking_unit', 'bi_check', 'pemberkasan_bank', 'pemberkasan_notaris', 'akad', 'pencairan'].includes(newStatus)) {
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
                    } else if (['booking_unit', 'bi_check', 'pemberkasan_bank', 'pemberkasan_notaris', 'akad', 'pencairan'].includes(newStatus)) {
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
                    
                    // Update progress bar dan alur status text
                    const progressContainer = card.find('.progress');
                    const progressBar = card.find('.progress-bar');
                    const alurText = card.find('.card-body small.text-muted');
                    
                    if (progressContainer.length && tipePenjualan) {
                        // Tentukan alur berdasarkan tipe penjualan
                        let statusOrder;
                        if (tipePenjualan === 'cash') {
                            statusOrder = ['booking_unit', 'pemberkasan_notaris', 'akad', 'bast', 'terjual'];
                        } else {
                            statusOrder = ['booking_unit', 'bi_check', 'pemberkasan_bank', 'acc', 'akad', 'pencairan', 'bast', 'terjual'];
                        }
                        
                        const currentIndex = statusOrder.indexOf(newStatus);
                        const progress = currentIndex !== -1 ? ((currentIndex + 1) / statusOrder.length) * 100 : 0;
                        
                        // Update progress bar
                        progressBar.css('width', progress + '%');
                        progressBar.attr('aria-valuenow', progress);
                        
                        // Update warna progress bar berdasarkan tipe
                        progressBar.removeClass('bg-success bg-primary');
                        progressBar.addClass(tipePenjualan === 'cash' ? 'bg-success' : 'bg-primary');
                        
                        // Update teks alur
                        if (alurText.length) {
                            let alurHtml = '';
                            
                            if (tipePenjualan === 'cash') {
                                const cashSteps = {
                                    'booking_unit': '<span class="badge bg-success">Booking</span> → Pemberkasan Notaris → Akad → BAST → Terjual',
                                    'pemberkasan_notaris': 'Booking → <span class="badge bg-success">Pemberkasan Notaris</span> → Akad → BAST → Terjual',
                                    'akad': 'Booking → Pemberkasan Notaris → <span class="badge bg-success">Akad</span> → BAST → Terjual',
                                    'bast': 'Booking → Pemberkasan Notaris → Akad → <span class="badge bg-success">BAST</span> → Terjual'
                                };
                                alurHtml = cashSteps[newStatus] || '';
                            } else {
                                const kreditSteps = {
                                    'booking_unit': '<span class="badge bg-primary">Booking</span> → BI Check → Pemberkasan Bank → ACC → Akad → Pencairan → BAST → Terjual',
                                    'bi_check': 'Booking → <span class="badge bg-primary">BI Check</span> → Pemberkasan Bank → ACC → Akad → Pencairan → BAST → Terjual',
                                    'pemberkasan_bank': 'Booking → BI Check → <span class="badge bg-primary">Pemberkasan Bank</span> → ACC → Akad → Pencairan → BAST → Terjual',
                                    'acc': 'Booking → BI Check → Pemberkasan Bank → <span class="badge bg-primary">ACC</span> → Akad → Pencairan → BAST → Terjual',
                                    'akad': 'Booking → BI Check → Pemberkasan Bank → ACC → <span class="badge bg-primary">Akad</span> → Pencairan → BAST → Terjual',
                                    'pencairan': 'Booking → BI Check → Pemberkasan Bank → ACC → Akad → <span class="badge bg-primary">Pencairan</span> → BAST → Terjual',
                                    'bast': 'Booking → BI Check → Pemberkasan Bank → ACC → Akad → Pencairan → <span class="badge bg-primary">BAST</span> → Terjual'
                                };
                                alurHtml = kreditSteps[newStatus] || '';
                            }
                            
                            if (alurHtml) {
                                alurText.html(alurHtml);
                            }
                        }
                    }
                }
                
                // Helper function untuk mendapatkan class badge berdasarkan status
                function getStatusBadgeClass(status) {
                    if (status === 'tersedia') return 'bg-success';
                    if (['booking_unit', 'bi_check', 'pemberkasan_bank', 'pemberkasan_notaris', 'akad', 'pencairan'].includes(status)) return 'bg-warning text-dark';
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