<x-app-layout>
    <x-slot name="pagetitle">{{ $project->exists ? 'Edit Project' : 'Project Baru' }}</x-slot>
    <x-slot name="csscustom">
        <style>
            .pm-select2 + .select2-container { width: 100% !important; }
            .pm-select2 + .select2-container .select2-selection--multiple { min-height: 38px; border-color: #ced4da; }
            .pm-select2 + .select2-container .select2-selection__rendered { padding: 2px 6px; }
        </style>
    </x-slot>
    <x-slot name="jscustom">
        <script>
            $(function () {
                $('#pm-members').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Pilih member project',
                    closeOnSelect: false,
                });
            });
        </script>
    </x-slot>

    <div class="app-content-header"><div class="container-fluid"><h3 class="mb-0"><i class="bi bi-kanban me-2"></i>{{ $project->exists ? 'Edit Project' : 'Project Baru' }}</h3></div></div>
    <div class="app-content"><div class="container-fluid"><div class="row"><div class="col-xl-8">
        <div class="card shadow-sm"><div class="card-body">
            <form method="POST" action="{{ $action }}">
                @csrf @method($method)
                <div class="mb-3">
                    <label class="form-label">Nama project</label>
                    <input type="text" name="name" value="{{ old('name', $project->name) }}" class="form-control @error('name') is-invalid @enderror" required maxlength="160">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @php($selectedMembers = old('members', $project->exists ? $project->members->pluck('id')->all() : []))
                <div class="mb-3">
                    <label class="form-label" for="pm-members">Member project</label>
                    <select id="pm-members" name="members[]" class="form-select pm-select2" multiple data-placeholder="Pilih member project">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(in_array($user->id, (array) $selectedMembers))>{{ $user->name }}{{ $user->jabatan ? ' — '.$user->jabatan : '' }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Member yang dipilih akan menjadi pilihan assignee pada tugas project ini.</div>
                    @error('members')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $project->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Kolom Kanban</label>
                    <textarea name="stages" rows="7" class="form-control @error('stages') is-invalid @enderror" required>{{ old('stages', $stagesText) }}</textarea>
                    <div class="form-text">Satu kolom per baris. Kolom terakhir dianggap status selesai.</div>
                    @error('stages')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @if($project->exists)
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $project->is_active) ? 'checked' : '' }}><label class="form-check-label" for="is_active">Project aktif</label></div>
                @endif
                <div class="d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button><a href="{{ route('pm.projects.index') }}" class="btn btn-outline-secondary">Batal</a></div>
            </form>
        </div></div>
    </div></div></div>
</x-app-layout>
