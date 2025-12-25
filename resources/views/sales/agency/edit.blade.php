<x-app-layout>
    <x-slot name="pagetitle">Edit Transaksi Agency Sale</x-slot>

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Edit Transaksi Agency Sale</h3>
                <div>
                    <a href="{{ route('agency-sales.show', $nota->id) }}" class="btn btn-info btn-sm">
                        <i class="bi bi-eye"></i> Lihat
                    </a>
                    <a href="{{ route('agency-sales.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <!-- Informasi Unit -->
            <div class="card card-info mb-4">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-house"></i> Informasi Unit
                    </h4>
                </div>
                <div class="card-body">
                    @if($nota->unitDetail && $nota->unitDetail->unit)
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Unit</label>
                                <input type="text" class="form-control" 
                                       value="{{ $nota->unitDetail->unit->namaunit ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Customer</label>
                                <input type="text" class="form-control" 
                                       value="{{ $nota->unitDetail->customer->nama_lengkap ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Harga Jual</label>
                                <input type="text" class="form-control" 
                                       value="Rp {{ number_format($nota->unitDetail->penjualan->harga_jual ?? 0, 0, ',', '.') }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status Transaksi</label>
                                <input type="text" class="form-control" 
                                       value="{{ strtoupper($nota->status) }}" readonly>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Informasi unit tidak tersedia
                        </div>
                    @endif
                </div>
            </div>

            <!-- Form Edit -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-pencil-square"></i> Form Edit Transaksi
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('agency-sales.update', $nota->id) }}" method="POST" enctype="multipart/form-data" id="frmEdit">
                        @csrf
                        @method('PUT')
                        
                        <input type="hidden" name="old_rekening" value="{{ $nota->idrek }}">
                        <input type="hidden" name="old_grand_total" value="{{ $nota->total }}">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">No Nota *</label>
                                <input type="text" class="form-control form-control-sm" 
                                       name="nota_no" value="{{ $nota->nota_no }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Transaksi *</label>
                                <input type="text" class="form-control form-control-sm" 
                                       name="namatransaksi" value="{{ $nota->namatransaksi }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" class="form-control form-control-sm" 
                                       name="tanggal" value="{{ date('Y-m-d', strtotime($nota->tanggal)) }}" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select form-select-sm" name="paymen_method" id="paymenMethod">
                                    <option value="cash" {{ $nota->paymen_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="tempo" {{ $nota->paymen_method == 'tempo' ? 'selected' : '' }}>Tempo</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Rekening *</label>
                                <select class="form-select form-select-sm select2" name="idrek" id="idRekening" required>
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach($rekening as $r)
                                        <option value="{{ $r->idrek }}" 
                                            {{ $nota->idrek == $r->idrek ? 'selected' : '' }}
                                            data-saldo="{{ $r->saldo }}">
                                            {{ $r->norek }} - {{ $r->namarek }} 
                                            (Saldo: Rp {{ number_format($r->saldo, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Bukti Nota (Opsional)</label>
                                <input type="file" class="form-control form-control-sm" 
                                       name="bukti_nota" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">
                                    @if($nota->bukti_nota)
                                        File saat ini: {{ basename($nota->bukti_nota) }}<br>
                                    @endif
                                    Kosongkan jika tidak ingin mengubah
                                </small>
                            </div>
                            
                            <div class="col-md-6" id="tglTempoContainer" 
                                 style="{{ $nota->paymen_method == 'tempo' ? '' : 'display:none;' }}">
                                <label class="form-label">Tanggal Tempo *</label>
                                <input type="date" class="form-control form-control-sm" 
                                       name="tgl_tempo" value="{{ $nota->tgl_tempo ? date('Y-m-d', strtotime($nota->tgl_tempo)) : '' }}"
                                       id="tglTempo">
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
                            <tbody id="transactionsBody">
                                @php 
                                    $rowIndex = 0;
                                    // Filter out PPN and Diskon transactions
                                    $regularTransactions = $nota->transactions->filter(function($transaction) {
                                        if ($transaction->kodeTransaksi) {
                                            return !in_array($transaction->kodeTransaksi->kodetransaksi, ['3001', '5001']);
                                        }
                                        return true;
                                    });
                                @endphp
                                
                                @foreach($regularTransactions as $transaction)
                                    <tr data-row="{{ $rowIndex }}">
                                        <td>
                                            <select class="form-select form-select-sm select2 kode-transaksi" 
                                                    name="transactions[{{ $rowIndex }}][idkodetransaksi]" required>
                                                <option value="">-- Pilih Kode --</option>
                                                @foreach($kodeTransaksi as $kt)
                                                    <option value="{{ $kt->id }}" 
                                                        {{ $transaction->idkodetransaksi == $kt->id ? 'selected' : '' }}>
                                                        {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="transactions[{{ $rowIndex }}][description]" 
                                                   value="{{ $transaction->description }}" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-end jml" 
                                                   name="transactions[{{ $rowIndex }}][jml]" 
                                                   value="{{ $transaction->jml }}" min="1" step="1">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-end nominal" 
                                                   name="transactions[{{ $rowIndex }}][nominal]" 
                                                   value="{{ $transaction->nominal }}" min="0">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm text-end total" 
                                                   value="{{ number_format($transaction->total, 0, ',', '.') }}" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger removeRow">x</button>
                                        </td>
                                    </tr>
                                    @php $rowIndex++; @endphp
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td colspan="2">
                                        <input type="text" class="form-control form-control-sm text-end" 
                                               id="subtotal" value="Rp {{ number_format($nota->subtotal, 0, ',', '.') }}" readonly>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">PPN</span>
                                            <input type="number" class="form-control form-control-sm text-end" 
                                                   name="ppn" id="ppnAmount" 
                                                   value="{{ $nota->ppn }}" min="0" placeholder="Nominal PPN">
                                        </div>
                                    </td>
                                    <td class="text-end"><strong>PPN:</strong></td>
                                    <td colspan="2">
                                        <input type="text" class="form-control form-control-sm text-end" 
                                               id="ppnDisplay" value="Rp {{ number_format($nota->ppn, 0, ',', '.') }}" readonly>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                    <td colspan="2">
                                        <input type="text" class="form-control form-control-sm text-end fw-bold" 
                                               id="grandTotal" value="Rp {{ number_format($nota->total, 0, ',', '.') }}" readonly>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <input type="hidden" name="subtotal" id="hiddenSubtotal" value="{{ $nota->subtotal }}">
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('agency-sales.show', $nota->id) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
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
            
            // Toggle tanggal tempo
            $('#paymenMethod').change(function() {
                if ($(this).val() === 'tempo') {
                    $('#tglTempoContainer').show();
                    $('#tglTempo').prop('required', true);
                } else {
                    $('#tglTempoContainer').hide();
                    $('#tglTempo').prop('required', false);
                }
            });
            
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
                
                // Get PPN
                let ppn = parseNumber($('#ppnAmount').val());
                
                // Calculate grand total
                let grandTotal = subtotal + ppn;
                
                // Update displays
                $('#subtotal').val(formatRupiah(subtotal));
                $('#ppnDisplay').val(formatRupiah(ppn));
                $('#grandTotal').val(formatRupiah(grandTotal));
                
                // Update hidden field
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
            
            // PPN calculation
            $(document).on('input', '#ppnAmount', function() {
                calculateTotals();
            });
            
            // Add row
            let rowIndex = {{ $rowIndex }};
            $('#addRow').click(function() {
                let html = `
                    <tr data-row="${rowIndex}">
                        <td>
                            <select class="form-select form-select-sm select2 kode-transaksi" 
                                    name="transactions[${rowIndex}][idkodetransaksi]" required>
                                <option value="">-- Pilih Kode --</option>
                                @foreach($kodeTransaksi as $kt)
                                    <option value="{{ $kt->id }}">
                                        {{ $kt->kodetransaksi }} - {{ $kt->transaksi }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" 
                                   name="transactions[${rowIndex}][description]" required>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-end jml" 
                                   name="transactions[${rowIndex}][jml]" value="1" min="1" step="1">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-end nominal" 
                                   name="transactions[${rowIndex}][nominal]" value="0" min="0">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm text-end total" 
                                   value="0" readonly>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger removeRow">x</button>
                        </td>
                    </tr>
                `;
                $('#transactionsBody').append(html);
                
                // Reinitialize select2 for new row
                $('.select2').select2({ width: '100%' });
                rowIndex++;
            });
            
            // Remove row
            $(document).on('click', '.removeRow', function() {
                if ($('#transactionsBody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calculateTotals();
                }
            });
            
            // Form submission
            $('#frmEdit').submit(function(e) {
                e.preventDefault();
                
                // Validasi
                let grandTotal = parseNumber($('#grandTotal').val());
                if (grandTotal <= 0) {
                    alert('Total transaksi harus lebih dari 0');
                    return false;
                }
                
                // Show loading
                let submitBtn = $(this).find('button[type="submit"]');
                let originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');
                
                // Submit form
                this.submit();
            });
            
            // Initialize calculation
            calculateTotals();
            
            // Set min date for tempo date
            $('#tanggal').change(function() {
                $('#tglTempo').attr('min', $(this).val());
            });
        });
        </script>
    </x-slot>
</x-app-layout>
