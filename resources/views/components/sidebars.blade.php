<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!-- Sidebar Brand -->
    @php
        use App\Models\Setting;
        $setting = Setting::first();

        function singkatPerusahaan($nama) {
            $parts = explode(' ', trim($nama));
            $result = '';
            foreach ($parts as $p) {
                if (strlen($p) > 2) { // ambil huruf depan kecuali "PT."
                    $result .= strtoupper(substr($p, 0, 1));
                }
            }
            return $result;
        }

        $namaPendek = singkatPerusahaan($setting->nama_perusahaan ?? 'Perusahaan');
    @endphp

    <div class="sidebar-brand text-center py-4">
<a href="{{ route('dashboard') }}">
    <img src="{{ asset($setting->path_logo ?? 'logo.png') }}" 
         alt="{{ $setting->nama_perusahaan }}" 
         class="brand-image opacity-75 shadow bg-white p-1 rounded"
         style="height: 50px;">
</a>

    <!-- <div class="brand-text nama-pt mt-2">
        {{ $namaPendek }}
    </div> -->
</div>

    <!-- Sidebar Wrapper -->
    <div class="sidebar-wrapper">

        @php
            function singkatNama($nama) {
                $parts = explode(' ', trim($nama));
                if (count($parts) <= 1) return $nama;

                $namaDepan = array_shift($parts);
                $inisial = array_map(fn($p) => strtoupper(substr($p, 0, 1)) . '.', $parts);

                return $namaDepan . ' ' . implode('', $inisial);
            }

            $displayName = singkatNama(auth()->user()->name ?? 'Guest');
        @endphp

        <div class="user-panel d-flex align-items-center p-3">
            <div class="image me-2">
                @if (isset(Auth::user()->foto) && Storage::disk('private')->exists("img/foto/".Auth::user()->foto))
                    <img src="{{ url('/doc/file/foto/'.Auth::user()->foto.'?t='. time()) }}"
                    class="img-circle elevation-2"
                    alt="User Image"
                    style="width: 40px; height: 40px; object-fit: cover;">
                @else
                    <img src="{{ asset('user.png') }}"
                    class="img-circle elevation-2"
                    alt="User Image"
                    style="width: 40px; height: 40px; object-fit: cover;">
                @endif
                
            </div>
            <div class="info">
                <a href="#" class="d-block text-white">{{ $displayName }}</a>

                @if(session('active_project_id'))
                    <a href="{{ route('choose.project') }}" 
                    class="d-flex align-items-center gap-1 text-warning text-decoration-none" 
                    style="font-size: 0.75rem; line-height: 1rem;">
                        <i class="fas fa-folder-open fa-sm"></i>
                        <span>{{ session('active_project_name') }}</span>
                    </a>
                @else
                    <a href="{{ route('choose.project') }}" 
                    class="d-flex align-items-center gap-1 text-muted text-decoration-none" 
                    style="font-size: 0.75rem; line-height: 1rem;">
                        <i class="fas fa-folder-plus fa-sm"></i>
                        <span>Pilih Project</span>
                    </a>
                @endif
        </div>

        </div>

        <!-- Search -->
        <div class="px-3 pt-2">
            <input type="text" id="menuSearch" class="form-control form-control-sm" placeholder="Cari menu...">
        </div>

        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                @foreach (request()->menu as $item)
                    @php
                        $p1 = explode(';', $item->role);
                        $lanjut = 0;
                        $isActiveParent = false;

                        if (!empty($item->children)) {
                            foreach ($item->children as $chl) {
                                $p2 = explode(';', $chl->role);
                                $inarray = array_intersect(auth()->user()->getRoleNames()->toArray(), $p2);
                                if ($inarray) $lanjut++;

                                if (Route::is($chl->link)) $isActiveParent = true;

                                if (!empty($chl->children)) {
                                    foreach ($chl->children as $chl2) {
                                        $p4 = explode(';', $chl2->role);
                                        $inarray2 = array_intersect(auth()->user()->getRoleNames()->toArray(), $p4);
                                        if ($inarray2 && Route::is($chl2->link)) $isActiveParent = true;
                                    }
                                }
                            }
                        }

                        if (array_intersect(auth()->user()->getRoleNames()->toArray(), $p1)) {
                            $lanjut++;
                        }

                        $itemActive = request()->routeIs($item->link);
                    @endphp

                    @if ($lanjut > 0)
                        <li class="nav-item menu-item {{ $isActiveParent || $itemActive ? 'menu-open' : '' }}">
                            <a href="{{ $item->children ? '#' : (Route::has($item->link) ? route($item->link) : '') }}"
                               class="nav-link menu-link {{ $itemActive || $isActiveParent ? 'active' : '' }}">
                                <i class="nav-icon {{ $item->icon }}"></i>
                                <p>
                                    {{ $item->name }}
                                    @if ($item->children)
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    @endif
                                </p>
                            </a>

                            @if ($item->children)
                                <ul class="nav nav-treeview submenu ps-4" style="{{ $isActiveParent ? 'display: block;' : '' }}">
                                    @foreach ($item->children as $chl)
                                        @php
                                            $p3 = explode(';', $chl->role);
                                            $inarray1 = array_intersect(auth()->user()->getRoleNames()->toArray(), $p3);
                                            $isActiveSub = Route::is($chl->link);
                                            $isActiveSubChild = false;

                                            if (!empty($chl->children)) {
                                                foreach ($chl->children as $chl2) {
                                                    if (Route::is($chl2->link)) {
                                                        $isActiveSubChild = true;
                                                    }
                                                }
                                            }
                                        @endphp

                                        @if ($inarray1)
                                            <li class="nav-item menu-item {{ $isActiveSubChild ? 'menu-open' : '' }}">
                                                @if ($chl->children)
                                                    <a href="#" class="nav-link menu-link {{ $isActiveSubChild ? 'active' : '' }}">
                                                        <i class="nav-icon {{ $chl->icon }}"></i>
                                                        <p>{{ $chl->name }}</p>
                                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                                    </a>
                                                    <ul class="nav nav-treeview submenu ps-5" style="{{ $isActiveSubChild ? 'display: block;' : '' }}">
                                                        @foreach ($chl->children as $chl2)
                                                            @php
                                                                $p4 = explode(';', $chl2->role);
                                                                $inarray2 = array_intersect(auth()->user()->getRoleNames()->toArray(), $p4);
                                                            @endphp
                                                            @if ($inarray2 && Route::has($chl2->link))
                                                                <li class="nav-item menu-item">
                                                                    <a href="{{ route($chl2->link) }}"
                                                                       class="nav-link menu-link {{ request()->routeIs($chl2->link) ? 'active' : '' }}">
                                                                        <i class="nav-icon {{ $chl2->icon }}"></i>
                                                                        <p>{{ $chl2->name }}</p>
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    @if (Route::has($chl->link))
                                                        <a href="{{ route($chl->link) }}"
                                                           class="nav-link menu-link {{ $isActiveSub ? 'active' : '' }}">
                                                            <i class="nav-icon {{ $chl->icon }}"></i>
                                                            <p>{{ $chl->name }}</p>
                                                        </a>
                                                    @endif
                                                @endif
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>
    </div>
</aside>

<script>
    document.getElementById('menuSearch').addEventListener('keyup', function () {
        const keyword = this.value.toLowerCase();
        const allItems = document.querySelectorAll('.menu-item');

        allItems.forEach(item => item.style.display = 'none'); 

        allItems.forEach(item => {
            const link = item.querySelector('.menu-link');
            if (!link) return;
            const text = link.textContent.toLowerCase();

            if (text.includes(keyword)) {
                item.style.display = 'block';

                let parent = item.parentElement;
                while (parent && parent.classList.contains('submenu')) {
                    parent.style.display = 'block';
                    parent = parent.parentElement.closest('.menu-item');
                    if (parent) parent.style.display = 'block';
                }
            }
        });
    });
</script>

<style>
    .menu-open > .submenu {
    display: block !important;
    }

    .nav-item.menu-item .nav-link.active {
        background-color: #0d6efd;
        color: #fff;
    }

    .submenu {
        display: none;
    }

    .nav-item.menu-item .nav-link.active {
        background-color: #0d6efd;
        color: #fff;
    }
    
    .label-fixed-width {
        min-width: 100px; /* Sesuaikan dengan panjang label terpanjang */
        text-align: right;
        justify-content: flex-end;
    }
    .tt-menu {
        width: 100%;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        z-index: 1000;
        max-height: 250px;
        overflow-y: auto;
    }
    .tt-suggestion {
        padding: 0.5rem 1rem;
        cursor: pointer;
    }
    .tt-suggestion:hover {
        background-color: #f8f9fa;
    }
</style>
