<x-app-layout>
    <x-slot name="pagetitle">Project Management</x-slot>
    <x-slot name="csscustom">
        <style>
            .pm-project-card { height: 100%; border: 1px solid #e1e5eb; border-top: 4px solid var(--project-color); border-radius: 10px; background: #fff; box-shadow: 0 2px 7px rgba(15,23,42,.05); transition: transform .15s ease, box-shadow .15s ease; }
            .pm-project-card:hover { transform: translateY(-2px); box-shadow: 0 7px 16px rgba(15,23,42,.1); }
            .pm-project-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 14px 15px 8px; }
            .pm-project-name { margin: 0; color: #1f2937; font-size: 15px; font-weight: 700; line-height: 1.3; }
            .pm-project-desc { min-height: 38px; margin: 0; padding: 0 15px 12px; color: #64748b; font-size: 12px; line-height: 1.55; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            .pm-project-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 15px; border-top: 1px solid #edf0f4; }
            .pm-project-meta { color: #64748b; font-size: 11px; }
            .pm-project-actions { display: flex; gap: 5px; }
            .pm-project-actions form { display: inline; }
        </style>
    </x-slot>

    <div class="app-content-header"><div class="container-fluid d-flex justify-content-between align-items-center gap-2"><div><h3 class="mb-0"><i class="bi bi-kanban me-2"></i>Project Management</h3><small class="text-muted">Kelola pekerjaan dan kolaborasi dalam Solar System.</small></div>@if(auth()->user()->hasAnyRole(['superadmin', 'admin']))<a href="{{ route('pm.projects.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Project Baru</a>@endif</div></div>
    <div class="app-content"><div class="container-fluid">
        @include('pm.partials.flash')
        <div class="row g-3">
            @php($projectColors = ['#2563eb', '#7c3aed', '#0891b2', '#d97706', '#059669', '#db2777'])
            @forelse($projects as $project)
                @php($projectColor = $projectColors[$loop->index % count($projectColors)])
                <div class="col-12 col-md-6 col-xl-4"><div class="pm-project-card" style="--project-color: {{ $projectColor }}">
                    <div class="pm-project-head"><div><h5 class="pm-project-name">{{ $project->name }}</h5><span class="badge {{ $project->is_active ? 'text-bg-success' : 'text-bg-secondary' }} mt-2">{{ $project->is_active ? 'Aktif' : 'Nonaktif' }}</span></div><span class="badge text-bg-light">{{ $project->tasks_count }} tugas</span></div>
                    <p class="pm-project-desc">{{ $project->description ?: 'Belum ada deskripsi project.' }}</p>
                    <div class="pm-project-footer"><div class="pm-project-meta"><i class="bi bi-calendar3 me-1"></i>{{ $project->start_date?->format('d/m/Y') ?: 'Tanpa tanggal' }}</div><div class="d-flex align-items-center gap-2"><a href="{{ route('pm.projects.board', $project) }}" class="btn btn-primary btn-sm"><i class="bi bi-columns-gap me-1"></i>Board</a>@if(auth()->user()->hasAnyRole(['superadmin', 'admin']) || (int) $project->created_by === (int) auth()->id())<div class="pm-project-actions"><a href="{{ route('pm.projects.edit', $project) }}" class="btn btn-outline-secondary btn-sm" title="Edit"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('pm.projects.destroy', $project) }}" onsubmit="return confirm('Hapus project ini?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button></form></div>@endif</div></div>
                </div></div>
            @empty
                <div class="col-12"><div class="alert alert-info">Belum ada project PM.</div></div>
            @endforelse
        </div>
    </div></div>
</x-app-layout>
