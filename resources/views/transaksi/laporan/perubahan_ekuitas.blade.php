<x-app-layout>
    <x-slot name="pagetitle">Laporan Perubahan Ekuitas</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-table"></i> Laporan Perubahan Ekuitas</h3>
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

            <div class="card card-primary card-outline">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%">Keterangan</th>
                                    <th class="text-end" style="width: 20%">Modal Disetor</th>
                                    <th class="text-end" style="width: 20%">Laba Ditahan</th>
                                    <th class="text-end" style="width: 20%">Total Ekuitas</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPerubahanEkuitas"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            function formatRupiah(value) {
                const number = Number(value || 0);
                const prefix = number < 0 ? '-Rp ' : 'Rp ';
                return prefix + Math.abs(number).toLocaleString('id-ID');
            }

            function formatDisplayDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }

            function renderRows(rows) {
                if (!rows || rows.length === 0) {
                    $('#tbodyPerubahanEkuitas').html(
                        `<tr><td colspan="4" class="text-center text-muted">Tidak ada data</td></tr>`
                    );
                    return;
                }

                let html = '';
                rows.forEach((row, idx) => {
                    const isClosing = idx === rows.length - 1;
                    html += `
                        <tr class="${isClosing ? 'table-warning fw-bold' : ''}">
                            <td>${row.keterangan}</td>
                            <td class="text-end">${formatRupiah(row.modal_disetor_raw)}</td>
                            <td class="text-end">${formatRupiah(row.laba_ditahan_raw)}</td>
                            <td class="text-end">${formatRupiah(row.total_ekuitas_raw)}</td>
                        </tr>
                    `;
                });

                $('#tbodyPerubahanEkuitas').html(html);
            }

            function loadPerubahanEkuitas() {
                $.get("{{ route('laporan.perubahan-ekuitas.data') }}", {
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    module: $('#module').val()
                })
                .done(function(response) {
                    if (!response.success) {
                        alert(response.message || 'Gagal memuat data');
                        return;
                    }

                    renderRows(response.data.rows);

                    const unmapped = response.summary.unmapped_accounts || [];
                    const summaryHtml = `
                        <div class="row">
                            <div class="col-md-8">
                                <strong>Periode:</strong>
                                ${formatDisplayDate(response.period.start)} - ${formatDisplayDate(response.period.end)}
                                <span class="ms-2 badge bg-secondary">${response.period.module === 'project' ? 'Project' : 'PT/Company'}</span>
                            </div>
                            <div class="col-md-4 text-end">
                                <small class="d-block text-muted">Total Ekuitas Akhir: ${formatRupiah(response.summary.total_akhir_raw)}</small>
                                <small class="d-block mt-1 text-muted">Akun belum terpetakan: ${unmapped.length}</small>
                                ${unmapped.length ? `
                                    <small class="d-block mt-1 text-muted">
                                        ${unmapped.slice(0, 5).map(x => `${x.kode} (${x.nama_akun})`).join(', ')}
                                    </small>
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
                $('#btnFilter').on('click', loadPerubahanEkuitas);
                $('#start_date, #end_date, #module').on('change', loadPerubahanEkuitas);
                loadPerubahanEkuitas();
            });
        </script>
    </x-slot>
</x-app-layout>
