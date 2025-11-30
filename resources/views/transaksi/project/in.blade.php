<x-app-layout>
    <x-slot name="pagetitle">Transaksi Masuk - Project</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Transaksi Masuk - Project</h3>
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
                                <th>Total</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Bukti</th>
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
                <form id="frmNota" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="idNota">
                    <input type="hidden" name="idproject" value="{{ session('active_project_id') }}">

                    <div class="modal-header">
                        <h5 class="modal-title">Form Nota Masuk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            {{-- No Invoice --}}
                            <div class="col-md-4">
                                <label class="form-label">No Invoice *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" name="nota_no" id="notaNo" required>
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

                            {{-- Payment Method --}}
                            <div class="col-md-4">
                                <label class="form-label">Payment Method *</label>
                                <select class="form-select form-select-sm" name="paymen_method" id="paymenMethod" required>
                                    <option value="cash">Cash</option>
                                    <option value="tempo">Tempo</option>
                                </select>
                            </div>

                            {{-- Tanggal --}}
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalNota" required>
                            </div>

                            {{-- Vendor --}}
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Vendor *</label>
                                <select class="form-select form-select-sm select2" name="vendor_id" id="vendorId" style="width:100%;" required>
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach(\App\Models\Vendor::whereNull('deleted_at')->get() as $v)
                                        <option value="{{ $v->id }}">{{ $v->namavendor }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Rekening --}}
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select form-select-sm select2" name="idrek" id="idRekening" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach(\App\Models\Rekening::where('idproject', session('active_project_id'))->get() as $rek)
                                        <option value="{{ $rek->idrek }}">{{ $rek->norek }} - {{ $rek->namarek }}</option>
                                    @endforeach
                                </select>
                                <small id="saldoRekening" class="text-success fw-bold mt-1 d-block">
                                    <i class="bi bi-cash-coin"></i> Saldo: Rp 0
                                </small>
                            </div>

                            {{-- Tanggal Tempo --}}
                            <div class="col-md-4 mt-2" id="tglTempoContainer" style="display:none;">
                                <label class="form-label">Tanggal Tempo *</label>
                                <input type="date" class="form-control form-control-sm" name="tgl_tempo" id="tglTempo">
                            </div>

                            {{-- Bukti Nota --}}
                            <div class="col-12 mt-2">
                                <label class="form-label">Bukti Nota (Optional)</label>
                                <input type="file" class="form-control form-control-sm" name="bukti_nota" id="buktiNota" 
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                                <div id="buktiPreview" class="mt-2" style="display:none;">
                                    <img id="previewImage" src="#" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6>Detail Transaksi</h6>
                        <table class="table table-sm table-bordered" id="tblDetail">
                            <thead>
                                <tr>
                                    <th>Kode Transaksi *</th>
                                    <th>Deskripsi *</th>
                                    <th>Qty</th>
                                    <th>Nominal</th>
                                    <th>Total</th>
                                    <th>
                                        <button type="button" class="btn btn-sm btn-success" id="addRow">+</button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select class="form-select form-select-sm select2" name="transactions[0][idkodetransaksi]" style="width:100%;" required>
                                            <option value="">-- Pilih Kode Transaksi --</option>
                                            @foreach(\App\Models\KodeTransaksi::all() as $kt)
                                                <option value="{{ $kt->id }}" data-kode="{{ $kt->kodetransaksi }}">
                                                    {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="transactions[0][description]" required></td>
                                    <td><input type="number" class="form-control form-control-sm jml" name="transactions[0][jml]" value="1" min="1" step="0.01"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm nominal" name="transactions[0][nominal]" value="0" min="0"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm total" name="transactions[0][total]" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" id="grandTotal" readonly></td>
                                    <td></td>
                                </tr>
                            </tfoot>
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
            $(document).ready(function() {
                // DataTable
                let tbNotas = $('#tbNotas').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('transaksi.project.getdata', 'in') }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'nota_no', name: 'nota_no' },
                        { data: 'project_name', name: 'project.name' },
                        { data: 'tanggal', name: 'tanggal' },
                        { data: 'total', name: 'total' },
                        { data: 'paymen_method', name: 'paymen_method' },
                        { data: 'status', name: 'status' },
                        { 
                            data: 'bukti_nota', 
                            name: 'bukti_nota',
                            orderable: false,
                            searchable: false,
                            render: function(data) {
                                if (data) {
                                    return '<a href="/storage/' + data + '" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> Lihat</a>';
                                }
                                return '-';
                            }
                        },
                        { data: 'action', orderable: false, searchable: false }
                    ]
                });

                // Generate nomor nota otomatis
                function generateNotaNo() {
                    let projectId = "{{ session('active_project_id') }}";
                    let tgl = $('#tanggalNota').val().replaceAll('-','');
                    let urut = Math.floor(Math.random() * 90000) + 10000;
                    return 'IN-' + projectId + '-' + tgl + '-' + urut;
                }

                // Toggle input manual nomor nota
                $('#chkManualNo').change(function() {
                    if ($(this).is(':checked')) {
                        $('#notaNo').prop('readonly', false).val('');
                    } else {
                        $('#notaNo').prop('readonly', true).val(generateNotaNo());
                    }
                });

                // Tampilkan tanggal tempo jika payment method = tempo
                $('#paymenMethod').change(function() {
                    if ($(this).val() === 'tempo') {
                        $('#tglTempoContainer').show();
                        $('#tglTempo').prop('required', true);
                    } else {
                        $('#tglTempoContainer').hide();
                        $('#tglTempo').prop('required', false);
                    }
                });

                // Preview image sebelum upload
                $('#buktiNota').change(function() {
                    const file = this.files[0];
                    if (file) {
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                $('#previewImage').attr('src', e.target.result);
                                $('#buktiPreview').show();
                            }
                            reader.readAsDataURL(file);
                        } else {
                            $('#buktiPreview').hide();
                        }
                    } else {
                        $('#buktiPreview').hide();
                    }
                });

                // Modal show event
                $('#modalNota').on('shown.bs.modal', function() {
                    let today = new Date().toISOString().split('T')[0];
                    $('#tanggalNota').val(today);
                    if (!$('#chkManualNo').is(':checked')) {
                        $('#notaNo').val(generateNotaNo());
                    }
                    
                    // Set tanggal tempo minimal hari ini
                    $('#tglTempo').attr('min', today);
                    
                    // Reset form
                    $('#frmNota')[0].reset();
                    $('#buktiPreview').hide();
                    $('.select2').val(null).trigger('change');
                    
                    // Reset detail transaksi ke 1 row
                    $('#tblDetail tbody').html(`
                        <tr>
                            <td>
                                <select class="form-select form-select-sm select2" name="transactions[0][idkodetransaksi]" style="width:100%;" required>
                                    <option value="">-- Pilih Kode Transaksi --</option>
                                    @foreach(\App\Models\KodeTransaksi::all() as $kt)
                                        <option value="{{ $kt->id }}" data-kode="{{ $kt->kodetransaksi }}">
                                            {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="text" class="form-control form-control-sm" name="transactions[0][description]" required></td>
                            <td><input type="number" class="form-control form-control-sm jml" name="transactions[0][jml]" value="1" min="1" step="0.01"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm nominal" name="transactions[0][nominal]" value="0" min="0"></td>
                            <td><input type="number" step="0.01" class="form-control form-control-sm total" name="transactions[0][total]" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                        </tr>
                    `);
                    $('#grandTotal').val('0.00');
                    $('.select2').select2({ dropdownParent: $('#modalNota') });
                });

                // Ambil saldo rekening
                $('#idRekening').change(function() {
                    let id = $(this).val();
                    if (id) {
                        let url = "{{ route('transaksi.project.rekening.saldo', ['id' => ':id']) }}";
                        url = url.replace(':id', id);
                        
                        $.get(url, function(res) {
                            $('#saldoRekening').html('<i class="bi bi-cash-coin"></i> Saldo: Rp ' + new Intl.NumberFormat('id-ID').format(res.saldo));
                        }).fail(function(xhr) {
                            console.error('Error mengambil saldo:', xhr);
                            $('#saldoRekening').html('<i class="bi bi-cash-coin"></i> Saldo: Error');
                        });
                    } else {
                        $('#saldoRekening').html('<i class="bi bi-cash-coin"></i> Saldo: Rp 0');
                    }
                });

                // Hitung total per row
                $(document).on('input', '.jml, .nominal', function() {
                    let row = $(this).closest('tr');
                    let jml = parseFloat(row.find('.jml').val()) || 0;
                    let nominal = parseFloat(row.find('.nominal').val()) || 0;
                    let total = jml * nominal;
                    row.find('.total').val(total.toFixed(2));
                    
                    calculateGrandTotal();
                });

                // Hitung grand total
                function calculateGrandTotal() {
                    let grandTotal = 0;
                    $('.total').each(function() {
                        grandTotal += parseFloat($(this).val()) || 0;
                    });
                    $('#grandTotal').val(grandTotal.toFixed(2));
                }

                // Tambah row detail
                let rowIndex = 1;
                $('#addRow').click(function() {
                    let html = `<tr>
                        <td>
                            <select class="form-select form-select-sm select2" name="transactions[${rowIndex}][idkodetransaksi]" style="width:100%;" required>
                                <option value="">-- Pilih Kode Transaksi --</option>
                                @foreach(\App\Models\KodeTransaksi::all() as $kt)
                                    <option value="{{ $kt->id }}" data-kode="{{ $kt->kodetransaksi }}">
                                        {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="transactions[${rowIndex}][description]" required></td>
                        <td><input type="number" class="form-control form-control-sm jml" name="transactions[${rowIndex}][jml]" value="1" min="1" step="0.01"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm nominal" name="transactions[${rowIndex}][nominal]" value="0" min="0"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm total" name="transactions[${rowIndex}][total]" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                    </tr>`;
                    $('#tblDetail tbody').append(html);
                    $('.select2').select2({ dropdownParent: $('#modalNota') });
                    rowIndex++;
                });

                // Hapus row detail
                $(document).on('click', '.removeRow', function() {
                    if ($('#tblDetail tbody tr').length > 1) {
                        $(this).closest('tr').remove();
                        calculateGrandTotal();
                    } else {
                        alert('Minimal harus ada 1 item transaksi');
                    }
                });

                // Submit form dengan FormData untuk handle file upload
                $('#frmNota').submit(function(e) {
                    e.preventDefault();
                    
                    // Validasi grand total
                    let grandTotal = parseFloat($('#grandTotal').val()) || 0;
                    if (grandTotal <= 0) {
                        alert('Total transaksi harus lebih dari 0');
                        return;
                    }

                    // Gunakan FormData untuk handle file upload
                    let formData = new FormData(this);

                    // Tampilkan loading
                    $('button[type="submit"]').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');

                    $.ajax({
                        url: "{{ route('transaksi.project.store', 'in') }}",
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            $('button[type="submit"]').prop('disabled', false).html('Simpan');
                            if (res.success) {
                                $('#modalNota').modal('hide');
                                tbNotas.ajax.reload();
                                alert(res.message);
                            } else {
                                alert('Error: ' + res.message);
                            }
                        },
                        error: function(xhr) {
                            $('button[type="submit"]').prop('disabled', false).html('Simpan');
                            let errors = xhr.responseJSON.errors;
                            let errorMsg = '';
                            if (errors) {
                                $.each(errors, function(key, value) {
                                    errorMsg += value[0] + '\n';
                                });
                            } else {
                                errorMsg = xhr.responseJSON.message || 'Terjadi kesalahan saat menyimpan data';
                            }
                            alert('Error: ' + errorMsg);
                        }
                    });
                });

                // Initialize select2
                $('.select2').select2({ dropdownParent: $('#modalNota') });
            });
        </script>
    </x-slot>
</x-app-layout>