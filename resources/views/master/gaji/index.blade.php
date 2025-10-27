<x-app-layout>
    <x-slot name="pagetitle">Master Gaji</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Gaji Pegawai</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline shadow-sm">
                <div class="card-body">
                    <table id="tbgaji" class="table table-sm table-striped table-hover align-middle" style="width: 100%; font-size: small;">
                        <thead class="table-info">
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama Pegawai</th>
                                <th>Verifikasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔹 Modal Riwayat Gaji -->
    <div class="modal fade" id="modalGaji" tabindex="-1" aria-labelledby="modalGajiLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info text-white py-2">
                    <h5 class="modal-title" id="modalGajiLabel">
                        <i class="bi bi-cash-stack me-2"></i>Riwayat Gaji: <span id="namaPegawai"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="table-responsive mb-3">
                        <table id="tblRiwayat" class="table table-sm table-striped table-hover align-middle" style="font-size: small;">
                            <thead class="table-light">
                                <tr>
                                    <th>Tgl Aktif</th>
                                    <th>Gaji Pokok</th>
                                    <th>Masa Kerja</th>
                                    <th>Komunikasi</th>
                                    <th>Transport</th>
                                    <th>Konsumsi</th>
                                    <th>Tunj. Asuransi</th>
                                    <th>Jabatan</th>
                                    <th>Asuransi</th>
                                    <th>Verif</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <hr class="my-2">

                    <!-- 🔸 Form Input/Edit Gaji -->
                    <form id="frmGaji" class="small">
                        @csrf
                        <input type="hidden" name="idgaji" id="idgaji">
                        <input type="hidden" name="nik" id="nik">
                        <div class="row g-2">
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Tgl Aktif</label>
                                <input type="date" name="tgl_aktif" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Gaji Pokok</label>
                                <input type="number" name="gajipokok" class="form-control form-control-sm" min="0">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Masa Kerja</label>
                                <input type="number" name="masakerja" class="form-control form-control-sm" step="0.1">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Komunikasi</label>
                                <input type="number" name="komunikasi" class="form-control form-control-sm" min="0">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Transportasi</label>
                                <input type="number" name="transportasi" class="form-control form-control-sm" min="0">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Konsumsi</label>
                                <input type="number" name="konsumsi" class="form-control form-control-sm" min="0">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Tunj. Asuransi</label>
                                <input type="number" name="tunj_asuransi" class="form-control form-control-sm" min="0">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Jabatan</label>
                                <input type="number" name="jabatan" class="form-control form-control-sm" min="0">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Asuransi</label>
                                <input type="number" name="asuransi" class="form-control form-control-sm" min="0">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1">Verifikasi</label>
                                <select name="verifikasi" class="form-select form-select-sm">
                                    <option value="0">Belum</option>
                                    <option value="1">Ya</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-12 text-end align-self-end">
                                <button class="btn btn-success btn-sm mt-2" id="btnSave">
                                    <i class="bi bi-save me-1"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            let table = $('#tbgaji').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('master.gaji.pegawai') }}",
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nip' },
                    { data: 'name' },
                    { data: 'verif_status', orderable: false, searchable: false },
                    { data: 'aksi', orderable: false, searchable: false },
                ]
            });

            // 🔹 Tombol Master Gaji
            $('#tbgaji').on('click', '.btn-gaji', function() {
                let nik = $(this).data('nik');
                let name = $(this).data('name');
                $('#namaPegawai').text(name);
                $('#nik').val(nik);
                $('#modalGaji').modal('show');
                loadRiwayat(nik);
            });

            function loadRiwayat(nik) {
                $.get("{{ url('master/gaji/riwayat') }}/" + nik, function(res){
                    let rows = '';
                    res.forEach(r => {
                        rows += `
                        <tr>
                            <td>${r.tgl_aktif}</td>
                            <td>${r.gajipokok ?? 0}</td>
                            <td>${r.masakerja ?? 0}</td>
                            <td>${r.komunikasi ?? 0}</td>
                            <td>${r.transportasi ?? 0}</td>
                            <td>${r.konsumsi ?? 0}</td>
                            <td>${r.tunj_asuransi ?? 0}</td>
                            <td>${r.jabatan ?? 0}</td>
                            <td>${r.asuransi ?? 0}</td>
                            <td>${r.verifikasi == 1 ? '✅' : '❌'}</td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit me-1" data-id='${JSON.stringify(r)}'>
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-del" data-id="${r.idgaji}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                    $('#tblRiwayat tbody').html(rows);
                });
            }

            // 🔹 Simpan atau Update Gaji
            $('#frmGaji').on('submit', function(e){
                e.preventDefault();
                let id = $('#idgaji').val();
                let url = id ? "{{ url('master/gaji') }}/" + id : "{{ route('master.gaji.store') }}";
                let method = id ? 'PUT' : 'POST';
                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: function(res){
                        Swal.fire('Berhasil', res.message, 'success');
                        loadRiwayat($('#nik').val());
                        table.ajax.reload();
                        $('#frmGaji')[0].reset();
                        $('#idgaji').val('');
                        $('#btnSave').html('<i class="bi bi-save me-1"></i> Simpan').removeClass('btn-primary').addClass('btn-success');
                    },
                    error: function(xhr){
                        Swal.fire('Error', xhr.responseJSON?.message || 'Gagal menyimpan', 'error');
                    }
                });
            });

            // 🔹 Edit Gaji
            $('#tblRiwayat').on('click', '.btn-edit', function(){
                let data = $(this).data('id');
                for (const key in data) {
                    if ($('#frmGaji [name="' + key + '"]').length) {
                        $('#frmGaji [name="' + key + '"]').val(data[key]);
                    }
                }
                $('#idgaji').val(data.idgaji);
                $('#btnSave').html('<i class="bi bi-pencil me-1"></i> Update')
                    .removeClass('btn-success')
                    .addClass('btn-primary');
            });

            // 🔹 Hapus Gaji
            $('#tblRiwayat').on('click','.btn-del', function(){
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Hapus data?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((res)=>{
                    if(res.isConfirmed){
                        $.ajax({
                            url: "{{ url('master/gaji') }}/"+id,
                            method: 'DELETE',
                            data: {_token: '{{ csrf_token() }}'},
                            success: function(resp){
                                Swal.fire('Terhapus', resp.message, 'success');
                                loadRiwayat($('#nik').val());
                                table.ajax.reload();
                            }
                        });
                    }
                });
            });
        </script>
    </x-slot>
</x-app-layout>
