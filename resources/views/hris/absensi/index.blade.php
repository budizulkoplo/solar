<x-app-layout>
    <x-slot name="pagetitle">Plotting Absensi Pegawai</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0"><i class="bi bi-calendar-check"></i> Plotting Absensi Pegawai</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            {{-- Filter Periode --}}
            <div class="card card-info card-outline mb-3">
                <div class="card-body row g-2 align-items-end">
                    <div class="col-md-2">
                        <label>Bulan</label>
                        <select id="bulan" class="form-select form-select-sm">
                            @for($m=1;$m<=12;$m++)
                                <option value="{{ $m }}" {{ $m==date('n')?'selected':'' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Tahun</label>
                        <select id="tahun" class="form-select form-select-sm">
                            @for($y=date('Y')-2;$y<=date('Y')+2;$y++)
                                <option value="{{ $y }}" {{ $y==date('Y')?'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button id="btnTampil" class="btn btn-sm btn-primary">
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                    </div>
                </div>
            </div>

            {{-- Loading --}}
            <div id="loading" class="text-center my-4" style="display:none;">
                <div class="spinner-border text-info" role="status"></div>
                <p class="mt-2">Memuat data...</p>
            </div>

            {{-- Tabel --}}
            <div class="card card-info card-outline">
                <div class="card-body table-responsive">
                    <style>
                        /* ✅ Sticky Kolom Nama / Unit */
                        #tblPlotting th, #tblPlotting td {
                            white-space: nowrap;
                            min-width: 100px;
                            text-align: center;
                            vertical-align: middle;
                        }

                        #tblPlotting th:first-child,
                        #tblPlotting td:first-child {
                            position: sticky;
                            left: 0;
                            background: #f8f9fa;
                            z-index: 2;
                        }

                        #tblPlotting th:nth-child(2),
                        #tblPlotting td:nth-child(2) {
                            position: sticky;
                            left: 60px;
                            background: #f8f9fa;
                            z-index: 2;
                            text-align: left;
                        }

                        #tblPlotting {
                            font-size: 12px;
                            min-width: 1300px;
                        }
                    </style>
                    <!-- Filter Pencarian Nama -->
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <input type="text" id="searchNama" class="form-control form-control-sm" placeholder="Cari Nama...">
                        </div>
                    </div>
                    <table id="tblPlotting" class="table table-bordered table-sm text-center align-middle">
                        <thead class="table-info">
                            <tr>
                                <th style="width:50px;">No</th>
                                <th style="min-width:200px;">Nama / Unit</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            function getShiftColor(shift) {
                switch (shift?.toLowerCase()) {
                    case 'pagi': return 'bg-success text-white';
                    case 'siang': return 'bg-warning text-dark';
                    case 'malam': return 'bg-primary text-white';
                    case 'libur': return 'bg-secondary text-white';
                    case 'marketing': return 'bg-danger text-white';
                    case 'gs': return 'bg-warning text-dark';
                    default: return '';
                }
            }

            function getIzinColor(status) {
                switch (status?.toUpperCase()) {
                    case 'IZIN': return 'bg-warning text-white';
                    case 'SAKIT': return 'bg-info text-dark';
                    case 'CUTI': return 'bg-danger text-white';
                    default: return '';
                }
            }

            function renderHeader(tglList) {
                let thead = '<tr><th>No</th><th>Nama / Unit</th>';
                tglList.forEach(d => {
                    let day = new Date(d);
                    let tglnum = day.getDate();
                    let hari = day.toLocaleDateString('id-ID', { weekday: 'short' });
                    thead += `<th>${tglnum}<br><small>${hari}</small></th>`;
                });
                thead += '</tr>';
                return thead;
            }

            function renderBody(pegawaiData, tglList) {
                let tbody = '';
                pegawaiData.forEach((p, i) => {
                    tbody += `<tr>
                        <td>${i + 1}</td>
                        <td class="text-start">
                            <strong>${p.nama ?? '-'}</strong><br>
                            <small class="text-muted">${p.unitkerja ?? '-'}</small>
                        </td>`;

                    tglList.forEach(tgl => {
                        const item = p.hari.find(h => h.tgl === tgl);
                        const shift = item?.shift ?? '';
                        const izin = item?.status_izin ?? '';
                        const izinKet = item?.keterangan_izin ?? '';

                        let shiftClass = getShiftColor(shift);
                        let izinClass = getIzinColor(izin);

                        // Jika ada izin
                        if (izin) {
                            tbody += `
                                <td style="vertical-align:top; min-width:130px; padding:4px;">
                                    <div class="${izinClass} fw-bold text-center rounded-pill py-1 mb-1" style="font-size:11px;">
                                        ${izin}
                                    </div>
                                    <div class="text-muted text-start" style="font-size:11px;">${izinKet || '-'}</div>
                                </td>`;
                            return;
                        }

                        // Detail presensi
                        let detail = `
                            <div style="
                                display:grid;
                                grid-template-columns: 55px auto;
                                gap: 1px 4px;
                                text-align:left;
                                font-size:11px;
                                margin-top:3px;
                            ">
                                ${item?.in ? `<div><span class='badge bg-success w-100'>IN</span></div><div>${item.in}</div>` : ''}
                                ${item?.out ? `<div><span class='badge bg-danger w-100'>OUT</span></div><div>${item.out}</div>` : ''}
                                ${item?.terlambat > 0 && item?.terlambat_jam ? `<div><i class='bi bi-alarm text-danger'></i></div><div class='text-danger fw-semibold'>${item.terlambat_jam}</div>` : ''}
                                ${item?.lembur_in ? `<div><span class='badge bg-warning text-dark w-100'>Lmb IN</span></div><div>${item.lembur_in}</div>` : ''}
                                ${item?.lembur_out ? `<div><span class='badge bg-secondary w-100'>Lmb OUT</span></div><div>${item.lembur_out}</div>` : ''}
                            </div>
                        `;

                        if (!item?.in && !item?.out && !item?.lembur_in && !item?.lembur_out && !item?.terlambat_jam) {
                            detail = `<div class="text-muted text-start" style="font-size:11px; margin-top:3px;">-</div>`;
                        }

                        tbody += `
                            <td style="vertical-align:top; min-width:130px; padding:4px;">
                                <div class="${shiftClass} fw-bold text-center rounded-pill py-1 mb-1" style="font-size:11px;">
                                    ${shift}
                                </div>
                                ${detail}
                            </td>`;
                    });

                    tbody += '</tr>';
                });
                return tbody;
            }

            function loadData(bulan, tahun) {
                $('#loading').show();
                $.get("{{ route('hris.absensi.getdata') }}", { bulan, tahun }, function(res) {
                    $('#loading').hide();
                    if (!res.data || res.data.length === 0) {
                        Swal.fire('Tidak ada data', 'Periode ini kosong.', 'info');
                        return;
                    }
                    const tglList = res.data[0].hari.map(h => h.tgl);
                    $('#tblPlotting thead').html(renderHeader(tglList));
                    $('#tblPlotting tbody').html(renderBody(res.data, tglList));
                }).fail(() => {
                    $('#loading').hide();
                    Swal.fire('Error', 'Gagal memuat data dari server.', 'error');
                });
            }

            $(function() {
                let bulanNow = $('#bulan').val();
                let tahunNow = $('#tahun').val();
                loadData(bulanNow, tahunNow);

                $('#btnTampil').on('click', function() {
                    let bulan = $('#bulan').val();
                    let tahun = $('#tahun').val();
                    if (!bulan || !tahun) {
                        Swal.fire('Oops!', 'Pilih bulan dan tahun.', 'warning');
                        return;
                    }
                    loadData(bulan, tahun);
                });
            });

            function filterNama() {
                let keyword = $('#searchNama').val().toLowerCase();
                $('#tblPlotting tbody tr').each(function(){
                    let nama = $(this).find('td:nth-child(2)').text().toLowerCase();
                    if(nama.includes(keyword)){
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            $(function(){
                // trigger filter saat mengetik
                $('#searchNama').on('keyup', filterNama);
            });

        </script>
    </x-slot>
</x-app-layout>
