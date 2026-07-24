<x-app-layout>
    <x-slot name="pagetitle">Laporan Perubahan Ekuitas</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-table"></i> Laporan Perubahan Ekuitas</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
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
                        <button type="button" class="btn btn-success btn-sm" id="btnExportExcel">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="btnPrintPdf">
                            <i class="bi bi-file-earmark-pdf"></i> PDF
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
                                    <th style="width: 34%">Keterangan</th>
                                    <th class="text-end" style="width: 24%">Modal Disetor</th>
                                    <th class="text-end" style="width: 24%">Laba Ditahan</th>
                                    <th class="text-end" style="width: 18%">Total Ekuitas</th>
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

            function parseRupiah(value) {
                return Number(String(value || '0').replace(/[^\d,-]/g, '').replace(',', '.')) || 0;
            }

            function formatDisplayDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }

            function renderEditableCell(row, field) {
                if (!row.editable) {
                    return formatRupiah(row[`${field}_raw`]);
                }

                const adjusted = row[`${field}_adjusted`] ? 'is-valid' : '';
                const label = String(row.keterangan || '').replace(/"/g, '&quot;');
                const value = Number(row[`${field}_raw`] || 0).toLocaleString('id-ID');

                return `
                    <div class="input-group input-group-sm ekuitas-input-group">
                        <input type="text"
                            class="form-control form-control-sm text-end ekuitas-value-input ${adjusted}"
                            value="${value}"
                            data-row-key="${row.row_key}"
                            data-field="${field}"
                            data-label="${label}">
                        <button type="button"
                            class="btn btn-outline-primary btn-save-ekuitas"
                            data-row-key="${row.row_key}"
                            data-field="${field}"
                            title="Simpan">
                            <i class="bi bi-save"></i>
                        </button>
                    </div>
                `;
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
                            <td class="text-end">${renderEditableCell(row, 'modal_disetor')}</td>
                            <td class="text-end">${renderEditableCell(row, 'laba_ditahan')}</td>
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

            function savePerubahanEkuitasAdjustment(button) {
                const $button = $(button);
                const rowKey = $button.data('row-key');
                const field = $button.data('field');
                const $input = $(`.ekuitas-value-input[data-row-key="${rowKey}"][data-field="${field}"]`);
                const value = parseRupiah($input.val());

                $input.val(value.toLocaleString('id-ID'));
                $button.prop('disabled', true);

                $.post("{{ route('laporan.perubahan-ekuitas.adjustment') }}", {
                    _token: '{{ csrf_token() }}',
                    module: $('#module').val(),
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    row_key: rowKey,
                    field: field,
                    label: $input.data('label'),
                    value: value
                })
                .done(function(response) {
                    if (response.success) {
                        $input.addClass('is-valid');
                        loadPerubahanEkuitas();
                    }
                })
                .fail(function(xhr) {
                    alert(xhr.responseJSON?.message || 'Gagal menyimpan nilai perubahan ekuitas');
                })
                .always(function() {
                    $button.prop('disabled', false);
                });
            }

            $(document).ready(function() {
                $('#btnFilter').on('click', loadPerubahanEkuitas);
                $('#start_date, #end_date, #module').on('change', loadPerubahanEkuitas);
                $('#btnExportExcel').on('click', function() {
                    const params = $.param({
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        module: $('#module').val()
                    });
                    window.location.href = "{{ route('laporan.perubahan-ekuitas.export-excel') }}?" + params;
                });
                $('#btnPrintPdf').on('click', function() {
                    const params = $.param({
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        module: $('#module').val()
                    });
                    window.open("{{ route('laporan.perubahan-ekuitas.print') }}?" + params, '_blank');
                });
                $(document).on('click', '.btn-save-ekuitas', function() {
                    savePerubahanEkuitasAdjustment(this);
                });
                loadPerubahanEkuitas();
            });
        </script>
        <style>
            .ekuitas-input-group {
                min-width: 160px;
            }
        </style>
    </x-slot>
</x-app-layout>
