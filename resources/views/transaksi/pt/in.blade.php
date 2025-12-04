<x-app-layout>
    <x-slot name="pagetitle">Transaksi Masuk - PT</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Transaksi Masuk - {{ session('active_company_name') }}</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" id="btnTambahNota">
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
                                <th>Nama Trans.</th>
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

    <!-- Modal Nota PT -->
    <div class="modal fade" id="modalNota" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="frmNota" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="idNota">
                    <input type="hidden" name="idcompany" value="{{ session('active_project_id') }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalNotaTitle">Form Nota Masuk - PT</h5>
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

                            <div class="col-md-4">
                                <label class="form-label">Nama Transaksi *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" name="namatransaksi" id="namatransaksi" required>
                                    
                                </div>
                            </div>

                            {{-- PT/Company --}}
                            <div class="col-md-4">
                                <label class="form-label">PT/Company</label>
                                <input type="text" class="form-control form-control-sm" value="{{ session('active_company_name') }}" disabled>
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

                            {{-- Rekening (Hanya rekening milik PT/Company) --}}
                            <div class="col-md-4 mt-2">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select form-select-sm select2" name="idrek" id="idRekening" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach(\App\Models\Rekening::where('idcompany', session('active_company_id'))->whereNull('idproject')->get() as $rek)
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
                                        <select class="form-select form-select-sm select2 kode-transaksi" name="transactions[0][idkodetransaksi]" style="width:100%;" required>
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
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span class="submit-text">Simpan</span>
                            <span class="loading-text" style="display:none;">
                                <i class="bi bi-hourglass-split"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal View Nota PT -->
    <div class="modal fade" id="modalViewNota" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Nota - PT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">No Nota</th>
                                    <td id="viewNotaNo">-</td>
                                </tr>
                                <tr>
                                    <th>Tanggal</th>
                                    <td id="viewTanggal">-</td>
                                </tr>
                                <tr>
                                    <th>PT/Company</th>
                                    <td id="viewCompany">-</td>
                                </tr>

                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Payment Method</th>
                                    <td id="viewPaymentMethod">-</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Tempo</th>
                                    <td id="viewTglTempo">-</td>
                                </tr>
                                <tr>
                                    <th>Total</th>
                                    <td id="viewTotal">-</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td id="viewStatus">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6>Detail Transaksi</h6>
                    <table class="table table-sm table-bordered" id="tblViewDetail">
                        <thead>
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Deskripsi</th>
                                <th>Qty</th>
                                <th>Nominal</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data akan diisi oleh JavaScript -->
                        </tbody>
                    </table>

                    <div id="viewBuktiNota" class="mt-3" style="display:none;">
                        <h6>Bukti Nota</h6>
                        <img id="viewBuktiImage" src="#" alt="Bukti Nota" class="img-thumbnail" style="max-height: 300px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
            // DataTable untuk PT
            let tbNotas = $('#tbNotas').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('transaksi.pt.getdata', 'in') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'nota_no', name: 'nota_no' },
                    { data: 'namatransaksi', name: 'namatransaksi' },
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
                    { 
                        data: 'action', 
                        orderable: false, 
                        searchable: false
                    }
                ]
            });

            // Generate nomor nota otomatis untuk IN PT
            function generateNotaNo() {
                let companyId = "{{ session('active_company_id') }}";
                let tgl = $('#tanggalNota').val().replaceAll('-','');
                let urut = Math.floor(Math.random() * 90000) + 10000;
                return 'PT-IN-' + companyId + '-' + tgl + '-' + urut;
            }

            // Set tanggal default ke hari ini
            function setDefaultDate() {
                let today = new Date().toISOString().split('T')[0];
                $('#tanggalNota').val(today);
            }

            // Set nomor nota otomatis
            function setAutoNotaNo() {
                if (!$('#chkManualNo').is(':checked')) {
                    $('#notaNo').val(generateNotaNo());
                }
            }

            // Reset form ke kondisi default
            function resetForm() {
                $('#frmNota')[0].reset();
                $('#idNota').val('');
                $('#buktiPreview').hide();
                $('#tglTempoContainer').hide();
                $('#tglTempo').prop('required', false);
                $('.select2').val(null).trigger('change');
                $('#modalNotaTitle').text('Form Nota Masuk - PT');
                
                // Reset detail transaksi ke 1 row
                $('#tblDetail tbody').html(`
                    <tr>
                        <td>
                            <select class="form-select form-select-sm select2 kode-transaksi" name="transactions[0][idkodetransaksi]" style="width:100%;" required>
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
                
                // Set default values
                setDefaultDate();
                setAutoNotaNo();
                
                // Re-initialize select2
                initializeSelect2();
                
                // Enable manual input checkbox
                $('#chkManualNo').prop('checked', false);
                $('#notaNo').prop('readonly', true);
            }

            // Initialize select2
            function initializeSelect2() {
                $('.select2').select2({ 
                    dropdownParent: $('#modalNota'),
                    width: '100%'
                });
            }

            // Toggle input manual nomor nota
            $('#chkManualNo').change(function() {
                if ($(this).is(':checked')) {
                    $('#notaNo').prop('readonly', false).val('');
                } else {
                    $('#notaNo').prop('readonly', true);
                    setAutoNotaNo();
                }
            });

            // Update nomor nota ketika tanggal berubah
            $('#tanggalNota').change(function() {
                if (!$('#chkManualNo').is(':checked')) {
                    setAutoNotaNo();
                }
                // Update min date untuk tgl tempo
                $('#tglTempo').attr('min', $(this).val());
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

            // Ambil saldo rekening PT
            $('#idRekening').change(function() {
                let id = $(this).val();
                if (id) {
                    let url = "{{ route('transaksi.pt.rekening.saldo', ['id' => ':id']) }}";
                    url = url.replace(':id', id);
                    
                    $.get(url, function(res) {
                        $('#saldoRekening').html('<i class="bi bi-cash-coin"></i> Saldo: Rp ' + new Intl.NumberFormat('id-ID').format(res.saldo));
                    }).fail(function(xhr) {
                        console.error('Error mengambil saldo PT:', xhr);
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
                        <select class="form-select form-select-sm select2 kode-transaksi" name="transactions[${rowIndex}][idkodetransaksi]" style="width:100%;" required>
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
                initializeSelect2();
                rowIndex++;
            });

            // Hapus row detail
            $(document).on('click', '.removeRow', function() {
                if ($('#tblDetail tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calculateGrandTotal();
                } else {
                    Swal.fire('Peringatan', 'Minimal harus ada 1 item transaksi', 'warning');
                }
            });

            // Tombol tambah nota - reset form dan buka modal
            $('#btnTambahNota').click(function() {
                resetForm();
                $('#modalNota').modal('show');
            });

            // View nota PT
            $(document).on('click', '.view-btn', function() {
                let notaId = $(this).data('id');
                
                $.get("/transaksi/pt/" + notaId, function(res) {
                    if (res.success) {
                        let nota = res.data;
                        
                        // Isi data header
                        $('#viewNotaNo').text(nota.nota_no);
                        $('#viewTanggal').text(nota.tanggal);
                        $('#viewCompany').text(nota.company ? nota.company.nama_perusahaan : '-');
                        $('#viewVendor').text(nota.vendor ? nota.vendor.namavendor : '-');
                        $('#viewPaymentMethod').text(nota.paymen_method);
                        $('#viewTglTempo').text(nota.tgl_tempo || '-');
                        $('#viewTotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(nota.total));
                        $('#viewStatus').html(getStatusBadge(nota.status));
                        
                        // Isi detail transaksi
                        let detailHtml = '';
                        if (nota.transactions && nota.transactions.length > 0) {
                            nota.transactions.forEach(function(transaction) {
                                detailHtml += `
                                    <tr>
                                        <td>${transaction.kode_transaksi ? transaction.kode_transaksi.kodetransaksi : '-'}</td>
                                        <td>${transaction.description}</td>
                                        <td>${transaction.jml}</td>
                                        <td>Rp ${new Intl.NumberFormat('id-ID').format(transaction.nominal)}</td>
                                        <td>Rp ${new Intl.NumberFormat('id-ID').format(transaction.total)}</td>
                                    </tr>
                                `;
                            });
                        }
                        $('#tblViewDetail tbody').html(detailHtml);
                        
                        // Tampilkan bukti nota jika ada
                        if (nota.bukti_nota) {
                            $('#viewBuktiImage').attr('src', '/storage/' + nota.bukti_nota);
                            $('#viewBuktiNota').show();
                        } else {
                            $('#viewBuktiNota').hide();
                        }
                        
                        $('#modalViewNota').modal('show');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error', 'Gagal memuat data nota PT', 'error');
                });
            });

            // Edit nota PT
            $(document).on('click', '.edit-btn', function() {
                let notaId = $(this).data('id');
                
                $.get("/transaksi/pt/" + notaId + "/edit", function(res) {
                    if (res.success) {
                        let nota = res.data.nota;
                        let transactions = res.data.transactions;
                        
                        // Isi form dengan data existing
                        $('#idNota').val(nota.id);
                        $('#notaNo').val(nota.nota_no);
                        $('#paymenMethod').val(nota.paymen_method).trigger('change');
                        $('#tanggalNota').val(nota.tanggal);
                        $('#vendorId').val(nota.vendor_id).trigger('change');
                        $('#idRekening').val(nota.idrek).trigger('change');
                        
                        // Handle tanggal tempo
                        if (nota.paymen_method === 'tempo' && nota.tgl_tempo) {
                            $('#tglTempoContainer').show();
                            $('#tglTempo').val(nota.tgl_tempo).prop('required', true);
                        }
                        
                        // Enable manual input untuk edit
                        $('#chkManualNo').prop('checked', true);
                        $('#notaNo').prop('readonly', false);
                        
                        // Isi detail transaksi
                        $('#tblDetail tbody').empty();
                        let newRowIndex = 0;
                        
                        if (transactions && transactions.length > 0) {
                            transactions.forEach(function(transaction) {
                                let html = `
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm kode-transaksi" name="transactions[${newRowIndex}][idkodetransaksi]" required>
                                                <option value="">-- Pilih Kode Transaksi --</option>
                                `;
                                
                                // Tambahkan options untuk kode transaksi
                                @foreach(\App\Models\KodeTransaksi::all() as $kt)
                                    html += `<option value="{{ $kt->id }}" ${transaction.idkodetransaksi == {{ $kt->id }} ? 'selected' : '' }>{{ $kt->kodetransaksi }} - {{ $kt->transaksi }}</option>`;
                                @endforeach
                                
                                html += `
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm" name="transactions[${newRowIndex}][description]" value="${transaction.description || ''}" required></td>
                                        <td><input type="number" class="form-control form-control-sm jml" name="transactions[${newRowIndex}][jml]" value="${transaction.jml || 1}" min="1" step="0.01"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm nominal" name="transactions[${newRowIndex}][nominal]" value="${transaction.nominal || 0}" min="0"></td>
                                        <td><input type="number" step="0.01" class="form-control form-control-sm total" name="transactions[${newRowIndex}][total]" value="${transaction.total || 0}" readonly></td>
                                        <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                                    </tr>
                                `;
                                
                                $('#tblDetail tbody').append(html);
                                newRowIndex++;
                            });
                        }
                        
                        calculateGrandTotal();
                        rowIndex = newRowIndex;
                        
                        // Update modal title
                        $('#modalNotaTitle').text('Edit Nota Masuk - PT');
                        
                        // Tampilkan modal
                        $('#modalNota').modal('show');
                        
                        // Initialize select2 setelah modal ditampilkan
                        setTimeout(() => {
                            initializeSelect2();
                        }, 300);
                        
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error', 'Gagal memuat data untuk edit', 'error');
                });
            });

            // Delete nota PT
            $(document).on('click', '.delete-btn', function() {
                let notaId = $(this).data('id');
                
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Transaksi PT ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "/transaksi/pt/" + notaId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    tbNotas.ajax.reload();
                                    Swal.fire('Berhasil!', res.message, 'success');
                                } else {
                                    Swal.fire('Error!', res.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                            }
                        });
                    }
                });
            });

            // Submit form PT
            $('#frmNota').submit(function(e) {
                e.preventDefault();
                
                // Validasi grand total
                let grandTotal = parseFloat($('#grandTotal').val()) || 0;
                if (grandTotal <= 0) {
                    Swal.fire('Peringatan', 'Total transaksi harus lebih dari 0', 'warning');
                    return;
                }

                // Validasi tanggal tempo jika payment method = tempo
                if ($('#paymenMethod').val() === 'tempo' && !$('#tglTempo').val()) {
                    Swal.fire('Peringatan', 'Tanggal tempo harus diisi untuk payment method tempo', 'warning');
                    return;
                }

                let notaId = $('#idNota').val();
                let url, method;
                
                if (notaId) {
                    // Edit existing nota
                    url = "/transaksi/pt/" + notaId + "/in";
                    method = 'PUT';
                } else {
                    // Create new nota
                    url = "{{ route('transaksi.pt.store', 'in') }}";
                    method = 'POST';
                }

                // Gunakan FormData untuk handle file upload
                let formData = new FormData(this);
                if (notaId) {
                    formData.append('_method', 'PUT');
                }

                // Tampilkan loading
                $('#btnSubmit').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalNota').modal('hide');
                            tbNotas.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        let errors = xhr.responseJSON?.errors;
                        let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data';
                        
                        if (errors) {
                            errorMsg = '';
                            $.each(errors, function(key, value) {
                                errorMsg += value[0] + '\n';
                            });
                        }
                        
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            });

            // Helper function untuk status badge
            function getStatusBadge(status) {
                const badge = {
                    'open': 'bg-warning',
                    'paid': 'bg-success', 
                    'partial': 'bg-info',
                    'cancel': 'bg-danger'
                };
                return `<span class="badge ${badge[status]}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
            }

            // Initialize select2 saat pertama kali load
            initializeSelect2();
            setDefaultDate();
            setAutoNotaNo();
        });
        </script>
    </x-slot>
</x-app-layout>