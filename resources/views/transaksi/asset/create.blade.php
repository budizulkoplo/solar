<x-app-layout>
    <x-slot name="pagetitle">Tambah Transaksi Asset</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Tambah Transaksi Asset</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <a href="{{ route('transaksi.asset.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form id="frmAssetTransaction" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-receipt"></i> Informasi Nota
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">No Invoice *</label>
                                        <input type="text" class="form-control" name="nota_no" 
                                               value="AST-{{ session('active_project_id') }}-{{ date('Ym') }}-001" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Transaksi *</label>
                                        <input type="text" class="form-control" name="namatransaksi" 
                                               placeholder="Contoh: Pembelian Komputer" required>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal *</label>
                                        <input type="date" class="form-control" name="tanggal" 
                                               value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Vendor *</label>
                                        <select class="form-select" name="vendor_id" required>
                                            <option value="">-- Pilih Vendor --</option>
                                            @foreach(\App\Models\Vendor::all() as $vendor)
                                                <option value="{{ $vendor->id }}">{{ $vendor->namavendor }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Payment Method *</label>
                                        <select class="form-select" name="paymen_method" required>
                                            <option value="cash">Cash</option>
                                            <option value="tempo">Tempo</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Rekening *</label>
                                        <select class="form-select" name="idrek" required>
                                            <option value="">-- Pilih Rekening --</option>
                                            @foreach(\App\Models\Rekening::forProject(session('active_project_id'))->get() as $rek)
                                                <option value="{{ $rek->idrek }}">
                                                    {{ $rek->norek }} - {{ $rek->namarek }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Bukti Nota *</label>
                                        <input type="file" class="form-control" name="bukti_nota" 
                                               accept=".jpg,.jpeg,.png,.pdf" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-list-check"></i> Detail Transaksi
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered" id="tblTransactions">
                                    <thead>
                                        <tr>
                                            <th>Kode Transaksi *</th>
                                            <th>Deskripsi *</th>
                                            <th width="100">Qty</th>
                                            <th width="150">Harga Satuan</th>
                                            <th width="150">Total</th>
                                            <th width="50">
                                                <button type="button" class="btn btn-sm btn-success" id="addTransaction">+</button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <select class="form-select form-select-sm" name="transactions[0][idkodetransaksi]" required>
                                                    <option value="">-- Pilih Kode --</option>
                                                    @foreach(\App\Models\KodeTransaksi::where('kodetransaksi', 'like', '4000%')->get() as $kt)
                                                        <option value="{{ $kt->id }}">
                                                            {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="transactions[0][description]" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-end" 
                                                       name="transactions[0][jml]" value="1" min="1" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-end" 
                                                       name="transactions[0][nominal]" value="0" min="0" step="1000" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm text-end total-trans" 
                                                       value="0" readonly>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger remove-trans">x</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                            <td colspan="2">
                                                <input type="text" class="form-control form-control-sm text-end" 
                                                       id="subtotal" value="0" readonly>
                                                <input type="hidden" name="subtotal" id="inputSubtotal">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">PPN</span>
                                                    <input type="number" class="form-control form-control-sm text-end" 
                                                           name="ppn" id="ppn" value="0" min="0">
                                                </div>
                                            </td>
                                            <td class="text-end"><strong>PPN:</strong></td>
                                            <td colspan="2">
                                                <input type="text" class="form-control form-control-sm text-end" 
                                                       id="ppnDisplay" value="0" readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Diskon:</strong></td>
                                            <td colspan="2">
                                                <input type="text" class="form-control form-control-sm text-end" 
                                                       name="diskon" id="diskon" value="0" readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                            <td colspan="2">
                                                <input type="text" class="form-control form-control-sm text-end fw-bold" 
                                                       id="grandTotal" value="0" readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-box-seam"></i> Data Asset
                                </h5>
                            </div>
                            <div class="card-body" id="assetDataContainer">
                                <!-- Asset forms will be added here -->
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-sm btn-primary w-100" id="addAsset">
                                    <i class="bi bi-plus-circle"></i> Tambah Asset
                                </button>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-calculator"></i> Perhitungan
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Total Asset</label>
                                    <input type="text" class="form-control" id="totalAssetValue" value="0" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Asset</label>
                                    <input type="text" class="form-control" id="assetCount" value="0" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Rata-rata per Asset</label>
                                    <input type="text" class="form-control" id="averagePerAsset" value="0" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-save"></i> Simpan Transaksi Asset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
            let assetCounter = 0;
            let transactionCounter = 1;
            
            // Format number
            function formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
            
            // Parse number
            function parseNumber(str) {
                return parseFloat(str.replace(/[^\d.-]/g, '')) || 0;
            }
            
            // Calculate totals
            function calculateTotals() {
                let subtotal = 0;
                
                // Calculate transaction subtotal
                $('input[name*="[nominal]"]').each(function() {
                    let row = $(this).closest('tr');
                    let qty = parseNumber(row.find('input[name*="[jml]"]').val());
                    let nominal = parseNumber($(this).val());
                    let total = qty * nominal;
                    
                    row.find('.total-trans').val(formatNumber(total));
                    subtotal += total;
                });
                
                $('#subtotal').val(formatNumber(subtotal));
                $('#inputSubtotal').val(subtotal);
                
                // Calculate PPN
                let ppn = parseNumber($('#ppn').val());
                $('#ppnDisplay').val(formatNumber(ppn));
                
                // Calculate grand total
                let diskon = parseNumber($('#diskon').val());
                let grandTotal = subtotal + ppn - diskon;
                $('#grandTotal').val(grandTotal);
                
                // Update asset calculations
                updateAssetCalculations();
            }
            
            // Update asset calculations
            function updateAssetCalculations() {
                let grandTotal = parseNumber($('#grandTotal').val());
                let assetCount = $('.asset-form').length;
                
                $('#totalAssetValue').val(formatNumber(grandTotal));
                $('#assetCount').val(assetCount);
                
                if (assetCount > 0) {
                    let average = grandTotal / assetCount;
                    $('#averagePerAsset').val(formatNumber(average));
                    
                    // Update harga perolehan per asset
                    $('.harga-perolehan-input').each(function() {
                        $(this).val(formatNumber(average.toFixed(2)));
                    });
                } else {
                    $('#averagePerAsset').val('0');
                }
            }
            
            // Add transaction row
            $('#addTransaction').click(function() {
                let html = `
                    <tr>
                        <td>
                            <select class="form-select form-select-sm" name="transactions[${transactionCounter}][idkodetransaksi]" required>
                                <option value="">-- Pilih Kode --</option>
                                @foreach(\App\Models\KodeTransaksi::where('kodetransaksi', 'like', '4000%')->get() as $kt)
                                    <option value="{{ $kt->id }}">
                                        {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" 
                                   name="transactions[${transactionCounter}][description]" required>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-end" 
                                   name="transactions[${transactionCounter}][jml]" value="1" min="1" required>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-end" 
                                   name="transactions[${transactionCounter}][nominal]" value="0" min="0" step="1000" required>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm text-end total-trans" 
                                   value="0" readonly>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger remove-trans">x</button>
                        </td>
                    </tr>
                `;
                $('#tblTransactions tbody').append(html);
                transactionCounter++;
                calculateTotals();
            });
            
            // Remove transaction row
            $(document).on('click', '.remove-trans', function() {
                if ($('#tblTransactions tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calculateTotals();
                }
            });
            
            // Add asset form
            $('#addAsset').click(function() {
                let today = new Date().toISOString().split('T')[0];
                let html = `
                    <div class="card mb-3 asset-form">
                        <div class="card-header bg-light py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Asset #${assetCounter + 1}</strong>
                                <button type="button" class="btn btn-sm btn-danger remove-asset">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label">Nama Asset *</label>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="assets[${assetCounter}][nama_aset]" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Mulai Susut *</label>
                                    <input type="date" class="form-control form-control-sm" 
                                           name="assets[${assetCounter}][tanggal_mulai_susut]" 
                                           value="${today}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Umur Ekonomis (bulan) *</label>
                                    <input type="number" class="form-control form-control-sm" 
                                           name="assets[${assetCounter}][umur_ekonomis]" 
                                           value="60" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nilai Residu</label>
                                    <input type="number" class="form-control form-control-sm" 
                                           name="assets[${assetCounter}][nilai_residu]" 
                                           value="0" min="0" step="1000">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Metode Penyusutan *</label>
                                    <select class="form-select form-select-sm" 
                                            name="assets[${assetCounter}][metode_penyusutan]">
                                        <option value="garis_lurus">Garis Lurus</option>
                                        <option value="saldo_menurun">Saldo Menurun</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Persentase Susut (%)</label>
                                    <input type="number" class="form-control form-control-sm" 
                                           name="assets[${assetCounter}][persentase_susut]" 
                                           value="20" min="0" max="100" step="0.1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Harga Perolehan</label>
                                    <input type="text" class="form-control form-control-sm text-end harga-perolehan-input" 
                                           readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="assets[${assetCounter}][lokasi]" 
                                           placeholder="Contoh: Kantor Pusat">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">PIC</label>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="assets[${assetCounter}][pic]" 
                                           placeholder="Penanggung Jawab">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Keterangan</label>
                                    <textarea class="form-control form-control-sm" 
                                              name="assets[${assetCounter}][keterangan]" 
                                              rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#assetDataContainer').append(html);
                assetCounter++;
                updateAssetCalculations();
            });
            
            // Remove asset form
            $(document).on('click', '.remove-asset', function() {
                if ($('.asset-form').length > 1) {
                    $(this).closest('.asset-form').remove();
                    updateAssetCalculations();
                } else {
                    Swal.fire('Peringatan', 'Minimal harus ada 1 asset', 'warning');
                }
            });
            
            // Auto add first asset
            $('#addAsset').click();
            
            // Recalculate on input changes
            $(document).on('input', 'input[name*="[nominal]"], input[name*="[jml]"], #ppn, #diskon', function() {
                calculateTotals();
            });
            
            // Submit form
            $('#frmAssetTransaction').submit(function(e) {
                e.preventDefault();
                
                // Validation
                let assetCount = $('.asset-form').length;
                if (assetCount === 0) {
                    Swal.fire('Peringatan', 'Minimal harus ada 1 asset', 'warning');
                    return;
                }
                
                let grandTotal = parseNumber($('#grandTotal').val());
                if (grandTotal <= 0) {
                    Swal.fire('Peringatan', 'Total transaksi harus lebih dari 0', 'warning');
                    return;
                }
                
                // Submit form
                let formData = new FormData(this);
                
                Swal.fire({
                    title: 'Simpan Transaksi?',
                    text: "Transaksi asset akan disimpan ke sistem",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Simpan!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: "{{ route('transaksi.asset.store') }}",
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false
                        }).then(response => {
                            if (!response.success) {
                                throw new Error(response.message);
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Error: ${error}`
                            );
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: result.value.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = "{{ route('transaksi.asset.index') }}";
                        });
                    }
                });
            });
            
            // Initial calculation
            calculateTotals();
        });
        </script>
    </x-slot>
</x-app-layout>