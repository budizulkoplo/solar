<x-app-layout>
    <x-slot name="pagetitle">Rekening</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Master Rekening Company & Project</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Rekening Company -->
                <div class="col-md-6">
                    <div class="card card-info card-outline mb-4">
                        <div class="card-header pt-1 pb-1">
                            <div class="card-tools">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalRekening" data-type="company">
                                    <i class="bi bi-file-earmark-plus"></i> Tambah Rekening Company
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="tbRekCompany" class="table table-sm table-striped w-100" style="font-size: small;">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>No Rek</th>
                                        <th>Nama</th>
                                        <th>Saldo</th>
                                        <th>Saldo Akhir</th>
                                        <th>Company</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Rekening Project -->
                <div class="col-md-6">
                    <div class="card card-success card-outline mb-4">
                        <div class="card-header pt-1 pb-1">
                            <div class="card-tools">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalRekening" data-type="project">
                                    <i class="bi bi-file-earmark-plus"></i> Tambah Rekening Project
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="tbRekProject" class="table table-sm table-striped w-100" style="font-size: small;">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>No Rek</th>
                                        <th>Nama</th>
                                        <th>Saldo</th>
                                        <th>Saldo Akhir</th>
                                        <th>Project</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Rekening -->
    <div class="modal fade" id="modalRekening" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="font-size: small;">
                <form id="frmRekening">
                    @csrf
                    <input type="hidden" name="id" id="idrek">
                    <input type="hidden" name="idcompany" id="idcompany">
                    <input type="hidden" name="idproject" id="idproject">
                    <div class="modal-header">
                        <h6 class="modal-title">Form Rekening</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">No Rek</label>
                            <input type="text" class="form-control form-control-sm" name="norek" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama Rekening</label>
                            <input type="text" class="form-control form-control-sm" name="namarek" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Saldo</label>
                            <input type="number" class="form-control form-control-sm" name="saldo">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Saldo Akhir</label>
                            <input type="number" class="form-control form-control-sm" name="saldoakhir">
                        </div>
                        <div class="mb-2 selectCompany d-none">
                            <label class="form-label">Company</label>
                            <select class="form-select form-select-sm" name="idcompany">
                                <option value="">Pilih Company</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2 selectProject d-none">
                            <label class="form-label">Project</label>
                            <select class="form-select form-select-sm" name="idproject">
                                <option value="">Pilih Project</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->namaproject }} ({{ $p->company->company_name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
            function formatRupiah(angka) {
                if (angka === null || angka === undefined) return 'Rp 0';
                return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            let tbRekCompany = $('#tbRekCompany').DataTable({
                ajax: { url: "{{ route('rekening.index') }}", data: {type:'company'} },
                columns: [
                    { data: 'DT_RowIndex' },
                    { data: 'norek' },
                    { data: 'namarek' },
                    { data: 'saldo', render: d => formatRupiah(d) },
                    { data: 'saldoakhir', render: d => formatRupiah(d) },
                    { data: 'company.company_name' },
                    { data: 'aksi', orderable:false, searchable:false }
                ]
            });

            let tbRekProject = $('#tbRekProject').DataTable({
                ajax: { url: "{{ route('rekening.index') }}", data: {type:'project'} },
                columns: [
                    { data: 'DT_RowIndex' },
                    { data: 'norek' },
                    { data: 'namarek' },
                    { data: 'saldo', render: d => formatRupiah(d) },
                    { data: 'saldoakhir', render: d => formatRupiah(d) },
                    { data: 'project.namaproject' },
                    { data: 'aksi', orderable:false, searchable:false }
                ]
            });

            $('#frmRekening').submit(function(e){
                e.preventDefault();
                $.post("{{ route('rekening.store') }}", $(this).serialize(), function(){
                    $('#modalRekening').modal('hide');
                    tbRekCompany.ajax.reload();
                    tbRekProject.ajax.reload();
                });
            });

            $('#tbRekCompany, #tbRekProject').on('click','.editRekening', function(){
                $.get('/rekening/'+$(this).data('id')+'/edit', function(d){
                    $('#idrek').val(d.idrek);
                    $('input[name="norek"]').val(d.norek);
                    $('input[name="namarek"]').val(d.namarek);
                    $('input[name="saldo"]').val(d.saldo);
                    $('input[name="saldoakhir"]').val(d.saldoakhir);
                    $('select[name="idcompany"]').val(d.idcompany);
                    $('select[name="idproject"]').val(d.idproject);
                    $('#modalRekening').modal('show');
                });
            });

            $('#tbRekCompany, #tbRekProject').on('click','.deleteRekening', function(){
                if(confirm('Hapus rekening ini?')){
                    $.ajax({
                        url:'/rekening/'+$(this).data('id'),
                        type:'DELETE',
                        data:{_token:'{{ csrf_token() }}'},
                        success:function(){
                            tbRekCompany.ajax.reload();
                            tbRekProject.ajax.reload();
                        }
                    });
                }
            });

            $('#modalRekening').on('show.bs.modal', function(e){
                let type = $(e.relatedTarget).data('type');
                $('.selectCompany, .selectProject').addClass('d-none');
                if(type==='company'){ $('.selectCompany').removeClass('d-none'); }
                if(type==='project'){ $('.selectProject').removeClass('d-none'); }
                $('#frmRekening')[0].reset();
                $('#idrek').val('');
            });
        </script>
    </x-slot>
</x-app-layout>
