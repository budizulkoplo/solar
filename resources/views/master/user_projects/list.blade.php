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
                                                style="padding: 10px 8px; min-width: fit-content; max-width: 220px; min-height: 60px; line-height: 1.2; border: 1px solid #ccc; border-radius: 6px; position: relative;">
                                                <small class="fw-bold text-truncate" 
                                                    style="background-color: #FFA500; width: 100%; text-align: center; padding: 3px 4px; border-radius: 3px;">
                                                    {{ $companyName }}
                                                </small>
                                                <span class="text-truncate" style="font-size: 0.85rem; text-align: center; display: block; margin-top: 4px;">
                                                    {{ $project->namaproject }}
                                                </span>
                                                <span class="spinner-border spinner-border-sm text-primary d-none" role="status" style="position: absolute; top: 5px; right: 5px;"></span>
                                            </button>
                                        @endforeach
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function(){

                // Klik tombol project -> langsung toggle akses
                $('.project-btn').click(function(){
                    let $btn = $(this);
                    let userId = $btn.closest('tr').data('user-id');
                    let projectId = $btn.data('project-id');
                    let spinner = $btn.find('.spinner-border');

                    spinner.removeClass('d-none'); // tampilkan loading

                    $.post("{{ route('user-projects.toggle') }}", {
                        _token: "{{ csrf_token() }}",
                        user_id: userId,
                        project_id: projectId
                    })
                    .done(function(res){
                        if(res.status === 'added'){
                            $btn.removeClass('btn-light').addClass('btn-success');
                        } else {
                            $btn.removeClass('btn-success').addClass('btn-light');
                        }
                    })
                    .fail(function(){
                        alert('Terjadi kesalahan saat update akses!');
                    })
                    .always(function(){
                        spinner.addClass('d-none'); // sembunyikan loading
                    });
                });

            });
        </script>
    </x-slot>
</x-app-layout>
