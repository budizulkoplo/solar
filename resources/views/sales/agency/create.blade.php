<x-app-layout>
    <x-slot name="pagetitle">Buat Transaksi Agency Sale</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Buat Transaksi Agency Sale</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Informasi Unit</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Project</label>
                            <input type="text" class="form-control" value="{{ $unitDetail->unit->project->namaproject ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control" value="{{ $unitDetail->unit->namaunit ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Customer</label>
                            <input type="text" class="form-control" value="{{ $unitDetail->customer->nama_lengkap ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Harga Jual</label>
                            <input type="text" class="form-control" 
                                   value="Rp {{ number_format($unitDetail->penjualan->harga_jual ?? 0, 0, ',', '.') }}" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title mb-0">Form Transaksi Agency</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('agency-sales.store') }}" method="POST" enctype="multipart/form-data" id="frmTransaction">
                        @csrf
                        <input type="hidden" name="unit_detail_id" value="{{ $unitDetail->id }}">
                        
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">No Nota *</label>
                                <input type="text" class="form-control form-control-sm" name="nota_no" required 
                                       value="AGENCY-{{ date('ymd') }}-{{ str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Transaksi *</label>
                                <input type="text" class="form-control form-control-sm" name="namatransaksi" required 
                                       value="Fee Agency {{ $unitDetail->unit->namaunit ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" name="tanggal" required 
                                       value="{{ date('Y-m-d') }}">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select form-select-sm" name="paymen_method">
                                    <option value="cash">Cash</option>
                                    <option value="tempo">Tempo</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select form-select-sm select2" name="idrek" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach($rekening as $rek)
                                        <option value="{{ $rek->idrek }}" data-saldo="{{ $rek->saldo }}">
                                            {{ $rek->norek }} - {{ $rek->namarek }} (Saldo: Rp {{ number_format($rek->saldo, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Bukti Nota</label>
                                <input type="file" class="form-control form-control-sm" name="bukti_nota" 
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Format: JPG, PNG, PDF (Max: 2MB)</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5>Detail Transaksi</h5>
                        
                        <table class="table table-sm table-bordered" id="tblDetail">
                            <thead>
                                <tr>
                                    <th>Kode Transaksi *</th>
                                    <th>Deskripsi *</th>
                                    <th width="80">Qty</th>
                                    <th width="120">Nominal</th>
                                    <th width="120">Total</th>
                                    <th width="40">
                                        <button type="button" class="btn btn-sm btn-success" id="addRow">+</button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select class="form-select form-select-sm select2 kode-transaksi" name="transactions[0][idkodetransaksi]" required>
                                            <option value="">-- Pilih Kode --</option>
                                            @foreach($kodeTransaksi as $kt)
                                                <option value="{{ $kt->id }}" data-kode="{{ $kt->kodetransaksi }}">
                                                    {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" name="transactions[0][description]" required></td>
                                    <td><input type="number" class="form-control form-control-sm text-end jml" name="transactions[0][jml]" value="1" min="1"></td>
                                    <td><input type="number" class="form-control form-control-sm text-end nominal" name="transactions[0][nominal]" value="0" min="0"></td>
                                    <td><input type="text" class="form-control form-control-sm text-end total" value="0" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td colspan="2"><input type="text" class="form-control form-control-sm text-end" id="subtotal" value="Rp 0" readonly></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                    <td colspan="2"><input type="text" class="form-control form-control-sm text-end fw-bold" id="grandTotal" value="Rp 0" readonly></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <input type="hidden" name="subtotal" id="hiddenSubtotal" value="0">
                        
                        <div class="mt-3">
                            <a href="{{ route('agency-sales.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="jscustom">
        <script>
        $(document).ready(function() {
            // Initialize select2
            $('.select2').select2({ width: '100%' });
            
            // Format Rupiah
            function formatRupiah(angka) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka || 0);
            }
            
            // Parse number
            function parseNumber(value) {
                if (!value) return 0;
                if (typeof value === 'string') {
                    value = value.replace(/[^\d.-]/g, '');
                }
                let num = parseFloat(value);
                return isNaN(num) ? 0 : num;
            }
            
            // Calculate totals
            function calculateTotals() {
                let subtotal = 0;
                $('.total').each(function() {
                    subtotal += parseNumber($(this).val());
                });
                
                $('#subtotal').val(formatRupiah(subtotal));
                $('#grandTotal').val(formatRupiah(subtotal));
                $('#hiddenSubtotal').val(subtotal);
            }
            
            // Calculate row total
            $(document).on('input', '.jml, .nominal', function() {
                let row = $(this).closest('tr');
                let jml = parseNumber(row.find('.jml').val());
                let nominal = parseNumber(row.find('.nominal').val());
                let total = jml * nominal;
                row.find('.total').val(total);
                calculateTotals();
            });
            
            // Add row
            let rowIndex = 1;
            $('#addRow').click(function() {
                let html = `<tr>
                    <td>
                        <select class="form-select form-select-sm select2 kode-transaksi" name="transactions[${rowIndex}][idkodetransaksi]" required>
                            <option value="">-- Pilih Kode --</option>
                            @foreach($kodeTransaksi as $kt)
                                <option value="{{ $kt->id }}" data-kode="{{ $kt->kodetransaksi }}">
                                    {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" name="transactions[${rowIndex}][description]" required></td>
                    <td><input type="number" class="form-control form-control-sm text-end jml" name="transactions[${rowIndex}][jml]" value="1" min="1"></td>
                    <td><input type="number" class="form-control form-control-sm text-end nominal" name="transactions[${rowIndex}][nominal]" value="0" min="0"></td>
                    <td><input type="text" class="form-control form-control-sm text-end total" value="0" readonly></td>
                    <td><button type="button" class="btn btn-sm btn-danger removeRow">x</button></td>
                </tr>`;
                $('#tblDetail tbody').append(html);
                $('.select2').select2({ width: '100%' });
                rowIndex++;
            });
            
            // Remove row
            $(document).on('click', '.removeRow', function() {
                if ($('#tblDetail tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calculateTotals();
                }
            });
            
            // Initialize calculation
            calculateTotals();
        });
        </script>
    </x-slot>
</x-app-layout>