<x-app-layout>
    <x-slot name="pagetitle">{{ $pageTitle }}</x-slot>
    <x-slot name="csscustom">
        <style>
            .pm-select2 + .select2-container { width: 100% !important; }
            .pm-select2 + .select2-container .select2-selection--multiple { min-height: 38px; border-color: #ced4da; }
            .pm-select2 + .select2-container .select2-selection__rendered { padding: 2px 6px; }
            #pm-description-editor { height: auto; border-color: #ced4da; }
            #pm-description-editor .ql-editor { height: auto; min-height: 260px; font-size: 14px; }
            #pm-description-editor .ql-editor img { max-width: 100%; height: auto; }
        </style>
    </x-slot>
    <x-slot name="jscustom">
        <script>
            $(function () {
                const project = $('#pm-project');
                const descriptionField = document.getElementById('pm-description');
                const descriptionEditor = document.getElementById('pm-description-editor');
                const assignees = $('#pm-assignees').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Pilih assignee dari member project',
                    closeOnSelect: false,
                });

                const quill = new Quill(descriptionEditor, {
                    theme: 'snow',
                    modules: {
                        toolbar: {
                            container: [
                                [{ header: [1, 2, 3, false] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ size: ['small', false, 'large', 'huge'] }],
                                [{ color: [] }, { background: [] }],
                                [{ list: 'ordered' }, { list: 'bullet' }],
                                [{ align: [] }],
                                ['link', 'image', 'blockquote', 'code-block'],
                                ['clean'],
                            ],
                            handlers: {
                                image: function () {
                                    const input = document.createElement('input');
                                    input.type = 'file';
                                    input.accept = 'image/jpeg,image/png,image/gif,image/webp,image/avif';
                                    input.click();
                                    input.onchange = async () => {
                                        if (!input.files?.length) return;

                                        const formData = new FormData();
                                        formData.append('image', input.files[0]);
                                        formData.append('project_id', project.val() || '');

                                        try {
                                            const response = await fetch(@json(route('pm.tasks.description.upload')), {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                                    'Accept': 'application/json',
                                                },
                                                body: formData,
                                            });
                                            const result = await response.json();
                                            if (!response.ok) throw new Error(result.message || 'Gagal mengunggah gambar.');

                                            const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
                                            quill.insertEmbed(range.index, 'image', result.url, 'user');
                                            quill.setSelection(range.index + 1, 0, 'silent');
                                        } catch (error) {
                                            alert(error.message || 'Gagal mengunggah gambar.');
                                        }
                                    };
                                },
                            },
                        },
                    },
                });

                const initialDescription = @json(\App\Support\PmHtml::sanitize(old('description', $task->description)));
                if (initialDescription) quill.clipboard.dangerouslyPasteHTML(initialDescription);

                document.getElementById('pm-task-form').addEventListener('submit', function () {
                    const html = quill.root.innerHTML;
                    descriptionField.value = html === '<p><br></p>' ? '' : html;
                });

                function syncAssigneeOptions() {
                    const projectId = String(project.val() || '');
                    const selected = assignees.val() || [];
                    const allowed = [];

                    assignees.find('option').each(function () {
                        const availableProjects = String($(this).data('projects') || '').split(',');
                        const available = availableProjects.includes(projectId);
                        $(this).prop('disabled', !available);
                        if (available) allowed.push(String($(this).val()));
                    });

                    assignees.val(selected.filter(id => allowed.includes(String(id)))).trigger('change.select2');
                }

                project.on('change', syncAssigneeOptions);
                syncAssigneeOptions();
            });
        </script>
    </x-slot>
    <div class="app-content-header"><div class="container-fluid"><h3 class="mb-0"><i class="bi bi-check2-square me-2"></i>{{ $pageTitle }}</h3></div></div>
    <div class="app-content"><div class="container-fluid"><div class="row"><div class="col-xl-9"><div class="card shadow-sm"><div class="card-body"><form id="pm-task-form" method="POST" action="{{ $action }}">@csrf @method($method)
        <div class="row g-3"><div class="col-md-8"><label class="form-label">Judul tugas</label><input name="title" value="{{ old('title', $task->title) }}" class="form-control @error('title') is-invalid @enderror" required maxlength="180">@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-4"><label class="form-label">Prioritas</label><select name="priority" class="form-select">@foreach($priorityLabels as $value => $label)<option value="{{ $value }}" @selected(old('priority', $task->priority) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Project</label><select name="project_id" id="pm-project" class="form-select" required>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((int) old('project_id', $task->project_id) === $project->id)>{{ $project->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Kolom</label><select name="stage_id" id="pm-stage" class="form-select">@foreach($projects as $project)@foreach($project->stages as $stage)<option data-project="{{ $project->id }}" value="{{ $stage->id }}" @selected((int) old('stage_id', $task->stage_id) === $stage->id)>{{ $project->name }} — {{ $stage->name }}</option>@endforeach @endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Tanggal mulai</label><input type="date" name="start_date" value="{{ old('start_date', $task->start_date?->format('Y-m-d')) }}" class="form-control"></div><div class="col-md-6"><label class="form-label">Jatuh tempo</label><input type="date" name="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}" class="form-control"></div>
        @php
            $selectedUsers = collect(old('assignees', $task->assignees->pluck('id')->all()))
                ->map(fn ($id) => (int) $id)
                ->all();
            $assigneeUsers = $projects->flatMap(fn ($projectOption) => $projectOption->members)
                ->unique('id')
                ->sortBy('name');
            $selectedProjectId = (string) old('project_id', $task->project_id);
        @endphp
        <div class="col-12"><label class="form-label" for="pm-assignees">Assignee ticket</label><select id="pm-assignees" name="assignees[]" class="form-select pm-select2" multiple data-placeholder="Pilih assignee dari member project">
            @foreach($assigneeUsers as $user)
                @php
                    $memberProjectIds = $projects
                        ->filter(fn ($projectOption) => $projectOption->members->contains('id', $user->id))
                        ->pluck('id')
                        ->map(fn ($id) => (string) $id)
                        ->all();
                @endphp
                <option data-projects="{{ implode(',', $memberProjectIds) }}" value="{{ $user->id }}" @selected(in_array((int) $user->id, $selectedUsers, true) && in_array($selectedProjectId, $memberProjectIds, true))>{{ $user->name }}{{ $user->jabatan ? ' — '.$user->jabatan : '' }}</option>
            @endforeach
        </select><div class="form-text">Pilihan assignee mengikuti member project yang dipilih.</div></div>
        <div class="col-12"><label class="form-label" for="pm-description-editor">Deskripsi</label><textarea name="description" id="pm-description" hidden></textarea><div id="pm-description-editor"></div><div class="form-text">Gunakan toolbar untuk format teks, menambahkan link, tabel, kode, atau gambar.</div>@error('description')<div class="text-danger small">{{ $message }}</div>@enderror</div></div>
        <div class="mt-4 d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button><a href="{{ $task->project_id ? route('pm.projects.board', $task->project_id) : route('pm.projects.index') }}" class="btn btn-outline-secondary">Batal</a></div>
    </form></div></div></div></div></div>
    <script>document.addEventListener('DOMContentLoaded',function(){const p=document.getElementById('pm-project'),s=document.getElementById('pm-stage');function f(){const id=p.value;Array.from(s.options).forEach(o=>{o.hidden=o.dataset.project!==id;if(!o.hidden&&!s.value)s.value=o.value;});if(s.selectedOptions[0]?.hidden){const first=Array.from(s.options).find(o=>!o.hidden);if(first)s.value=first.value;}}p.addEventListener('change',f);f();});</script>
</x-app-layout>
