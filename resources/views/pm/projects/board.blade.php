<x-app-layout>
    <x-slot name="pagetitle">Board - {{ $project->name }}</x-slot>
    <x-slot name="csscustom">
        <style>
            .pm-board-toolbar { padding: 10px; border: 1px solid #d7dce3; border-radius: 8px; background: #fff; }
            .pm-filter-label { display: block; margin-bottom: 4px; color: #667085; font-size: 11px; font-weight: 600; line-height: 1.2; }
            .pm-filter-check { display: flex; align-items: center; min-height: 31px; margin: 0; padding: 0 10px; border: 1px solid #d7dce3; border-radius: 5px; background: #fff; color: #344054; font-size: 12px; }
            .pm-filter-check .form-check-input { margin: 0 7px 0 0; }
            .pm-filter-action { width: 100%; min-height: 31px; }
            .pm-kanban { display: flex; gap: 14px; overflow-x: auto; align-items: flex-start; padding: 2px 2px 14px; }
            .pm-kanban-column { flex: 0 0 286px; min-height: 420px; border: 1px solid #d7dce3; border-radius: 0; background: #f5f7fb; overflow: hidden; }
            .pm-stage-header { display: flex; align-items: center; justify-content: space-between; min-height: 48px; padding: 10px 12px; border-radius: 0; color: #fff; background: var(--stage-color); }
            .pm-stage-title { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; }
            .pm-stage-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,.9); box-shadow: 0 0 0 3px rgba(255,255,255,.18); }
            .pm-stage-count { min-width: 24px; padding: 3px 7px; border-radius: 999px; background: rgba(255,255,255,.2); text-align: center; font-size: 12px; font-weight: 700; }
            .pm-stage-body { min-height: 365px; padding: 10px; transition: background .15s ease; }
            .pm-stage-body.pm-drag-over { background: #e8eefc; }
            .pm-kanban-card { margin-bottom: 9px; border: 1px solid #d5dae2; border-radius: 5px; background: #fff; box-shadow: 0 1px 3px rgba(15,23,42,.07); transition: transform .15s ease, box-shadow .15s ease; }
            .pm-kanban-card[draggable="true"] { cursor: grab; }
            .pm-kanban-card.pm-dragging { opacity: .55; transform: rotate(1deg); box-shadow: 0 10px 22px rgba(15,23,42,.16); }
            .pm-card-main { padding: 9px 9px 10px; }
            .pm-card-top, .pm-card-bottom { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
            .pm-card-code { max-width: 68%; overflow: hidden; padding: 3px 6px; border-radius: 4px; background: #f5f6f8; color: #526174; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 10px; line-height: 1.2; text-overflow: ellipsis; white-space: nowrap; }
            .pm-card-title { display: block; margin: 9px 0 4px; color: #1f2937; font-size: 13px; font-weight: 700; line-height: 1.35; text-decoration: none; }
            .pm-card-title:hover { color: #2563eb; }
            .pm-card-description { display: -webkit-box; min-height: 50px; overflow: hidden; color: #637083; font-size: 11px; line-height: 1.35; -webkit-box-orient: vertical; -webkit-line-clamp: 4; }
            .pm-card-meta { color: #64748b; font-size: 11px; }
            .pm-card-footer { display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; padding: 0 9px 9px; border-top: 1px solid #edf0f3; }
            .pm-assignee-list { min-width: 0; flex: 1; }
            .pm-assignee { display: flex; align-items: center; min-width: 0; max-width: 100%; gap: 5px; margin-top: 4px; color: #344054; font-size: 11px; line-height: 1.2; }
            .pm-assignee-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .pm-avatar { display: inline-flex; flex: 0 0 16px; align-items: center; justify-content: center; width: 16px; height: 16px; border-radius: 50%; background: #3b82f6; color: #fff; font-size: 9px; font-weight: 800; }
            .pm-assignee-more { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 24px; margin-top: 4px; padding: 0 7px; border-radius: 999px; background: #f3f4f6; color: #475467; font-size: 10px; }
            .pm-card-view { display: inline-flex; flex: 0 0 32px; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid #dfe3e8; border-radius: 6px; background: #fff; color: #2563eb; text-decoration: none; }
            .pm-card-view:hover { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
            .pm-empty-column { padding: 42px 8px; color: #94a3b8; text-align: center; font-size: 12px; border: 1px dashed #d6dce7; border-radius: 8px; }
            .pm-priority { padding: 3px 7px; border-radius: 999px; color: #fff; font-size: 10px; font-weight: 800; line-height: 1; }
            .pm-priority-low { background: #64748b; } .pm-priority-normal { background: #f59e0b; } .pm-priority-high { background: #ef4444; } .pm-priority-urgent { background: #b91c1c; }
            @media (max-width: 768px) { .pm-kanban-column { flex-basis: 82vw; } }
        </style>
    </x-slot>

    <div class="app-content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center gap-2">
            <div><h3 class="mb-0"><i class="bi bi-columns-gap me-2"></i>{{ $project->name }}</h3><small class="text-muted">Kanban Project Management</small></div>
            <div class="d-flex gap-2"><a href="{{ route('pm.projects.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-folder me-1"></i>Project</a><a href="{{ route('pm.tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Tugas</a></div>
        </div>
    </div>

    <div class="app-content"><div class="container-fluid">
        @include('pm.partials.flash')
        <form class="pm-board-toolbar card-body mb-3" method="GET" action="{{ route('pm.projects.board', $project) }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3"><label class="pm-filter-label" for="pm-project-filter">Project</label><select id="pm-project-filter" class="form-select form-select-sm" onchange="if(this.value) window.location.href=this.value"><option value="">{{ $project->name }}</option>@foreach($projects as $item)<option value="{{ route('pm.projects.board', $item) }}">{{ $item->name }}</option>@endforeach</select></div>
                <div class="col-lg-3"><label class="pm-filter-label" for="pm-task-search">Cari tugas</label><input id="pm-task-search" name="q" value="{{ $keyword }}" class="form-control form-control-sm" placeholder="Judul tugas..."></div>
                <div class="col-lg-2"><label class="pm-filter-label" for="pm-due-filter">Jatuh tempo</label><select id="pm-due-filter" name="due_date" class="form-select form-select-sm"><option value="">Semua</option><option value="today" @selected($dueDate === 'today')>Hari ini</option><option value="week" @selected($dueDate === 'week')>Minggu ini</option><option value="month" @selected($dueDate === 'month')>Bulan ini</option></select></div>
                <div class="col-lg-2"><span class="pm-filter-label">Opsi tampilan</span><label class="pm-filter-check" for="show_closed"><input class="form-check-input" type="checkbox" name="show_closed" value="1" id="show_closed" @checked($showClosed)><span>Tampilkan ditutup</span></label></div>
                <div class="col-lg-2"><span class="pm-filter-label">&nbsp;</span><button class="btn btn-outline-primary btn-sm pm-filter-action"><i class="bi bi-search me-1"></i>Filter</button></div>
            </div>
        </form>

        <div class="pm-kanban" id="pm-kanban">
            @php($stageColors = ['#2563eb', '#7c3aed', '#0891b2', '#d97706', '#059669', '#db2777'])
            @foreach($project->stages as $stage)
                @php($stageColor = $stage->color ?: $stageColors[$loop->index % count($stageColors)])
                <section class="pm-kanban-column" data-stage-id="{{ $stage->id }}">
                    <div class="pm-stage-header" style="--stage-color: {{ $stageColor }}"><div class="pm-stage-title"><span class="pm-stage-dot"></span><span>{{ $stage->name }}</span></div><span class="pm-stage-count">{{ $stage->tasks->count() }}</span></div>
                    <div class="pm-stage-body" data-stage-id="{{ $stage->id }}">
                        @forelse($stage->tasks as $task)
                            @php($editable = in_array($task->id, $editableTaskIds, true))
                            @php($taskDescription = \App\Support\PmHtml::text($task->description))
                            <article class="pm-kanban-card" data-task-id="{{ $task->id }}" data-move-url="{{ route('pm.tasks.move', $task) }}" draggable="{{ $editable ? 'true' : 'false' }}">
                                <div class="pm-card-main">
                                    <div class="pm-card-top"><span class="pm-card-code">{{ $task->uuid ?: $project->name.'-'.$task->id }}</span><span class="pm-priority pm-priority-{{ $task->priority }}">{{ $priorityLabels[$task->priority] ?? ucfirst($task->priority) }}</span></div>
                                    <a class="pm-card-title" href="{{ route('pm.tasks.show', $task) }}">{{ $task->title }}</a>
                                    @if($taskDescription)
                                        <div class="pm-card-description">{{ \Illuminate\Support\Str::limit($taskDescription, 150) }}</div>
                                    @else
                                        <div class="pm-card-description text-muted">Belum ada deskripsi tugas.</div>
                                    @endif
                                </div>
                                <div class="pm-card-footer">
                                    <div class="pm-assignee-list" title="{{ $task->assignees->pluck('name')->join(', ') }}">
                                        @forelse($task->assignees->take(2) as $assignee)
                                            <div class="pm-assignee"><span class="pm-avatar">{{ strtoupper(substr($assignee->name, 0, 1)) }}</span><span class="pm-assignee-name">{{ $assignee->name }}</span></div>
                                        @empty
                                            <div class="pm-assignee"><span class="pm-card-meta">Belum ditugaskan</span></div>
                                        @endforelse
                                        @if($task->assignees->count() > 2)
                                            <span class="pm-assignee-more">+{{ $task->assignees->count() - 2 }}</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('pm.tasks.show', $task) }}" class="pm-card-view" title="Lihat detail"><i class="bi bi-eye-fill"></i></a>
                                </div>
                            </article>
                        @empty
                            <div class="pm-empty-column"><i class="bi bi-inbox d-block fs-4 mb-2"></i>Tarik kartu ke sini</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
        <div class="small text-muted mt-1"><i class="bi bi-info-circle me-1"></i>Kartu yang dapat Anda kelola bisa digeser ke kolom lain untuk memperbarui status.</div>
    </div></div>

    <script>
        (() => {
            let dragged = null;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            document.querySelectorAll('.pm-kanban-card[draggable="true"]').forEach(card => {
                card.addEventListener('dragstart', event => { dragged = card; card.classList.add('pm-dragging'); event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', card.dataset.taskId); });
                card.addEventListener('dragend', () => { card.classList.remove('pm-dragging'); dragged = null; document.querySelectorAll('.pm-stage-body').forEach(body => body.classList.remove('pm-drag-over')); });
            });
            document.querySelectorAll('.pm-stage-body').forEach(body => {
                body.addEventListener('dragover', event => { event.preventDefault(); body.classList.add('pm-drag-over'); });
                body.addEventListener('dragleave', () => body.classList.remove('pm-drag-over'));
                body.addEventListener('drop', async event => {
                    event.preventDefault(); body.classList.remove('pm-drag-over');
                    if (!dragged || dragged.closest('.pm-kanban-column')?.dataset.stageId === body.dataset.stageId) return;
                    body.appendChild(dragged);
                    const form = new URLSearchParams({ _token: csrf || '', _method: 'PUT', stage_id: body.dataset.stageId });
                    try {
                        const response = await fetch(dragged.dataset.moveUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf || '', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: form });
                        if (!response.ok) throw new Error('Gagal memperbarui status');
                        window.location.reload();
                    } catch (error) { alert(error.message); window.location.reload(); }
                });
            });
        })();
    </script>
</x-app-layout>
