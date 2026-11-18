<x-app-layout>
    <x-slot name="pagetitle">Detail Tugas</x-slot>
    <x-slot name="csscustom">
        <style>
            .pm-detail-card { border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 2px 7px rgba(15,23,42,.05); }
            .pm-detail-label { display: block; margin-bottom: 3px; color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; }
            .pm-rich-content { color: #475569; line-height: 1.7; overflow-wrap: anywhere; }
            .pm-rich-content img { max-width: 100%; height: auto; border-radius: 7px; }
            .pm-rich-content a { overflow-wrap: anywhere; }
            .pm-comment { padding: 12px; border: 1px solid #e5e7eb; border-radius: 9px; background: #fbfcfe; }
            .pm-comment + .pm-comment { margin-top: 9px; }
        </style>
    </x-slot>

    <div class="app-content-header"><div class="container-fluid d-flex justify-content-between align-items-center gap-2"><div><h3 class="mb-0">{{ $task->title }}</h3><small class="text-muted">{{ $task->project->name }} / {{ $task->stage?->name ?: '-' }}</small></div><div class="d-flex gap-2"><a href="{{ route('pm.projects.board', $task->project) }}" class="btn btn-outline-secondary btn-sm">Kembali ke Board</a>@if($canEditTask)<a href="{{ route('pm.tasks.edit', $task) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>@endif</div></div></div>

    <div class="app-content"><div class="container-fluid">@include('pm.partials.flash')
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card pm-detail-card"><div class="card-body">
                    <div class="row g-3 mb-3"><div class="col-6 col-md-3"><span class="pm-detail-label">Status</span><strong>{{ $task->stage?->name ?: '-' }}</strong></div><div class="col-6 col-md-3"><span class="pm-detail-label">Prioritas</span><span class="badge text-bg-primary">{{ $priorityLabels[$task->priority] ?? ucfirst($task->priority) }}</span></div><div class="col-6 col-md-3"><span class="pm-detail-label">Mulai</span><strong>{{ $task->start_date?->format('d/m/Y') ?: '-' }}</strong></div><div class="col-6 col-md-3"><span class="pm-detail-label">Jatuh tempo</span><strong>{{ $task->due_date?->format('d/m/Y') ?: '-' }}</strong></div></div>
                    <hr><h5 class="mb-3">Deskripsi</h5><div class="pm-rich-content">{!! \App\Support\PmHtml::sanitize($task->description) ?: '<span class="text-muted">Belum ada deskripsi.</span>' !!}</div>
                    <hr><h5 class="mb-3">Diskusi</h5>
                    @forelse($task->comments as $comment)
                        <div class="pm-comment"><div class="d-flex justify-content-between gap-2"><strong>{{ $comment->user?->name ?: 'User' }}</strong><small class="text-muted">{{ $comment->created_at?->format('d/m/Y H:i') }}</small></div><div class="pm-rich-content mt-2">{!! \App\Support\PmHtml::sanitize($comment->message) !!}</div>@if((int) $comment->user_id === (int) auth()->id() || auth()->user()->hasAnyRole(['superadmin','admin']))<div class="mt-2"><form method="POST" action="{{ route('pm.tasks.comments.destroy', [$task, $comment]) }}" onsubmit="return confirm('Hapus komentar ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></div>@endif</div>
                    @empty <div class="text-muted">Belum ada diskusi.</div>@endforelse
                    <form method="POST" action="{{ route('pm.tasks.comments.store', $task) }}" class="mt-3">@csrf<textarea name="message" class="form-control mb-2" rows="3" placeholder="Tulis komentar..." required></textarea><button class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Kirim Komentar</button></form>
                </div></div>
            </div>
            <div class="col-xl-4">
                <div class="card pm-detail-card mb-3"><div class="card-header">Informasi Tugas</div><div class="card-body"><div class="mb-3"><span class="pm-detail-label">Dibuat oleh</span><strong>{{ $task->creator?->name ?: 'Tidak diketahui' }}</strong><small class="d-block text-muted">{{ $task->created_at?->format('d/m/Y H:i') }}</small></div><div><span class="pm-detail-label">Assignee</span>@forelse($task->assignees as $user)<div class="d-flex align-items-center gap-2 mb-2"><span class="rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center" style="width:32px;height:32px">{{ strtoupper(substr($user->name, 0, 1)) }}</span><div><strong>{{ $user->name }}</strong><small class="d-block text-muted">{{ $user->jabatan ?: 'User' }}</small></div></div>@empty<span class="text-muted">Belum ada assignee.</span>@endforelse</div></div></div>
                <div class="card pm-detail-card"><div class="card-header">Riwayat Status</div><div class="card-body">@forelse($task->statusLogs as $log)<div class="border-bottom pb-2 mb-2"><strong>{{ $log->oldStage?->name ?: '-' }} &rarr; {{ $log->newStage?->name ?: '-' }}</strong><small class="d-block text-muted">{{ $log->changed_at?->format('d/m/Y H:i') }} oleh {{ $log->changer?->name ?: 'Sistem' }}</small><small class="text-muted">{{ $log->note }}</small></div>@empty<span class="text-muted">Belum ada riwayat.</span>@endforelse</div></div>
                @if($canDeleteTask)<form method="POST" action="{{ route('pm.tasks.destroy', $task) }}" class="mt-3" onsubmit="return confirm('Hapus tugas ini?')">@csrf @method('DELETE')<button class="btn btn-outline-danger w-100">Hapus Tugas</button></form>@endif
            </div>
        </div>
    </div></div>
</x-app-layout>
