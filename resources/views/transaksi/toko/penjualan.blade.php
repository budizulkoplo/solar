<x-app-layout>
    <x-slot name="pagetitle">Transaksi Penjualan - Toko</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Penjualan Barang - Toko</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-info card-outline mb-4">
                <div class="card-header pt-1 pb-1">
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" id="btnTambahPenjualan">
                            <i class="bi bi-file-earmark-plus"></i> Transaksi Penjualan Baru
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tbPenjualan" class="table table-sm table-striped w-100" style="font-size: small;">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nota No</th>
                                <th>Nama Trans.</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-center">Tipe</th>
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

    <!-- Modal Penjualan -->
    <div class="modal fade" id="modalPenjualan" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="frmPenjualan" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="idPenjualan">

                    <div class="modal-header">
                        <h6 class="modal-title">Form Penjualan Barang</h6>
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
                                <input type="text" class="form-control form-control-sm" name="namatransaksi" id="namaTransaksi" value="Penjualan Barang" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalPenjualan" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Jenis Penjualan *</label>
                                <select class="form-select form-select-sm" name="jenis_penjualan" id="jenisPenjualan" required>
                                    <option value="toko">Toko (Umum)</option>
                                    <option value="project">Ke Project</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="projectTujuanContainer" style="display:none;">
                                <label class="form-label">Project Tujuan *</label>
                                <select class="form-select form-select-sm select2" name="project_tujuan_id" id="projectTujuanId" style="width:100%;">
                                    <option value="">-- Pilih Project --</option>
                                    @foreach(\App\Models\Project::where('id', '!=', session('active_project_id'))->get() as $project)
                                        <option value="{{ $project->id }}">{{ $project->namaproject }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12">
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
                                    <small class="text-muted">Saldo akan bertambah: <strong id="saldoInfo">Rp 0</strong></small>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Keterangan / Customer</label>
                                <input type="text" class="form-control form-control-sm" name="keterangan_customer" id="keteranganCustomer" placeholder="Nama customer / keterangan penjualan">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Bukti Nota</label>
                                <input type="file" class="form-control form-control-sm" name="bukti_nota" id="buktiNota" 
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                            </div>
                        </div>

                        <hr>

                        <h6>Detail Barang <small class="text-muted">(Stock akan otomatis dikurangi)</small></h6>
                        
                        <div class="alert alert-warning p-2 mb-2" id="stockWarning" style="display:none;">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span id="warningMessage"></span>
                        </div>
                        
                        <table class="table table-sm table-bordered" id="tblDetailBarang">
                            <thead>
                                <tr>
                                    <th width="40%">Barang *</th>
                                    <th width="80">Stock</th>
                                    <th width="80">Qty</th>
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
                                        <select class="form-select form-select-sm barang-select" name="transactions[0][idbarang]" style="width:100%;" required>
                                            <option value="">-- Pilih Barang --</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <span class="stock-info badge bg-secondary">0</span>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[0][qty]" value="1" min="1"></td>
                                    <td><input type="number" class="form-control form-control-sm text-end harga-jual" name="transactions[0][harga_jual]" value="0" min="0" required></td>
                                    <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[0][total]" value="0" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-danger removeBarangRow">x</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                    <td><input type="text" class="form-control form-control-sm text-end fw-bold" id="grandTotal" value="Rp 0" readonly></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span class="submit-text">Simpan Penjualan</span>
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
            // DataTable penjualan
            let tbPenjualan = $('#tbPenjualan').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('toko.penjualan.data') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center' },
                    { data: 'nota_no', name: 'nota_no' },
                    { data: 'namatransaksi', name: 'namatransaksi' },
                    { 
                        data: 'tanggal', 
                        name: 'tanggal', 
                        className: 'text-center',
                        render: function(data) {
                            return new Date(data).toLocaleDateString('id-ID');
                        }
                    },
                    { 
                        data: 'type', 
                        name: 'type',
                        className: 'text-center',
                        render: function(data, type, row) {
                            let jenis = 'Toko';
                            if (data === 'project') {
                                jenis = 'Ke Project';
                            }
                            return '<span class="badge bg-success">' + jenis + '</span>';
                        }
                    },
                    { 
                        data: 'total', 
                        name: 'total', 
                        className: 'text-end',
                        render: function(data) {
                            return 'Rp ' + formatNumber(data);
                        }
                    },
                    { 
                        data: 'status', 
                        name: 'status', 
                        className: 'text-center',
                        render: function(data) {
                            const badge = {
                                'open': 'bg-warning',
                                'paid': 'bg-success', 
                                'partial': 'bg-info',
                                'cancel': 'bg-danger'
                            };
                            return '<span class="badge ' + (badge[data] || 'bg-secondary') + '">' + 
                                   data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                        }
                    },
                    { data: 'namauser', name: 'namauser', className: 'text-center' },
                    { 
                        data: 'action', 
                        orderable: false, 
                        searchable: false, 
                        className: 'text-center' 
                    }
                ],
                order: [[3, 'desc']]
            });

            // Set tanggal default
            $('#tanggalPenjualan').val(new Date().toISOString().split('T')[0]);

            // Generate nomor nota otomatis
            function generateNotaNo() {
                let tgl = $('#tanggalPenjualan').val().replaceAll('-','');
                let urut = Math.floor(Math.random() * 90000) + 10000;
                return 'JUAL-' + tgl + '-' + urut;
            }
            
            $('#tanggalPenjualan').change(function() {
                $('#notaNo').val(generateNotaNo());
            });
            
            $('#notaNo').val(generateNotaNo());

            // Initialize select2
            function initializeSelect2() {
                $('.select2').select2({ 
                    dropdownParent: $('#modalPenjualan'),
                    width: '100%'
                });
                
                // Initialize select2 untuk barang (sama seperti di pembelian)
                $('.barang-select').each(function(index) {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            dropdownParent: $('#modalPenjualan'),
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

            // Toggle project tujuan
            $('#jenisPenjualan').change(function() {
                if ($(this).val() === 'project') {
                    $('#projectTujuanContainer').show();
                    
                    $('#namaTransaksi').val('Penjualan Barang ke Project');
                } else {
                    $('#projectTujuanContainer').hide();
                    $('#projectTujuanId').prop('required', false);
                    $('#namaTransaksi').val('Penjualan Barang');
                }
            });

            // Update saldo info
            function updateSaldoInfo() {
                let selectedRekening = $('#idRekening').find(':selected');
                let saldo = selectedRekening.data('saldo') || 0;
                $('#saldoInfo').text('Rp ' + formatNumber(saldo));
            }

            // Load detail barang saat dipilih (sama seperti pembelian)
            $(document).on('select2:select', '.barang-select', function(e) {
                let row = $(this).closest('tr');
                let barangId = e.params.data.id;
                
                if (barangId) {
                    let detailUrl = "{{ route('toko.barang.detail', ['id' => ':id']) }}";
                    detailUrl = detailUrl.replace(':id', barangId);
                    
                    $.get(detailUrl, function(res) {
                        if (res.success) {
                            let barang = res.data.barang;
                            let stock = res.data.stock;
                            
                            // Update stock info
                            let stockBadge = stock > 10 ? 'bg-success' : (stock > 0 ? 'bg-warning' : 'bg-danger');
                            row.find('.stock-info')
                                .text(stock)
                                .removeClass('bg-secondary bg-success bg-warning bg-danger')
                                .addClass(stockBadge);
                            
                            // Set harga jual
                            row.find('.harga-jual').val(barang.harga_jual);
                            
                            // Hitung total
                            calculateRowTotal(row);
                        }
                    }).fail(function() {
                        console.error('Gagal mengambil detail barang');
                    });
                }
            });

            // Check stock availability
            function checkStockAvailability() {
                let warnings = [];
                let isValid = true;
                
                $('#tblDetailBarang tbody tr').each(function() {
                    let row = $(this);
                    let barangId = row.find('.barang-select').val();
                    let stockElement = row.find('.stock-info');
                    let stock = parseInt(stockElement.text());
                    let qty = parseInt(row.find('.qty').val()) || 0;
                    
                    if (barangId && !isNaN(stock) && !isNaN(qty)) {
                        if (qty > stock) {
                            let barangName = row.find('.barang-select').select2('data')[0]?.text || 'Barang';
                            barangName = barangName.split('|')[0].trim();
                            warnings.push(`${barangName}: Qty ${qty} > Stock ${stock}`);
                            isValid = false;
                        }
                    }
                });
                
                if (warnings.length > 0) {
                    $('#warningMessage').text('Stock tidak cukup: ' + warnings.join(', '));
                    $('#stockWarning').show();
                } else {
                    $('#stockWarning').hide();
                }
                
                return isValid;
            }

            // Add row barang
            let barangRowIndex = 1;
            $('#addBarangRow').click(function() {
                let html = `
                    <tr>
                        <td>
                            <select class="form-select form-select-sm barang-select" name="transactions[${barangRowIndex}][idbarang]" style="width:100%;" required>
                                <option value="">-- Pilih Barang --</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <span class="stock-info badge bg-secondary">0</span>
                        </td>
                        <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[${barangRowIndex}][qty]" value="1" min="1"></td>
                        <td><input type="number" class="form-control form-control-sm text-end harga-jual" name="transactions[${barangRowIndex}][harga_jual]" value="0" min="0" required></td>
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
                    checkStockAvailability();
                    calculateGrandTotal();
                }
            });

            // Calculate row total
            function calculateRowTotal(row) {
                let qty = parseFloat(row.find('.qty').val()) || 0;
                let hargaJual = parseFloat(row.find('.harga-jual').val()) || 0;
                let total = qty * hargaJual;
                row.find('.total').val(formatRupiah(total));
                calculateGrandTotal();
                checkStockAvailability();
            }

            // Calculate grand total
            function calculateGrandTotal() {
                let grandTotal = 0;
                $('.total').each(function() {
                    let val = parseFloat($(this).val().replace(/[^\d]/g, '')) || 0;
                    grandTotal += val;
                });
                $('#grandTotal').val(formatRupiah(grandTotal));
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
            $(document).on('input', '.qty, .harga-jual', function() {
                calculateRowTotal($(this).closest('tr'));
            });

            // Update saldo saat rekening berubah
            $('#idRekening').change(function() {
                updateSaldoInfo();
            });

            // Tombol tambah penjualan
            $('#btnTambahPenjualan').click(function() {
                resetForm();
                $('#modalPenjualan').modal('show');
            });

            // Reset form
            function resetForm() {
                $('#frmPenjualan')[0].reset();
                $('#idPenjualan').val('');
                $('#jenisPenjualan').val('toko').trigger('change');
                $('#projectTujuanContainer').hide();
                $('#stockWarning').hide();
                $('#keteranganCustomer').val('');
                $('#namaTransaksi').val('Penjualan Barang');
                
                $('#tblDetailBarang tbody').html(`
                    <tr>
                        <td>
                            <select class="form-select form-select-sm barang-select" name="transactions[0][idbarang]" style="width:100%;" required>
                                <option value="">-- Pilih Barang --</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <span class="stock-info badge bg-secondary">0</span>
                        </td>
                        <td><input type="number" class="form-control form-control-sm text-end qty" name="transactions[0][qty]" value="1" min="1"></td>
                        <td><input type="number" class="form-control form-control-sm text-end harga-jual" name="transactions[0][harga_jual]" value="0" min="0" required></td>
                        <td><input type="text" class="form-control form-control-sm text-end total" name="transactions[0][total]" value="0" readonly></td>
                        <td><button type="button" class="btn btn-sm btn-danger removeBarangRow">x</button></td>
                    </tr>
                `);
                $('#grandTotal').val('Rp 0');
                $('#tanggalPenjualan').val(new Date().toISOString().split('T')[0]);
                $('#notaNo').val(generateNotaNo());
                barangRowIndex = 1;
                initializeSelect2();
                updateSaldoInfo();
            }

            // Submit form
            $('#frmPenjualan').submit(function(e) {
                e.preventDefault();
                
                // Validasi stock
                if (!checkStockAvailability()) {
                    Swal.fire('Peringatan', 'Ada barang yang stocknya tidak cukup. Silahkan periksa kembali.', 'warning');
                    return;
                }
                
                // Validasi minimal 1 barang
                let hasBarang = false;
                $('#tblDetailBarang tbody tr').each(function() {
                    if ($(this).find('.barang-select').val()) {
                        hasBarang = true;
                    }
                });
                
                if (!hasBarang) {
                    Swal.fire('Peringatan', 'Minimal harus ada 1 barang yang dipilih', 'warning');
                    return;
                }
                
                let formData = new FormData(this);
                
                // Tampilkan loading
                $('#btnSubmit').prop('disabled', true);
                $('.submit-text').hide();
                $('.loading-text').show();

                $.ajax({
                    url: "{{ route('toko.penjualan.store') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btnSubmit').prop('disabled', false);
                        $('.submit-text').show();
                        $('.loading-text').hide();
                        
                        if (res.success) {
                            $('#modalPenjualan').modal('hide');
                            tbPenjualan.ajax.reload();
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
            });

            // Initialize
            initializeSelect2();
            updateSaldoInfo();
        });
        </script>
    </x-slot>
</x-app-layout>