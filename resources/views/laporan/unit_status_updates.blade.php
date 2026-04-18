<x-app-layout>
    <x-slot name="pagetitle">Laporan Update Status Unit</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Laporan Update Status Unit</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <h6 class="mb-0">Filter Laporan</h6>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control form-control-sm" name="start_date" id="start_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control form-control-sm" name="end_date" id="end_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Project</label>
                            <select class="form-select form-select-sm" name="project_id" id="project_id">
                                <option value="">Semua Project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->namaproject }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Unit</label>
                            <select class="form-select form-select-sm" name="unit_id" id="unit_id">
                                <option value="">Semua Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" data-project="{{ $unit->idproject }}">
                                        {{ $unit->namaunit }} ({{ $unit->blok ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select form-select-sm" name="status" id="status">
                                <option value="">Semua Status</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">User Update</label>
                            <input type="text" class="form-control form-control-sm" name="update_user" id="update_user" placeholder="Nama user">
                        </div>
                        <div class="col-12 mt-2">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" id="btnResetFilter">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header pt-1 pb-1">
                    <h6 class="mb-0">Riwayat Perubahan Status Unit</h6>
                </div>
                <div class="card-body">
                    <table id="tbUnitStatusUpdates" class="table table-sm table-hover w-100" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th width="3%">No</th>
                                <th>Tanggal Update</th>
                                <th>Project</th>
                                <th>Unit</th>
                                <th>Blok</th>
                                <th>Jenis Unit</th>
                                <th>No Rumah</th>
                                <th>Customer</th>
                                <th>Status Lama</th>
                                <th>Status Baru</th>
                                <th>Update User</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(document).ready(function() {
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                $('#start_date').val(firstDay.toISOString().split('T')[0]);
                $('#end_date').val(lastDay.toISOString().split('T')[0]);

                const allUnitOptions = $('#unit_id option').clone();

                function filterUnitsByProject() {
                    const projectId = $('#project_id').val();
                    const currentValue = $('#unit_id').val();

                    $('#unit_id').empty().append(allUnitOptions.filter(function() {
                        const optionProject = $(this).data('project');
                        return !projectId || !optionProject || String(optionProject) === String(projectId);
                    }));

                    if ($('#unit_id option[value="' + currentValue + '"]').length) {
                        $('#unit_id').val(currentValue);
                    } else {
                        $('#unit_id').val('');
                    }
                }

                $('#project_id').change(function() {
                    filterUnitsByProject();
                });

                let table = $('#tbUnitStatusUpdates').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('laporan.unit-status-updates') }}",
                        type: "GET",
                        data: function(d) {
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                            d.project_id = $('#project_id').val();
                            d.unit_id = $('#unit_id').val();
                            d.status = $('#status').val();
                            d.update_user = $('#update_user').val();
                        }
                    },
                    columns: [
                        {
                            data: null,
                            name: 'no',
                            className: 'text-center',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        { data: 'tanggal_update_formatted', name: 'updatetime', className: 'text-center' },
                        { data: 'project_name', name: 'project_name' },
                        { data: 'unit_name', name: 'unit_name' },
                        { data: 'blok', name: 'blok' },
                        { data: 'jenis_unit', name: 'jenis_unit' },
                        { data: 'no_rumah', name: 'no_rumah' },
                        { data: 'customer_name', name: 'customer_name' },
                        { data: 'old_status_badge', name: 'old_status', className: 'text-center' },
                        { data: 'new_status_badge', name: 'new_status', className: 'text-center' },
                        { data: 'update_user', name: 'update_user' }
                    ],
                    order: [[1, 'desc']],
                    language: {
                        emptyTable: "Tidak ada riwayat update status unit",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Tidak ditemukan data yang cocok",
                        loadingRecords: "Memuat...",
                        processing: "Memproses...",
                        paginate: {
                            first: "Awal",
                            last: "Akhir",
                            next: "›",
                            previous: "‹"
                        }
                    }
                });

                $('#filterForm').submit(function(e) {
                    e.preventDefault();
                    table.ajax.reload();
                });

                $('#btnResetFilter').click(function() {
                    $('#filterForm')[0].reset();
                    $('#start_date').val(firstDay.toISOString().split('T')[0]);
                    $('#end_date').val(lastDay.toISOString().split('T')[0]);
                    filterUnitsByProject();
                    table.ajax.reload();
                });

                filterUnitsByProject();
            });
        </script>
    </x-slot>
</x-app-layout>
