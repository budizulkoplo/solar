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
                                <th>Tanggal</th>
                                <th>Vendor</th>
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
                    <input type="hidden" name="idproject" value="{{ session('active_project_id') }}">
                    <input type="hidden" name="idcompany" value="{{ session('active_project_company_id') }}">

                    <div class="modal-header">
                        <h5 class="modal-title">Form Nota</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">

                            {{-- No Invoice --}}
                            <div class="col-md-4">
                                <label class="form-label">No Invoice</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" name="nota_no" id="notaNo" readonly>
                                    <div class="input-group-text">
                                        <input type="checkbox" id="chkManualNo"> Manual
                                    </div>
                                </div>
                            </div>

                            {{-- Project --}}
                            <div class="col-md-4">
                                <label class="form-label">Project</label>
                                <input type="text" class="form-control form-control-sm" value="{{ session('active_project_name') }}" disabled>
                            </div>

                            {{-- Jenis --}}
                            <div class="col-md-4">
                                <label class="form-label">Jenis</label>
                                <select class="form-select form-select-sm" name="jenis" id="jenisNota" required>
                                    <option value="cash">Cash</option>
                                    <option value="tempo">Tempo</option>
                                </select>
                            </div>

                            {{-- Tanggal --}}
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Tanggal</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalNota" required>
                            </div>

                            {{-- Vendor --}}
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Vendor</label>
                                <select class="form-select form-select-sm select2" name="vendor_id" style="width:100%;" required>
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach(\App\Models\Vendor::whereNull('deleted_at')->get() as $v)
                                        <option value="{{ $v->id }}">{{ $v->namavendor }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Rekening --}}
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Rekening</label>
                                <select class="form-select form-select-sm select2" name="idrek" id="idRekening" style="width:100%;">
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach(\App\Models\Rekening::where('idproject', session('active_project_id'))->get() as $rek)
                                        <option value="{{ $rek->idrek }}">{{ $rek->norek }} - {{ $rek->namarek }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Debit / Credit --}}
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Posisi</label>
                                <select class="form-select form-select-sm" name="posisi" required>
                                    <option value="debit">Debit</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>

                        </div>

                        <hr>

                        <h6>Detail Transaksi</h6>
                        <table class="table table-sm table-bordered" id="tblDetail">
                            <thead>
                                <tr>
                                    <th>COA</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Harga</th>
                                    <th>Total</th>
                                    <th>
                                        <button type="button" class="btn btn-sm btn-success" id="addRow">+</button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select class="form-select form-select-sm select2" name="transactions[0][coa_id]" style="width:100%;" required>
                                            <option value="">-- Pilih COA --</option>
                                            @foreach(\App\Models\Coa::all() as $coa)
                                                <option value="{{ $coa->id }}">{{ $coa->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="transactions[0][description]" required></td>
                                    <td><input type="number" class="form-control form-control-sm qty" name="transactions[0][qty]" value="1" min="1"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm harga" name="transactions[0][harga]" value="0"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm total" name="transactions[0][total]" readonly></td>
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
                    { data:'tanggal', name:'tanggal'},
                    { data:'vendor', name:'vendor'},
                    { data:'jenis', name:'jenis'},
                    { data:'total', name:'total'},
                    { data:'status', name:'status'},
                    { data:'action', orderable:false, searchable:false }
                ]
            });

            function generateNotaNo(){
                let projectId = "{{ session('active_project_id') }}";
                let tgl = $('#tanggalNota').val().replaceAll('-','');
                let urut = Math.floor(Math.random() * 90000) + 10000; // contoh dummy urut
                return projectId + "-" + tgl + "-" + urut;
            }

            $('#chkManualNo').change(function(){
                if($(this).is(':checked')){
                    $('#notaNo').prop('readonly', false).val('');
                }else{
                    $('#notaNo').prop('readonly', true).val(generateNotaNo());
                }
            });

            $('#modalNota').on('shown.bs.modal', function(){
                let today = new Date().toISOString().split('T')[0];
                $('#tanggalNota').val(today);
                if(!$('#chkManualNo').is(':checked')){
                    $('#notaNo').val(generateNotaNo());
                }
            });

            // detail transaksi row handler sama seperti sebelumnya ...
            $(document).on('input','.qty, .harga',function(){
                let row = $(this).closest('tr');
                let qty = parseFloat(row.find('.qty').val()) || 0;
                let harga = parseFloat(row.find('.harga').val()) || 0;
                row.find('.total').val((qty * harga).toFixed(2));
            });

            let rowIndex = 1;
            $('#addRow').click(function(){
                let html = `<tr>
                    <td>
                        <select class="form-select form-select-sm select2" name="transactions[${rowIndex}][coa_id]" style="width:100%;" required>
                            <option value="">-- Pilih COA --</option>
                            @foreach(\App\Models\Coa::all() as $coa)
                                <option value="{{ $coa->id }}">{{ $coa->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" name="transactions[${rowIndex}][description]" required></td>
                    <td><input type="number" class="form-control form-control-sm qty" name="transactions[${rowIndex}][qty]" value="1" min="1"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm harga" name="transactions[${rowIndex}][harga]" value="0"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm total" name="transactions[${rowIndex}][total]" readonly></td>
                    <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                </tr>`;
                $('#tblDetail tbody').append(html);
                $('.select2').select2({ dropdownParent: $('#modalNota') });
                rowIndex++;
            });

            $(document).on('click','.removeRow',function(){
                $(this).closest('tr').remove();
            });

            $('#frmNota').submit(function(e){
                e.preventDefault();
                $.post("{{ route('transaksi.notas.store') }}", $(this).serialize(), function(res){
                    $('#modalNota').modal('hide');
                    tbNotas.ajax.reload();
                    alert(res.message);
                }).fail(function(xhr){
                    alert('Error: ' + xhr.responseJSON.message);
                });
            });

            $('.select2').select2({ dropdownParent: $('#modalNota') });
        </script>
    </x-slot>
</x-app-layout>
