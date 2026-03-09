<x-app-layout>
    <x-slot name="pagetitle">Laporan Laba Rugi</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-bar-chart-line"></i> Laporan Laba Rugi</h3>
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

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="small-box text-bg-success">
                        <div class="inner">
                            <h3 id="totalPendapatan">Rp 0</h3>
                            <p>Total Pendapatan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box text-bg-danger">
                        <div class="inner">
                            <h3 id="totalBeban">Rp 0</h3>
                            <p>Total Beban</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box text-bg-primary">
                        <div class="inner">
                            <h3 id="labaBersih">Rp 0</h3>
                            <p id="statusLabaRugi">LABA / RUGI BERSIH</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card card-success card-outline">
                        <div class="card-header"><strong>PENDAPATAN</strong></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">No</th>
                                        <th>Akun</th>
                                        <th width="30%" class="text-end">Nominal (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyPendapatan"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-danger card-outline">
                        <div class="card-header"><strong>BEBAN</strong></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">No</th>
                                        <th>Akun</th>
                                        <th width="30%" class="text-end">Nominal (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyBeban"></tbody>
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
                    $(selector).html('<tr><td colspan="3" class="text-center text-muted">Tidak ada data</td></tr>');
                    return;
                }

                let html = '';
                groups.forEach(group => {
                    html += `<tr class="table-secondary fw-bold"><td colspan="3">${group.kategori}</td></tr>`;

                    group.items.forEach((row, idx) => {
                        const label = `${row.kode_akun} - ${row.nama_akun}`;
                        html += `
                            <tr>
                                <td class="text-center">${idx + 1}</td>
                                <td>${label}<br><small class="text-muted">${row.rincian}</small></td>
                                <td class="text-end ${row.nominal_raw < 0 ? 'text-danger' : ''}">${formatRupiah(row.nominal_raw)}</td>
                            </tr>
                        `;
                    });

                    html += `
                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-end">Sub Total ${group.kategori}</td>
                            <td class="text-end">${formatRupiah(group.subtotal_raw)}</td>
                        </tr>
                    `;
                });

                $(selector).html(html);
            }

            function loadLabaRugi() {
                $.get("{{ route('laporan.laba-rugi.data') }}", {
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    module: $('#module').val()
                })
                .done(function(response) {
                    if (!response.success) {
                        alert(response.message || 'Gagal memuat data');
                        return;
                    }

                    renderGroups('#tbodyPendapatan', response.data.pendapatan_groups);
                    renderGroups('#tbodyBeban', response.data.beban_groups);

                    $('#totalPendapatan').text(formatRupiah(response.summary.total_pendapatan_raw));
                    $('#totalBeban').text(formatRupiah(response.summary.total_beban_raw));
                    $('#labaBersih').text(formatRupiah(response.summary.laba_bersih_raw));
                    $('#statusLabaRugi').text(response.summary.status + ' BERSIH');

                    const summaryHtml = `
                        <div class="row">
                            <div class="col-md-8">
                                <strong>Periode:</strong> ${formatDisplayDate(response.period.start)} - ${formatDisplayDate(response.period.end)}
                                <span class="ms-2 badge bg-secondary">${response.period.module === 'project' ? 'Project' : 'PT/Company'}</span>
                            </div>
                            <div class="col-md-4 text-end">
                                <small class="d-block">Laba Kotor: <strong>${formatRupiah(response.summary.laba_kotor_raw)}</strong></small>
                                <small class="d-block ${response.summary.laba_bersih_raw >= 0 ? 'text-success' : 'text-danger'}">
                                    Laba Bersih: <strong>${formatRupiah(response.summary.laba_bersih_raw)}</strong>
                                </small>
                                <small class="d-block mt-1 text-muted">Unmapped akun: ${response.summary.unmapped_accounts || 0}</small>
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
                $('#btnFilter').on('click', loadLabaRugi);
                $('#start_date, #end_date, #module').on('change', loadLabaRugi);
                loadLabaRugi();
            });
        </script>
    </x-slot>
</x-app-layout>