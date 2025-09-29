<x-app-layout>
    <x-slot name="pagetitle">Transaksi Nota</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Transaksi Nota</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNota">
                            <i class="bi bi-file-earmark-plus"></i> Tambah Nota
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbNotas" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nota No</th>
                                <th>Project</th>
                                <th>Company</th>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nota -->
    <div class="modal fade" id="modalNota" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="frmNota">
                    @csrf
                    <input type="hidden" name="id" id="idNota">
                    <input type="hidden" name="saldo_rekening" id="saldoRekening">

                    <div class="modal-header">
                        <h5 class="modal-title">Form Nota</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            {{-- Project & Company otomatis --}}
                            <div class="col-md-4">
                                <label class="form-label">Project</label>
                                <input type="text" class="form-control form-control-sm" value="{{ session('active_project_name') }}" disabled>
                                <input type="hidden" name="idproject" value="{{ session('active_project_id') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Company</label>
                                @php
                                    $company = \App\Models\Company::find(session('active_project_company_id'));
                                @endphp
                                <input type="text" class="form-control form-control-sm" value="{{ $company->company_name ?? '-' }}" disabled>
                                <input type="hidden" name="idcompany" value="{{ $company->id ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis</label>
                                <select class="form-select form-select-sm" name="jenis" id="jenisNota" required>
                                    <option value="cash">Cash</option>
                                    <option value="tempo">Tempo</option>
                                </select>
                            </div>
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Tanggal</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" required>
                            </div>
                            <div class="col-md-4 mt-2" id="saldoContainer" style="display:none;">
                                <label class="form-label">Saldo Rekening</label>
                                <input type="text" class="form-control form-control-sm" id="displaySaldo" disabled>
                            </div>
                        </div>

                        <hr>

                        <h6>Detail Transaksi</h6>
                        <table class="table table-sm table-bordered" id="tblDetail">
                            <thead>
                                <tr>
                                    <th>COA</th>
                                    <th>Amount</th>
                                    <th>Keterangan</th>
                                    <th><button type="button" class="btn btn-sm btn-success" id="addRow">+</button></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select class="form-select form-select-sm" name="transactions[0][coa_id]" required>
                                            <option value="">-- Pilih COA --</option>
                                            @foreach(\App\Models\Coa::all() as $coa)
                                                <option value="{{ $coa->id }}">{{ $coa->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="transactions[0][amount]" required></td>
                                    <td><input type="text" class="form-control form-control-sm" name="transactions[0][keterangan]"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                                </tr>
                            </tbody>
                        </table>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            let tbNotas = $('#tbNotas').DataTable({
    processing:true,
    serverSide:true,
    ajax:"{{ route('transaksi.notas.getdata') }}",
    columns:[
        { data:'DT_RowIndex', name:'DT_RowIndex'},
        { data:'nota_no', name:'nota_no'},
        { data:'project', name:'project'},
        { data:'company', name:'company'},
        { data:'tanggal', name:'tanggal'},
        { data:'jenis', name:'jenis'},
        { data:'total', name:'total'},
        { data:'status', name:'status'},
        { data:'action', orderable:false, searchable:false }
    ]
});

$('#frmNota').submit(function(e){
    e.preventDefault();
    $.post("{{ route('transaksi.notas.store') }}", $(this).serialize(), function(){
        $('#modalNota').modal('hide');
        tbNotas.ajax.reload();
    });
});


            let rowIndex = 1;

            $('#addRow').click(function(){
                let html = `<tr>
                    <td>
                        <select class="form-select form-select-sm" name="transactions[${rowIndex}][coa_id]" required>
                            <option value="">-- Pilih COA --</option>
                            @foreach(\App\Models\Coa::all() as $coa)
                                <option value="{{ $coa->id }}">{{ $coa->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="transactions[${rowIndex}][amount]" required></td>
                    <td><input type="text" class="form-control form-control-sm" name="transactions[${rowIndex}][keterangan]"></td>
                    <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                </tr>`;
                $('#tblDetail tbody').append(html);
                rowIndex++;
            });

            $(document).on('click','.removeRow',function(){
                $(this).closest('tr').remove();
            });

            // Tampilkan saldo jika cash
            $('#jenisNota').change(function(){
                if($(this).val()=='cash'){
                    $.get("/rekening/{{ session('active_project_id') }}/saldo", function(data){
                        $('#saldoContainer').show();
                        $('#displaySaldo').val(data.saldo);
                        $('#saldoRekening').val(data.saldo);
                    });
                } else {
                    $('#saldoContainer').hide();
                    $('#displaySaldo').val('');
                    $('#saldoRekening').val('');
                }
            }).trigger('change');

            $('#frmNota').submit(function(e){
                e.preventDefault();
                $.post("{{ route('transaksi.notas.store') }}", $(this).serialize(), function(){
                    $('#modalNota').modal('hide');
                    tbNotas.ajax.reload();
                    $('#frmNota')[0].reset();
                    $('#tblDetail tbody').html(`<tr>
                        <td>
                            <select class="form-select form-select-sm" name="transactions[0][coa_id]" required>
                                <option value="">-- Pilih COA --</option>
                                @foreach(\App\Models\Coa::all() as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm" name="transactions[0][amount]" required></td>
                        <td><input type="text" class="form-control form-control-sm" name="transactions[0][keterangan]"></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                    </tr>`);
                    rowIndex = 1;
                }).fail(function(xhr){
                    alert('Error: ' + xhr.responseJSON.message);
                });
            });
        </script>
    </x-slot>
</x-app-layout>
