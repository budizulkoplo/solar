<x-app-layout>
    <x-slot name="pagetitle">Transaksi Pekerjaan Konstruksi</x-slot>

    <div class="container-fluid py-2">
        <!-- Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Transaksi Pekerjaan Konstruksi</h4>
                        <small class="text-muted">Kelola transaksi untuk setiap pekerjaan konstruksi</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Status Pekerjaan</label>
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">Semua Status</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Jenis Pekerjaan</label>
                        <select class="form-select form-select-sm" id="filterJenis">
                            <option value="">Semua Jenis</option>
                            @foreach($jenisPekerjaan as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
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

        <!-- Construction Projects Grid -->
        <div class="row" id="constructionGrid">
            @foreach($pekerjaan as $item)
                <div class="col-md-4 col-sm-6 mb-3 construction-item" 
                     data-status="{{ $item->status }}"
                     data-jenis="{{ $item->jenis_pekerjaan }}">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <div class="card-header py-2 
                            @if($item->status === 'planning') bg-secondary
                            @elseif($item->status === 'ongoing') bg-warning
                            @elseif($item->status === 'completed') bg-success
                            @else bg-light @endif text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="fw-bold">{{ $item->project->namaproject ?? '-' }}</small>
                                <span class="badge bg-light text-dark">
                                    {{ $statuses[$item->status] ?? $item->status }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body p-3">
                            <h6 class="card-title mb-2">{{ $item->nama_pekerjaan }}</h6>
                            
                            <div class="small mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Jenis:</span>
                                    <span class="fw-semibold">{{ $jenisPekerjaan[$item->jenis_pekerjaan] ?? $item->jenis_pekerjaan }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Lokasi:</span>
                                    <span class="fw-semibold">{{ $item->lokasi ?? '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Anggaran:</span>
                                    <span class="fw-semibold text-primary">
                                        Rp {{ number_format($item->anggaran ?? 0, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            @php
                                $progress = $item->getProgressAttribute();
                                $progressClass = '';
                                if ($item->status === 'completed') {
                                    $progressClass = 'bg-success';
                                    $progress = 100;
                                } elseif ($item->status === 'ongoing') {
                                    $progressClass = 'bg-warning progress-bar-striped progress-bar-animated';
                                } elseif ($item->status === 'planning') {
                                    $progressClass = 'bg-secondary';
                                    $progress = 0;
                                }
                            @endphp
                            
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small">
                                    <span>Progress</span>
                                    <span>{{ round($progress, 0) }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar {{ $progressClass }}" role="progressbar" 
                                         style="width: {{ $progress }}%"></div>
                                </div>
                            </div>
                            
                            <!-- Transaction Summary -->
                            @php
                                $totalTransaksi = \App\Models\Nota::where('pekerjaan_konstruksi_id', $item->id)
                                    ->where('cashflow', 'out')
                                    ->sum('total');
                                $sisaAnggaran = $item->anggaran - $totalTransaksi;
                                $persentase = $item->anggaran > 0 ? ($totalTransaksi / $item->anggaran) * 100 : 0;
                            @endphp
                            
                            <div class="small">
                                <div class="d-flex justify-content-between">
                                    <span>Total Transaksi:</span>
                                    <span class="fw-semibold">
                                        Rp {{ number_format($totalTransaksi, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Sisa Anggaran:</span>
                                    <span class="fw-semibold 
                                        @if($sisaAnggaran < 0) text-danger
                                        @elseif($sisaAnggaran < $item->anggaran * 0.1) text-warning
                                        @else text-success @endif">
                                        Rp {{ number_format($sisaAnggaran, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer py-2 bg-transparent border-top">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('construction.transactions.detail', $item->id) }}" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-receipt"></i> Lihat Transaksi
                                </a>
                                <a href="{{ route('construction.transactions.create', $item->id) }}" 
                                   class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> Tambah
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Empty State -->
        <div id="emptyState" class="text-center d-none">
            <div class="py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5>Tidak ada pekerjaan konstruksi</h5>
                <p class="text-muted">Belum ada data pekerjaan konstruksi</p>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function() {
                // Filter construction items
                function filterItems() {
                    const status = $('#filterStatus').val();
                    const jenis = $('#filterJenis').val();
                    let visibleCount = 0;
                    
                    $('.construction-item').each(function() {
                        const item = $(this);
                        const itemStatus = item.data('status');
                        const itemJenis = item.data('jenis');
                        
                        // Check filters
                        const statusMatch = !status || itemStatus == status;
                        const jenisMatch = !jenis || itemJenis == jenis;
                        
                        if (statusMatch && jenisMatch) {
                            item.show();
                            visibleCount++;
                        } else {
                            item.hide();
                        }
                    });
                    
                    // Update empty state
                    if (visibleCount === 0) {
                        $('#emptyState').removeClass('d-none');
                    } else {
                        $('#emptyState').addClass('d-none');
                    }
                }
                
                // Filter button
                $('#btnFilter').click(function() {
                    filterItems();
                });
                
                // Reset filter
                $('#btnReset').click(function() {
                    $('#filterStatus, #filterJenis').val('');
                    filterItems();
                });
                
                // Initial filter
                filterItems();
            });
        </script>
        
        <style>
            .hover-shadow:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
                transform: translateY(-2px);
                transition: all 0.3s ease;
            }
            
            .progress-bar-animated {
                animation: progress-bar-stripes 1s linear infinite;
            }
            
            @keyframes progress-bar-stripes {
                0% { background-position: 1rem 0; }
                100% { background-position: 0 0; }
            }
        </style>
    </x-slot>
</x-app-layout>