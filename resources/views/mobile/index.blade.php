@extends('layouts.mobile')

@section('content')

<link rel="stylesheet" href="{{ asset('assets/css/homes.css') }}">

<div id="user-section">
    <div id="user-detail">
        <div class="profil">
            @if($user->foto)
                <img src="{{ asset('storage/foto/' . $user->foto) }}" alt="avatar" loading="lazy">
            @else
                <img src="{{ asset('assets/img/avatar1.jpg') }}" alt="avatar" loading="lazy">
            @endif
        </div>
        
        <div id="user-info">
            @php
                use App\Models\Setting;
                $setting = Setting::first();

                function singkatPerusahaan($nama) {
                    $parts = explode(' ', trim($nama));
                    $result = '';
                    foreach ($parts as $p) {
                        if (strlen($p) > 2) {
                            $result .= strtoupper(substr($p, 0, 1));
                        }
                    }
                    return $result;
                }

                $namaPendek = singkatPerusahaan($setting->nama_perusahaan ?? 'Perusahaan');
            @endphp
            <div id="user-role">{{ $setting->nama_perusahaan ?? 'Perusahaan' }}</div>
            <h3>{{ $user->name ?? 'Nama User' }}</h3>
            <div id="user-role">Unit: {{ $user->unitkerja?->company_name ?? '-' }}</div>
        </div>
    </div>
</div>

<!-- Rekap Presensi Section -->
<div class="performance-card mb-3">
    <div class="todaypresence">
        <div class="rekappresensi">
            <h3 class="section-title">
                Rekap Presensi Bulan {{ $namabulan[$bulanini] ?? 'Bulan' }} Tahun {{ $tahunini ?? date('Y') }}
            </h3>

            <div class="row text-center">
                @php
                    $presensiData = [
                        ['label' => 'Hadir', 'icon' => 'accessibility-outline', 'value' => $rekappresensi->jmlhadir ?? 0, 'color' => 'primary'],
                        ['label' => 'Izin', 'icon' => 'newspaper-outline', 'value' => $rekapizin->jmlizin ?? 0, 'color' => 'warning'],
                        ['label' => 'Sakit', 'icon' => 'medkit-outline', 'value' => $rekapizin->jmlsakit ?? 0, 'color' => 'danger'],
                        ['label' => 'Telat', 'icon' => 'alarm-outline', 'value' => $rekappresensi->jmlterlambat ?? 0, 'color' => 'danger'],
                    ];
                @endphp

                @foreach($presensiData as $data)
                <div class="col-3 mb-2">
                    <div class="card stat-card">
                        <div class="card-body position-relative">
                            @if($data['value'] > 0)
                                <span class="badge bg-danger position-absolute count-badge">
                                    {{ $data['value'] }}
                                </span>
                            @endif
                            <ion-icon name="{{ $data['icon'] }}" class="text-{{ $data['color'] }} stat-icon"></ion-icon>
                            <br>
                            <span class="stat-label">{{ $data['label'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Task Management Summary -->
<div class="performance-card mb-3">
    <div class="todaypresence">
        <div class="rekappresensi">
            <h3 class="section-title">Task Management</h3>
            <div class="row text-center">
                @if($ticketSummary->count() > 0)
                    @foreach($ticketSummary as $status => $count)
                        @php
                            $color = match(strtolower($status)) {
                                'to do' => 'secondary',
                                'in progress' => 'info',
                                'review' => 'warning',
                                'done' => 'success',
                                default => 'dark'
                            };
                        @endphp
                        <div class="col-3 mb-2">
                            <div class="card stat-card">
                                <div class="card-body position-relative">
                                    <span class="badge bg-{{ $color }} position-absolute count-badge">
                                        {{ $count }}
                                    </span>
                                    <ion-icon name="clipboard-outline" class="text-{{ $color }} stat-icon"></ion-icon>
                                    <br>
                                    <span class="stat-label">{{ $status }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <p class="text-muted small">Tidak ada tiket</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="section-title text-white" id="ticketModalLabel">Daftar Tiket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="list-group" id="ticketList">
          <!-- List tiket akan diisi lewat JS -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Tabs Bulan Ini & Leaderboard -->
<div class="performance-card">
    <div class="todaypresence">
        <div class="presencetab">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item flex-fill">
                    <a class="nav-link active text-center" data-toggle="tab" href="#bulanIni" role="tab">
                        <ion-icon name="calendar-outline" class="tab-icon"></ion-icon>
                        <span>Bulan Ini</span>
                    </a>
                </li>
                <li class="nav-item flex-fill">
                    <a class="nav-link text-center" data-toggle="tab" href="#leaderboard" role="tab">
                        <ion-icon name="trophy-outline" class="tab-icon"></ion-icon>
                        <span>Leaderboard</span>
                    </a>
                </li>
            </ul>
            <div class="tab-content">
                <!-- Bulan Ini -->
                <div class="tab-pane fade show active" id="bulanIni" role="tabpanel">
                    <div class="tab-section">
                        <h5 class="tab-section-title">Riwayat Presensi Bulan Ini</h5>
                        <ul class="listview image-listview stylish-presence">
                            @if(count($rekapPresensiBulanIni) > 0)
                                @foreach ($rekapPresensiBulanIni as $tanggal => $data)
                                    @php
                                        \Carbon\Carbon::setLocale('id');
                                        $tglLabel = \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y');
                                        $jamMasuk = $data['masuk']->jam_in ?? null;
                                        $jamPulang = $data['pulang']->jam_in ?? null;
                                        $jamMasukShift = $data['jam_masuk_shift'];
                                    @endphp

                                    <li class="presence-card">
                                        <div class="presence-header">
                                            <ion-icon name="calendar-outline" class="text-primary"></ion-icon>
                                            <span class="presence-date">{{ $tglLabel }}</span>
                                        </div>

                                        <div class="presence-body">
                                            <div class="presence-item">
                                                <div class="presence-icon-container">
                                                    <ion-icon name="log-in-outline" class="text-success"></ion-icon>
                                                </div>
                                                <div class="presence-info">
                                                    <small class="presence-label">Absen Masuk</small>
                                                    <h6 class="presence-time {{ $jamMasuk && $jamMasuk > $jamMasukShift ? 'text-danger' : 'text-success' }}">
                                                        {{ $jamMasuk ? \Carbon\Carbon::parse($jamMasuk)->format('H:i') : '-' }}
                                                    </h6>
                                                </div>
                                            </div>

                                            <div class="presence-divider"></div>

                                            <div class="presence-item">
                                                <div class="presence-icon-container">
                                                    <ion-icon name="log-out-outline" class="text-danger"></ion-icon>
                                                </div>
                                                <div class="presence-info">
                                                    <small class="presence-label">Absen Pulang</small>
                                                    <h6 class="presence-time {{ $jamPulang ? 'text-danger' : 'text-muted' }}">
                                                        {{ $jamPulang ? \Carbon\Carbon::parse($jamPulang)->format('H:i') : 'Belum absen' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @else
                                <li class="empty-state">
                                    <ion-icon name="calendar-outline"></ion-icon>
                                    <p>Tidak ada data presensi bulan ini</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="tab-pane fade" id="leaderboard" role="tabpanel">
                    <div class="tab-section">
                        <h5 class="tab-section-title">Leaderboard Presensi</h5>
                        <ul class="listview image-listview leaderboard-presence">
                            @if(count($leaderboard) > 0)
                                @foreach ($leaderboard->whereNotNull('jam_masuk')->sortBy('jam_masuk') as $d)
                                    @php
                                        $jamMasuk = $d->jam_masuk ?? null;
                                        $jamPulang = $d->jam_pulang ?? null;
                                    @endphp
                                    <li>
                                        <div class="leaderboard-item">
                                            <div class="leaderboard-left">
                                                <div class="avatar">
                                                    @if(!empty($d->foto_in))
                                                        <img src="{{ asset('storage/uploads/absensi/' . $d->foto_in) }}" alt="Foto Absen Masuk" loading="lazy">
                                                    @else
                                                        <img src="{{ asset('assets/img/avatar1.jpg') }}" alt="avatar" loading="lazy">
                                                    @endif
                                                </div>
                                                <div class="user-info">
                                                    <b class="user-name">{{ $d->name ?? '-' }}</b>
                                                    <small class="user-position">{{ $d->jabatan ?? '-' }}</small>
                                                </div>
                                            </div>

                                            <div class="leaderboard-right">
                                                <div class="time-row">
                                                    <span class="time-label">Masuk:</span>
                                                    <span class="time-badge {{ isset($jamMasukShift) && $jamMasuk && $jamMasuk > $jamMasukShift ? 'badge-late' : 'badge-ontime' }}">
                                                        {{ $jamMasuk ? \Carbon\Carbon::parse($jamMasuk)->format('H:i') : '-' }}
                                                    </span>
                                                </div>
                                                <div class="time-row">
                                                    <span class="time-label">Pulang:</span>
                                                    <span class="time-badge {{ $jamPulang ? 'badge-pulang' : 'badge-nopulang' }}">
                                                        {{ $jamPulang ? \Carbon\Carbon::parse($jamPulang)->format('H:i') : '-' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @else
                                <li class="empty-state">
                                    <ion-icon name="trophy-outline"></ion-icon>
                                    <p>Tidak ada data leaderboard</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* General Styles */
.performance-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.section-title {
    font-size: 1.1rem;
    margin-bottom: 16px;
    color: #2c3e50;
    font-weight: 600;
    text-align: center;
    padding-bottom: 0;
}

/* Stat Cards */
.stat-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card .card-body {
    padding: 12px 8px !important;
    line-height: 0.8rem;
    position: relative;
}

.count-badge {
    top: 2px;
    right: 5px;
    font-size: 0.55rem;
    z-index: 999;
}

.stat-icon {
    font-size: 1.4rem;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.7rem;
    font-weight: 500;
    color: #495057;
}

/* Tabs */
.nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 16px;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    border-radius: 6px 6px 0 0;
    font-size: 0.7rem;
    padding: 2px 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1px;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: #f8f9fa;
    border-bottom: 3px solid #007bff;
}

.tab-icon {
    font-size: 1.2rem;
}

/* Tab Sections */
.tab-section {
    background-color: transparent;
    border-radius: 8px;
    padding: 0;
}

.tab-section-title {
    font-size: 0.95rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 12px; /* Dikurangi dari 16px */
    color: #2c3e50;
    padding-bottom: 0;
    border-bottom: none;
}

/* Presence Cards - DIKURANGI JARAKNYA */
.stylish-presence .presence-card {
    background: #fff;
    border-radius: 8px; /* Dikurangi dari 10px */
    padding: 10px 12px; /* Dikurangi dari 16px */
    margin-bottom: 8px; /* Dikurangi dari 12px */
    box-shadow: 0 1px 4px rgba(0,0,0,0.08); /* Shadow lebih kecil */
    border: 1px solid #e9ecef;
    border-left: 3px solid #007bff; /* Border lebih tipis */
}

.presence-header {
    display: flex;
    align-items: center;
    gap: 6px; /* Dikurangi dari 8px */
    font-weight: 600;
    font-size: 0.8rem; /* Lebih kecil */
    margin-bottom: 8px; /* Dikurangi dari 12px */
    color: #2c3e50;
}

.presence-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px; /* Dikurangi dari 16px */
    padding: 4px 0; /* Dikurangi dari 8px 0 */
}

.presence-item {
    display: flex;
    align-items: center;
    gap: 6px; /* Dikurangi dari 8px */
    flex: 1;
}

.presence-icon-container {
    background: #f8f9fa;
    border-radius: 6px; /* Lebih kecil */
    padding: 4px; /* Dikurangi dari 6px */
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 28px; /* Ukuran tetap */
}

.presence-icon-container ion-icon {
    font-size: 0.9rem; /* Lebih kecil */
}

.presence-info {
    flex: 1;
}

.presence-label {
    display: block;
    font-size: 0.65rem; /* Lebih kecil */
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 1px; /* Tambahkan sedikit spacing */
}

.presence-time {
    font-size: 0.8rem; /* Lebih kecil */
    margin: 0;
    font-weight: 600;
    line-height: 1.2;
}

.presence-divider {
    width: 1px;
    height: 24px; /* Dikurangi dari 30px */
    background: #e9ecef;
    margin: 0 6px; /* Dikurangi dari 8px */
}

/* Leaderboard Cards - Juga disesuaikan */
.leaderboard-presence .leaderboard-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px; /* Dikurangi dari 12px 16px */
    background: #fff;
    border-radius: 8px;
    margin-bottom: 8px; /* Dikurangi dari 10px */
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    border-left: 3px solid #28a745;
}

.leaderboard-left {
    display: flex;
    align-items: center;
    gap: 10px; /* Dikurangi dari 12px */
    flex: 1;
}

.leaderboard-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 3px; /* Dikurangi dari 4px */
}

.avatar img {
    width: 36px; /* Dikurangi dari 40px */
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #e9ecef; /* Border lebih tipis */
}

.profil img {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

.user-name {
    font-size: 0.85rem; /* Lebih kecil */
    color: #2c3e50;
    margin: 0;
    line-height: 1.2;
}

.user-position {
    font-size: 0.7rem; /* Lebih kecil */
    color: #6c757d;
    line-height: 1.2;
}

.time-badge {
    font-size: 0.65rem; /* Lebih kecil */
    font-weight: 500;
    padding: 3px 6px; /* Dikurangi dari 4px 8px */
    border-radius: 4px;
    min-width: 55px; /* Lebih kecil */
    text-align: center;
}

.badge-ontime {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.badge-late {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f1b0b7;
}

.badge-pulang {
    background: #ffeaa7;
    color: #856404;
    border: 1px solid #ffda6a;
}

.badge-nopulang {
    background: #e9ecef;
    color: #495057;
    border: 1px solid #dee2e6;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 30px 20px; /* Dikurangi dari 40px 20px */
    color: #6c757d;
}

.empty-state ion-icon {
    font-size: 2.5rem; /* Lebih kecil */
    color: #ced4da;
    margin-bottom: 8px; /* Dikurangi */
}

.empty-state p {
    font-size: 0.85rem; /* Lebih kecil */
    margin: 0;
    color: #6c757d;
}

/* Menghilangkan semua garis HR yang tidak diperlukan */
.listview.image-listview::before,
.listview.image-listview::after,
.presence-card::before,
.presence-card::after,
.leaderboard-item::before,
.leaderboard-item::after {
    display: none !important;
}

/* Memastikan tidak ada border tambahan */
.stylish-presence,
.leaderboard-presence {
    border: none !important;
}

.performance-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

/* Ticket Description Styles */
.ticket-description {
    line-height: 1.5;
}

.ticket-description p {
    margin-bottom: 0.5rem;
}

.ticket-description figure {
    margin: 1rem 0;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.ticket-description img {
    max-width: 100%;
    height: auto;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ticket-description .attachment__caption {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.5rem;
    text-align: center;
}

.ticket-description .attachment__name {
    font-weight: 500;
}

.ticket-description .attachment__size {
    color: #868e96;
}

/* Responsive images */
.img-fluid {
    max-width: 100%;
    height: auto;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const allTickets = @json($userTickets ?? []);
    const modalEl = document.getElementById('ticketModal');
    const list = document.getElementById('ticketList');

    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', function () {
            const status = this.querySelector('.stat-label').textContent.trim();
            const filtered = allTickets.filter(t => 
                t.ticket_status && t.ticket_status.toLowerCase() === status.toLowerCase()
            );

            list.innerHTML = '';

            if (!filtered.length) {
                list.innerHTML = `<div class="text-center p-3 text-muted">
                    Tidak ada tiket dengan status <b>${status}</b>.
                </div>`;
            } else {
                filtered.forEach(ticket => {
                    const listItem = document.createElement('div');
                    listItem.className = 'list-group-item list-group-item-action border-bottom';
                    
                    // Process description to render HTML properly
                    const processedDescription = decodeHtmlEntities(ticket.description || '');
                    
                    listItem.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-dark">${ticket.ticket_name || '-'}</h6>
                            <span class="badge bg-${getStatusColor(ticket.ticket_status)}">
                                ${ticket.ticket_status}
                            </span>
                        </div>
                        <small class="text-muted d-block mb-2">${ticket.status_created_at || '-'}</small>
                        <div class="ticket-description mb-2">
                            ${processedDescription}
                        </div>
                        <small class="text-muted d-block">Mulai: ${ticket.start_date || '-'} | Deadline: ${ticket.due_date || '-'}</small>
                    `;
                    list.appendChild(listItem);
                });
            }

            // Simple show modal
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Add backdrop
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        });
    });

    // Manual close handlers
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            modalEl.style.display = 'none';
            modalEl.classList.remove('show');
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
        });
    });

    // Close when clicking outside
    modalEl.addEventListener('click', function(e) {
        if (e.target === modalEl) {
            modalEl.style.display = 'none';
            modalEl.classList.remove('show');
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
        }
    });

    // Function to decode HTML entities
    function decodeHtmlEntities(text) {
        if (!text) return '<span class="text-muted">Tidak ada deskripsi</span>';
        
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        const decoded = textArea.value;
        
        // Process images to make them responsive
        return processImages(decoded);
    }

    // Function to process images and make them responsive
    function processImages(html) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Process all images
        const images = tempDiv.querySelectorAll('img');
        images.forEach(img => {
            img.classList.add('img-fluid', 'rounded');
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            
            // Wrap image in a container if it's inside a figure
            const figure = img.closest('figure');
            if (figure) {
                figure.style.margin = '10px 0';
                figure.style.textAlign = 'center';
            }
        });
        
        // Process links to open in new tab
        const links = tempDiv.querySelectorAll('a');
        links.forEach(link => {
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        });
        
        return tempDiv.innerHTML;
    }

    function getStatusColor(status) {
        if (!status) return 'dark';
        switch(status.toLowerCase()) {
            case 'done': return 'success';
            case 'review': return 'warning';
            case 'in progress': return 'info';
            case 'to do': return 'secondary';
            default: return 'dark';
        }
    }
});
</script>

@endsection