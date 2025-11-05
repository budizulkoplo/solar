<x-app-layout>
    <x-slot name="pagetitle">Pengajuan Izin</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Manajemen Pengajuan Izin</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-header d-flex justify-content-start align-items-center gap-2 flex-wrap">
                    <select id="filterBulan" class="form-select form-select-sm" style="width:auto;">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ sprintf('%02d', $i) }}" {{ $i == date('m') ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                            </option>
                        @endfor
                    </select>

                    <select id="filterTahun" class="form-select form-select-sm" style="width:auto;">
                        @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <button id="btnFilter" class="btn btn-sm btn-secondary">
                        <i class="bi bi-filter"></i> Tampilkan
                    </button>

                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalIzin" id="btnAdd">
                        <i class="bi bi-file-earmark-plus"></i> Tambah Izin
                    </button>
                </div>
                <div class="card-body">
                    <table id="tbizin" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Tanggal & Jam</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Approved</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form --}}
    <div class="modal fade" id="modalIzin" tabindex="-1">
        <div class="modal-dialog">
            <form id="frmIzin" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="fidizin" id="fidizin">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Pengajuan Izin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Pegawai (NIK - Nama)</label>
                            <select class="form-control select2" name="nik" id="nik" required style="width: 100%;"></select>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Izin</label>
                                <input type="date" class="form-control" name="tgl_izin" id="tgl_izin" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Mulai</label>
                                <input type="time" class="form-control" name="izin_mulai" id="izin_mulai">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Selesai</label>
                                <input type="time" class="form-control" name="izin_selesai" id="izin_selesai">
                            </div>
                        </div>
                        <div class="mb-2" id="div_lampiran" style="display:none;">
                            <label class="form-label">Upload Surat Sakit (PDF / JPG / PNG)</label>
                            <input type="file" class="form-control" name="lampiran" id="lampiran" accept=".pdf,.jpg,.jpeg,.png">
                            <div id="lampiran_preview" class="mt-1"></div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="i">Izin</option>
                                <option value="c">Cuti</option>
                                <option value="s">Sakit</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="keterangan" required></textarea>
                        </div>

                        <div class="mb-2 approval-section" style="display:none;">
                            <label class="form-label">Approval</label>
                            <select name="approved" id="approved" class="form-control">
                                <option value="0">Belum Disetujui</option>
                                <option value="1">Disetujui</option>
                                <option value="2">Tidak Disetujui</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            // Datatable init
            let table = $('#tbizin').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('hris.pengajuanizin.data') }}",
                    data: function (d) {
                        d.bulan = $('#filterBulan').val();
                        d.tahun = $('#filterTahun').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nik' },
                    { data: 'pegawai' },
                    { data: 'tgl_izin' },
                    { data: 'status_text' },
                    { data: 'keterangan' },
                    { data: 'approved_text' },
                    { data: 'aksi', orderable: false, searchable: false },
                ]
            });

            $('#btnFilter').click(() => table.ajax.reload());

            // select2 pegawai
            $('.select2').select2({
                ajax: {
                    url: "{{ route('hris.pengajuanizin.select2pegawai') }}",
                    dataType: 'json',
                    delay: 250,
                    processResults: data => ({ results: data.results })
                },
                placeholder: 'Pilih Pegawai',
                allowClear: true,
                dropdownParent: $('#modalIzin')
            });

            // tampil lampiran hanya untuk sakit
            $('#status').on('change', function () {
                if ($(this).val() === 's') $('#div_lampiran').show();
                else $('#div_lampiran').hide();
            });

            // tambah data baru
            $('#btnAdd').on('click', function(){
                $('#frmIzin')[0].reset();
                $('#fidizin').val('');
                $('#nik').val(null).trigger('change');
                $('#div_lampiran').hide();
                $('#lampiran_preview').html('');
                $('.approval-section').hide();
                $('#modalIzin .modal-title').text('Tambah Pengajuan Izin');
            });

            // simpan form
            $('#frmIzin').on('submit', function(e){
                e.preventDefault();
                $.ajax({
                    url: "{{ route('hris.pengajuanizin.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(){
                        $('#modalIzin').modal('hide');
                        table.ajax.reload();
                        $('#frmIzin')[0].reset();
                        $('#nik').val(null).trigger('change');
                    },
                    error: () => alert('Terjadi kesalahan saat menyimpan data.')
                });
            });

            // edit data
            $('#tbizin').on('click', '.btn-edit', function(){
                let id = $(this).data('id');
                $.get("{{ url('hris/pengajuan-izin/show') }}/" + id, function(res){
                    $('#fidizin').val(res.id);
                    $('#tgl_izin').val(res.tgl_izin);
                    $('#status').val(res.status).trigger('change');
                    $('#keterangan').val(res.keterangan);
                    $('#approved').val(res.status_approved ?? 0);
                    $('#izin_mulai').val(res.izin_mulai ? res.izin_mulai.substring(11,16) : '');
                    $('#izin_selesai').val(res.izin_selesai ? res.izin_selesai.substring(11,16) : '');

                    let pegawaiText = res.nik + ' - ' + (res.user?.name ?? '');
                    let newOption = new Option(pegawaiText, res.nik, true, true);
                    $('#nik').append(newOption).trigger('change');

                    if(res.status === 's'){
                        $('#div_lampiran').show();
                        if(res.lampiran){
                            let url = "{{ asset('storage/surat_sakit') }}/" + res.lampiran;
                            $('#lampiran_preview').html(`<a href="${url}" target="_blank" class="text-primary"><i class="bi bi-file-earmark-pdf"></i> Lihat Lampiran</a>`);
                        }
                    } else {
                        $('#div_lampiran').hide();
                        $('#lampiran_preview').html('');
                    }

                    $('.approval-section').show();
                    $('#modalIzin .modal-title').text('Edit Pengajuan Izin');
                    $('#modalIzin').modal('show');
                });
            });

            // hapus data
            $('#tbizin').on('click', '.btn-hapus', function(){
                if(confirm('Yakin ingin menghapus data ini?')){
                    let id = $(this).data('id');
                    $.ajax({
                        url: "{{ url('hris/pengajuan-izin') }}/" + id,
                        method: "DELETE",
                        data: {_token: "{{ csrf_token() }}"},
                        success: () => table.ajax.reload()
                    });
                }
            });
        </script>
    </x-slot>
</x-app-layout>
