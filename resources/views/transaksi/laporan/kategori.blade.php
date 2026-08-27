<x-app-layout>
    <x-slot name="pagetitle">Laporan by Kategori</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><i class="bi bi-tags"></i> Laporan Transaksi by Kategori</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-outline card-primary mb-3">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label mb-1">Tanggal Awal</label>
                            <input type="date" id="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Tanggal Akhir</label>
                            <input type="date" id="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Module</label>
                            <select id="module" class="form-select form-select-sm">
                                <option value="project" {{ $module == 'project' ? 'selected' : '' }}>Project</option>
                                <option value="company" {{ $module == 'company' ? 'selected' : '' }}>PT/Company</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Header Transaksi</label>
                            <select id="idheader" class="form-select form-select-sm">
                                <option value="">Semua Header</option>
                                @foreach($transaksiHeaders as $header)
                                    <option value="{{ $header->id }}">{{ $header->keterangan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Kode Transaksi</label>
                            <select id="kode_transaksi_ids" class="form-select form-select-sm" multiple>
                                @foreach($kodeTransaksiList as $kode)
                                    <option value="{{ $kode->id }}" data-header="{{ $kode->idheader }}">
                                        ({{ $kode->kodetransaksi }}) {{ $kode->transaksi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btnFilter">
                            <i class="bi bi-search"></i> Tampilkan
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

            <div id="summaryInfo" class="alert alert-info mb-3">Pilih kode transaksi, lalu klik Tampilkan.</div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="small-box text-bg-success">
                        <div class="inner">
                            <h3 id="totalPemasukan">Rp 0</h3>
                            <p>Total Pemasukan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box text-bg-danger">
                        <div class="inner">
                            <h3 id="totalPengeluaran">Rp 0</h3>
                            <p>Total Pengeluaran</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box text-bg-primary">
                        <div class="inner">
                            <h3 id="selisih">Rp 0</h3>
                            <p>Selisih (Masuk - Keluar)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info mb-3">
                <div class="card-header"><strong>Total per Kode Transaksi</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Transaksi</th>
                                <th>Header</th>
                                <th class="text-end">Pemasukan</th>
                                <th class="text-end">Pengeluaran</th>
                                <th class="text-end">Selisih</th>
                                <th class="text-end">Qty</th>
                            </tr>
                        </thead>
                        <tbody id="tbodySummary"></tbody>
                    </table>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card card-success card-outline">
                        <div class="card-header"><strong>PEMASUKAN</strong></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="8%">No</th>
                                        <th>Transaksi</th>
                                        <th width="28%" class="text-end">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyPemasukan"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-danger card-outline">
                        <div class="card-header"><strong>PENGELUARAN</strong></div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="8%">No</th>
                                        <th>Transaksi</th>
                                        <th width="28%" class="text-end">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyPengeluaran"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            const kodeOptions = @json($kodeTransaksiList->map(fn ($item) => [
                'id' => $item->id,
                'idheader' => $item->idheader,
                'label' => '(' . $item->kodetransaksi . ') ' . $item->transaksi,
            ]));

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

            function filterKodeOptions() {
                const headerId = $('#idheader').val();
                const selected = $('#kode_transaksi_ids').val() || [];
                $('#kode_transaksi_ids').empty();

                kodeOptions.forEach(item => {
                    if (!headerId || String(item.idheader) === String(headerId)) {
                        const option = new Option(item.label, item.id, false, selected.includes(String(item.id)));
                        $('#kode_transaksi_ids').append(option);
                    }
                });

                $('#kode_transaksi_ids').trigger('change');
            }

            function renderDetails(selector, rows) {
                if (!rows || rows.length === 0) {
                    $(selector).html('<tr><td colspan="3" class="text-center text-muted">Tidak ada data</td></tr>');
                    return;
                }

                let html = '';
                rows.forEach((row, idx) => {
                    html += `
                        <tr>
                            <td class="text-center">${idx + 1}</td>
                            <td>
                                <div><strong>${row.nota_no}</strong> <small class="text-muted">${row.tanggal_display}</small></div>
                                <div>(${row.kode}) ${row.nama}</div>
                                <small class="text-muted">${row.deskripsi}</small>
                                <div><small>${row.vendor} | ${row.rekening}</small></div>
                            </td>
                            <td class="text-end">${formatRupiah(row.nominal_raw)}</td>
                        </tr>
                    `;
                });
                $(selector).html(html);
            }

            function renderSummaries(rows) {
                if (!rows || rows.length === 0) {
                    $('#tbodySummary').html('<tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>');
                    return;
                }

                let html = '';
                rows.forEach(row => {
                    html += `
                        <tr>
                            <td>${row.kode}</td>
                            <td>${row.nama}</td>
                            <td>${row.header}</td>
                            <td class="text-end text-success">${formatRupiah(row.total_pemasukan_raw)}</td>
                            <td class="text-end text-danger">${formatRupiah(row.total_pengeluaran_raw)}</td>
                            <td class="text-end">${formatRupiah(row.selisih_raw)}</td>
                            <td class="text-end">${row.jumlah_transaksi}</td>
                        </tr>
                    `;
                });
                $('#tbodySummary').html(html);
            }

            function getFilterParams() {
                return {
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    module: $('#module').val(),
                    idheader: $('#idheader').val(),
                    'kode_transaksi_ids[]': $('#kode_transaksi_ids').val() || []
                };
            }

            function loadLaporanKategori() {
                const kodeIds = $('#kode_transaksi_ids').val() || [];
                const headerId = $('#idheader').val();

                if (kodeIds.length === 0 && !headerId) {
                    $('#summaryInfo').text('Pilih kode transaksi, lalu klik Tampilkan.').removeClass('d-none alert-success').addClass('alert-info');
                    renderSummaries([]);
                    renderDetails('#tbodyPemasukan', []);
                    renderDetails('#tbodyPengeluaran', []);
                    $('#totalPemasukan, #totalPengeluaran, #selisih').text('Rp 0');
                    return;
                }

                $.get("{{ route('laporan.kategori.data') }}", getFilterParams())
                    .done(function(response) {
                        if (!response.success) {
                            alert(response.message || 'Gagal memuat data');
                            return;
                        }

                        renderSummaries(response.data.summaries);
                        renderDetails('#tbodyPemasukan', response.data.pemasukan);
                        renderDetails('#tbodyPengeluaran', response.data.pengeluaran);

                        $('#totalPemasukan').text(formatRupiah(response.summary.total_pemasukan_raw));
                        $('#totalPengeluaran').text(formatRupiah(response.summary.total_pengeluaran_raw));
                        $('#selisih').text(formatRupiah(response.summary.selisih_raw));

                        $('#summaryInfo').html(`
                            <strong>Periode:</strong> ${formatDisplayDate(response.period.start)} - ${formatDisplayDate(response.period.end)}
                            <span class="ms-2 badge bg-secondary">${response.period.module === 'project' ? 'Project' : 'PT/Company'}</span>
                            <div class="mt-1"><strong>Kategori:</strong> ${response.summary.kode_label}</div>
                            <div><small>Jumlah transaksi: ${response.summary.jumlah_transaksi}</small></div>
                        `).removeClass('d-none');
                    })
                    .fail(function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat memuat data';
                        alert(msg);
                    });
            }

            $(document).ready(function() {
                $('#kode_transaksi_ids').select2({
                    placeholder: 'Pilih kode transaksi',
                    width: '100%',
                    allowClear: true
                });
                $('#idheader').select2({
                    placeholder: 'Semua Header',
                    width: '100%',
                    allowClear: true
                });

                $('#idheader').on('change', filterKodeOptions);
                $('#btnFilter').on('click', loadLaporanKategori);
                $('#btnExportExcel').on('click', function() {
                    const params = $.param(getFilterParams());
                    window.location.href = "{{ route('laporan.kategori.export-excel') }}?" + params;
                });
                $('#btnPrintPdf').on('click', function() {
                    const params = $.param(getFilterParams());
                    window.open("{{ route('laporan.kategori.print') }}?" + params, '_blank');
                });
            });
        </script>
    </x-slot>
</x-app-layout>
