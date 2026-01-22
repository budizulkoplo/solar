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
                                <input type="text" class="form-control form-control-sm" name="namatransaksi" value="Pembelian Barang Toko" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" id="tanggalPembelian" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vendor</label>
                                <select class="form-select form-select-sm select2" name="vendor_id" id="vendorId" style="width:100%;">
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
                                            {{ $rek->norek }} - {{ $rek->namarek }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted" id="saldoInfo">Saldo: Rp 0</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Bukti Nota</label>
                                <input type="file" class="form-control form-control-sm" name="bukti_nota" id="buktiNota" 
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                            </div>
                        </div>

                        <hr>

                        <h6>Detail Barang</h6>
                        <table class="table table-sm table-bordered" id="tblDetailBarang">
                            <thead>
                                <tr>
                                    <th width="40%">Barang *</th>
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
                                        <select class="form-select form-select-sm select2 barang-select" name="transactions[0][idbarang]" style="width:100%;">
                                            <option value="">-- Pilih Barang --</option>
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1 barang-new" 
                                               name="transactions[0][nama_barang]" placeholder="Nama Barang Baru" style="display:none;">
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
            }

            // Load barang untuk select2
            $('.barang-select').select2({
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
            }).on('select2:select', function(e) {
                let row = $(this).closest('tr');
                let barangId = e.params.data.id;
                
                // Get detail barang
                $.get("{{ route('toko.barang.detail', '') }}/" + barangId, function(res) {
                    if (res.success) {
                        let barang = res.data.barang;
                        row.find('.harga-beli').val(barang.harga_beli);
                        row.find('.harga-jual').val(barang.harga_jual);
                        row.find('.barang-new').hide();
                        calculateRowTotal(row);
                    }
                });
            });

            // Toggle input barang baru
            $(document).on('change', '.barang-select', function() {
                if ($(this).val() === 'new') {
                    $(this).hide();
                    $(this).closest('td').find('.barang-new').show().focus();
                } else {
                    $(this).show();
                    $(this).closest('td').find('.barang-new').hide();
                }
            });

            // Add row barang
            let barangRowIndex = 1;
            $('#addBarangRow').click(function() {
                let html = `
                    <tr>
                        <td>
                            <select class="form-select form-select-sm select2 barang-select" name="transactions[${barangRowIndex}][idbarang]" style="width:100%;">
                                <option value="">-- Pilih Barang --</option>
                            </select>
                            <input type="text" class="form-control form-control-sm mt-1 barang-new" 
                                   name="transactions[${barangRowIndex}][nama_barang]" placeholder="Nama Barang Baru" style="display:none;">
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
            }

            // Format Rupiah
            function formatRupiah(angka) {
                if (!angka || isNaN(angka)) return 'Rp 0';
                return 'Rp ' + new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(angka);
            }

            // Event listeners untuk perhitungan
            $(document).on('input', '.qty, .harga-beli', function() {
                calculateRowTotal($(this).closest('tr'));
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
                $('#tblDetailBarang tbody').html(`
                    <tr>
                        <td>
                            <select class="form-select form-select-sm select2 barang-select" name="transactions[0][idbarang]" style="width:100%;">
                                <option value="">-- Pilih Barang --</option>
                            </select>
                            <input type="text" class="form-control form-control-sm mt-1 barang-new" 
                                   name="transactions[0][nama_barang]" placeholder="Nama Barang Baru" style="display:none;">
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
            }

            // Submit form
            $('#frmPembelian').submit(function(e) {
                e.preventDefault();
                
                let formData = new FormData(this);
                
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
            });

            // Initialize
            initializeSelect2();
        });
        </script>
    </x-slot>
</x-app-layout>