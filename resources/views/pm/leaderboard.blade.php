<x-app-layout>
    <x-slot name="pagetitle">Contribution Leaderboard</x-slot>
    <x-slot name="csscustom">
        <style>
            .pm-leaderboard-page { padding-bottom: 18px; }
            .pm-page-title { margin: 0 0 5px; color: #101828; font-size: 29px; font-weight: 700; }
            .pm-page-subtitle { margin-bottom: 30px; color: #526174; font-size: 16px; }
            .pm-leaderboard-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 20px; }
            .pm-leaderboard-filter { min-width: 132px; }
            .pm-leaderboard-card, .pm-score-card { border: 1px solid #e4e7ec; border-radius: 12px; background: #fff; box-shadow: 0 1px 2px rgba(16,24,40,.03); }
            .pm-leaderboard-card { padding: 22px 24px 24px; }
            .pm-section-title { margin: 0; color: #101828; font-size: 16px; font-weight: 600; }
            .pm-section-subtitle { margin: 4px 0 26px; color: #667085; font-size: 13px; }
            .pm-rank-row { display: flex; align-items: center; gap: 14px; margin: 0 0 12px; padding: 18px 20px; border: 1px solid #eaecf0; border-radius: 9px; }
            .pm-rank-number { flex: 0 0 32px; color: #526174; font-size: 13px; text-align: center; }
            .pm-rank-medal { display: block; font-size: 19px; }
            .pm-rank-avatar { display: inline-flex; flex: 0 0 40px; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; color: #fff; font-size: 12px; font-weight: 700; }
            .pm-rank-info { min-width: 180px; flex: 1; }
            .pm-rank-name { color: #101828; font-size: 16px; font-weight: 600; }
            .pm-rank-score { margin-top: 4px; color: #667085; font-size: 13px; }
            .pm-rank-metrics { display: grid; grid-template-columns: repeat(4, 75px); gap: 14px; text-align: center; }
            .pm-rank-metrics strong { display: block; color: #101828; font-size: 18px; line-height: 1.1; }
            .pm-rank-metrics span { display: block; margin-top: 5px; color: #526174; font-size: 11px; }
            .pm-score-card { margin-top: 22px; padding: 20px 24px 24px; }
            .pm-formula { margin-top: 22px; padding: 16px; border-radius: 8px; background: #f8fafc; color: #344054; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; }
            .pm-empty-leaderboard { padding: 45px 20px; border: 1px dashed #d0d5dd; border-radius: 9px; color: #667085; text-align: center; }
            @media (max-width: 767.98px) {
                .pm-page-title { font-size: 25px; }
                .pm-leaderboard-toolbar { align-items: stretch; flex-direction: column; }
                .pm-rank-row { align-items: flex-start; flex-wrap: wrap; padding: 14px; }
                .pm-rank-info { min-width: calc(100% - 100px); }
                .pm-rank-metrics { width: 100%; grid-template-columns: repeat(4, 1fr); gap: 5px; }
            }
        </style>
    </x-slot>

    <div class="app-content-header"><div class="container-fluid d-flex justify-content-between align-items-center gap-2"><div><h3 class="mb-0"><i class="bi bi-trophy me-2"></i>Contribution Leaderboard</h3><small class="text-muted">Kontributor teratas berdasarkan aktivitas dan engagement</small></div><a href="{{ route('pm.contributors') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-people me-1"></i>Contributors</a></div></div>
    <div class="app-content"><div class="container-fluid pm-leaderboard-page">
        <form class="pm-leaderboard-toolbar" method="GET" action="{{ route('pm.leaderboard') }}"><div><label class="form-label small mb-1">Periode</label><select name="days" class="form-select form-select-sm pm-leaderboard-filter"><option value="7" @selected($days === 7)>Last 7 Days</option><option value="30" @selected($days === 30)>Last 30 Days</option><option value="90" @selected($days === 90)>Last 90 Days</option></select></div><div class="d-flex align-items-end gap-2"><div><label class="form-label small mb-1">Show Top</label><select name="top" class="form-select form-select-sm pm-leaderboard-filter"><option value="5" @selected($top === 5)>Top 5</option><option value="10" @selected($top === 10)>Top 10</option><option value="20" @selected($top === 20)>Top 20</option></select></div><button class="btn btn-primary btn-sm mb-0"><i class="bi bi-filter me-1"></i>Apply</button></div></form>
        <section class="pm-leaderboard-card"><h2 class="pm-section-title">🏆 Contribution Leaderboard</h2><div class="pm-section-subtitle">{{ $periodLabel }}</div>@if(!$contributors)<div class="pm-empty-leaderboard">Belum ada data kontribusi pada periode ini.</div>@else @foreach($contributors as $index => $contributor)<div class="pm-rank-row"><div class="pm-rank-number">@if($index === 0)<span class="pm-rank-medal">🏆</span>@elseif($index === 1)<span class="pm-rank-medal">🥈</span>@elseif($index === 2)<span class="pm-rank-medal">🥉</span>@else#{{ $index + 1 }}@endif</div><span class="pm-rank-avatar" style="background: {{ $contributor['color'] }}">{{ $contributor['initials'] }}</span><div class="pm-rank-info"><div class="pm-rank-name">{{ $contributor['name'] }}</div><div class="pm-rank-score">Total Score: <strong>{{ $contributor['score'] }}</strong></div></div><div class="pm-rank-metrics"><div><strong>{{ $contributor['tickets'] }}</strong><span>Tickets</span></div><div><strong>{{ $contributor['updates'] }}</strong><span>Updates</span></div><div><strong>{{ $contributor['comments'] }}</strong><span>Comments</span></div><div><strong>{{ $contributor['active_days'] }}</strong><span>Active Days</span></div></div></div>@endforeach @endif</section>
        <section class="pm-score-card"><h2 class="pm-section-title">📊 Scoring System</h2><div class="pm-section-subtitle">Cara perhitungan skor kontribusi</div><div class="pm-formula"><strong>Score Calculation Formula:</strong><br><br>Total Score = (Status Changes × 5) + (Tickets Created × 2) + (Comments Made × 2) + (Active Days × 1)</div></section>
    </div></div>
</x-app-layout>
