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
                @php
                    $user = Auth::user();
                @endphp
            
                @if ($user && $user->foto)
                    <img 
                        src="{{ asset('storage/uploads/karyawan/' . $user->foto) }}" 
                        alt="User Image"
                        class="elevation-2 shadow-sm"
                        style="
                            width: 40px; 
                            height: 40px; 
                            border-radius: 50%; 
                            object-fit: cover;
                            border: 2px solid #fff;
                        "
                        loading="lazy"
                    >
                @else
                    <img 
                        src="{{ asset('assets/img/avatar1.jpg') }}" 
                        alt="User Image"
                        class="elevation-2 shadow-sm"
                        style="
                            width: 40px; 
                            height: 40px; 
                            border-radius: 50%; 
                            object-fit: cover;
                            border: 2px solid #fff;
                        "
                        loading="lazy"
                    >
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

        @php
            use App\Models\Menu;

            $userRoles = auth()->user()->getRoleNames()->toArray();
            $allMenu = Menu::orderBy('seq')->get();

            // Ambil module aktif dari session
            $activeModule = session('active_project_module', null);

            function getChildMenu($allMenu, $parentId, $userRoles, $activeModule) {
                $children = $allMenu->filter(fn($m) => $m->parent_id == $parentId)
                                    ->filter(function($m) use ($userRoles, $activeModule) {
                                        $roles = array_filter(explode(';', $m->role));
                                        $hasRole = count(array_intersect($roles, $userRoles)) > 0;

                                        // Jika menu punya module, harus sama dengan active module
                                        $moduleOk = !$m->module || ($activeModule && $m->module == $activeModule);

                                        return $hasRole && $moduleOk;
                                    });

                return $children;
            }

            function renderMenu($allMenu, $parentId, $userRoles, $activeModule) {
                $menus = getChildMenu($allMenu, $parentId, $userRoles, $activeModule);

                foreach ($menus as $menu) {
                    $children = getChildMenu($allMenu, $menu->id, $userRoles, $activeModule);
                    $isActiveParent = request()->routeIs($menu->link) || ($children->contains(fn($c) => request()->routeIs($c->link)));

                    echo '<li class="nav-item menu-item ' . ($isActiveParent ? 'menu-open' : '') . '">';

                    $href = $menu->link && Route::has($menu->link) ? route($menu->link) : '#';
                    echo '<a href="' . $href . '" class="nav-link menu-link ' . ($isActiveParent ? 'active' : '') . '">';
                    echo '<i class="nav-icon ' . ($menu->icon ?? '') . '"></i>';
                    echo '<p>' . $menu->name;
                    if ($children->count()) echo ' <i class="nav-arrow bi bi-chevron-right"></i>';
                    echo '</p></a>';

                    if ($children->count()) {
                        echo '<ul class="nav nav-treeview submenu ps-4" style="' . ($isActiveParent ? 'display: block;' : '') . '">';
                        renderMenu($allMenu, $menu->id, $userRoles, $activeModule);
                        echo '</ul>';
                    }

                    echo '</li>';
                }
            }
        @endphp
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                @if(session('active_project_module'))
                    @php renderMenu($allMenu, null, $userRoles, session('active_project_module')); @endphp
                @else
                    <li class="nav-item">
                        <span class="text-muted ps-3">Pilih module/project terlebih dahulu</span>
                    </li>
                @endif
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
