<x-app-layout>
    <x-slot name="pagetitle">Laporan Neraca</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-journal-text"></i> Laporan Neraca</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <input type="date" id="start_date" class="form-control form-control-sm w-auto" value="{{ $startDate }}">
                        <span class="align-self-center">s/d</span>
                        <input type="date" id="end_date" class="form-control form-control-sm w-auto" value="{{ $endDate }}">
                        <select id="module" class="form-select form-select-sm w-auto">
                            <option value="project" {{ $module == 'project' ? 'selected' : '' }}>Project</option>
                            <option value="company" {{ $module == 'company' ? 'selected' : '' }}>PT/Company</option>
                        </select>
                        <button type="button" class="btn btn-primary btn-sm" id="btnFilter">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div id="summaryInfo" class="alert alert-info mb-3 d-none"></div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <strong>AKTIVA</strong>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">No</th>
                                        <th>Akun</th>
                                        <th width="30%" class="text-end">Nilai (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyAktiva"></tbody>
                                <tfoot>
                                    <tr class="table-active fw-bold">
                                        <td colspan="2" class="text-end">TOTAL AKTIVA</td>
                                        <td class="text-end" id="totalAktiva">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <strong>PASIVA</strong>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">No</th>
                                        <th>Akun</th>
                                        <th width="30%" class="text-end">Nilai (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyPasiva"></tbody>
                                <tfoot>
                                    <tr class="table-active fw-bold">
                                        <td colspan="2" class="text-end">TOTAL PASIVA</td>
                                        <td class="text-end" id="totalPasiva">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            function formatRupiah(value) {
                const number = Number(value || 0);
                return 'Rp ' + number.toLocaleString('id-ID');
            }

            function formatDisplayDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }

            function renderGroups(selector, groups) {
                if (!groups || groups.length === 0) {
                    $(selector).html(
                        `<tr><td colspan="3" class="text-center text-muted">Tidak ada data</td></tr>`
                    );
                    return;
                }

                const parents = {};
                groups.forEach(group => {
                    const parentKey = group.parent || 'Lainnya';
                    if (!parents[parentKey]) {
                        parents[parentKey] = {
                            label: parentKey,
                            order: group.parent_order || 999,
                            groups: []
                        };
                    }
                    parents[parentKey].groups.push(group);
                });

                const parentList = Object.values(parents).sort((a, b) => a.order - b.order);
                let html = '';
                parentList.forEach(parent => {
                    html += `
                        <tr class="table-primary fw-bold">
                            <td colspan="3">${parent.label}</td>
                        </tr>
                    `;

                    parent.groups.sort((a, b) => (a.order || 9999) - (b.order || 9999));
                    let parentSubtotal = 0;

                    parent.groups.forEach(group => {
                        html += `
                            <tr class="table-secondary fw-bold">
                                <td colspan="3">${group.rincian}</td>
                            </tr>
                        `;

                        let nomor = 1;
                        group.items.forEach(row => {
                            html += `
                                <tr>
                                    <td class="text-center">${nomor++}</td>
                                    <td>${row.nama_akun}</td>
                                    <td class="text-end">${formatRupiah(row.nilai_raw)}</td>
                                </tr>
                            `;
                        });

                        html += `
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-end">Sub Total ${group.rincian}</td>
                                <td class="text-end">${formatRupiah(group.subtotal_raw)}</td>
                            </tr>
                        `;

                        parentSubtotal += Number(group.subtotal_raw || 0);
                    });

                    html += `
                        <tr class="table-warning fw-bold">
                            <td colspan="2" class="text-end">Sub Total ${parent.label}</td>
                            <td class="text-end">${formatRupiah(parentSubtotal)}</td>
                        </tr>
                    `;
                });
                $(selector).html(html);
            }

            function loadNeraca() {
                $.get("{{ route('laporan.neraca.data') }}", {
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    module: $('#module').val()
                })
                .done(function(response) {
                    if (!response.success) {
                        alert(response.message || 'Gagal memuat data');
                        return;
                    }

                    renderGroups('#tbodyAktiva', response.data.aktiva_groups);
                    renderGroups('#tbodyPasiva', response.data.pasiva_groups);

                    $('#totalAktiva').text(formatRupiah(response.summary.total_aktiva_raw));
                    $('#totalPasiva').text(formatRupiah(response.summary.total_pasiva_raw));

                    const summaryHtml = `
                        <div class="row">
                            <div class="col-md-8">
                                <strong>Periode:</strong>
                                ${formatDisplayDate(response.period.start)} - ${formatDisplayDate(response.period.end)}
                                <span class="ms-2 badge bg-secondary">${response.period.module === 'project' ? 'Project' : 'PT/Company'}</span>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge ${response.summary.balance ? 'bg-success' : 'bg-danger'}">
                                    ${response.summary.balance ? 'SEIMBANG' : 'TIDAK SEIMBANG'}
                                </span>
                                <small class="d-block mt-1 text-muted">Unmapped akun: ${response.summary.unmapped_accounts || 0}</small>
                                ${(response.summary.unmapped_account_list || []).length ? `
                                    <small class="d-block mt-1 text-muted">
                                        ${response.summary.unmapped_account_list.slice(0, 5).map(x => `${x.kode} (${x.nama_akun})`).join(', ')}
                                    </small>
                                ` : ''}
                                ${!response.summary.balance ? `
                                    <small class="d-block mt-1 text-danger">Selisih: ${formatRupiah(response.summary.difference_raw)}</small>
                                ` : ''}
                            </div>
                        </div>
                    `;

                    $('#summaryInfo').html(summaryHtml).removeClass('d-none');
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat memuat data';
                    alert(msg);
                });
            }

            $(document).ready(function() {
                $('#btnFilter').on('click', loadNeraca);
                $('#start_date, #end_date, #module').on('change', loadNeraca);
                loadNeraca();
            });
        </script>
    </x-slot>
</x-app-layout>
