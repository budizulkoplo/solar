<style>
    /* Kolom penting di header dan body */
    #tblPayroll th.bg-key, 
    #tblPayroll td.bg-key {
        background-color: #ebebebff; /* abu-abu terang */
    }

    #tblPayroll th.bg-total, 
    #tblPayroll td.bg-total {
        background-color: #ebebebff; /* abu-abu gelap */
    }
</style>

<x-app-layout>
    <x-slot name="pagetitle">Payroll</x-slot>

    <div class="app-content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="bi bi-cash-stack"></i> Payroll Pegawai</h3>
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
                </div>
            </div>

            {{-- Payroll Table --}}
            <div class="card card-info card-outline">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm text-center align-middle" id="tblPayroll" style="font-size: small;">
                        <thead class="table-info">
                            <tr>
                                <th rowspan="2">No</th>
                                <th rowspan="2">Slip</th>
                                <th rowspan="2" style="text-align:left;">NIP</th>
                                <th rowspan="2" style="text-align:left;">Nama</th>
                                <th colspan="8">Pendapatan</th>
                                <th colspan="3">Potongan</th>
                                <th rowspan="2">Total Pendapatan</th>
                                <th rowspan="2">Total Potongan</th>
                                <th rowspan="2">Jumlah</th>
                            </tr>
                            <tr>
                                <th>Gaji Pokok</th>
                                <th>Pek Tambahan</th>
                                <th>Masa Kerja</th>
                                <th>Komunikasi</th>
                                <th>Transportasi</th>
                                <th>Konsumsi</th>
                                <th>Tunj Asuransi</th>
                                <th>Jabatan</th>
                                <th>Cicilan</th>
                                <th>Asuransi</th>
                                <th>Zakat</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <x-slot name="jscustom">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script> window.JSZip = JSZip; </script>

<script>
$(function(){

    // ===============================
    // Fungsi utilitas
    // ===============================

    // Bersihkan string/angka menjadi number murni
    function cleanNumber(val){ 
        return parseInt((val||0).toString().replace(/[^\d]/g,'')) || 0; 
    }

    // Alias agar mudah dipanggil
function parseNumber(val){
    if(!val) return 0;
    return parseFloat(val) || 0;
}

    // Format angka ke Rupiah
    function formatRupiah(val){
        val = parseNumber(val); // pastikan angka murni
        return 'Rp ' + val.toLocaleString('id-ID');
    }
    // ===============================
    // Hitung total per baris
    // ===============================
    function hitungJumlah(row){
        let pend = ['gajipokok','pek_tambahan','masakerja','komunikasi',
                    'transportasi','konsumsi','tunj_asuransi','jabatan'];

        let pot = ['cicilan','asuransi','zakat'];

        let totalPend = 0;
        let totalPot = 0;

        pend.forEach(f => totalPend += cleanNumber(row.find(`[data-field="${f}"]`).text()));
        pot.forEach(f => totalPot += cleanNumber(row.find(`[data-field="${f}"]`).text()));

        // Update kolom total, pakai format Rupiah
        row.find('.totalPendapatan').text(formatRupiah(totalPend));
        row.find('.totalPotongan').text(formatRupiah(totalPot));
        row.find('.jumlah').text(formatRupiah(totalPend - totalPot));
    }

    // ===============================
    // Update manual ke server
    // ===============================
    function updatePayroll(nik, field, value, row){
        let dataToSend = {
            _token : "{{ csrf_token() }}",
            nik    : nik,
            field  : field,
            value  : value
        };

        $.post("{{ route('hris.payroll.update_manual') }}", dataToSend, function(res){
            if(res.success){
                hitungJumlah(row);
            } else {
                alert("❌ Gagal update data!");
            }
        }).fail(()=>{ 
            alert("⚠️ Gagal terhubung ke server."); 
        });
    }

    // ===============================
    // Tombol Tampilkan Payroll
    // ===============================
    $('#btnTampil').click(function(){
        let bulan   = $('#bulan').val(),
            tahun   = $('#tahun').val(),
            unit_id = $('#unit_id').val();

        $('#periode-text').text($('#bulan option:selected').text()+' '+tahun);

        $.get("{{ route('hris.payroll.data') }}", { bulan, tahun, unit_id }, function(res){
            let rows = '';

            res.data.forEach((r,i)=>{
                // Hitung total dengan parseNumber agar angka valid
                let totalPendapatan = parseNumber(r.gajipokok) + 
                                    parseNumber(r.pek_tambahan) + 
                                    parseNumber(r.masakerja) + 
                                    parseNumber(r.komunikasi) +
                                    parseNumber(r.transportasi) + 
                                    parseNumber(r.konsumsi) + 
                                    parseNumber(r.tunj_asuransi) + 
                                    parseNumber(r.jabatan);

                let totalPotongan = parseNumber(r.cicilan) + 
                                    parseNumber(r.asuransi) + 
                                    parseNumber(r.zakat);

                rows += `
                <tr data-nik="${r.nik}">
                    <td class="bg-key">${i+1}</td>
                    <td class="bg-key">
                        <a href="/hris/payroll/slip/${r.id}" target="_blank" class="btn btn-sm btn-success">
                            <i class="bi bi-download"></i>
                        </a>
                    </td>
                    <td class="bg-key" style="text-align:left;">${r.nip}</td>
                    <td class="bg-key" style="text-align:left;">${r.nama}</td>

                    <td contenteditable class="editable pendapatan" data-field="gajipokok">${formatRupiah(r.gajipokok)}</td>
                    <td contenteditable class="editable pendapatan" data-field="pek_tambahan">${formatRupiah(r.pek_tambahan)}</td>
                    <td contenteditable class="editable pendapatan" data-field="masakerja">${formatRupiah(r.masakerja)}</td>
                    <td contenteditable class="editable pendapatan" data-field="komunikasi">${formatRupiah(r.komunikasi)}</td>
                    <td contenteditable class="editable pendapatan" data-field="transportasi">${formatRupiah(r.transportasi)}</td>
                    <td contenteditable class="editable pendapatan" data-field="konsumsi">${formatRupiah(r.konsumsi)}</td>
                    <td contenteditable class="editable pendapatan" data-field="tunj_asuransi">${formatRupiah(r.tunj_asuransi)}</td>
                    <td contenteditable class="editable pendapatan" data-field="jabatan">${formatRupiah(r.jabatan)}</td>

                    <td contenteditable class="editable potongan" data-field="cicilan">${formatRupiah(r.cicilan)}</td>
                    <td contenteditable class="editable potongan" data-field="asuransi">${formatRupiah(r.asuransi)}</td>
                    <td contenteditable class="editable potongan" data-field="zakat">${formatRupiah(r.zakat)}</td>

                    <td class="totalPendapatan bg-total">${formatRupiah(totalPendapatan)}</td>
                    <td class="totalPotongan bg-total">${formatRupiah(totalPotongan)}</td>
                    <td class="jumlah bg-total">${formatRupiah(totalPendapatan - totalPotongan)}</td>
                </tr>`;
            });

            $('#tblPayroll tbody').html(rows);
        });
    });

    // ===============================
    // Editable cell: focus
    // ===============================
    $(document).on('focus','.editable',function(){
        let el = $(this);
        el.text(cleanNumber(el.text()));

        let range = document.createRange();
        range.selectNodeContents(el[0]);

        let sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    });

    // ===============================
    // Editable cell: blur
    // ===============================
    $(document).on('blur','.editable',function(){
        let el    = $(this),
            nik   = el.closest('tr').data('nik'),
            field = el.data('field'),
            row   = el.closest('tr');

        let value = cleanNumber(el.text());
        el.text(formatRupiah(value));

        updatePayroll(nik, field, value, row);
    });

});
</script>
</x-slot>

</x-app-layout>
