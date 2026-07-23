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

            <div class="row g-3 d-none" id="legacyNeracaLayout">
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

            <div class="card card-primary card-outline" id="templateNeracaLayout">
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0 neraca-template-table">
                        <thead>
                            <tr class="table-light text-center fw-bold">
                                <th colspan="3">AKTIVA</th>
                                <th colspan="3">PASIVA</th>
                            </tr>
                            <tr class="table-light text-center">
                                <th width="6%"></th>
                                <th></th>
                                <th width="18%"></th>
                                <th width="6%"></th>
                                <th></th>
                                <th width="18%"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyNeracaTemplate"></tbody>
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td colspan="2" class="text-center">Total Aktiva</td>
                                <td class="text-end" id="templateTotalAktiva">Rp 0</td>
                                <td colspan="2" class="text-center">Total Pasiva</td>
                                <td class="text-end" id="templateTotalPasiva">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
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

            function normalizeTemplateLabel(label) {
                return String(label || '')
                    .replace(/&nbsp;/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();
            }

            function makeNeracaRowKey(side, row, index) {
                return `${side}-${index}-${normalizeTemplateLabel(row.label).replace(/[^a-z0-9]+/g, '-')}`;
            }

            function applyNeracaAdjustments(rows, side, adjustments) {
                rows.forEach((row, index) => {
                    row.side = side;
                    row.rowKey = makeNeracaRowKey(side, row, index);

                    const adjustment = adjustments?.[`${side}:${row.rowKey}`];
                    if (!row.isHeader && !row.isSubtotal && !row.systemValue && adjustment) {
                        row.value = Number(adjustment.value || 0);
                        row.adjusted = true;
                    }
                });
            }

            function recalculateTemplateSubtotals(rows) {
                let activeHeaderIndex = -1;

                rows.forEach((row, index) => {
                    if (row.isHeader) {
                        activeHeaderIndex = index;
                        return;
                    }

                    if (!row.isSubtotal) {
                        return;
                    }

                    const items = rows.slice(activeHeaderIndex + 1, index);
                    row.value = items
                        .filter(item => !item.isHeader && !item.isSubtotal)
                        .reduce((total, item) => total + Number(item.value || 0), 0);
                });
            }

            function getTemplateLeafTotal(rows) {
                return rows
                    .filter(row => !row.isHeader && !row.isSubtotal)
                    .reduce((total, row) => total + Number(row.value || 0), 0);
            }

            function renderEditableValueCell(row) {
                if (row.isHeader || !row.label) {
                    return '';
                }

                if (row.isSubtotal) {
                    return formatRupiah(row.value || 0);
                }

                return `
                    <input type="text"
                        class="form-control form-control-sm text-end neraca-value-input ${row.adjusted ? 'is-valid' : ''}"
                        value="${Number(row.value || 0).toLocaleString('id-ID')}"
                        data-side="${row.side}"
                        data-row-key="${row.rowKey}"
                        data-label="${String(row.label || '').replace(/"/g, '&quot;')}">
                `;
            }

            function collectTemplateValueMap(groups) {
                const map = {};

                (groups || []).forEach(group => {
                    const groupKey = normalizeTemplateLabel(group.rincian);
                    map[groupKey] = Number(group.subtotal_raw || 0);

                    (group.items || []).forEach(item => {
                        const key = normalizeTemplateLabel(item.nama_akun);
                        map[key] = Number(item.nilai_raw || 0);
                    });

                    const subtotalKey = normalizeTemplateLabel(group.subtotal_label || ('Sub Total ' + group.rincian));
                    map[subtotalKey] = Number(group.subtotal_raw || 0);
                });

                return map;
            }

            function buildCompanyTemplateRows(response) {
                const aktivaMap = collectTemplateValueMap(response.data?.aktiva_groups || []);
                const pasivaMap = collectTemplateValueMap(response.data?.pasiva_groups || []);

                const aktivaRows = [
                    { no: 'a.', label: 'Aktiva Lancar', value: null, isHeader: true },
                    { no: '1', label: 'Kas dan Bank (saldo)', value: aktivaMap[normalizeTemplateLabel('Kas dan Bank (saldo)')] ?? 0 },
                    { no: '2', label: 'Piutang Usaha', value: aktivaMap[normalizeTemplateLabel('Piutang Usaha')] ?? 0 },
                    { no: '3', label: 'Biaya Dibayar Dimuka', value: aktivaMap[normalizeTemplateLabel('Biaya Dibayar Dimuka')] ?? 0 },
                    { no: '4', label: 'Uang Muka Pembelian', value: aktivaMap[normalizeTemplateLabel('Uang Muka Pembelian')] ?? 0 },
                    { no: '5', label: 'Sewa Dibayar Dimuka', value: aktivaMap[normalizeTemplateLabel('Sewa Dibayar Dimuka')] ?? 0 },
                    { no: '6', label: 'Persediaan Real Estate (Tanah & Bangunan Siap Jual)', value: aktivaMap[normalizeTemplateLabel('Persediaan Real Estate (Tanah & Bangunan Siap Jual)')] ?? 0 },
                    { no: '', label: '&nbsp;&nbsp;&nbsp;Bangunan', value: aktivaMap[normalizeTemplateLabel('Bangunan')] ?? 0 },
                    { no: '', label: '&nbsp;&nbsp;&nbsp;Bahan Baku', value: aktivaMap[normalizeTemplateLabel('Bahan Baku')] ?? 0 },
                    { no: '', label: '&nbsp;&nbsp;&nbsp;Tanah', value: aktivaMap[normalizeTemplateLabel('Tanah')] ?? 0 },
                    { no: '', label: 'Sub Total Aktiva Lancar', value: aktivaMap[normalizeTemplateLabel('Sub Total Aktiva Lancar')] ?? response.summary?.total_aktiva_raw ?? 0, isSubtotal: true },
                    { no: 'b.', label: 'Aktiva Tetap', value: null, isHeader: true },
                    { no: '1', label: 'Tanah', value: 0 },
                    { no: '2', label: 'Bangunan', value: 0 },
                    { no: '3', label: 'Inventaris Kantor', value: 0 },
                    { no: '4', label: 'Kendaraan', value: 0 },
                    { no: '5', label: 'Peralatan Kantor', value: 0 },
                    { no: '6', label: 'Peralatan Proyek', value: 0 },
                    { no: '7', label: 'Akumulasi Penyusutan (-)', value: 0 },
                    { no: '', label: 'Sub Total Aktiva Tetap', value: 0, isSubtotal: true },
                    { no: 'c.', label: 'Aktiva Lancar Lainnya', value: null, isHeader: true },
                    { no: '1', label: 'Piutang Pengurus', value: 0 },
                    { no: '2', label: 'Piutang Karyawan', value: 0 },
                    { no: '3', label: 'Piutang Lainnya', value: 0 },
                    { no: '4', label: 'Piutang Antar Perusahaan', value: 0 },
                    { no: '', label: 'Sub Total Aktiva Lancar Lainnya', value: 0, isSubtotal: true },
                ];

                const pasivaRows = [
                    { no: 'd.', label: 'Hutang Jangka Pendek', value: null, isHeader: true },
                    { no: '1', label: 'Hutang Usaha', value: pasivaMap[normalizeTemplateLabel('Hutang Usaha')] ?? 0 },
                    { no: '2', label: 'Hutang Bank', value: pasivaMap[normalizeTemplateLabel('Hutang Bank')] ?? 0 },
                    { no: '3', label: 'Hutang Pembiayaan', value: 0, systemValue: true },
                    { no: '4', label: 'Hutang Pajak', value: pasivaMap[normalizeTemplateLabel('Hutang Pajak')] ?? 0 },
                    { no: '5', label: 'Hutang Aset', value: pasivaMap[normalizeTemplateLabel('Hutang Aset')] ?? 0 },
                    { no: '6', label: 'Uang muka yang diterima (Pendapatan diterima dimuka)', value: pasivaMap[normalizeTemplateLabel('Uang Muka yang Diterima (Pendapatan Diterima Dimuka)')] ?? 0 },
                    { no: '7', label: 'Hutang Lain-Lain', value: pasivaMap[normalizeTemplateLabel('Hutang Lain-Lain')] ?? 0 },
                    { no: '', label: 'Sub Total Hutang Jangka Pendek', value: 0, isSubtotal: true },
                    { no: 'e.', label: 'Hutang Jangka Panjang', value: null, isHeader: true },
                    { no: '1', label: 'Hutang Usaha', value: pasivaMap[normalizeTemplateLabel('Hutang Usaha (Jangka Panjang)')] ?? 0 },
                    { no: '2', label: 'Hutang Bank', value: pasivaMap[normalizeTemplateLabel('Hutang Bank (Jangka Panjang)')] ?? 0 },
                    { no: '3', label: 'Hutang Pembiayaan', value: Math.max(0, Number(response.pembiayaan?.hutang_jangka_panjang_raw ?? pasivaMap[normalizeTemplateLabel('Hutang Pembiayaan (Jangka Panjang)')] ?? 0)), systemValue: true },
                    { no: '4', label: 'Hutang Pajak', value: pasivaMap[normalizeTemplateLabel('Hutang Pajak (Jangka Panjang)')] ?? 0 },
                    { no: '5', label: 'Hutang Aset', value: pasivaMap[normalizeTemplateLabel('Hutang Aset (Jangka Panjang)')] ?? 0 },
                    { no: '6', label: 'Hutang Lain - lain', value: pasivaMap[normalizeTemplateLabel('Hutang Lain - lain (Jangka Panjang)')] ?? 0 },
                    { no: '', label: 'Sub Total Hutang Jangka Panjang', value: 0, isSubtotal: true },
                    { no: 'f.', label: 'Ekuitas', value: null, isHeader: true },
                    { no: '1', label: 'Modal Disetor', value: pasivaMap[normalizeTemplateLabel('Modal Disetor')] ?? 0 },
                    { no: '2', label: 'Laba Ditahan', value: pasivaMap[normalizeTemplateLabel('Laba Ditahan')] ?? 0 },
                    { no: '', label: 'Sub Total Ekuitas', value: 0, isSubtotal: true },
                ];

                applyNeracaAdjustments(aktivaRows, 'aktiva', response.adjustments || {});
                applyNeracaAdjustments(pasivaRows, 'pasiva', response.adjustments || {});
                recalculateTemplateSubtotals(aktivaRows);
                recalculateTemplateSubtotals(pasivaRows);

                return { aktivaRows, pasivaRows };
            }

            function renderCompanyTemplate(response) {
                const { aktivaRows, pasivaRows } = buildCompanyTemplateRows(response);
                const totalRows = Math.max(aktivaRows.length, pasivaRows.length);
                let html = '';

                for (let i = 0; i < totalRows; i++) {
                    const aktiva = aktivaRows[i] || { no: '', label: '', value: null };
                    const pasiva = pasivaRows[i] || { no: '', label: '', value: null };

                    const aktivaClass = aktiva.isHeader ? 'table-primary fw-bold' : aktiva.isSubtotal ? 'table-light fw-bold' : '';
                    const pasivaClass = pasiva.isHeader ? 'table-primary fw-bold' : pasiva.isSubtotal ? 'table-light fw-bold' : '';

                    html += `
                        <tr>
                            <td class="text-center ${aktivaClass}">${aktiva.no || ''}</td>
                            <td class="${aktivaClass}">${aktiva.label || ''}</td>
                            <td class="text-end ${aktivaClass}">${renderEditableValueCell(aktiva)}</td>
                            <td class="text-center ${pasivaClass}">${pasiva.no || ''}</td>
                            <td class="${pasivaClass}">${pasiva.label || ''}</td>
                            <td class="text-end ${pasivaClass}">${renderEditableValueCell(pasiva)}</td>
                        </tr>
                    `;
                }

                $('#tbodyNeracaTemplate').html(html);
                const totalAktiva = getTemplateLeafTotal(aktivaRows);
                const totalPasiva = getTemplateLeafTotal(pasivaRows);
                response.summary.total_aktiva_raw = totalAktiva;
                response.summary.total_pasiva_raw = totalPasiva;
                response.summary.balance = Math.abs(totalAktiva - totalPasiva) < 0.5;
                response.summary.difference_raw = Math.abs(totalAktiva - totalPasiva);
                $('#templateTotalAktiva').text(formatRupiah(totalAktiva));
                $('#templateTotalPasiva').text(formatRupiah(totalPasiva));
                $('#templateNeracaLayout').removeClass('d-none');
                $('#legacyNeracaLayout').addClass('d-none');
            }

            function renderGroups(selector, groups) {
                if (!groups || groups.length === 0) {
                    $(selector).html(
                        `<tr><td colspan="3" class="text-center text-muted">Tidak ada data</td></tr>`
                    );
                    return;
                }

                const isTemplateStyle = groups.every(group => group.template_style);

                if (isTemplateStyle) {
                    let html = '';

                    groups
                        .sort((a, b) => (a.order || 9999) - (b.order || 9999))
                        .forEach(group => {
                            html += `
                                <tr class="table-primary fw-bold">
                                    <td class="text-center">${group.prefix || ''}</td>
                                    <td>${group.rincian}</td>
                                    <td class="text-end"></td>
                                </tr>
                            `;

                            group.items.forEach(row => {
                                html += `
                                    <tr>
                                        <td class="text-center">${row.nomor || ''}</td>
                                        <td>${row.nama_akun}</td>
                                        <td class="text-end">${formatRupiah(row.nilai_raw)}</td>
                                    </tr>
                                `;
                            });

                            html += `
                                <tr class="table-light fw-bold">
                                    <td></td>
                                    <td>${group.subtotal_label || ('Sub Total ' + group.rincian)}</td>
                                    <td class="text-end">${formatRupiah(group.subtotal_raw)}</td>
                                </tr>
                            `;
                        });

                    $(selector).html(html);
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

                    if (['company', 'project'].includes($('#module').val())) {
                        renderCompanyTemplate(response);
                    } else {
                        $('#templateNeracaLayout').addClass('d-none');
                        $('#legacyNeracaLayout').removeClass('d-none');
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

            function saveNeracaAdjustment(input) {
                const $input = $(input);
                const value = parseRupiah($input.val());
                const side = $input.data('side');
                const rowKey = $input.data('row-key');
                const label = $input.data('label');

                $input.val(value.toLocaleString('id-ID'));

                return $.post("{{ route('laporan.neraca.adjustment') }}", {
                    _token: '{{ csrf_token() }}',
                    module: $('#module').val(),
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    side: side,
                    row_key: rowKey,
                    label: label,
                    value: value
                }).done(function(response) {
                    if (response.success) {
                        $input.addClass('is-valid');
                    }
                }).fail(function(xhr) {
                    alert(xhr.responseJSON?.message || 'Gagal menyimpan nilai neraca');
                });
            }

            $(document).ready(function() {
                $('#btnFilter').on('click', loadNeraca);
                $('#start_date, #end_date, #module').on('change', loadNeraca);
                $('#btnExportExcel').on('click', function() {
                    const params = $.param({
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        module: $('#module').val()
                    });
                    window.location.href = "{{ route('laporan.neraca.export-excel') }}?" + params;
                });
                $('#btnPrintPdf').on('click', function() {
                    const params = $.param({
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        module: $('#module').val()
                    });
                    window.open("{{ route('laporan.neraca.print') }}?" + params, '_blank');
                });
                $(document).on('change blur', '.neraca-value-input', function(e) {
                    if (e.type === 'blur' && this.dataset.savedOnChange === '1') {
                        this.dataset.savedOnChange = '';
                        return;
                    }

                    if (e.type === 'change') {
                        this.dataset.savedOnChange = '1';
                    }

                    saveNeracaAdjustment(this).done(loadNeraca);
                });
                loadNeraca();
            });
        </script>
        <style>
            .neraca-template-table td,
            .neraca-template-table th {
                vertical-align: middle;
            }
        </style>
    </x-slot>
</x-app-layout>
