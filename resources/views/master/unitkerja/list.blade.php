<x-app-layout>
    <x-slot name="pagetitle">Unit Kerja</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Manajemen Unit Kerja</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUnit" id="btnAdd">
                        <i class="bi bi-file-earmark-plus"></i> Tambah Unit
                    </button>
                </div>
                <div class="card-body">
                    <table id="tbunit" class="table table-sm table-striped" style="width: 100%; font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Unit</th>
                                <th>Lokasi</th>
                                <th>UMK</th>
                                <th>Lokasi Lock</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit -->
    <div class="modal fade" id="modalUnit" tabindex="-1">
        <div class="modal-dialog">
            <form id="frmUnit">
                @csrf
                <input type="hidden" name="fidunit" id="fidunit">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Unit Kerja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama Unit</label>
                            <input type="text" class="form-control" name="namaunit" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">UMK (Upah Minimum)</label>
                            <input type="number" step="0.01" class="form-control" name="umk" id="umk" placeholder="Contoh: 4500000">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Lokasi (Klik peta untuk memilih)</label>
                            <input type="text" class="form-control" name="lokasi" id="lokasi" placeholder="Lat,Lng">
                            <div id="map" style="height: 250px; border-radius: 10px; margin-top: 5px;"></div>
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
        <!-- Leaflet -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <!-- Leaflet Search & Geocoder -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
        <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

        <script>
            // ====== DataTable ======
            let table = $('#tbunit').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('master.unitkerja.data') }}",
                columns: [
                    {data: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'namaunit'},
                    {data: 'lokasi'},
                    {
                        data: 'umk',
                        render: function(data) {
                            if (!data) return '-';
                            let val = parseFloat(String(data).replace(/[^0-9]/g, ''));
                            if (isNaN(val)) return '-';
                            return 'Rp ' + val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        }
                    },
                    {
                        data: 'lokasi_lock',
                        render: function(data, type, row) {
                            let cls = data == 1 ? 'bg-success' : 'bg-secondary';
                            let text = data == 1 ? 'ON' : 'OFF';
                            return `<span class="badge ${cls} btn-toggle-lock" data-id="${row.enc_id}" data-val="${data}" style="cursor:pointer;">${text}</span>`;
                        }
                    },
                    {data: 'aksi', orderable: false, searchable: false}
                ]
            });


            // ====== Inisialisasi Peta ======
            let map = L.map('map', { zoomControl: true }).setView([-6.2, 106.8], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Tambah pencarian lokasi
            L.Control.geocoder({
                defaultMarkGeocode: false,
                placeholder: 'Cari lokasi...'
            })
            .on('markgeocode', function(e) {
                const latlng = e.geocode.center;
                marker.setLatLng(latlng);
                map.setView(latlng, 15);
                $('#lokasi').val(latlng.lat + ',' + latlng.lng);
            })
            .addTo(map);

            // Marker default
            let marker = L.marker([-6.2, 106.8], {draggable: true}).addTo(map);
            marker.on('dragend', function(e){
                const pos = e.target.getLatLng();
                $('#lokasi').val(pos.lat + ',' + pos.lng);
            });

            map.on('click', function(e){
                marker.setLatLng(e.latlng);
                $('#lokasi').val(e.latlng.lat + ',' + e.latlng.lng);
            });

            // ====== Tambah Data ======
            $('#btnAdd').on('click', function(){
                $('#frmUnit')[0].reset();
                $('#fidunit').val('');
                map.setView([-6.2, 106.8], 11);
                marker.setLatLng([-6.2, 106.8]);
            });

            // ====== Simpan Data ======
            $('#frmUnit').on('submit', function(e){
                e.preventDefault();
                $.ajax({
                    url: "{{ route('master.unitkerja.store') }}",
                    method: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(res){
                        $('#modalUnit').modal('hide');
                        table.ajax.reload();
                        $('#frmUnit')[0].reset();
                    },
                    error: function(xhr) {
                        alert('Gagal menyimpan data!');
                    }
                });
            });

            // ====== Edit Data ======
            $('#tbunit').on('click', '.btn-edit', function(){
                let id = $(this).data('id');
                $.get("{{ url('master/unitkerja') }}/" + id, function(res){
                    $('#fidunit').val(res.id);
                    $('input[name="namaunit"]').val(res.namaunit);
                    $('input[name="lokasi"]').val(res.lokasi);
                    $('input[name="umk"]').val(res.umk ?? '');
                    $('#modalUnit').modal('show');

                    if (res.lokasi && res.lokasi.includes(',')) {
                        let [lat, lng] = res.lokasi.split(',').map(parseFloat);
                        marker.setLatLng([lat, lng]);
                        map.setView([lat, lng], 15);
                    }
                });
            });

            // ====== Hapus Data ======
            $('#tbunit').on('click', '.btn-hapus', function(){
                if (!confirm('Hapus data ini?')) return;
                let id = $(this).data('id');
                $.ajax({
                    url: "{{ url('master/unitkerja') }}/" + id,
                    method: 'DELETE',
                    data: {_token: "{{ csrf_token() }}"},
                    success: function(){
                        table.ajax.reload();
                    },
                    error: function() {
                        alert('Gagal menghapus data!');
                    }
                });
            });

            // ====== Fix bug map tidak tampil saat modal dibuka ======
            $('#modalUnit').on('shown.bs.modal', function () {
                setTimeout(() => { map.invalidateSize(); }, 200);
            });
            
            $('#tbunit').on('click', '.btn-toggle-lock', function() {
                let id = $(this).data('id');
                let val = $(this).data('val');
                let el = $(this);

                $.ajax({
                    url: "{{ route('master.unitkerja.togglelock') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id
                    },
                    beforeSend: function() {
                        el.removeClass('bg-success bg-secondary').addClass('bg-warning').text('...');
                    },
                    success: function(res) {
                        if (res.success) {
                            let baru = res.lokasi_lock;
                            if (baru == 1) {
                                el.removeClass('bg-warning bg-secondary').addClass('bg-success').text('ON');
                            } else {
                                el.removeClass('bg-warning bg-success').addClass('bg-secondary').text('OFF');
                            }
                            el.data('val', baru);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Terjadi kesalahan server', 'error');
                        table.ajax.reload(null, false);
                    }
                });
            });

        </script>
    </x-slot>
</x-app-layout>
