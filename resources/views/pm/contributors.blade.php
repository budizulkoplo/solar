<x-app-layout>
    <x-slot name="pagetitle">Contributors</x-slot>
    <x-slot name="csscustom">
        <style>
            .pm-activity-page { padding-bottom: 18px; }
            .pm-page-title { margin: 0 0 5px; color: #101828; font-size: 29px; font-weight: 700; }
            .pm-page-subtitle { margin-bottom: 28px; color: #526174; font-size: 16px; }
            .pm-contributor-card { margin-bottom: 22px; padding: 20px 22px 22px; border: 1px solid #e4e7ec; border-radius: 12px; background: #fff; box-shadow: 0 1px 2px rgba(16,24,40,.03); }
            .pm-contributor-head { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
            .pm-contributor-avatar { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; color: #fff; font-size: 13px; font-weight: 700; }
            .pm-contributor-name { color: #101828; font-size: 16px; font-weight: 600; }
            .pm-contributor-period { color: #667085; font-size: 13px; }
            .pm-contributor-summary { display: grid; grid-template-columns: repeat(4, 1fr); margin-bottom: 18px; border: 1px solid #eaecf0; border-radius: 9px; }
            .pm-contributor-metric { padding: 21px 10px; text-align: center; }
            .pm-contributor-metric strong { display: block; color: #101828; font-size: 20px; line-height: 1.1; }
            .pm-contributor-metric span { display: block; margin-top: 5px; color: #344054; font-size: 12px; }
            .pm-activity-label { display: flex; align-items: center; justify-content: space-between; margin: 0 0 14px; color: #101828; font-size: 15px; font-weight: 600; }
            .pm-heat-legend { display: flex; align-items: center; gap: 4px; color: #667085; font-size: 12px; font-weight: 400; }
            .pm-heat-cell { display: block; width: 12px; height: 12px; border-radius: 1px; background: #eaecf0; }
            .pm-heat-cell.level-1 { background: #bbf7d0; } .pm-heat-cell.level-2 { background: #86efac; } .pm-heat-cell.level-3 { background: #22c55e; } .pm-heat-cell.level-4 { background: #166534; }
            .pm-heatmap { display: flex; gap: 4px; min-height: 112px; padding: 24px 28px; border: 1px solid #eaecf0; border-radius: 9px; overflow-x: auto; }
            .pm-heatmap-labels { display: grid; flex: 0 0 28px; grid-template-rows: repeat(7, 12px); gap: 3px; color: #526174; font-size: 11px; line-height: 12px; }
            .pm-heatmap-week { display: grid; flex: 0 0 12px; grid-template-rows: repeat(7, 12px); gap: 3px; }
            .pm-empty-contributors { padding: 45px 20px; border: 1px dashed #d0d5dd; border-radius: 10px; color: #667085; text-align: center; }
            @media (max-width: 767.98px) {
                .pm-page-title { font-size: 25px; }
                .pm-contributor-card { padding: 16px; }
                .pm-contributor-summary { grid-template-columns: repeat(2, 1fr); }
                .pm-contributor-metric { padding: 15px 8px; }
                .pm-heatmap { padding: 20px 14px; }
            }
        </style>
    </x-slot>

    <div class="app-content-header"><div class="container-fluid d-flex justify-content-between align-items-center gap-2"><div><h3 class="mb-0"><i class="bi bi-people me-2"></i>Contributors</h3><small class="text-muted">Aktivitas kontribusi member Project Management</small></div><a href="{{ route('pm.leaderboard') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-trophy me-1"></i>Leaderboard</a></div></div>
    <div class="app-content"><div class="container-fluid pm-activity-page">
        @if(!$contributors)
            <div class="pm-empty-contributors">Belum ada data contributor.</div>
        @else
            @foreach($contributors as $contributor)
                <section class="pm-contributor-card">
                    <div class="pm-contributor-head"><span class="pm-contributor-avatar" style="background: {{ $contributor['color'] }}">{{ $contributor['initials'] }}</span><div><div class="pm-contributor-name">{{ $contributor['name'] }}</div><div class="pm-contributor-period">{{ $periodLabel }}</div></div></div>
                    <div class="pm-contributor-summary"><div class="pm-contributor-metric"><strong>{{ $contributor['tickets'] }}</strong><span>Tickets Created</span></div><div class="pm-contributor-metric"><strong>{{ $contributor['updates'] }}</strong><span>Status Changes</span></div><div class="pm-contributor-metric"><strong>{{ $contributor['comments'] }}</strong><span>Comments Made</span></div><div class="pm-contributor-metric"><strong>{{ $contributor['active_days'] }}</strong><span>Active Days</span></div></div>
                    <div class="pm-activity-label"><span>Daily Contributions</span><span class="pm-heat-legend">Less <i class="pm-heat-cell"></i><i class="pm-heat-cell level-1"></i><i class="pm-heat-cell level-2"></i><i class="pm-heat-cell level-3"></i><i class="pm-heat-cell level-4"></i> More</span></div>
                    <div class="pm-heatmap"><div class="pm-heatmap-labels"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>@foreach($contributor['weeks'] as $week)<div class="pm-heatmap-week">@foreach($week as $day)<i class="pm-heat-cell level-{{ $day['level'] }}" title="{{ $day['date'] }}: {{ $day['count'] }} aktivitas"></i>@endforeach</div>@endforeach</div>
                </section>
            @endforeach
        @endif
    </div></div>
</x-app-layout>
