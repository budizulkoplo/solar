<x-app-layout>
    <x-slot name="pagetitle">User Project Access</x-slot>

    <div class="container-fluid">
        <h3>User Project Access</h3>

        <div class="card">
            <div class="card-body p-2">
                <table class="table table-bordered table-hover" id="userProjectTable" style="table-layout: auto; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 1%;">No</th>
                            <th style="white-space: nowrap;">User</th>
                            <th>Projects</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $index => $user)
                        <tr data-user-id="{{ $user->id }}">
                            <td>{{ $index + 1 }}</td>
                            <td style="white-space: nowrap;">{{ $user->name }}</td>
                            <td>
                                @php
                                    $projectsByCompany = $projects->groupBy(fn($p) => $p->company?->company_name ?? 'Tanpa Company');
                                @endphp
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; max-height: 250px; overflow-y: auto;">
                                    @foreach($projectsByCompany as $companyName => $companyProjects)
                                        @foreach($companyProjects as $project)
                                            <button 
                                                type="button" 
                                                class="btn project-btn {{ $user->projects->contains($project->id) ? 'btn-success' : 'btn-light' }} d-flex flex-column align-items-center justify-content-center text-center"
                                                data-project-id="{{ $project->id }}" 
                                                data-company-name="{{ $companyName }}"
                                                style="padding: 10px 8px; min-width: fit-content; max-width: 220px; min-height: 60px; line-height: 1.2; border: 1px solid #ccc; border-radius: 6px;">
                                                <small class="fw-bold text-truncate" 
                                                    style="background-color: #FFA500; width: 100%; text-align: center; padding: 3px 4px; border-radius: 3px;">
                                                    {{ $companyName }}
                                                </small>
                                                <span class="text-truncate" style="font-size: 0.85rem; text-align: center; display: block; margin-top: 4px;">
                                                    {{ $project->namaproject }}
                                                </span>
                                            </button>
                                        @endforeach
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <button id="saveAllAccess" class="btn btn-primary mt-3">Simpan Semua Akses</button>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function(){

                // Toggle akses saat tombol project diklik
                $('.project-btn').click(function(){
                    $(this).toggleClass('btn-light btn-success');
                });

                // Simpan semua akses
                $('#saveAllAccess').click(function(){
                    let data = [];

                    $('#userProjectTable tbody tr').each(function(){
                        let userId = $(this).data('user-id');
                        let projectIds = [];

                        $(this).find('.project-btn.btn-success').each(function(){
                            projectIds.push($(this).data('project-id'));
                        });

                        data.push({
                            user_id: userId,
                            project_ids: projectIds
                        });
                    });

                    let requests = data.map(item => {
                        return $.post("{{ route('user-projects.store') }}", {
                            _token: "{{ csrf_token() }}",
                            user_id: item.user_id,
                            project_ids: item.project_ids
                        });
                    });

                    $.when.apply($, requests).done(function(){
                        alert('Semua akses berhasil disimpan!');
                    });
                });

            });
        </script>
    </x-slot>
</x-app-layout>
