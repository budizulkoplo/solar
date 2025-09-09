<x-app-layout>
    <x-slot name="pagetitle">Dashboard</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="app-content">
        <div class="container-fluid my-4">
            <h2 class="mb-4">📊 Ringkasan Dashboard</h2>

            <div class="row g-4 mt-2">
                <!-- Jumlah Vendor -->
                <div class="col-md-3">
                    <div class="card shadow-sm text-white bg-primary">
                        <div class="card-body">
                            <h6 class="mb-2">Jumlah Vendor</h6>
                            <h3>{{ $jumlahVendors }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Jumlah Armada -->
                <div class="col-md-3">
                    <div class="card shadow-sm text-white bg-success">
                        <div class="card-body">
                            <h6 class="mb-2">Jumlah Armada</h6>
                            <h3>{{ $jumlahArmadas }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Jumlah Project -->
                <div class="col-md-3">
                    <div class="card shadow-sm text-white bg-warning">
                        <div class="card-body">
                            <h6 class="mb-2">Jumlah Project</h6>
                            <h3>{{ $jumlahProjects }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Total Input & Volume Project -->
                <div class="col-md-3">
                    <div class="card shadow-sm text-white bg-info">
                        <div class="card-body">
                            <h6 class="mb-2">Project Input & Volume</h6>
                            <ul class="mb-0" style="list-style:none; padding-left:0;">
                                @foreach($projectsSummary as $p)
                                    <li>
                                        <strong>{{ $p->nama_project }}</strong>: 
                                        {{ $p->jumlah_input }} input, 
                                        {{ number_format($p->total_volume, 2) }} m³
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    </div>

    <x-slot name="jscustom">
        
    </x-slot>
</x-app-layout>
