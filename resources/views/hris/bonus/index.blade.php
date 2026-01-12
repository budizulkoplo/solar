<x-app-layout>
    <x-slot name="pagetitle">Bonus Pegawai</x-slot>

    <div class="app-content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="bi bi-gift"></i> Bonus Pegawai</h3>
            <div class="text-muted small">
                Periode: <span id="periode-text">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</span>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            {{-- Filter Panel --}}
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
                            @for($y=date('Y')-2;$y<=date('Y')+1;$y++)
                                <option value="{{ $y }}" {{ $y==date('Y')?'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Unit Kerja</label>
                        <select id="unit_id" class="form-select form-select-sm">
                            <option value="">Semua Unit</option>
                            @foreach($unitkerja as $uk)
                                <option value="{{ $uk->id }}">{{ $uk->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button id="btnTampil" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button id="btnRefresh" class="btn btn-secondary btn-sm w-100">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>

            {{-- Bonus Table --}}
            <div class="card card-info card-outline">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm text-center align-middle" id="tblBonus" style="font-size: small;">
                        <thead class="table-info">
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th style="text-align:left;">Nama</th>
                                <th style="text-align:left;">Unit Kerja</th>
                                <th>Total Bonus</th>
                                <th>Jumlah Item</th>
                                <th>Detail Bonus</th>
                                <th>Input Bonus</th>
                                <th>Slip</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal Input Bonus --}}
    <div class="modal fade" id="modalInputBonus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Input Bonus Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formInputBonus">
                    <div class="modal-body">
                        <input type="hidden" id="periode_hidden" value="">
                        <input type="hidden" id="nik_bonus" value="">
                        
                        <div class="mb-3">
                            <label>NIK</label>
                            <input type="text" id="nik_display" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="mb-3">
                            <label>Nama Pegawai</label>
                            <input type="text" id="nama_pegawai" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="mb-3">
                            <label>Periode</label>
                            <input type="text" id="periode_display" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="mb-3">
                            <label>Keterangan Bonus</label>
                            <input type="text" id="keterangan" class="form-control form-control-sm" 
                                   maxlength="255" required 
                                   placeholder="Contoh: Bonus Projek, THR, Insentif, dll">
                        </div>
                        <div class="mb-3">
                            <label>Nominal Bonus</label>
                            <input type="text" id="nominal" class="form-control form-control-sm" 
                                   required placeholder="Rp 0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Detail Bonus --}}
    <div class="modal fade" id="modalDetailBonus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Bonus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>NIK:</strong> <span id="detail_nik"></span><br>
                        <strong>Nama:</strong> <span id="detail_nama"></span><br>
                        <strong>Periode:</strong> <span id="detail_periode"></span>
                    </div>
                    
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="60%">Keterangan</th>
                                <th width="25%">Nominal</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detailTableBody"></tbody>
                        <tfoot>
                            <tr class="table-warning">
                                <td colspan="2" class="text-end"><strong>TOTAL:</strong></td>
                                <td id="detailTotal" class="text-end"><strong>Rp 0</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
<script>
$(function(){

    let currentPeriode = '';
    let currentNikDetail = '';

    function formatRupiah(val){ 
        return 'Rp '+Number(val||0).toLocaleString('id-ID'); 
    }

    function cleanNumber(val){ 
        return Number((val||0).toString().replace(/[^\d]/g,'')); 
    }

    // Format nominal input
    $('#nominal').on('keyup', function() {
        let val = cleanNumber($(this).val());
        $(this).val(formatRupiah(val));
    });

    // Tampilkan data
    function loadData() {
        let bulan   = $('#bulan').val(),
            tahun   = $('#tahun').val(),
            unit_id = $('#unit_id').val();

        currentPeriode = tahun + '-' + bulan.toString().padStart(2, '0');
        
        $('#periode-text').text($('#bulan option:selected').text()+' '+tahun);

        $.get("{{ route('hris.bonus.data') }}",{ 
            bulan, 
            tahun, 
            unit_id
        }, function(res){
            let rows='';

            res.data.forEach((user, i) => {
                let bonusList = '';
                let totalBonus = 0;
                
                if (user.bonuses.length > 0) {
                    user.bonuses.forEach((bonus, idx) => {
                        bonusList += `${idx+1}. ${bonus.keterangan}: ${formatRupiah(bonus.nominal)}<br>`;
                        totalBonus += bonus.nominal;
                    });
                } else {
                    bonusList = '<span class="text-muted">Belum ada bonus</span>';
                }

                rows += `
                <tr data-nik="${user.nik}">
                    <td>${i+1}</td>
                    <td>${user.nik}</td>
                    <td style="text-align:left;">${user.nama}</td>
                    <td style="text-align:left;">${user.unit_kerja || '-'}</td>
                    <td class="fw-bold text-success">${formatRupiah(user.total_bonus)}</td>
                    <td><span class="badge ${user.bonus_count > 0 ? 'bg-success' : 'bg-secondary'}">${user.bonus_count} item</span></td>
                    <td>
                        <button class="btn btn-sm btn-info btn-detail" 
                                data-nik="${user.nik}"
                                data-nama="${user.nama}"
                                title="Lihat Detail">
                            <i class="bi bi-eye"></i> Detail
                        </button>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary btn-input" 
                                data-nik="${user.nik}"
                                data-nama="${user.nama}"
                                title="Tambah Bonus">
                            <i class="bi bi-plus-circle"></i> Input
                        </button>
                    </td>
                    <td>
                        ${user.bonus_count > 0 ? 
                            `<a href="/hris/bonus/slip/${user.nik}/${currentPeriode}" target="_blank" class="btn btn-sm btn-success" title="Download Slip">
                                <i class="bi bi-download"></i>
                            </a>` : 
                            '<span class="text-muted">-</span>'
                        }
                    </td>
                </tr>`;
            });

            $('#tblBonus tbody').html(rows);
        });
    }

    // Tombol tampil
    $('#btnTampil').click(loadData);
    $('#btnRefresh').click(loadData);

    // Tombol input bonus
    $(document).on('click', '.btn-input', function(){
        let nik = $(this).data('nik');
        let nama = $(this).data('nama');
        
        $('#nik_bonus').val(nik);
        $('#nik_display').val(nik);
        $('#nama_pegawai').val(nama);
        $('#periode_hidden').val(currentPeriode);
        $('#periode_display').val($('#periode-text').text());
        $('#keterangan').val('');
        $('#nominal').val('');
        
        $('#modalInputBonus').modal('show');
    });

    // Tombol detail bonus
    $(document).on('click', '.btn-detail', function(){
        let nik = $(this).data('nik');
        let nama = $(this).data('nama');
        
        currentNikDetail = nik;
        
        $('#detail_nik').text(nik);
        $('#detail_nama').text(nama);
        $('#detail_periode').text($('#periode-text').text());
        
        // Load detail bonus
        $.get('/hris/bonus/user-bonus', {
            nik: nik,
            periode: currentPeriode
        }, function(res){
            let rows = '';
            let total = 0;
            
            if (res.bonuses.length > 0) {
                res.bonuses.forEach((bonus, i) => {
                    total += bonus.nominal;
                    rows += `
                    <tr data-id="${bonus.id}">
                        <td>${i+1}</td>
                        <td>${bonus.keterangan}</td>
                        <td class="text-end">${formatRupiah(bonus.nominal)}</td>
                        <td>
                            <button class="btn btn-sm btn-warning btn-edit-bonus" data-id="${bonus.id}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-hapus-bonus" data-id="${bonus.id}" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                });
            } else {
                rows = '<tr><td colspan="4" class="text-center text-muted">Belum ada bonus</td></tr>';
            }
            
            $('#detailTableBody').html(rows);
            $('#detailTotal').html(`<strong>${formatRupiah(total)}</strong>`);
            $('#modalDetailBonus').modal('show');
        });
    });

    // Submit form input bonus
    $('#formInputBonus').submit(function(e){
        e.preventDefault();
        
        let data = {
            _token: "{{ csrf_token() }}",
            periode: $('#periode_hidden').val(),
            nik: $('#nik_bonus').val(),
            keterangan: $('#keterangan').val(),
            nominal: cleanNumber($('#nominal').val())
        };

        if (!data.periode) {
            alert('Pilih periode terlebih dahulu!');
            return;
        }

        $.post("{{ route('hris.bonus.store') }}", data, function(res){
            if(res.success){
                $('#modalInputBonus').modal('hide');
                $('#formInputBonus')[0].reset();
                loadData(); // Reload data
                alert('Bonus berhasil ditambahkan!');
            } else {
                alert(res.message || 'Gagal menambahkan bonus!');
            }
        });
    });

    // Edit bonus dari modal detail
    $(document).on('click', '.btn-edit-bonus', function(){
        let id = $(this).data('id');
        
        // Implement edit modal di sini
        alert('Edit bonus dengan ID: ' + id);
    });

    // Hapus bonus dari modal detail
    $(document).on('click', '.btn-hapus-bonus', function(){
        if(!confirm('Yakin ingin menghapus bonus ini?')) return;
        
        let id = $(this).data('id');
        
        $.ajax({
            url: '/hris/bonus/' + id,
            type: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            success: function(res){
                if(res.success){
                    // Reload detail
                    $('.btn-detail[data-nik="' + currentNikDetail + '"]').click();
                    // Reload tabel utama
                    loadData();
                    alert('Bonus berhasil dihapus!');
                }
            }
        });
    });

    // Load data pertama kali
    loadData();

});
</script>
    </x-slot>

</x-app-layout>