<x-app-layout>
    <x-slot name="pagetitle">Unit Details</x-slot>

    <div class="container-fluid">
        <h3 class="mb-3">Daftar Unit</h3>

        <div class="row">
            @foreach($projects as $project)
                @if($project->units->isNotEmpty())
                    <div class="col-md-12 mb-4">
                        <div class="card card-info card-outline shadow-sm">
                            {{-- Header dengan garis warna --}}
                            <div class="card-header pt-2 pb-2">
                                <h5 class="mb-0">{{ $project->namaproject }}</h5>
                            </div>

                            {{-- Body project --}}
                            <div class="card-body">
                                <div class="row">
                                    @foreach($project->units as $unit)
                                        @foreach($unit->details as $detail)
                                            <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                                                {{-- Jika terjual: outline merah + isi abu2 --}}
                                                <div class="card h-100 text-center shadow-sm 
                                                    @if($detail->status === 'terjual') border-danger bg-light text-muted 
                                                    @endif">
                                                    <div class="card-body d-flex flex-column justify-content-center">
                                                        
                                                        {{-- Nama & Blok --}}
                                                        <h6 class="fw-bold mb-1">{{ $unit->namaunit ?? '-' }}</h6>
                                                        <p class="mb-2">Blok: {{ $unit->blok ?? '-' }}</p>

                                                        {{-- Icon Rumah --}}
                                                        <i class="fas fa-home fa-3x mb-3
                                                            @if($detail->status === 'tersedia') text-success
                                                            @elseif($detail->status === 'booking') text-warning
                                                            @elseif($detail->status === 'terjual') text-danger
                                                            @endif">
                                                        </i>

                                                        {{-- Detail Info --}}
                                                        <p class="small mb-2">
                                                            Unit ID: {{ $detail->id }}
                                                        </p>

                                                        {{-- Badge Status --}}
                                                        <span class="badge px-3 py-2 
                                                            @if($detail->status === 'tersedia') bg-success 
                                                            @elseif($detail->status === 'booking') bg-warning text-dark
                                                            @elseif($detail->status === 'terjual') bg-danger
                                                            @endif">
                                                            {{ ucfirst($detail->status) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-app-layout>
