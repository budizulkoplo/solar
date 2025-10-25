@extends('layouts.mobile')

@section('header')
<?php
if (!function_exists('secondsToTime')) {
    function secondsToTime($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf("%02d:%02d", $hours, $minutes);
    }
    
    // Fungsi baru untuk format jam:menit saja
    function formatJamRingkas($waktu) {
        if (empty($waktu)) return null;
        
        // Jika format sudah HH:MM, langsung return
        if (preg_match('/^\d{1,2}:\d{2}$/', $waktu)) {
            return $waktu;
        }
        
        // Jika format HH:MM:SS, ambil hanya HH:MM
        if (preg_match('/^(\d{1,2}:\d{2}):\d{2}$/', $waktu, $matches)) {
            return $matches[1];
        }
        
        // Jika format lain, coba parsing dengan Carbon
        try {
            $time = \Carbon\Carbon::parse($waktu);
            return $time->format('H:i');
        } catch (\Exception $e) {
            return $waktu; // Fallback ke nilai asli
        }
    }
}
?>
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Kalender Absensi</div>
    <div class="right"></div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
:root {
    --primary-color: #1976d2;
    --success-color: #198754;
    --warning-color: #ff6699;
    --info-color: #0d6efd;
    --danger-color: #d32f2f;
    --special-color: #ffbf00;
    --light-gray: #f5f5f5;
    --border-color: #e0e0e0;
    --today-highlight: #e3f2fd;
    --holiday-bg: #ffebee;
    --sunday-bg: #fff8e1;
}

.table { 
    width: 100%; 
    border-collapse: collapse; 
    table-layout: fixed; 
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}
.table th, .table td { 
    padding: 4px; 
    border: 1px solid var(--border-color); 
    vertical-align: top; 
}
.kalender-cell { 
    min-height: 85px;
    height: auto;
    position: relative; 
    background-color: #fff; 
    overflow: hidden; 
    font-size: 10px; 
    transition: all 0.2s ease;
}
.kalender-cell:active {
    transform: scale(0.97);
    background-color: var(--light-gray);
}
.date-label { 
    font-weight: bold; 
    font-size: 10px; 
    color: #333; 
    position: absolute; 
    top: 2px; 
    right: 2px; 
    background: rgba(255,255,255,0.9); 
    padding: 0 3px; 
    border-radius: 3px; 
    z-index: 1;
}
.cell-content { 
    padding: 2px; 
    padding-top: 18px;
    min-height: 65px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.shift-box { 
    font-size: 8pt !important; 
    padding: 2px 2px; 
    border-radius: 3px; 
    margin: 0;
    display: block; 
    width: 100%; 
    white-space: nowrap; 
    overflow: hidden; 
    text-overflow: ellipsis; 
    text-align: center; 
    flex-shrink: 0;
}
.jam-container { 
    display: flex; 
    align-items: center; 
    font-size: 8px !important; 
    line-height: 1.2; 
    margin: 0;
    flex-shrink: 0;
}
.jam-label { 
    color: #666; 
    width: 16px; 
    text-align: left; 
    flex-shrink: 0; 
    font-weight: bold;
    font-size: 7px !important;
}
.jam-value { 
    flex: 1; 
    white-space: nowrap; 
    overflow: hidden; 
    text-overflow: ellipsis; 
    font-family: monospace; 
    font-size: 9px !important;
    font-weight: bold;
}
.jam-value.terlambat { 
    color: var(--danger-color);
    font-size: 8px !important;
}
.terlambat-container { 
    font-size: 7px !important; 
    color: var(--danger-color); 
    margin: 0;
    white-space: nowrap; 
    overflow: hidden; 
    text-overflow: ellipsis; 
    font-weight: bold;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 2px;
}
.lembur-section {
    margin-top: 2px;
    padding-top: 2px;
    border-top: 1px dashed #ccc;
    flex-shrink: 0;
}
.lembur-indicator { 
    font-size: 7px !important; 
    color: #00796b; 
    font-weight: bold; 
    margin: 0 0 2px 0;
    line-height: 1.2; 
    flex-shrink: 0;
}
.lembur-container {
    display: flex;
    align-items: center;
    font-size: 7px !important;
    line-height: 1.2;
    margin: 1px 0;
    flex-shrink: 0;
}
.lembur-jam-value {
    font-family: monospace;
    font-weight: bold;
    font-size: 8px !important;
}
.status-khusus { 
    font-size: 7px !important; 
    margin: 0;
    white-space: nowrap; 
    overflow: hidden; 
    text-overflow: ellipsis; 
    color: #5d4037;
    font-weight: bold;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 2px;
    background-color: var(--danger-color); 
    color: #fff; 
    padding-left: 5px; 
}
.empty-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 8px;
    flex-grow: 1;
}
.libur-nasional { 
    background-color: var(--holiday-bg); 
}
.minggu { 
    background-color: var(--sunday-bg); 
}
.shift-pagi { 
    background-color: var(--success-color); 
    color: #fff; 
}
.shift-siang { 
    background-color: var(--warning-color); 
    color: #000; 
}
.shift-malam { 
    background-color: var(--info-color); 
    color: #fff; 
}
.libur { 
    background-color: #c9c9c9; 
    color: #000; 
}
.shift-gs { 
    background-color: var(--special-color); 
    color: #fff; 
}
.hari-ini { 
    box-shadow: inset 0 0 0 2px var(--primary-color);
    background-color: var(--today-highlight);
}
.holiday-list { 
    background: #fff; 
    border: 1px solid var(--border-color); 
    border-radius: 8px; 
    padding: 15px; 
    margin-top: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.holiday-item { 
    padding: 10px 0; 
    border-bottom: 1px solid var(--light-gray); 
    display: flex;
    align-items: center;
}
.holiday-item:last-child {
    border-bottom: none;
}
.holiday-date { 
    font-weight: bold; 
    color: #333; 
    width: 40%;
    flex-shrink: 0;
}
.holiday-event { 
    color: #666; 
    font-size: 14px; 
}
.holiday-icon {
    margin-right: 10px;
    color: var(--danger-color);
    flex-shrink: 0;
}

/* Filter section styling */
.filter-section {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.filter-title {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 10px;
    color: #333;
    display: flex;
    align-items: center;
}
.filter-title ion-icon {
    margin-right: 8px;
}

/* Legend styling */
.legend {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 15px;
    padding: 5px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.legend-item {
    display: flex;
    align-items: center;
    font-size: 10px;
    margin-right: 10px;
}
.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    margin-right: 5px;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 30px 15px;
    color: #666;
}
.empty-state ion-icon {
    font-size: 48px;
    margin-bottom: 10px;
    color: #ccc;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .kalender-cell { min-height: 75px; }
    .cell-content { min-height: 55px; }
    .date-label { font-size: 9px; }
    .shift-box, .jam-container { font-size: 7px !important; }
    .jam-label { width: 14px; font-size: 6px !important; }
    .jam-value { font-size: 8px !important; }
    .holiday-list { padding: 12px; }
    .holiday-item {
        flex-direction: column;
        align-items: flex-start;
    }
    .holiday-date {
        width: 100%;
        margin-bottom: 5px;
    }
    .table th { font-size: 9px; }
}

@media (max-width: 400px) {
    .kalender-cell { min-height: 70px; }
    .cell-content { min-height: 75px; }
    .date-label { font-size: 8px; }
    .table th { font-size: 8px; padding: 4px 1px; }
    .legend {
        justify-content: center;
    }
    .jam-label { width: 12px; }
    .jam-value { font-size: 7px !important; }
}

/* Animation for today's cell */
@keyframes pulse {
    0% { box-shadow: inset 0 0 0 2px var(--primary-color); }
    50% { box-shadow: inset 0 0 0 4px var(--primary-color); }
    100% { box-shadow: inset 0 0 0 2px var(--primary-color); }
}

.hari-ini {
    animation: pulse 2s infinite;
}

/* Ensure all rows have consistent height */
.table tbody tr {
    height: auto;
}

/* Dynamic height adjustment */
.dynamic-height {
    height: auto !important;
}

/* Compact time display */
.time-compact {
    font-family: 'Courier New', monospace;
    font-weight: bold;
}
</style>
@endsection

@section('content')
<div class="row" style="margin-top:70px">
    <div class="col">
        <div class="filter-section">
            <form method="post" action="{{ url()->current() }}">
                @csrf
                <div class="mb-2">
                    <input type="month" name="bulan" value="{{ $bulan }}" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <ion-icon name="calendar-outline"></ion-icon> Tampilkan Kalender
                </button>
            </form>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color shift-pagi"></div>
                <span>Shift Pagi</span>
            </div>
            <div class="legend-item">
                <div class="legend-color shift-siang"></div>
                <span>Shift Siang</span>
            </div>
            <div class="legend-item">
                <div class="legend-color shift-malam"></div>
                <span>Shift Malam</span>
            </div>
            <div class="legend-item">
                <div class="legend-color shift-gs"></div>
                <span>Shift GS</span>
            </div>
            <div class="legend-item">
                <div class="legend-color libur"></div>
                <span>Libur</span>
            </div>
        </div>

        @if (!empty($dataKalender))
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $day)
                                <th>{{ $day }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weeks as $mingguKe)
                            <tr>
                                @foreach ($mingguKe as $tgl)
                                    @php
                                        $data = $dataKalender[$tgl] ?? null;
                                        $dayOfWeek = \Carbon\Carbon::parse($tgl)->dayOfWeekIso;
                                        $isHoliday = isset($liburNasional[$tgl]);
                                        $cellClass = ($isHoliday ? 'libur-nasional' : '') . ($dayOfWeek == 7 ? ' minggu' : '');
                                        $isToday = \Carbon\Carbon::parse($tgl)->isToday() ? 'hari-ini' : '';

                                        $showAttendance = $data && (
                                            !empty($data['shift']) ||
                                            !empty($data['jam_masuk']) ||
                                            !empty($data['jam_pulang']) ||
                                            !empty($data['lembur_in']) ||
                                            !empty($data['lembur_out']) ||
                                            !empty($data['status_khusus'])
                                        );

                                        $shiftClass = '';
                                        $jamMasuk = $jamPulang = $lateSeconds = $lemburMasuk = $lemburPulang = null;
                                        $itemCount = 0;

                                        if ($showAttendance) {
                                            $shiftMap = [
                                                'pagi' => 'shift-pagi',
                                                'siang' => 'shift-siang',
                                                'malam' => 'shift-malam',
                                                'libur' => 'libur',
                                                'gs' => 'shift-gs',
                                            ];
                                            $shiftLower = strtolower($data['shift'] ?? '');
                                            $shiftClass = $shiftMap[$shiftLower] ?? '';

                                            // Format jam menjadi lebih ringkas
                                            $jamMasuk = formatJamRingkas($data['jam_masuk'] ?? null);
                                            $jamMasukShift = formatJamRingkas($data['jam_masuk_shift'] ?? null);
                                            $jamPulang = formatJamRingkas($data['jam_pulang'] ?? null);
                                            $lateSeconds = $data['terlambat'] ?? 0;

                                            $lemburMasuk = formatJamRingkas($data['lembur_in'] ?? null);
                                            $lemburPulang = formatJamRingkas($data['lembur_out'] ?? null);
                                            
                                            // Hitung jumlah item yang akan ditampilkan
                                            $itemCount = 0;
                                            if (!empty($data['shift'])) $itemCount++;
                                            if ($jamMasuk || $jamPulang) $itemCount += 2;
                                            if ($lateSeconds > 0) $itemCount++;
                                            if ($lemburMasuk || $lemburPulang) $itemCount += 2;
                                            if (!empty($data['status_khusus'])) $itemCount++;
                                        }
                                    @endphp
                                    <td class="kalender-cell {{ $cellClass }} {{ $isToday }} dynamic-height" data-items="{{ $itemCount }}">
                                        <div class="date-label">{{ \Carbon\Carbon::parse($tgl)->format('j') }}</div>
                                        <div class="cell-content">
                                            @if($showAttendance)
                                                @if(!empty($data['shift']))
                                                    <div class="shift-box {{ $shiftClass }}">{{ ucfirst($data['shift']) }}</div>
                                                @endif
                                                
                                                @if($jamMasuk || $jamPulang)
                                                    <div class="jam-container">
                                                        <span class="jam-label">IN</span>
                                                        <span class="jam-value {{ $lateSeconds > 0 ? 'terlambat' : '' }} time-compact">
                                                            {{ $jamMasuk ?? '-' }}
                                                        </span>
                                                    </div>
                                                    <div class="jam-container">
                                                        <span class="jam-label">OUT</span>
                                                        <span class="jam-value time-compact">{{ $jamPulang ?? '-' }}</span>
                                                    </div>
                                                @endif
                                                
                                                @if($lateSeconds > 0)
                                                    <div class="terlambat-container">
                                                        <span>⏱</span>
                                                        <span>{{ secondsToTime($lateSeconds) }}</span>
                                                    </div>
                                                @endif
                                                
                                                @if($lemburMasuk || $lemburPulang)
                                                    <div class="lembur-section">
                                                        <div class="lembur-indicator">LEMBUR</div>
                                                        @if($lemburMasuk)
                                                            <div class="lembur-container">
                                                                <span class="jam-label">IN</span>
                                                                <span class="lembur-jam-value time-compact">{{ $lemburMasuk }}</span>
                                                            </div>
                                                        @endif
                                                        @if($lemburPulang)
                                                            <div class="lembur-container">
                                                                <span class="jam-label">OUT</span>
                                                                <span class="lembur-jam-value time-compact">{{ $lemburPulang }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                                
                                                @if(!empty($data['status_khusus']))
                                                    <div class="status-khusus">
                                                        <span>📌</span>
                                                        <span>{{ $data['status_khusus'] }}</span>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="empty-cell">
                                                    @if($isHoliday || $dayOfWeek == 7)
                                                        Libur
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="holiday-list">
                <h5>
                    <ion-icon name="calendar-outline" class="holiday-icon"></ion-icon>
                    Hari Libur {{ \Carbon\Carbon::parse($bulan.'-01')->translatedFormat('F Y') }}
                </h5>
                @forelse ($liburBulanIni as $date => $event)
                    <div class="holiday-item">
                        <div class="holiday-date">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</div>
                        <div class="holiday-event">{{ $event }}</div>
                    </div>
                @empty
                    <div class="empty-state">
                        <ion-icon name="happy-outline"></ion-icon>
                        <p>Tidak ada hari libur nasional pada bulan ini.</p>
                    </div>
                @endforelse
            </div>
        @elseif(request()->isMethod('post'))
            <div class="alert alert-warning text-center">
                <ion-icon name="warning-outline" style="font-size: 24px; margin-bottom: 8px;"></ion-icon>
                <p class="mb-0">Tidak ada data absensi yang ditemukan untuk periode yang dipilih.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function() {
    $('.select2').select2({ placeholder: 'Pilih Pegawai', width: '100%' });
    
    // Highlight today's cell with animation
    $('.kalender-cell.hari-ini').css('background-color', 'var(--today-highlight)');
    
    // Add click effect for calendar cells
    $('.kalender-cell').on('click', function() {
        $(this).addClass('active');
        setTimeout(() => {
            $(this).removeClass('active');
        }, 200);
    });
    
    // Adjust row heights dynamically based on content
    function adjustRowHeights() {
        $('tbody tr').each(function() {
            let maxHeight = 0;
            $(this).find('.kalender-cell').each(function() {
                const height = $(this).outerHeight();
                if (height > maxHeight) {
                    maxHeight = height;
                }
            });
            
            // Set minimum height for consistency
            if (maxHeight < 85) {
                maxHeight = 85;
            }
            
            $(this).find('.kalender-cell').css('min-height', maxHeight + 'px');
        });
    }
    
    // Run adjustment after page load and window resize
    $(window).on('load resize', function() {
        setTimeout(adjustRowHeights, 100);
    });
    
    // Initial adjustment
    setTimeout(adjustRowHeights, 300);
    
    // Format waktu client-side juga untuk memastikan konsistensi
    $('.jam-value, .lembur-jam-value').each(function() {
        let timeText = $(this).text().trim();
        if (timeText !== '-' && timeText !== '') {
            // Hapus detik jika ada format HH:MM:SS
            if (timeText.match(/^\d{1,2}:\d{2}:\d{2}$/)) {
                $(this).text(timeText.substring(0, 5));
            }
        }
    });
});
</script>
@endsection