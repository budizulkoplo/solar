<x-app-layout>
    <x-slot name="pagetitle">Pegawai</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Manajemen Pegawai</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <button class="btn btn-primary btn-sm shadow" data-bs-toggle="modal" data-bs-target="#modalPegawai" id="btnAdd">
                        <i class="bi bi-file-earmark-plus"></i> Tambah Pegawai
                    </button>
                </div>
                <div class="card-body">
                    <table id="tbpegawai" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Unit Kerja</th>
                                <th>Jabatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔹 Modal Add/Edit -->
    <div class="modal fade" id="modalPegawai" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="frmPegawai" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="fidusers" id="fidusers">

                <div class="modal-content shadow-lg border-0 rounded-4">
                    <div class="modal-header bg-gradient-primary text-white rounded-top-4">
                        <h5 class="modal-title"><i class="bi bi-person-badge"></i> Form Pegawai</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body bg-light">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">NIK</label>
                                <input type="text" class="form-control" name="nik" id="nik" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>

                            <div class="col-md-6 mb-2">
                                <label class="form-label">NIP</label>
                                <input type="text" class="form-control" name="nip" id="nip" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No HP</label>
                                <input type="text" class="form-control" name="nohp">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jabatan</label>
                                <select name="jabatan" class="form-select" required>
                                    <option value="">-- Pilih Jabatan --</option>
                                    <option value="Komisaris Utama">Komisaris Utama</option>
                                    <option value="Komisaris">Komisaris</option>
                                    <option value="Direktur Utama">Direktur Utama</option>
                                    <option value="Direktur">Direktur</option>
                                    <option value="Accounting">Accounting</option>
                                    <option value="HRD">HRD</option>
                                    <option value="IT">IT</option>
                                    <option value="Project Manager">Project Manager</option>
                                    <option value="Project Administrator">Project Administrator</option>
                                    <option value="Marketing Manager">Marketing Manager</option>
                                    <option value="Marketing">Marketing</option>
                                </select>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Unit Kerja</label>
                                <select name="id_unitkerja" class="form-select" required>
                                    <option value="">-- Pilih Unit Kerja --</option>
                                    @foreach ($unitkerja as $u)
                                        <option value="{{ $u->id }}">{{ $u->company_name }} ({{ $u->lokasi }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Awal Kontrak</label>
                                <input type="date" class="form-control" name="awal_kontrak" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Akhir Kontrak</label>
                                <input type="date" class="form-control" name="akhir_kontrak">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tempat Lahir</label>
                                <input type="text" class="form-control" name="tempat_lahir">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tanggal Lahir</label>
                                <input type="date" class="form-control" name="tanggal_lahir">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Alamat KTP</label>
                                <textarea name="alamat_ktp" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Kode Pos</label>
                                <input type="text" class="form-control" name="kode_pos">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Golongan Darah</label>
                                <select name="gol_darah" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="AB">AB</option>
                                    <option value="O">O</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status Perkawinan</label>
                                <select name="status_perkawinan" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    <option value="LAJANG">Lajang</option>
                                    <option value="KAWIN">Kawin</option>
                                    <option value="CERAI">Cerai</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jumlah Anak</label>
                                <input type="number" class="form-control" name="jumlah_anak" min="0">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Ibu Kandung</label>
                                <input type="text" class="form-control" name="nama_ibu_kandung">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pendidikan Terakhir</label>
                                <input type="text" class="form-control" name="pendidikan_terakhir">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. JKN-KIS</label>
                                <input type="text" class="form-control" name="no_jkn_kis">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. KPJ</label>
                                <input type="text" class="form-control" name="no_kpj">
                            </div>

                            <!-- Status Pegawai -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status Pegawai</label>
                                <select name="status" class="form-select">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer bg-light rounded-bottom-4">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Tutup
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

   <x-slot name="jscustom">
        <script>
            // 🔹 Inisialisasi DataTables
            let table = $('#tbpegawai').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25, // default tampil 25 data
                lengthMenu: [10, 25, 50, 100],
                ajax: "{{ route('pegawai.getdata') }}",
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nip' },
                    { data: 'nik' },
                    { data: 'name' },
                    { data: 'unitkerja', defaultContent: '-' },
                    { data: 'jabatan' },
                    { data: 'status' },
                    { data: 'aksi', orderable: false, searchable: false }
                ]
            });

            // 🔹 Inisialisasi Select2 untuk Unit Kerja
            $(document).ready(function() {
                $('select[name="id_unitkerja"]').select2({
                    dropdownParent: $('#modalPegawai'),
                    placeholder: '-- Pilih Unit Kerja --',
                    allowClear: true,
                    width: '100%'
                });
            });

            // 🔹 Tombol Tambah Pegawai
            $('#btnAdd').click(function() {
                $('#frmPegawai')[0].reset();
                $('#fidusers').val('');
                $('select[name="id_unitkerja"]').val('').trigger('change'); // reset select2
            });

            // 🔹 Simpan / Update data
            $('#frmPegawai').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: "{{ route('pegawai.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Menyimpan...',
                            text: 'Mohon tunggu sebentar.',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    },
                    success: function(res) {
                        Swal.close();
                        $('#modalPegawai').modal('hide');
                        table.ajax.reload();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data pegawai berhasil disimpan.',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data.'
                        });
                    }
                });
            });

            // 🔹 Edit data pegawai
            $('#tbpegawai').on('click', '.btn-edit', function() {
                let id = $(this).data('id');

                $.get("{{ url('pegawai') }}/" + id, function(res) {
                    const u = res.user;
                    const d = res.detail || {};

                    $('#fidusers').val(u.id);
                    $('input[name="nip"]').val(u.nip);
                    $('input[name="nik"]').val(u.nik);
                    $('input[name="name"]').val(u.name);
                    $('input[name="email"]').val(u.email);
                    $('input[name="nohp"]').val(u.nohp);
                    $('select[name="jabatan"]').val(u.jabatan);
                    $('select[name="id_unitkerja"]').val(u.id_unitkerja).trigger('change');
                    $('input[name="awal_kontrak"]').val(d.awal_kontrak || u.tanggal_masuk);
                    $('input[name="akhir_kontrak"]').val(d.akhir_kontrak);
                    $('input[name="tempat_lahir"]').val(d.tempat_lahir);
                    $('input[name="tanggal_lahir"]').val(d.tanggal_lahir);
                    $('textarea[name="alamat_ktp"]').val(d.alamat_ktp || u.alamat);
                    $('input[name="kode_pos"]').val(d.kode_pos);
                    $('select[name="jenis_kelamin"]').val(d.jenis_kelamin);
                    $('select[name="gol_darah"]').val(d.gol_darah);
                    $('select[name="status_perkawinan"]').val(d.status_perkawinan);
                    $('input[name="jumlah_anak"]').val(d.jumlah_anak);
                    $('input[name="nama_ibu_kandung"]').val(d.nama_ibu_kandung);
                    $('input[name="pendidikan_terakhir"]').val(d.pendidikan_terakhir);
                    $('input[name="no_jkn_kis"]').val(d.no_jkn_kis);
                    $('input[name="no_kpj"]').val(d.no_kpj);
                    $('select[name="status"]').val(u.status || 'aktif');

                    $('#modalPegawai').modal('show');
                });
            });
        </script>
    </x-slot>

</x-app-layout>
