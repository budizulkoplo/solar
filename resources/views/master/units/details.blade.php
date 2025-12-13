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
                            <option value="booking" {{ request('status') == 'booking' ? 'selected' : '' }}>Booking</option>
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
        <div class="row mb-3" id="statsCards">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-light shadow-sm">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Unit</h6>
                                <h4 class="mb-0" id="totalUnits">0</h4>
                            </div>
                            <div class="bg-primary rounded-circle p-2">
                                <i class="bi bi-house text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-success bg-opacity-10 shadow-sm">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Tersedia</h6>
                                <h4 class="mb-0 text-success" id="tersediaCount">0</h4>
                                <small class="text-muted"><span id="tersediaPercent">0</span>%</small>
                            </div>
                            <div class="bg-success rounded-circle p-2">
                                <i class="bi bi-check-circle text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-warning bg-opacity-10 shadow-sm">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Booking</h6>
                                <h4 class="mb-0 text-warning" id="bookingCount">0</h4>
                                <small class="text-muted"><span id="bookingPercent">0</span>%</small>
                            </div>
                            <div class="bg-warning rounded-circle p-2">
                                <i class="bi bi-clock-history text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card border-0 bg-danger bg-opacity-10 shadow-sm">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Terjual</h6>
                                <h4 class="mb-0 text-danger" id="terjualCount">0</h4>
                                <small class="text-muted"><span id="terjualPercent">0</span>%</small>
                            </div>
                            <div class="bg-danger rounded-circle p-2">
                                <i class="bi bi-cash-coin text-white"></i>
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
                                         data-status="{{ $detail->status }}">
                                        <div class="card h-100 shadow-sm hover-shadow">
                                            <!-- Header dengan warna status -->
                                            <div class="card-header py-1 px-2 
                                                @if($detail->status === 'tersedia') bg-success text-white
                                                @elseif($detail->status === 'booking') bg-warning text-dark
                                                @else bg-danger text-white @endif">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="fw-bold text-truncate">
                                                        {{ $project->namaproject }}
                                                    </small>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm p-0" type="button" 
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="bi bi-three-dots-vertical text-white"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
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
                                                                        data-status="booking">
                                                                    <i class="bi bi-clock-history text-warning me-2"></i>Booking
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item change-status" 
                                                                        data-id="{{ $detail->id }}" 
                                                                        data-status="terjual">
                                                                    <i class="bi bi-cash-coin text-danger me-2"></i>Terjual
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Card Body -->
                                            <div class="card-body p-2">
                                                <!-- Nama Unit dan Tipe -->
                                                <div class="mb-1">
                                                    <h6 class="card-title mb-0 text-truncate" title="{{ $unit->namaunit }}">
                                                        <i class="fas fa-home me-1 text-primary"></i>
                                                        {{ $unit->namaunit }} 
                                                    </h6>
                                                    @if($unit->tipe)
                                                        <h6> - Tipe {{ $unit->tipe }} </h6>
                                                    @endif
                                                </div>
                                                
                                                <!-- Info Blok dan Jenis -->
                                                <div class="small mb-2">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Blok:</span>
                                                        <span class="fw-semibold">{{ $unit->blok ?? '-' }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Jenis:</span>
                                                        <span class="fw-semibold">{{ $unit->jenisUnit->jenisunit ?? '-' }}</span>
                                                    </div>
                                                </div>
                                                
                                                <!-- No Unit -->
                                                <div class="text-center my-2">
                                                    <div class="bg-light rounded p-2">
                                                        <small class="text-muted d-block">No Rumah</small>
                                                        <strong class="text-primary fs-5">{{ $detail->id }}</strong>
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

    <x-slot name="jscustom">
    <style>
        .unit-card {
            transition: transform 0.2s;
        }
        .unit-card:hover {
            transform: translateY(-3px);
        }
        .hover-shadow:hover {
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
        }
        .card {
            border-radius: 10px;
            overflow: hidden;
        }
        .card-header {
            padding: 0.25rem 0.5rem;
        }
        .card-title {
            font-size: 0.85rem;
            font-weight: 600;
        }
        .card-body {
            padding: 0.5rem;
            font-size: 0.8rem;
        }
        .card-footer {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>
    
    <script>
        $(document).ready(function() {
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
                const card = $(this).closest('.unit-card');
                
                Swal.fire({
                    title: 'Ubah Status?',
                    text: `Apakah Anda yakin ingin mengubah status unit menjadi ${newStatus}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Ubah!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading on button
                        const button = $(this);
                        const originalHtml = button.html();
                        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                        
                        // Gunakan URL langsung
                        $.ajax({
                            url: '/units/details/' + detailId + '/status',
                            type: 'PUT',
                            data: {
                                _token: '{{ csrf_token() }}',
                                status: newStatus
                            },
                            success: function(response) {
                                button.prop('disabled', false).html(originalHtml);
                                
                                if (response.success) {
                                    showSuccess(response.message);
                                    
                                    // Update card appearance
                                    updateCardStatus(card, newStatus);
                                    
                                    // Update card data attribute
                                    card.attr('data-status', newStatus);
                                    
                                    // Reload statistics
                                    loadStatistics();
                                    
                                    // Re-filter cards jika ada filter status aktif
                                    filterCards();
                                } else {
                                    showError(response.message);
                                }
                            },
                            error: function(xhr) {
                                button.prop('disabled', false).html(originalHtml);
                                showError('Terjadi kesalahan saat mengubah status');
                            }
                        });
                    }
                });
            });
            
            // Quick view modal
            $(document).on('click', '.card-body', function() {
                const unitName = $(this).find('.card-title').text().trim();
                const unitId = $(this).closest('.unit-card').find('.change-status').data('id');
                
                $('#unitModalBody').html(`
                    <div class="text-center">
                        <i class="fas fa-home fa-3x text-primary mb-3"></i>
                        <h6>${unitName}</h6>
                        <p class="mb-1">Unit ID: <strong>${unitId}</strong></p>
                        <small class="text-muted">Klik untuk melihat detail lengkap</small>
                    </div>
                `);
                $('#unitModal').modal('show');
            });
            
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
                    const cardStatus = card.data('status');
                    
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
            
            // Update card status appearance
            function updateCardStatus(card, newStatus) {
                const header = card.find('.card-header');
                header.removeClass('bg-success bg-warning bg-danger text-white text-dark');
                
                if (newStatus === 'tersedia') {
                    header.addClass('bg-success text-white');
                } else if (newStatus === 'booking') {
                    header.addClass('bg-warning text-dark');
                } else {
                    header.addClass('bg-danger text-white');
                }
                
                // Update status badge if exists
                const statusBadge = header.find('.badge');
                if (statusBadge.length) {
                    statusBadge.text(newStatus.toUpperCase());
                }
            }
            
            // Initial filter and statistics load
            filterCards();
        });
    </script>
</x-slot>
</x-app-layout>