<x-app-layout>
    <x-slot name="pagetitle">Transaksi Pembelian - Toko</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Pembelian Barang - Toko</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" id="btnTambahPembelian">
                            <i class="bi bi-file-earmark-plus"></i> Transaksi Pembelian Baru
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbPembelian" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nota No</th>
                                <th>Nama Trans.</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-center">Vendor</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">User</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pembelian -->
    <div class="modal fade" id="modalPembelian" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="frmPembelian" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="idPembelian">

                    <div class="modal-header">
                        <h6 class="modal-title">Form Pembelian Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">No Invoice *</label>
                                <input type="text" class="form-control form-control-sm" name="nota_no" id="notaNo" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Transaksi *</label>
                                <input type="text" class="form-control form-control-sm" name="namatransaksi" value="Pembelian Barang" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalPembelian" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor *</label>
                                <select class="form-select form-select-sm select2" name="vendor_id" id="vendorId" style="width:100%;" required>
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach(\App\Models\Vendor::whereNull('deleted_at')->get() as $v)
                                        <option value="{{ $v->id }}">{{ $v->namavendor }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select form-select-sm select2" name="idrek" id="idRekening" style="width:100%;" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach(\App\Models\Rekening::forProject(session('active_project_id'))->get() as $rek)
                                        <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                            {{ $rek->norek }} - {{ $rek->namarek }} (Saldo: Rp {{ number_format($rek->saldo, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="mt-1">
                                    <small class="text-muted">Saldo tersedia: <strong id="saldoInfo">Rp 0</strong></small>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Bukti Nota</label>
                                <input type="file" class="form-control form-control-sm" name="bukti_nota" id="buktiNota" 
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Detail Barang</h6>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleBarangBaru">
                                    <i class="bi bi-plus-circle"></i> Mode Tambah Barang Baru
                                </button>
                            </div>
                        </div>
                        
                        <div class="alert alert-info p-2 mb-3" id="modeBarangBaruAlert" style="display:none;">
                            <i class="bi bi-info-circle"></i> Mode Tambah Barang Baru aktif. Anda bisa langsung membuat barang baru di form ini.
                        </div>
                        
                        <table class="table table-sm table-bordered" id="tblDetailBarang">
                            <thead>
                                <tr>
                                    <th width="35%">Barang *</th>
                                    <th>Deskripsi</th>
                                    <th width="80">Qty</th>
                                    <th width="120">Harga Beli</th>
                                    <th width="120">Harga Jual</th>
                                    <th width="120">Total</th>
                                    <th width="40">
                                        <button type="button" class="btn btn-sm btn-success" id="addBarangRow">+</button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select form-select-sm barang-select" name="transactions[0][idbarang]" style="width:100%;" required>
                                                <option value="">-- Pilih Barang --</option>
                                            </select>
                                            <button class="btn btn-outline-secondary btn-sm" type="button" id="toggleBarangBaru0">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                        <div class="barang-baru-container mt-1" style="display:none;">
                                            <input type="text" class="form-control form-control-sm barang-nama" 
                                                   name="transactions[0][nama_barang]" placeholder="Nama Barang Baru *" required>
                                            <small class="text-muted">Barang baru akan dibuat otomatis</small>
                                        </div>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="transactions[0][deskripsi]" placeholder="Deskripsi"></td>
                                    <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[0][qty]" value="1" min="1"></td>
                                    <td><input type="number"  class="form-control form-control-sm text-end harga-beli" name="transactions[0][harga_beli]" value="0" min="0" required></td>
                                    <td><input type="number"  class="form-control form-control-sm text-end harga-jual" name="transactions[0][harga_jual]" value="0" min="0" required></td>
                                    <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[0][total]" value="0" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-danger removeBarangRow">x</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Grand Total:</strong></td>
                                    <td><input type="text" class="form-control form-control-sm text-end fw-bold" id="grandTotal" value="Rp 0" readonly></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <!-- Warning saldo tidak cukup -->
                        <div class="alert alert-warning p-2 mt-2" id="saldoWarning" style="display:none;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Peringatan:</strong> Grand Total melebihi saldo rekening!
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span class="submit-text">Simpan Pembelian</span>
                            <span class="loading-text" style="display:none;">
                                <i class="bi bi-hourglass-split"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
            // DataTable pembelian
            let tbPembelian = $('#tbPembelian').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('toko.pembelian.data') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center' },
                    { data: 'nota_no', name: 'nota_no' },
                    { data: 'namatransaksi', name: 'namatransaksi' },
                    { data: 'tanggal', name: 'tanggal', className: 'text-center' },
                    { data: 'vendor.namavendor', name: 'vendor.namavendor', className: 'text-center' },
                    { data: 'total', name: 'total', className: 'text-end' },
                    { data: 'status', name: 'status', className: 'text-center' },
                    { data: 'namauser', name: 'namauser', className: 'text-center' },
                    { data: 'action', orderable: false, searchable: false, className: 'text-center' }
                ]
            });

            // Set tanggal default
            $('#tanggalPembelian').val(new Date().toISOString().split('T')[0]);

            // Generate nomor nota otomatis
            function generateNotaNo() {
                let tgl = $('#tanggalPembelian').val().replaceAll('-','');
                let urut = Math.floor(Math.random() * 90000) + 10000;
                return 'BLI-' + tgl + '-' + urut;
            }
            
            $('#tanggalPembelian').change(function() {
                $('#notaNo').val(generateNotaNo());
            });
            
            $('#notaNo').val(generateNotaNo());

            // Initialize select2
            function initializeSelect2() {
                $('.select2').select2({ 
                    dropdownParent: $('#modalPembelian'),
                    width: '100%'
                });
                
                // Initialize select2 untuk barang
                $('.barang-select').each(function(index) {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            dropdownParent: $('#modalPembelian'),
                            width: '100%',
                            ajax: {
                                url: "{{ route('toko.barang.search') }}",
                                dataType: 'json',
                                delay: 250,
                                data: function(params) {
                                    return {
                                        search: params.term
                                    };
                                },
                                processResults: function(data) {
                                    return {
                                        results: data
                                    };
                                }
                            },
                            minimumInputLength: 1
                        });
                    }
                });
            }

            // Update saldo rekening
            function updateSaldoRekening() {
                let selectedRekening = $('#idRekening').find(':selected');
                let saldo = selectedRekening.data('saldo') || 0;
                $('#saldoInfo').text('Rp ' + formatNumber(saldo));
                checkSaldoCukup();
            }

            // Cek saldo cukup
            function checkSaldoCukup() {
                let saldo = parseFloat($('#idRekening').find(':selected').data('saldo')) || 0;
                let grandTotal = parseFloat($('#grandTotal').val().replace(/[^\d]/g, '')) || 0;
                
                if (grandTotal > 0 && grandTotal > saldo) {
                    $('#saldoWarning').show();
                    return false;
                } else {
                    $('#saldoWarning').hide();
                    return true;
                }
            }

            // Toggle mode barang baru
            let modeBarangBaru = false;
            $('#btnToggleBarangBaru').click(function() {
                modeBarangBaru = !modeBarangBaru;
                
                if (modeBarangBaru) {
                    $('#modeBarangBaruAlert').show();
                    $('#btnToggleBarangBaru').html('<i class="bi bi-box"></i> Mode Pilih Barang');
                    $('.barang-select').prop('disabled', true);
                    $('.barang-baru-container').show();
                    $('.barang-nama').prop('required', true);
                    $('.barang-select').prop('required', false);
                } else {
                    $('#modeBarangBaruAlert').hide();
                    $('#btnToggleBarangBaru').html('<i class="bi bi-plus-circle"></i> Mode Tambah Barang Baru');
                    $('.barang-select').prop('disabled', false);
                    $('.barang-baru-container').hide();
                    $('.barang-nama').prop('required', false);
                    $('.barang-select').prop('required', true);
                }
            });

            // Toggle barang baru per row
            $(document).on('click', '.toggle-barang-baru', function() {
                let button = $(this);
                let container = button.closest('.input-group').next('.barang-baru-container');
                let select = button.closest('.input-group').find('.barang-select');
                
                if (container.is(':visible')) {
                    container.hide();
                    select.prop('disabled', false).prop('required', true);
                    container.find('.barang-nama').prop('required', false);
                    button.html('<i class="bi bi-plus"></i>');
                } else {
                    container.show();
                    select.prop('disabled', true).prop('required', false);
                    container.find('.barang-nama').prop('required', true);
                    button.html('<i class="bi bi-box"></i>');
                }
            });

            // Load detail barang saat dipilih
            $(document).on('select2:select', '.barang-select', function(e) {
                let row = $(this).closest('tr');
                let barangId = e.params.data.id;
                
                if (barangId) {
                    let detailUrl = "{{ route('toko.barang.detail', ['id' => ':id']) }}";
                    detailUrl = detailUrl.replace(':id', barangId);
                    
                    $.get(detailUrl, function(res) {
                        if (res.success) {
                            let barang = res.data.barang;
                            row.find('.harga-beli').val(barang.harga_beli);
                            row.find('.harga-jual').val(barang.harga_jual);
                            calculateRowTotal(row);
                        }
                    }).fail(function() {
                        console.error('Gagal mengambil detail barang');
                    });
                }
            });

            // Add row barang
            let barangRowIndex = 1;
            $('#addBarangRow').click(function() {
                let html = `
                    <tr>
                        <td>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm barang-select" name="transactions[${barangRowIndex}][idbarang]" style="width:100%;" ${modeBarangBaru ? 'disabled' : 'required'}>
                                    <option value="">-- Pilih Barang --</option>
                                </select>
                                <button class="btn btn-outline-secondary btn-sm toggle-barang-baru" type="button">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <div class="barang-baru-container mt-1" style="display:${modeBarangBaru ? 'block' : 'none'}">
                                <input type="text" class="form-control form-control-sm barang-nama" 
                                       name="transactions[${barangRowIndex}][nama_barang]" placeholder="Nama Barang Baru *" ${modeBarangBaru ? 'required' : ''}>
                                <small class="text-muted">Barang baru akan dibuat otomatis</small>
                            </div>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="transactions[${barangRowIndex}][deskripsi]" placeholder="Deskripsi"></td>
                        <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[${barangRowIndex}][qty]" value="1" min="1"></td>
                        <td><input type="number"  class="form-control form-control-sm text-end harga-beli" name="transactions[${barangRowIndex}][harga_beli]" value="0" min="0" required></td>
                        <td><input type="number"  class="form-control form-control-sm text-end harga-jual" name="transactions[${barangRowIndex}][harga_jual]" value="0" min="0" required></td>
                        <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[${barangRowIndex}][total]" value="0" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeBarangRow">x</button></td>
                    </tr>
                `;
                $('#tblDetailBarang tbody').append(html);
                initializeSelect2();
                barangRowIndex++;
            });

            // Remove row barang
            $(document).on('click', '.removeBarangRow', function() {
                if ($('#tblDetailBarang tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calculateGrandTotal();
                }
            });

            // Calculate row total
            function calculateRowTotal(row) {
                let qty = parseFloat(row.find('.qty').val()) || 0;
                let hargaBeli = parseFloat(row.find('.harga-beli').val()) || 0;
                let total = qty * hargaBeli;
                row.find('.total').val(formatRupiah(total));
                calculateGrandTotal();
            }

            // Calculate grand total
            function calculateGrandTotal() {
                let grandTotal = 0;
                $('.total').each(function() {
                    let val = parseFloat($(this).val().replace(/[^\d]/g, '')) || 0;
                    grandTotal += val;
                });
                $('#grandTotal').val(formatRupiah(grandTotal));
                checkSaldoCukup();
            }

            // Format Rupiah
            function formatRupiah(angka) {
                if (!angka || isNaN(angka)) return 'Rp 0';
                return 'Rp ' + formatNumber(angka);
            }
            
            // Format number
            function formatNumber(num) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(num);
            }

            // Event listeners untuk perhitungan
            $(document).on('input', '.qty, .harga-beli, .harga-jual', function() {
                calculateRowTotal($(this).closest('tr'));
            });

            // Update saldo saat rekening berubah
            $('#idRekening').change(function() {
                updateSaldoRekening();
            });

            // Tombol tambah pembelian
            $('#btnTambahPembelian').click(function() {
                resetForm();
                $('#modalPembelian').modal('show');
            });

            // Reset form
            function resetForm() {
                $('#frmPembelian')[0].reset();
                $('#idPembelian').val('');
                modeBarangBaru = false;
                $('#modeBarangBaruAlert').hide();
                $('#btnToggleBarangBaru').html('<i class="bi bi-plus-circle"></i> Mode Tambah Barang Baru');
                $('#saldoWarning').hide();
                
                $('#tblDetailBarang tbody').html(`
                    <tr>
                        <td>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm barang-select" name="transactions[0][idbarang]" style="width:100%;" required>
                                    <option value="">-- Pilih Barang --</option>
                                </select>
                                <button class="btn btn-outline-secondary btn-sm toggle-barang-baru" type="button" id="toggleBarangBaru0">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <div class="barang-baru-container mt-1" style="display:none;">
                                <input type="text" class="form-control form-control-sm barang-nama" 
                                       name="transactions[0][nama_barang]" placeholder="Nama Barang Baru *">
                                <small class="text-muted">Barang baru akan dibuat otomatis</small>
                            </div>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="transactions[0][deskripsi]" placeholder="Deskripsi"></td>
                        <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[0][qty]" value="1" min="1"></td>
                        <td><input type="number"  class="form-control form-control-sm text-end harga-beli" name="transactions[0][harga_beli]" value="0" min="0" required></td>
                        <td><input type="number"  class="form-control form-control-sm text-end harga-jual" name="transactions[0][harga_jual]" value="0" min="0" required></td>
                        <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[0][total]" value="0" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeBarangRow">x</button></td>
                    </tr>
                `);
                $('#grandTotal').val('Rp 0');
                $('#tanggalPembelian').val(new Date().toISOString().split('T')[0]);
                $('#notaNo').val(generateNotaNo());
                barangRowIndex = 1;
                initializeSelect2();
                updateSaldoRekening();
            }

            // Submit form
            $('#frmPembelian').submit(function(e) {
                e.preventDefault();
                
                // Validasi saldo
                if (!checkSaldoCukup()) {
                    Swal.fire({
                        title: 'Saldo Tidak Cukup',
                        text: 'Saldo rekening tidak mencukupi untuk transaksi ini. Lanjutkan?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Lanjutkan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            processFormSubmission();
                        }
                    });
                } else {
                    processFormSubmission();
                }
            });

            function processFormSubmission() {
                let formData = new FormData($('#frmPembelian')[0]);
                
                // Validasi minimal 1 barang
                let isValid = false;
                $('#tblDetailBarang tbody tr').each(function() {
                    let barangId = $(this).find('.barang-select').val();
                    let namaBarang = $(this).find('.barang-nama').val();
                    
                    if (barangId || (namaBarang && namaBarang.trim() !== '')) {
                        isValid = true;
                    }
                });
                
                if (!isValid) {
                    Swal.fire('Peringatan', 'Minimal harus ada 1 barang yang dipilih atau dibuat', 'warning');
                    return;
                }
                
                // Tampilkan loading
                $('#btnSubmit').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();

                $.ajax({
                    url: "{{ route('toko.pembelian.store') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalPembelian').modal('hide');
                            tbPembelian.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            }

            // Initialize
            initializeSelect2();
            updateSaldoRekening();
        });
        </script>
    </x-slot>
</x-app-layout>