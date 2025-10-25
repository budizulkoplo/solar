<x-app-layout>
    <x-slot name="pagetitle">Plotting Unit Kerja Pegawai</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0"><i class="bi bi-diagram-3"></i> Plotting Unit Kerja Pegawai</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-body">
                    <table id="tblPegawai" class="table table-sm table-striped" style="width:100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Unit Kerja</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Plotting -->
    <div class="modal fade" id="modalPlot" tabindex="-1">
        <div class="modal-dialog">
            <form id="formPlot">
                @csrf
                <input type="hidden" id="id_user" name="id_user">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Atur Unit Kerja Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="id_unitkerja" class="form-label">Pilih Unit Kerja</label>
                            <select id="id_unitkerja" name="id_unitkerja" class="form-select">
                                <option value="">-- Tidak Ada Unit --</option>
                                @foreach($unitkerja as $uk)
                                    <option value="{{ $uk->id }}">{{ $uk->namaunit }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            $(function() {
                // ====== DataTable ======
                let table = $('#tblPegawai').DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: 25, // ✅ Default tampil 25 data
                    lengthMenu: [10, 25, 50, 100],
                    ajax: "{{ route('plotting.unitkerja.data') }}",
                    columns: [
                        { data: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'name' },
                        { data: 'jabatan' },
                        { data: 'unitkerja', orderable: false },
                        { data: 'aksi', orderable: false, searchable: false },
                    ]
                });

                // ====== Inisialisasi Select2 ======
                $('#id_unitkerja').select2({
                    dropdownParent: $('#modalPlot'),
                    placeholder: '-- Tidak Ada Unit --',
                    allowClear: true,
                    width: '100%'
                });

                // ====== Tampilkan modal plotting ======
                $('#tblPegawai').on('click', '.btn-plot', function() {
                    let id = $(this).data('id');
                    let unit = $(this).data('unit') ?? '';
                    $('#id_user').val(id);
                    $('#id_unitkerja').val(unit).trigger('change'); // sync select2
                    $('#modalPlot').modal('show');
                });

                // ====== Submit plotting ======
                $('#formPlot').on('submit', function(e) {
                    e.preventDefault();

                    $.ajax({
                        url: "{{ route('plotting.unitkerja.update') }}",
                        type: "POST",
                        data: $(this).serialize(),
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

                            if (res.success) {
                                $('#modalPlot').modal('hide');
                                table.ajax.reload();

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sukses!',
                                    text: res.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal!',
                                    text: res.message || 'Terjadi kesalahan.'
                                });
                            }
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: 'Terjadi kesalahan server.'
                            });
                        }
                    });
                });
            });
        </script>
    </x-slot>

</x-app-layout>
