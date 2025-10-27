<x-app-layout>
    <x-slot name="pagetitle">Jadwal Pegawai</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0"><i class="bi bi-calendar3"></i> Jadwal Pegawai</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <!-- Filter -->
            <div class="card card-info card-outline mb-3">
                <div class="card-body row g-2 align-items-end" style="font-size: 0.875rem;">
                    <div class="col-md-2 col-sm-4">
                        <label class="form-label mb-1">Bulan</label>
                        <select id="bulan" class="form-select form-select-sm">
                            @for($m=1;$m<=12;$m++)
                                <option value="{{ $m }}" {{ $m==date('n')?'selected':'' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label class="form-label mb-1">Tahun</label>
                        <select id="tahun" class="form-select form-select-sm">
                            @for($y=date('Y')-2;$y<=date('Y')+2;$y++)
                                <option value="{{ $y }}" {{ $y==date('Y')?'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label mb-1">Unit Kerja</label>
                        <select id="unit_id" class="form-select form-select-sm">
                            <option value="">-- Pilih Unit --</option>
                            @foreach($unitkerja as $uk)
                                <option value="{{ $uk->id }}">{{ $uk->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-12 d-flex gap-2">
                        <button id="btnTampil" class="btn btn-sm btn-primary flex-fill">
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                        <button id="btnGenerate" class="btn btn-sm btn-success flex-fill">
                            <i class="bi bi-magic"></i> Generate
                        </button>
                    </div>
                </div>
            </div>
            <!-- Loading -->
            <div id="loading" class="text-center my-4" style="display:none;">
                <div class="spinner-border text-info" role="status"></div>
                <p class="mt-2">Memuat data...</p>
            </div>

            <!-- Tabel Pegawai & Jadwal -->
            <div class="card card-info card-outline">
                <div class="card-body table-responsive">
                    <style>
                        #tblJadwal th, #tblJadwal td {
                            white-space: nowrap;
                            min-width: 90px;
                            text-align: center;
                            vertical-align: middle;
                        }
                        #tblJadwal select {
                            width: 100%;
                            font-size: 0.8rem;
                            padding: 2px 4px;
                        }
                        #tblJadwal th:first-child,
                        #tblJadwal td:first-child {
                            position: sticky;
                            left: 0;
                            background: #f8f9fa;
                            z-index: 2;
                        }
                        #tblJadwal th:nth-child(2),
                        #tblJadwal td:nth-child(2) {
                            position: sticky;
                            left: 120px;
                            background: #f8f9fa;
                            z-index: 2;
                        }
                    </style>

                    <table class="table table-bordered table-sm text-center align-middle" id="tblJadwal" style="font-size: small;">
                        <thead class="table-info">
                            <tr>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <!-- tanggal akan diisi dinamis via JS -->
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
        let shifts = @json($kelompokjam->pluck('shift'));
        let kelompokJam = @json($kelompokjam->keyBy('shift'));

        function renderHeader(tgl){
            let thead = '<tr><th>Nama</th><th>Jabatan</th>';
            tgl.forEach(d => {
                let day = new Date(d);
                let tglnum = day.getDate();
                let hari = day.toLocaleDateString('id-ID', { weekday: 'short' });
                thead += `<th>${tglnum}<br><small>${hari}</small></th>`;
            });
            thead += '</tr>';
            return thead;
        }

        function renderJadwal(pegawai, tgl, jadwalDB){
            let tbody = '';
            pegawai.forEach(p => {
                tbody += `<tr><td>${p.name}</td><td>${p.jabatan ?? '-'}</td>`;
                tgl.forEach(d => {
                    let shiftVal = jadwalDB[p.nik]?.[d]?.shift || '';
                    tbody += `<td>
                        <select class="form-select form-select-sm shift-select"
                            data-nik="${p.nik}" data-tgl="${d}">
                            <option value="">-</option>`;
                    shifts.forEach(s => {
                        tbody += `<option value="${s}" ${s==shiftVal?'selected':''}>${s}</option>`;
                    });
                    tbody += `</select></td>`;
                });
                tbody += '</tr>';
            });
            return tbody;
        }

        $(function(){
            let pegawaiList = [], tglList = [], jadwalData = {};

            // ✅ Aktifkan Select2 untuk Unit Kerja
            $('#unit_id').select2({
                placeholder: '-- Pilih Unit Kerja --',
                allowClear: true,
                width: '100%'
            });

            $('#btnTampil').on('click', function(){
                let bulan = $('#bulan').val();
                let tahun = $('#tahun').val();
                let unit = $('#unit_id').val();
                if(!bulan || !tahun || !unit){
                    Swal.fire('Oops!', 'Pilih bulan, tahun, dan unit kerja.', 'warning');
                    return;
                }

                $('#loading').show();
                $.get("{{ route('master.jadwal.pegawai') }}", {bulan, tahun, unit_id:unit}, function(res){
                    $('#loading').hide();

                    pegawaiList = res.pegawai;
                    tglList = res.tgl;
                    jadwalData = {};

                    res.jadwal.forEach(j => {
                        if(!jadwalData[j.pegawai_nik]) jadwalData[j.pegawai_nik] = {};
                        jadwalData[j.pegawai_nik][j.tgl] = {shift:j.shift};
                    });

                    $('#tblJadwal thead').html(renderHeader(tglList));
                    $('#tblJadwal tbody').html(renderJadwal(pegawaiList, tglList, jadwalData));
                });
            });

            $(document).on('change', '.shift-select', function(){
                let nik = $(this).data('nik');
                let tgl = $(this).data('tgl');
                let shift = $(this).val();
                $.post("{{ route('master.jadwal.update') }}", {
                    _token: "{{ csrf_token() }}",
                    pegawai_nik: nik,
                    tgl: tgl,
                    shift: shift
                });
            });

            $('#btnGenerate').on('click', function(){
                let bulan = $('#bulan').val();
                let tahun = $('#tahun').val();
                let unit = $('#unit_id').val();
                if(!bulan || !tahun || !unit){
                    Swal.fire('Oops!', 'Pilih bulan, tahun, dan unit kerja.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Generate Jadwal?',
                    text: 'Sistem akan membuat jadwal otomatis sesuai pola [Pagi, Pagi, Siang, Siang, Malam, Malam, Libur, Libur].',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Generate!',
                    cancelButtonText: 'Batal'
                }).then((r) => {
                    if(r.isConfirmed){
                        $('#loading').show();
                        $.post("{{ route('master.jadwal.generate') }}", {
                            _token: "{{ csrf_token() }}",
                            bulan: bulan,
                            tahun: tahun,
                            unit_id: unit
                        }, function(res){
                            $('#loading').hide();
                            if(res.success){
                                Swal.fire('Berhasil', res.message, 'success');
                                $('#btnTampil').click();
                            }else{
                                Swal.fire('Gagal', res.message, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
</x-slot>

</x-app-layout>
