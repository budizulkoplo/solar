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
                                <option value="{{ $uk->id }}">{{ $uk->namaunit }}</option>
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
                                <th rowspan="2" style="text-align:left;">NIK</th>
                                <th rowspan="2" style="text-align:left;">Nama</th>
                                <th colspan="4">Pendapatan</th>
                                <th colspan="4">Potongan</th>
                                <th rowspan="2">Total Pendapatan</th>
                                <th rowspan="2">Total Potongan</th>
                                <th rowspan="2">Jumlah</th>
                            </tr>
                            <tr>
                                <th class="pendapatan">Gaji</th>
                                <th class="pendapatan">Tunjangan</th>
                                <th class="pendapatan">Lembur</th>
                                <th class="pendapatan">HLN</th>
                                <th class="potongan">BPJS Kes</th>
                                <th class="potongan">BPJS TK</th>
                                <th class="potongan">Kasbon</th>
                                <th class="potongan">Sisa Kasbon</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <x-slot name="jscustom">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        #tblPayroll td:nth-child(1), #tblPayroll td:nth-child(2), #tblPayroll td:nth-child(3) { background:#f0f0f0; text-align:left; }
        #tblPayroll td.pendapatan, #tblPayroll td.potongan { background:#ffffff; } /* data kolom tetap putih */
        #tblPayroll td.jumlah { background:#e8f3ff; font-weight:600; }
        #tblPayroll td.editable { cursor:pointer; }
        #tblPayroll td.editable:focus { outline:2px solid #007bff; background:#eaf4ff; }
    </style>

    <script>
    $(function(){
        $('#unit_id').select2({ placeholder:'Pilih Unit Kerja', width:'100%', theme:'bootstrap-5' });

        function formatRupiah(val){ return 'Rp '+Number(val||0).toLocaleString('id-ID'); }
        function cleanNumber(val){ return Number((val||0).toString().replace(/[^\d]/g,'')); }

        function hitungJumlah(row){
            let pend = ['gaji','tunjangan','nominallembur','hln'];
            let pot = ['bpjs_kes','bpjs_tk','kasbon','sisakasbon'];
            let totalPend=0,totalPot=0;
            pend.forEach(f=> totalPend+=cleanNumber(row.find(`[data-field="${f}"]`).text()));
            pot.forEach(f=> totalPot+=cleanNumber(row.find(`[data-field="${f}"]`).text()));
            row.find('.totalPendapatan').remove();
            row.find('.totalPotongan').remove();
            row.find('td.jumlah').before(`<td class="totalPendapatan">${formatRupiah(totalPend)}</td><td class="totalPotongan">${formatRupiah(totalPot)}</td>`);
            row.find('td.jumlah').text(formatRupiah(totalPend-totalPot));
            updateSubtotal();
        }

        function updateSubtotal(){
            let total={gaji:0,tunjangan:0,nominallembur:0,hln:0,bpjs_kes:0,bpjs_tk:0,kasbon:0,sisakasbon:0,totalPendapatan:0,totalPotongan:0,jumlah:0};
            $('#tblPayroll tbody tr').each(function(){
                let r=$(this);
                ['gaji','tunjangan','nominallembur','hln','bpjs_kes','bpjs_tk','kasbon','sisakasbon'].forEach(f=> total[f]+=cleanNumber(r.find(`[data-field="${f}"]`).text()));
                total.totalPendapatan += cleanNumber(r.find('.totalPendapatan').text());
                total.totalPotongan += cleanNumber(r.find('.totalPotongan').text());
                total.jumlah += cleanNumber(r.find('td.jumlah').text());
            });
            $('.sub-gaji').text(formatRupiah(total.gaji));
            $('.sub-tunjangan').text(formatRupiah(total.tunjangan));
            $('.sub-lembur').text(formatRupiah(total.nominallembur));
            $('.sub-hln').text(formatRupiah(total.hln));
            $('.sub-bpjs_kes').text(formatRupiah(total.bpjs_kes));
            $('.sub-bpjs_tk').text(formatRupiah(total.bpjs_tk));
            $('.sub-kasbon').text(formatRupiah(total.kasbon));
            $('.sub-sisakasbon').text(formatRupiah(total.sisakasbon));
            $('.sub-totalPendapatan').text(formatRupiah(total.totalPendapatan));
            $('.sub-totalPotongan').text(formatRupiah(total.totalPotongan));
            $('.sub-jumlah').text(formatRupiah(total.jumlah));
        }

        function updatePayroll(nik,field,value,row){
            $.post("{{ route('hris.payroll.update_manual') }}",
            {_token:"{{ csrf_token() }}", nik, field, value},
            function(res){ if(res.success) hitungJumlah(row); else alert("❌ Gagal update!"); })
            .fail(()=>alert("⚠️ Gagal terhubung server."));
        }

        $('#btnTampil').click(function(){
            let bulan=$('#bulan').val(), tahun=$('#tahun').val(), unit_id=$('#unit_id').val();
            $('#periode-text').text($('#bulan option:selected').text()+' '+tahun);
            $.get("{{ route('hris.payroll.data') }}",{bulan,tahun,unit_id},function(res){
                let rows='';
                res.data.forEach((r,i)=>{
                    rows+=`<tr data-nik="${r.nik}">
                        <td>${i+1}</td>
                        <td>
                            <a href="/hris/payroll/slip/${r.id}" target="_blank" class="btn btn-sm btn-success">
                                <i class="bi bi-download"></i>
                            </a>
                        </td>
                        <td>${r.nip}</td>
                        <td>${r.nama}</td>
                        <td contenteditable class="editable pendapatan" data-field="gaji">${formatRupiah(r.gaji)}</td>
                        <td contenteditable class="editable pendapatan" data-field="tunjangan">${formatRupiah(r.tunjangan)}</td>
                        <td contenteditable class="editable pendapatan" data-field="nominallembur">${formatRupiah(r.nominallembur)}</td>
                        <td contenteditable class="editable pendapatan" data-field="hln">${formatRupiah(r.hln)}</td>
                        <td contenteditable class="editable potongan" data-field="bpjs_kes">${formatRupiah(r.bpjs_kes)}</td>
                        <td contenteditable class="editable potongan" data-field="bpjs_tk">${formatRupiah(r.bpjs_tk)}</td>
                        <td contenteditable class="editable potongan" data-field="kasbon">${formatRupiah(r.kasbon)}</td>
                        <td contenteditable class="editable potongan" data-field="sisakasbon">${formatRupiah(r.sisakasbon)}</td>
                        <td class="totalPendapatan">${formatRupiah(cleanNumber(r.gaji)+cleanNumber(r.tunjangan)+cleanNumber(r.nominallembur)+cleanNumber(r.hln))}</td>
                        <td class="totalPotongan">${formatRupiah(cleanNumber(r.bpjs_kes)+cleanNumber(r.bpjs_tk)+cleanNumber(r.kasbon)+cleanNumber(r.sisakasbon))}</td>
                        <td class="jumlah">${formatRupiah((cleanNumber(r.gaji)+cleanNumber(r.tunjangan)+cleanNumber(r.nominallembur)+cleanNumber(r.hln))-(cleanNumber(r.bpjs_kes)+cleanNumber(r.bpjs_tk)+cleanNumber(r.kasbon)+cleanNumber(r.sisakasbon)))}</td>
                    </tr>`;
                });
                $('#tblPayroll tbody').html(rows);
                updateSubtotal();
            });
        });

        $(document).on('focus','.editable',function(){
            let el=$(this); el.text(cleanNumber(el.text()));
            let range=document.createRange(); range.selectNodeContents(el[0]);
            let sel=window.getSelection(); sel.removeAllRanges(); sel.addRange(range);
        });

        $(document).on('blur','.editable',function(){
            let el=$(this), nik=el.closest('tr').data('nik'), field=el.data('field'), row=el.closest('tr');
            let value=cleanNumber(el.text()); el.text(formatRupiah(value));
            updatePayroll(nik,field,value,row);
        });

    });
    </script>
    </x-slot>
</x-app-layout>
