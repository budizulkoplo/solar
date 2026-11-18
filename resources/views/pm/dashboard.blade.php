<x-app-layout>
    <x-slot name="pagetitle">Dashboard PM</x-slot>

    <x-slot name="csscustom">
        <style>
            .pm-dashboard { padding-bottom: 18px; }
            .pm-dashboard-title { margin: 0 0 28px; color: #101828; font-size: 29px; font-weight: 700; letter-spacing: -.4px; }
            .pm-stat-card, .pm-chart-card { border: 1px solid #e2e5e9; border-radius: 12px; background: #fff; box-shadow: 0 1px 2px rgba(16,24,40,.03); }
            .pm-stat-card { min-height: 142px; padding: 24px 25px 20px; }
            .pm-stat-label { color: #526174; font-size: 14px; }
            .pm-stat-value { margin: 7px 0 8px; color: #101828; font-size: 31px; font-weight: 500; line-height: 1; }
            .pm-stat-note { display: flex; align-items: center; gap: 5px; color: #667085; font-size: 13px; }
            .pm-stat-note i { font-size: 15px; }
            .pm-stat-note.pm-blue { color: #2563eb; }
            .pm-stat-note.pm-green { color: #12b76a; }
            .pm-stat-note.pm-gray { color: #98a2b3; }
            .pm-stat-icon { float: right; color: #d0d5dd; font-size: 20px; }
            .pm-chart-card { height: 100%; overflow: hidden; }
            .pm-chart-title { margin: 0; padding: 18px 25px; border-bottom: 1px solid #e4e7ec; color: #101828; font-size: 16px; font-weight: 600; }
            .pm-chart-body { position: relative; height: 245px; padding: 15px 18px 10px; }
            .pm-chart-card.pm-trend .pm-chart-body { height: 235px; }
            @media (max-width: 767.98px) {
                .pm-dashboard-title { margin-bottom: 20px; font-size: 25px; }
                .pm-stat-card { min-height: 125px; padding: 18px; }
                .pm-chart-body, .pm-chart-card.pm-trend .pm-chart-body { height: 260px; padding: 12px 8px 8px; }
            }
        </style>
    </x-slot>

    <x-slot name="jscustom">
        <script src="{{ asset('plugins/chart.umd.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Chart === 'undefined') return;

                const dashboardData = {
                    projects: @json($projectChart),
                    users: @json($userChart),
                    trend: @json($trendChart),
                };

                const axisColor = '#667085';
                const gridColor = '#e4e7ec';
                const commonOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: { padding: 10, displayColors: true },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: axisColor, font: { size: 11 }, maxRotation: 30, minRotation: 30 } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: axisColor, font: { size: 11 }, precision: 0 } },
                    },
                };

                new Chart(document.getElementById('pmProjectChart'), {
                    type: 'bar',
                    data: {
                        labels: dashboardData.projects.labels,
                        datasets: [{
                            label: 'Tickets',
                            data: dashboardData.projects.values,
                            backgroundColor: ['#98a2b3', '#a5b4fc', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                            borderRadius: 0,
                            borderSkipped: false,
                            barPercentage: .68,
                            categoryPercentage: .78,
                        }],
                    },
                    options: commonOptions,
                });

                new Chart(document.getElementById('pmUserChart'), {
                    type: 'bar',
                    data: {
                        labels: dashboardData.users.labels,
                        datasets: [
                            { label: 'Total Projects', data: dashboardData.users.projects, backgroundColor: '#3b82f6', borderRadius: 0, barPercentage: .7, categoryPercentage: .72 },
                            { label: 'Total Assigned Tickets', data: dashboardData.users.assigned, backgroundColor: '#12b981', borderRadius: 0, barPercentage: .7, categoryPercentage: .72 },
                        ],
                    },
                    options: {
                        ...commonOptions,
                        plugins: { ...commonOptions.plugins, legend: { display: true, position: 'top', align: 'center', labels: { boxWidth: 13, boxHeight: 13, color: axisColor, font: { size: 12 }, padding: 12 } } },
                    },
                });

                new Chart(document.getElementById('pmTrendChart'), {
                    type: 'line',
                    data: {
                        labels: dashboardData.trend.labels,
                        datasets: [{
                            label: 'Tickets Created',
                            data: dashboardData.trend.values,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,.12)',
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#3b82f6',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            borderWidth: 2,
                            fill: true,
                            tension: .35,
                        }],
                    },
                    options: {
                        ...commonOptions,
                        plugins: { ...commonOptions.plugins, legend: { display: true, position: 'top', labels: { boxWidth: 13, boxHeight: 13, color: axisColor, font: { size: 12 }, padding: 12 } } },
                        scales: { ...commonOptions.scales, x: { ...commonOptions.scales.x, ticks: { ...commonOptions.scales.x.ticks, minRotation: 0, maxRotation: 0 } } },
                    },
                });
            });
        </script>
    </x-slot>

    <div class="app-content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center gap-2">
            <div><h3 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h3><small class="text-muted">Ringkasan Project Management</small></div>
            <div class="d-flex gap-2"><a href="{{ route('pm.projects.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-kanban me-1"></i>Projects</a><a href="{{ route('pm.tasks.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-check2-square me-1"></i>Tickets</a></div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid pm-dashboard">
            <h1 class="pm-dashboard-title d-none">Dashboard</h1>

            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6 col-xl-3"><div class="pm-stat-card"><i class="pm-stat-icon bi bi-briefcase-fill"></i><div class="pm-stat-label">Total Projects</div><div class="pm-stat-value">{{ $stats['projects'] }}</div><div class="pm-stat-note pm-blue"><i class="bi bi-briefcase-fill"></i>{{ $stats['activeProjects'] }} active projects in the system</div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="pm-stat-card"><i class="pm-stat-icon bi bi-ticket-perforated-fill"></i><div class="pm-stat-label">Total Tickets</div><div class="pm-stat-value">{{ $stats['tasks'] }}</div><div class="pm-stat-note pm-green"><i class="bi bi-ticket-perforated-fill"></i>Tickets across all projects</div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="pm-stat-card"><i class="pm-stat-icon bi bi-person-check-fill"></i><div class="pm-stat-label">My Assigned Tickets</div><div class="pm-stat-value">{{ $stats['myTasks'] }}</div><div class="pm-stat-note pm-blue"><i class="bi bi-person-circle"></i>Tickets assigned to you</div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="pm-stat-card"><i class="pm-stat-icon bi bi-people-fill"></i><div class="pm-stat-label">Team Members</div><div class="pm-stat-value">{{ $stats['members'] }}</div><div class="pm-stat-note pm-gray"><i class="bi bi-people-fill"></i>Registered PM users</div></div></div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-6"><div class="pm-chart-card"><h2 class="pm-chart-title">Number of tickets per project</h2><div class="pm-chart-body"><canvas id="pmProjectChart"></canvas></div></div></div>
                <div class="col-12 col-xl-6"><div class="pm-chart-card"><h2 class="pm-chart-title">User Statistics Chart</h2><div class="pm-chart-body"><canvas id="pmUserChart"></canvas></div></div></div>
            </div>

            <div class="row g-3"><div class="col-12"><div class="pm-chart-card pm-trend"><h2 class="pm-chart-title">Monthly Ticket Creation Trend</h2><div class="pm-chart-body"><canvas id="pmTrendChart"></canvas></div></div></div></div>
        </div>
    </div>
</x-app-layout>
